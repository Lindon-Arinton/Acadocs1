<?php

namespace App\Libraries;

use App\Models\BiometricEmployeeModel;
use App\Models\BiometricPunchModel;
use App\Models\UserModel;
use Config\Biometric as BiometricConfig;

/**
 * Pulls new punches off the ZKTeco terminal over the LAN into
 * biometric_punches, then rebuilds the affected days' time_records from
 * there (same rules as an uploaded DTR export — see TimeRecordImporter).
 *
 * Only punches added since the last sync are read from the device (the
 * number of records already read is kept as a cursor), so a sync takes a
 * few seconds; the first one reads just the recent end of the log.
 * The cursor and the outcome of the last sync live in
 * writable/biometric_sync.json.
 */
class BiometricSync
{
    /** New punches older than this many days are archived but don't rewrite time_records (unless backfilling with $daysBack). */
    private const MAX_CATCH_UP_DAYS = 31;

    private BiometricConfig $config;

    public function __construct()
    {
        $this->config = config('Biometric');
    }

    /** True when an automatic sync is due (enabled and the interval has passed since the last attempt). */
    public function isDue(): bool
    {
        if (! $this->config->enabled) {
            return false;
        }

        $last = self::lastStatus();

        return $last === null || ! isset($last['at']) || time() - strtotime($last['at']) >= $this->config->intervalMinutes * 60;
    }

    /**
     * @param int|null $daysBack rebuild this many days of time_records from the stored punches (a backfill); by default only the days new punches fall on
     * @return array{ok:bool,at:string,message:string,new:int,latest_punch:?string,busy?:bool,summary?:?array}
     */
    public function pull(?int $daysBack = null): array
    {
        // One sync at a time: a page poll and the scheduled task can overlap.
        $lock = fopen(WRITEPATH . 'biometric_sync.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            return (self::lastStatus() ?? []) + ['ok' => true, 'busy' => true, 'at' => date('Y-m-d H:i:s'), 'message' => 'A sync is already running.', 'new' => 0, 'latest_punch' => null];
        }

        set_time_limit(300); // a large backfill reads ~25,000 punches a minute
        $state = self::lastStatus() ?? [];

        try {
            $device = $this->connect($state);
            // Without a cursor (first sync, or a backfill) only the recent end of the log is read.
            $window = (new \DateTimeImmutable('today'))->modify('-' . max(0, ($daysBack ?? $this->config->daysBack) - 1) . ' days');
            $read   = $daysBack !== null
                ? $device->attendance(0, '', null, $window)
                : $device->attendance((int) ($state['cursor'] ?? 0), (string) hex2bin($state['anchor'] ?? ''), null, $window);

            $punches = array_map(static fn (array $log) => [
                'ac_no' => ctype_digit($log['user_id']) ? (string) (int) $log['user_id'] : $log['user_id'],
                'time'  => $log['time'],
                'punch' => $log['punch'],
            ], $read['logs']);

            // Names come from the device only for AC-Nos nobody has been matched to yet.
            $names = $this->hasUnknownAcNos($punches) ? array_column($device->users(), 'name', 'user_id') : [];
            $device->disconnect();

            (new BiometricPunchModel())->store($punches);

            $summary = null;
            $days    = $this->daysToRebuild($punches, $daysBack);
            if ($days !== null) {
                $stored = [];
                foreach ((new BiometricPunchModel())->since(min(array_keys($days))) as $p) {
                    if (isset($days[$p['time']->format('Y-m-d')])) {
                        $stored[] = $p + ['name' => $names[$p['ac_no']] ?? ''];
                    }
                }
                $summary = $stored === [] ? null : (new TimeRecordImporter())->importPunches($stored);
            }

            // Past school days: whoever didn't scan at all is Absent (today they're just "not yet timed in").
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            (new TimeRecordImporter())->fillNoScanDays(date('Y-m-d', strtotime('-' . self::MAX_CATCH_UP_DAYS . ' days')), $yesterday);

            $latest = $punches === [] ? ($state['latest_punch'] ?? null) : end($punches)['time']->format('Y-m-d H:i:s');
            $status = [
                'ok'           => true,
                'at'           => date('Y-m-d H:i:s'),
                'archived_to'  => $state['archived_to'] ?? null,
                'host'         => $state['host'],
                'config_host'  => $this->config->host,
                'serial'       => $state['serial'] ?? null,
                'mac'          => $state['mac'] ?? null,
                'cursor'       => $read['count'],
                'anchor'       => bin2hex($read['anchor']),
                'new'          => count($punches),
                'latest_punch' => $latest,
                'message'      => $punches === []
                    ? 'No new punches' . ($latest ? ' (latest ' . date('M d, h:i A', strtotime($latest)) . ').' : '.')
                        . ($days !== null && $summary ? sprintf(' Filled in %d missing day(s): %d record(s) saved.', count($days), $summary['inserted'] + $summary['updated']) : '')
                    : sprintf('%d new punch(es), latest %s.', count($punches), date('M d, h:i A', strtotime($latest)))
                        . ($summary ? sprintf(' %d present, %d late, %d absent.', $summary['present'], $summary['late'], $summary['absent']) : ''),
                'summary'      => $summary,
            ];
            $status['message'] = self::foundNote($state) . $status['message'];
        } catch (\Throwable $e) {
            // Keep the cursor so the next sync still only reads what's new.
            $status = array_merge($state, ['ok' => false, 'at' => date('Y-m-d H:i:s'), 'new' => 0, 'message' => $e->getMessage()]);
            $status['latest_punch'] ??= null;
            log_message('error', 'Biometric sync failed: ' . $e->getMessage());
        }

        @file_put_contents(self::statusFile(), json_encode($status));
        flock($lock, LOCK_UN);
        fclose($lock);

        return $status;
    }

    /**
     * Finds and connects to the device without syncing anything, searching
     * the network straight away if it isn't at its last known address (for
     * `acadocs:biometric --test`). Remembers where it was found.
     */
    public function locate(?\Closure $trace = null): ZkDevice
    {
        $state  = self::lastStatus() ?? [];
        $device = $this->connect($state, true, $trace);

        $lock = fopen(WRITEPATH . 'biometric_sync.lock', 'c');
        if ($lock && flock($lock, LOCK_EX | LOCK_NB)) { // a running sync saves it anyway
            @file_put_contents(self::statusFile(), json_encode(array_diff_key($state, ['moved_from' => 1, 'replaced' => 1])));
            flock($lock, LOCK_UN);
        }
        if ($lock) {
            fclose($lock);
        }

        return $device;
    }

    /** "Device found at ..." for the sync message, when connect() had to search for it. */
    public static function foundNote(array $state): string
    {
        if (! empty($state['replaced'])) {
            return "Now syncing from the biometric device found at {$state['host']}" . (empty($state['serial']) ? '' : " (serial {$state['serial']})") . '. ';
        }
        if (array_key_exists('moved_from', $state)) {
            return "Device found at {$state['host']}" . ($state['moved_from'] ? " (was {$state['moved_from']})" : '') . '. ';
        }

        return '';
    }

    /** Config\Biometric::$host is 'auto' (or empty): no fixed address, find the device on the network. */
    private function isAuto(): bool
    {
        return in_array(strtolower(trim($this->config->host)), ['', 'auto'], true);
    }

    /**
     * Connects to the device at its last known address. With $host 'auto'
     * that's wherever it was last found; with a fixed IP it's that IP (or
     * wherever the device moved to from it). If it doesn't answer there — its
     * IP changed (DHCP), or this is a new install that hasn't found it yet —
     * the networks this computer is on are searched (see rediscover()), at
     * most once every DISCOVERY_MINUTES unless $search. Updates $state's
     * host / serial / mac (and sets moved_from / replaced when it searched).
     */
    private function connect(array &$state, bool $search = false, ?\Closure $trace = null): ZkDevice
    {
        $auto = $this->isAuto();
        if (! $auto && ($state['config_host'] ?? null) !== $this->config->host) {
            // A fixed address was just configured: start there (it may be another device).
            $state['host'] = $this->config->host;
            unset($state['serial'], $state['mac']);
        }
        $state['config_host'] = $this->config->host;
        unset($state['moved_from'], $state['replaced']);

        $open = function (string $host) use ($trace): ZkDevice {
            $device        = new ZkDevice($host, $this->config->port, $this->config->password, $this->config->timeout, $this->config->protocol);
            $device->trace = $trace;
            $device->connect();

            return $device;
        };

        $device = $error = null;
        if (! empty($state['host'])) {
            try {
                $device = $open($state['host']);
            } catch (\Throwable $e) {
                $error = $e;
            }
        }

        $device ??= $this->rediscover($state, $open, $search);
        if ($device === null) {
            throw $error ?? new \RuntimeException(
                'No biometric device found on this computer\'s network (' . implode(', ', array_map(static fn ($s) => "{$s}.x", ZkDevice::localSubnets())) . ').'
                . ' Check that it is switched on and plugged into the same network, or set its address as biometric.host in .env.'
            );
        }

        try {
            $state['serial'] ??= $device->serialNumber() ?: null;
        } catch (\Throwable) {
            // older firmware may not report it; the MAC still identifies the device
        }
        $state['mac']    ??= ZkDevice::neighbours(self::subnetOf($device->host()), $this->config->port, false)[$device->host()] ?? null;

        return $device;
    }

    /** Minutes between LAN searches while the device can't be reached. */
    private const DISCOVERY_MINUTES = 10;

    /**
     * Searches the networks this computer is on (the last known address's
     * first) for the device: on each, addresses with its MAC (or, before
     * that's known, any ZKTeco MAC) are tried first, then anything that
     * accepts connections on the SDK port. Each candidate must complete the
     * ZKTeco handshake, and once the serial number is known only that unit is
     * taken — unless $host is 'auto' and no unit with that serial turns up,
     * in which case the first other ZKTeco device found is (e.g. the copy of
     * writable/ shipped with a deployment remembers the development unit);
     * its sync then starts afresh.
     */
    private function rediscover(array &$state, \Closure $open, bool $force): ?ZkDevice
    {
        if (! $force && isset($state['discovered_at']) && time() - strtotime($state['discovered_at']) < self::DISCOVERY_MINUTES * 60) {
            return null;
        }
        $state['discovered_at'] = date('Y-m-d H:i:s');

        $port    = $this->config->port;
        $subnets = ZkDevice::localSubnets();
        if (! empty($state['host'])) {
            array_unshift($subnets, self::subnetOf($state['host']));
        }

        $tried = array_filter([$state['host'] ?? null]); // already failed
        $other = null; // [device, ip, serial] of a ZKTeco unit that isn't the remembered one

        foreach (array_unique($subnets) as $subnet) {
            $arp    = ZkDevice::neighbours($subnet, $port);
            $likely = array_keys(array_filter($arp, static fn ($mac) => ! empty($state['mac']) ? $mac === $state['mac'] : str_starts_with($mac, ZkDevice::ZKTECO_OUI)));

            foreach ([$likely, null] as $candidates) {
                // Only sweep the port when the MAC didn't give it away (without the sockets extension, just the live ARP neighbours).
                $candidates ??= ZkDevice::listening($subnet, $port, 1.5, extension_loaded('sockets') ? null : array_keys($arp));

                foreach (array_diff($candidates, $tried) as $ip) {
                    $tried[] = $ip;
                    try {
                        $device = $open($ip);
                    } catch (\Throwable) {
                        continue; // not a ZKTeco device, or not answering
                    }
                    try {
                        $serial = $device->serialNumber();
                    } catch (\Throwable) {
                        $serial = ''; // older firmware may not report it
                    }

                    if (empty($state['serial']) || $serial === '' || $serial === $state['serial']) {
                        $other && $other[0]->disconnect();
                        log_message('notice', 'Biometric device found at ' . $ip . (empty($state['host']) ? '' : " (was {$state['host']})"));
                        $state['moved_from'] = $state['host'] ?? null;
                        $state['host']       = $ip;

                        return $device;
                    }

                    if ($other === null && $this->isAuto()) {
                        $other = [$device, $ip, $serial];
                    } else {
                        $device->disconnect(); // another ZKTeco unit on the network
                    }
                }
            }
        }

        if ($other === null) {
            return null;
        }

        [$device, $ip, $serial] = $other;
        log_message('notice', "Biometric device {$state['serial']} not found; syncing from {$serial} at {$ip} instead");
        // A different unit: its log positions mean nothing for the old one's cursor.
        unset($state['cursor'], $state['anchor'], $state['archived_to'], $state['mac']);
        $state['host']     = $ip;
        $state['serial']   = $serial;
        $state['replaced'] = true;

        return $device;
    }

    /** "192.168.100.143" -> "192.168.100" */
    private static function subnetOf(string $ip): string
    {
        return implode('.', array_slice(explode('.', $ip), 0, 3));
    }

    /**
     * The days whose time_records get rebuilt from the stored punches (a
     * day's time-in/out needs all of its punches, not just the new ones):
     *  - with new punches, the last Config\Biometric::$daysBack days (so gaps
     *    still get marked Absent) and every day a new punch falls on;
     *  - always, any day that has punches stored but no time_records at all
     *    (e.g. weeks nobody imported a file for), so attendance is never
     *    missing for days the scanner recorded;
     * all within MAX_CATCH_UP_DAYS. Other days — including manual
     * corrections — are left alone. An explicit $daysBack rebuilds every day
     * in that range.
     *
     * @return array<string,true>|null Y-m-d => true, or null for nothing to do
     */
    private function daysToRebuild(array $punches, ?int $daysBack): ?array
    {
        $daysAgo = static fn (int $days) => date('Y-m-d', strtotime('-' . max(0, $days - 1) . ' days'));

        if ($daysBack !== null) {
            $days = [];
            for ($d = $daysAgo($daysBack); $d <= date('Y-m-d'); $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
                $days[$d] = true;
            }

            return $days;
        }

        $limit = $daysAgo(self::MAX_CATCH_UP_DAYS);
        $days  = [];

        if ($punches !== []) {
            for ($d = $daysAgo($this->config->daysBack); $d <= date('Y-m-d'); $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
                $days[$d] = true;
            }
            foreach ($punches as $p) {
                $d = $p['time']->format('Y-m-d');
                if ($d >= $limit) {
                    $days[$d] = true;
                }
            }
        }

        foreach (self::missingDays($limit) as $d) {
            $days[$d] = true;
        }

        return $days === [] ? null : $days;
    }

    /** @return list<string> days (Y-m-d, oldest first) the scanner recorded punches on but that have no time_records at all */
    private static function missingDays(string $since = '2000-01-01'): array
    {
        return array_column(db_connect()->query(
            'SELECT DISTINCT DATE(p.punched_at) AS d FROM biometric_punches p
             WHERE p.punched_at >= ? AND NOT EXISTS (SELECT 1 FROM time_records t WHERE t.date = DATE(p.punched_at))
             ORDER BY d',
            [$since . ' 00:00:00']
        )->getResultArray(), 'd');
    }

    /** Records copied per device session while archiving the whole history (~15 seconds each). */
    private const ARCHIVE_BATCH = 6000;

    /**
     * Copies the device's entire attendance log into biometric_punches, in
     * short sessions so one slow transfer can't wedge the device (progress is
     * saved after each, so a failed run picks up where it stopped), then
     * builds time_records for every day that has punches but no records yet.
     * Days that already have records — imports, manual edits, leaves — are
     * left as they are.
     *
     * @param \Closure(string):void|null $progress called with a line per step
     * @return array{ok:bool,message:string}
     */
    public function archiveAll(?\Closure $progress = null): array
    {
        $say  = $progress ?? static fn (string $line) => null;
        $lock = fopen(WRITEPATH . 'biometric_sync.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            return ['ok' => false, 'message' => 'A sync is running — try again in a minute.'];
        }
        set_time_limit(0);

        try {
            $state = self::lastStatus() ?? [];
            $from  = (int) ($state['archived_to'] ?? 0);
            $names = [];
            $count = null;

            while ($count === null || $from < $count) {
                for ($attempt = 1; ; $attempt++) {
                    try {
                        $device = $this->connect($state);
                        if (! empty($state['replaced'])) {
                            $from  = 0; // a different unit than the one archived so far
                            $names = [];
                        }
                        $names  = $names ?: array_column($device->users(), 'name', 'user_id');
                        $read   = $device->attendanceRange($from, $from + self::ARCHIVE_BATCH);
                        $device->disconnect();
                        break;
                    } catch (\Throwable $e) {
                        if ($attempt === 3) {
                            throw $e;
                        }
                        $say("  retrying after: {$e->getMessage()}");
                        sleep(10);
                    }
                }

                $count = $read['count'];
                (new BiometricPunchModel())->store(array_map(static fn (array $log) => [
                    'ac_no' => ctype_digit($log['user_id']) ? (string) (int) $log['user_id'] : $log['user_id'],
                    'time'  => $log['time'],
                    'punch' => $log['punch'],
                ], $read['logs']));

                $from = min($count, $from + self::ARCHIVE_BATCH);
                $state['archived_to'] = $from;
                @file_put_contents(self::statusFile(), json_encode($state)); // progress survives a failed run
                $say(sprintf('Copied %d of %d punches off the device.', $from, $count));
            }

            // Build attendance for every recorded day that has none, a month at a time.
            $missing = self::missingDays();
            $saved   = 0;
            $byMonth = [];
            foreach ($missing as $d) {
                $byMonth[substr($d, 0, 7)][$d] = true;
            }
            foreach ($byMonth as $month => $days) {
                $punches = [];
                foreach ((new BiometricPunchModel())->since(min(array_keys($days)), max(array_keys($days))) as $p) {
                    if (isset($days[$p['time']->format('Y-m-d')])) {
                        $punches[] = $p + ['name' => $names[$p['ac_no']] ?? ''];
                    }
                }
                if ($punches !== []) {
                    $summary = (new TimeRecordImporter())->importPunches($punches);
                    $saved  += $summary['inserted'] + $summary['updated'];
                    $say(sprintf('%s: %d day(s) filled in.', $month, count($days)));
                }
            }

            $absent = (new TimeRecordImporter())->fillNoScanDays('2000-01-01', date('Y-m-d', strtotime('-1 day')));
            $say("Marked {$absent} no-scan school day(s) Absent.");

            return ['ok' => true, 'message' => sprintf('All %d punches are stored. Filled in %d day(s) that had no attendance (%d record(s)), plus %d Absent record(s) for people who didn\'t scan.', $count, count($missing), $saved, $absent)];
        } catch (\Throwable $e) {
            log_message('error', 'Biometric archive failed: ' . $e->getMessage());

            return ['ok' => false, 'message' => $e->getMessage() . ' — run it again to continue from where it stopped.'];
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function hasUnknownAcNos(array $punches): bool
    {
        $acNos = array_values(array_unique(array_column($punches, 'ac_no')));
        if ($acNos === []) {
            return false;
        }

        $known = array_merge(
            array_column((new UserModel())->select('ac_no')->whereIn('ac_no', $acNos)->findAll(), 'ac_no'),
            array_column((new BiometricEmployeeModel())->select('ac_no')->whereIn('ac_no', $acNos)->where('is_placeholder', 0)->findAll(), 'ac_no'),
        );

        return array_diff($acNos, array_map('strval', $known)) !== [];
    }

    /** @return array<string,mixed>|null the last sync's outcome (ok, at, message, latest_punch, ...) */
    public static function lastStatus(): ?array
    {
        $raw = @file_get_contents(self::statusFile());

        return $raw ? json_decode($raw, true) : null;
    }

    private static function statusFile(): string
    {
        return WRITEPATH . 'biometric_sync.json';
    }
}

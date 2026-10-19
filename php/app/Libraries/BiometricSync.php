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
            if (! empty($state['moved_from'])) {
                $status['message'] = "Device found at its new address {$state['host']} (was {$state['moved_from']}). " . $status['message'];
            }
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
     * Connects to the device at its last known address (Config\Biometric::$host
     * until it's been found elsewhere; editing $host overrides that). If it
     * doesn't answer there, its IP may have changed (DHCP), so the LAN is
     * searched for its MAC address (or, before that's known, any ZKTeco
     * device) and the serial number checked before switching — at most once
     * every DISCOVERY_MINUTES. Updates $state's host / serial / mac.
     */
    private function connect(array &$state): ZkDevice
    {
        $sameConfig    = ($state['config_host'] ?? null) === $this->config->host;
        $state['host'] = $sameConfig && ! empty($state['host']) ? $state['host'] : $this->config->host;
        if (! $sameConfig) {
            unset($state['serial'], $state['mac']); // a different device was configured
        }
        unset($state['moved_from']);

        $open = function (string $host): ZkDevice {
            $device = new ZkDevice($host, $this->config->port, $this->config->password, $this->config->timeout, $this->config->protocol);
            $device->connect();

            return $device;
        };

        try {
            $device = $open($state['host']);
        } catch (\Throwable $e) {
            $device = $this->rediscover($state, $open);
            if ($device === null) {
                throw $e;
            }
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

    private function rediscover(array &$state, \Closure $open): ?ZkDevice
    {
        if (isset($state['discovered_at']) && time() - strtotime($state['discovered_at']) < self::DISCOVERY_MINUTES * 60) {
            return null;
        }
        $state['discovered_at'] = date('Y-m-d H:i:s');

        $candidates = [];
        foreach (ZkDevice::neighbours(self::subnetOf($state['host']), $this->config->port) as $ip => $mac) {
            $known = ! empty($state['mac']) ? $mac === $state['mac'] : str_starts_with($mac, ZkDevice::ZKTECO_OUI);
            if ($known && $ip !== $state['host']) {
                $candidates[] = $ip;
            }
        }

        foreach ($candidates as $ip) {
            try {
                $device = $open($ip);
                if (! empty($state['serial']) && $device->serialNumber() !== $state['serial']) {
                    $device->disconnect();

                    continue; // another ZKTeco unit on the network
                }
            } catch (\Throwable) {
                continue;
            }

            log_message('notice', "Biometric device moved from {$state['host']} to {$ip}");
            $state['moved_from'] = $state['host'];
            $state['host']       = $ip;

            return $device;
        }

        return null;
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

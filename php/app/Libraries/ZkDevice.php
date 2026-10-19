<?php

namespace App\Libraries;

/**
 * Minimal client for ZKTeco attendance terminals (K30, K40, iClock, ...)
 * over their "standalone SDK" protocol on port 4370 — the same protocol the
 * vendor's ZKTime / Attendance Management software uses. Read-only: it
 * fetches the enrolled users and the attendance log, never changes or
 * clears anything on the device.
 *
 * Works over TCP (newer firmware) or UDP (older firmware); with $protocol
 * 'auto' it tries TCP first. Uses PHP streams, so the sockets extension
 * isn't needed. Ported from the open-source pyzk library.
 */
class ZkDevice
{
    private const CMD_CONNECT        = 1000;
    private const CMD_EXIT           = 1001;
    private const CMD_AUTH           = 1102;
    private const CMD_GET_FREE_SIZES = 50;
    private const CMD_OPTIONS_RRQ    = 11;
    private const CMD_USERTEMP_RRQ   = 9;
    private const CMD_ATTLOG_RRQ     = 13;
    private const CMD_PREPARE_BUFFER = 1503;
    private const CMD_READ_BUFFER    = 1504;
    private const CMD_FREE_DATA      = 1502;
    private const CMD_PREPARE_DATA   = 1500;
    private const CMD_DATA           = 1501;
    private const CMD_ACK_OK         = 2000;
    private const CMD_ACK_UNAUTH     = 2005;
    private const FCT_USER           = 5;
    private const USHRT_MAX          = 65535;

    /** @var resource|null */
    private $socket;
    private bool $tcp = true;
    private int $sessionId = 0;
    private int $replyId = self::USHRT_MAX - 1;

    /** Called with a line per packet sent/received, for troubleshooting (acadocs:biometric --test --verbose). */
    public ?\Closure $trace = null;

    public function __construct(
        private string $host,
        private int $port = 4370,
        private int $password = 0,
        private int $timeout = 5,
        private string $protocol = 'auto',
    ) {
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public function connect(): void
    {
        $transports = match ($this->protocol) {
            'tcp'   => [true],
            'udp'   => [false],
            default => [true, false],
        };

        $errors = [];
        foreach ($transports as $tcp) {
            try {
                $this->open($tcp);
                $this->handshake();

                return;
            } catch (\Throwable $e) {
                $errors[] = ($tcp ? 'TCP' : 'UDP') . ': ' . $e->getMessage();
                $this->closeSocket();
            }
        }

        throw new \RuntimeException("Could not connect to the biometric device at {$this->host}:{$this->port} (" . implode('; ', $errors) . ')');
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            try {
                $this->send(self::CMD_EXIT);
            } catch (\Throwable) {
                // the device may already have dropped the session
            }
        }
        $this->closeSocket();
    }

    public function transport(): string
    {
        return $this->tcp ? 'TCP' : 'UDP';
    }

    public function host(): string
    {
        return $this->host;
    }

    /** The device's serial number (Menu → System Info), which identifies it whatever its IP. */
    public function serialNumber(): string
    {
        [, $data] = $this->command(self::CMD_OPTIONS_RRQ, "~SerialNumber\0");
        $value    = explode('=', explode("\0", $data, 2)[0], 2)[1] ?? '';

        return trim($value);
    }

    /** MAC address prefix registered to ZKTeco. */
    public const ZKTECO_OUI = '00:17:61';

    /**
     * Live devices on a /24 network (e.g. "192.168.100"), as IP => MAC, from
     * this computer's ARP table. A UDP packet is first sent to every address
     * so the table covers the whole network, not just recently used hosts
     * (skip that with $probe = false to look up a host just talked to).
     * Finds the device again after its IP changed (its MAC never does).
     *
     * @return array<string,string> MACs lowercase, colon-separated
     */
    public static function neighbours(string $subnet, int $port = 4370, bool $probe = true): array
    {
        $sockets = [];
        foreach ($probe ? range(1, 254) : [] as $n) {
            $socket = @stream_socket_client("udp://{$subnet}.{$n}:{$port}");
            if ($socket) {
                @fwrite($socket, "\0");
                $sockets[] = $socket;
            }
        }
        if ($sockets !== []) {
            usleep(1_500_000); // let the ARP replies come in
        }
        foreach ($sockets as $socket) {
            fclose($socket);
        }

        $output = [];
        @exec(PHP_OS_FAMILY === 'Windows' ? 'arp -a' : 'arp -an', $output);

        $table = [];
        foreach ($output as $line) {
            if (preg_match('/(' . preg_quote($subnet, '/') . '\.\d{1,3})\D.*?((?:[0-9a-f]{1,2}[:-]){5}[0-9a-f]{1,2})/i', $line, $m)) {
                $mac = implode(':', array_map(static fn ($b) => str_pad($b, 2, '0', STR_PAD_LEFT), preg_split('/[:-]/', strtolower($m[2]))));
                if ($mac !== 'ff:ff:ff:ff:ff:ff') {
                    $table[$m[1]] = $mac;
                }
            }
        }

        return $table;
    }

    /** @return array{users:int,records:int} counts the device reports */
    public function sizes(): array
    {
        [, $data] = $this->command(self::CMD_GET_FREE_SIZES);
        if (strlen($data) < 80) {
            return ['users' => 0, 'records' => 0];
        }
        $fields = array_values(unpack('l20', $data));

        return ['users' => $fields[4], 'records' => $fields[8]];
    }

    /**
     * Enrolled users: internal slot (uid) => user id ("AC-No." on the
     * device screen and in its Excel exports) and name.
     *
     * @return array<int,array{user_id:string,name:string}>
     */
    public function users(): array
    {
        $count = $this->sizes()['users'];
        if ($count === 0) {
            return [];
        }

        $data = $this->readBuffer(self::CMD_USERTEMP_RRQ, self::FCT_USER);
        if (strlen($data) < 4) {
            return [];
        }
        $total = unpack('V', $data)[1];
        $data  = substr($data, 4);
        $size  = intdiv($total, $count) === 28 ? 28 : 72;

        $users = [];
        foreach (str_split($data, $size) as $row) {
            if (strlen($row) < $size) {
                break;
            }
            if ($size === 28) {
                $u = unpack('vuid/Cprivilege/a5password/a8name/Vcard/x/Cgroup/vtimezone/Vuser_id', $row);
                $userId = (string) $u['user_id'];
            } else {
                $u = unpack('vuid/Cprivilege/a8password/a24name/Vcard/x/a7group/x/a24user_id', $row);
                $userId = self::cString($u['user_id']);
            }
            $users[$u['uid']] = ['user_id' => $userId, 'name' => trim(self::cString($u['name']))];
        }

        return $users;
    }

    /**
     * The attendance log from record number $skip on, oldest first. The
     * device only ever appends to its log, so the `count` returned by one
     * call is the $skip for the next: only new punches cross the network
     * (a few hundred bytes instead of the whole history).
     *
     * $anchor is the `anchor` the previous call returned (the raw bytes of
     * its last record). If the record at that position no longer matches —
     * the log was cleared on the device and has refilled since — the whole
     * log is read again and `restarted` is set.
     *
     * With no cursor, $notBefore limits a first read to the recent end of
     * the log: it's read backwards a chunk at a time until a record older
     * than $notBefore turns up (so ~2 seconds per few weeks of punches rather
     * than minutes for the whole history). Older records may be included.
     *
     * @param array<int,array{user_id:string,name:string}>|null $users from users(); only needed for very old firmware (8-byte records)
     * @return array{count:int,logs:list<array{user_id:string,time:\DateTimeImmutable,punch:int}>,anchor:string,restarted:bool}
     *   punch is the attendance state pressed: 0 check-in, 1 check-out,
     *   2 break-out, 3 break-in, 4 OT-in, 5 OT-out
     */
    public function attendance(int $skip = 0, string $anchor = '', ?array $users = null, ?\DateTimeInterface $notBefore = null): array
    {
        $count = $this->sizes()['records'];
        if ($count === 0) {
            return ['count' => 0, 'logs' => [], 'anchor' => '', 'restarted' => $skip > 0];
        }

        $restarted = $skip > $count;
        if ($restarted || $anchor === '') {
            $skip = 0;
        }

        // Staging the log on the device takes a few seconds; reading it is what's slow, so read only the tail.
        $size       = $this->prepareBuffer(self::CMD_ATTLOG_RRQ);
        $recordSize = match (intdiv($size - 4, $count)) {
            8       => 8,
            16      => 16,
            default => 40,
        };

        try {
            $data = null;
            if ($skip > 0) {
                // Re-read the last record already seen (the anchor) to make sure it's still where it was.
                $data = $this->readStaged(4 + ($skip - 1) * $recordSize, $size);
                if (substr($data, 0, $recordSize) === $anchor) {
                    $data = substr($data, $recordSize);
                } else {
                    $restarted = true;
                    $data      = null;
                }
            }
            $data ??= $notBefore !== null
                ? $this->readRecentStaged($size, $recordSize, $notBefore)
                : $this->readStaged(4, $size);
        } finally {
            $this->freeData();
        }

        if ($recordSize === 8) {
            $users ??= $this->users();
        }

        $logs = [];
        $last = '';
        foreach (str_split($data, $recordSize) as $row) {
            if (strlen($row) < $recordSize) {
                break;
            }
            $last   = $row;
            $logs[] = $this->parseRecord($row, $recordSize, $users ?? []);
        }

        usort($logs, static fn ($a, $b) => $a['time'] <=> $b['time']);

        return [
            'count'     => $count,
            'logs'      => $logs,
            'anchor'    => $last !== '' ? $last : $anchor,
            'restarted' => $restarted,
        ];
    }

    /**
     * Attendance records number $from up to (not including) $to, by position
     * in the device log — for copying the history in short sessions (see
     * BiometricSync::archiveAll()).
     *
     * @param array<int,array{user_id:string,name:string}>|null $users as for attendance()
     * @return array{count:int,logs:list<array{user_id:string,time:\DateTimeImmutable,punch:int}>}
     */
    public function attendanceRange(int $from, int $to, ?array $users = null): array
    {
        $count = $this->sizes()['records'];
        $to    = min($to, $count);
        if ($from >= $to) {
            return ['count' => $count, 'logs' => []];
        }

        $size       = $this->prepareBuffer(self::CMD_ATTLOG_RRQ);
        $recordSize = match (intdiv($size - 4, $count)) {
            8       => 8,
            16      => 16,
            default => 40,
        };

        try {
            $data = $this->readStaged(4 + $from * $recordSize, 4 + $to * $recordSize);
        } finally {
            $this->freeData();
        }

        if ($recordSize === 8) {
            $users ??= $this->users();
        }

        $logs = [];
        foreach (str_split($data, $recordSize) as $row) {
            if (strlen($row) === $recordSize) {
                $logs[] = $this->parseRecord($row, $recordSize, $users ?? []);
            }
        }

        return ['count' => $count, 'logs' => $logs];
    }

    /** @return array{user_id:string,time:\DateTimeImmutable,punch:int} */
    private function parseRecord(string $row, int $recordSize, array $users): array
    {
        if ($recordSize === 8) {
            $r      = unpack('vuid/Cstatus/Vtime/Cpunch', $row);
            $userId = $users[$r['uid']]['user_id'] ?? (string) $r['uid'];
        } elseif ($recordSize === 16) {
            $r      = unpack('Vuser_id/Vtime/Cstatus/Cpunch', $row);
            $userId = (string) $r['user_id'];
        } else {
            $r      = unpack('vuid/a24user_id/Cstatus/Vtime/Cpunch', $row);
            $userId = self::cString($r['user_id']);
        }

        return ['user_id' => $userId, 'time' => self::decodeTime($r['time']), 'punch' => $r['punch']];
    }

    // ── Protocol ─────────────────────────────────────────────────────

    private function open(bool $tcp): void
    {
        $this->tcp       = $tcp;
        $this->sessionId = 0;
        $this->replyId   = self::USHRT_MAX - 1;

        $socket = @stream_socket_client(($tcp ? 'tcp' : 'udp') . "://{$this->host}:{$this->port}", $errno, $errstr, $this->timeout);
        if ($socket === false) {
            throw new \RuntimeException($errstr ?: "error {$errno}");
        }
        stream_set_timeout($socket, $this->timeout);
        $this->socket = $socket;
    }

    private function closeSocket(): void
    {
        if ($this->socket) {
            fclose($this->socket);
        }
        $this->socket = null;
    }

    private function handshake(): void
    {
        [$code, , $header] = $this->command(self::CMD_CONNECT, '', false);
        $this->sessionId = $header['session'];

        if ($code === self::CMD_ACK_UNAUTH) {
            [$code] = $this->command(self::CMD_AUTH, self::commKey($this->password, $this->sessionId), false);
        }

        if ($code !== self::CMD_ACK_OK) {
            throw new \RuntimeException($code === self::CMD_ACK_UNAUTH ? 'wrong communication password' : "connection refused (reply {$code})");
        }
    }

    /**
     * Sends a command and waits for its reply.
     *
     * @return array{0:int,1:string,2:array{command:int,session:int,reply:int}} [reply code, reply data, header]
     */
    private function command(int $command, string $data = '', bool $requireOk = true): array
    {
        $this->send($command, $data);
        [$header, $payload] = $this->receive();

        if ($requireOk && ! in_array($header['command'], [self::CMD_ACK_OK, self::CMD_PREPARE_DATA, self::CMD_DATA], true)) {
            throw new \RuntimeException("device rejected command {$command} (reply {$header['command']})");
        }

        return [$header['command'], $payload, $header];
    }

    private function send(int $command, string $data = ''): void
    {
        // The checksum covers the previous reply id; the packet carries the next one.
        $checksum      = self::checksum(pack('vvvv', $command, 0, $this->sessionId, $this->replyId) . $data);
        $this->replyId = ($this->replyId + 1) % self::USHRT_MAX;
        $packet        = pack('vvvv', $command, $checksum, $this->sessionId, $this->replyId) . $data;

        if ($this->tcp) {
            $packet = pack('vvV', 0x5050, 0x7D82, strlen($packet)) . $packet;
        }

        if ($this->trace) {
            ($this->trace)('> ' . bin2hex(substr($packet, 0, 64)));
        }

        if (@fwrite($this->socket, $packet) === false) {
            throw new \RuntimeException('could not send to the device');
        }
    }

    /** @return array{0:array{command:int,session:int,reply:int},1:string} one reply packet: header + data */
    private function receive(): array
    {
        if ($this->tcp) {
            $prefix = $this->readExactly(8);
            $frame  = unpack('vm1/vm2/Vlength', $prefix);
            if ($frame['m1'] !== 0x5050 || $frame['m2'] !== 0x7D82) {
                throw new \RuntimeException('unexpected reply (not a ZKTeco device?)');
            }
            $packet = $this->readExactly($frame['length']);
        } else {
            $packet = fread($this->socket, 65535);
            if ($packet === false || $packet === '') {
                throw new \RuntimeException('no reply (timed out)');
            }
        }

        if (strlen($packet) < 8) {
            throw new \RuntimeException('short reply from the device');
        }

        if ($this->trace) {
            ($this->trace)('< ' . strlen($packet) . ' bytes: ' . bin2hex(substr($packet, 0, 32)));
        }

        $header = unpack('vcommand/vchecksum/vsession/vreply', $packet);
        $this->replyId = $header['reply'];

        return [$header, substr($packet, 8)];
    }

    private function readExactly(int $length): string
    {
        $buffer = '';
        while (strlen($buffer) < $length) {
            $chunk = fread($this->socket, $length - strlen($buffer));
            if ($chunk === false || $chunk === '') {
                $meta = stream_get_meta_data($this->socket);
                throw new \RuntimeException(! empty($meta['timed_out']) ? 'no reply (timed out)' : 'connection closed by the device');
            }
            $buffer .= $chunk;
        }

        return $buffer;
    }

    /** Asks the device to stage a whole data set (users, attendance log) and reads it back. */
    private function readBuffer(int $command, int $fct = 0): string
    {
        $size = $this->prepareBuffer($command, $fct);
        try {
            return $this->readStaged(0, $size);
        } finally {
            $this->freeData();
        }
    }

    /**
     * Releases a staged data set on the device. Also done when a read fails
     * part-way: a device left holding one can refuse new sessions until it's
     * restarted.
     */
    private function freeData(): void
    {
        try {
            $this->command(self::CMD_FREE_DATA, '', false);
        } catch (\Throwable) {
            // best effort — the connection may be what failed
        }
    }

    /** Small data sets the device sent straight back instead of staging them. */
    private ?string $staged = null;

    /** Has the device stage a data set for reading with readStaged(); returns its size in bytes. */
    private function prepareBuffer(int $command, int $fct = 0): int
    {
        [$code, $data] = $this->command(self::CMD_PREPARE_BUFFER, pack('cvVV', 1, $command, $fct, 0));

        if ($code === self::CMD_DATA) {
            $this->staged = $data;

            return strlen($data);
        }
        $this->staged = null;

        return unpack('V', substr($data, 1, 4))[1];
    }

    /** Bytes [$from, $to) of the staged data set, in chunks the transport can carry. */
    private function readStaged(int $from, int $to): string
    {
        if ($this->staged !== null) {
            return substr($this->staged, $from, max(0, $to - $from));
        }

        $maxChunk = $this->tcp ? 0xFFC0 : 16 * 1024;
        $out      = '';

        for ($start = $from; $start < $to; $start += $maxChunk) {
            $out .= $this->readChunk($start, min($maxChunk, $to - $start));
        }

        return $out;
    }

    /** The staged attendance log's records, read backwards from the end until one older than $notBefore turns up. */
    private function readRecentStaged(int $size, int $recordSize, \DateTimeInterface $notBefore): string
    {
        $perChunk = intdiv($this->tcp ? 0xFFC0 : 16 * 1024, $recordSize) * $recordSize;
        $timeAt   = [8 => 3, 16 => 4, 40 => 27][$recordSize]; // byte offset of the timestamp inside a record
        $limit    = $notBefore->format('Y-m-d H:i:s');
        $data     = '';

        for ($end = $size; $end > 4; $end = $start) {
            $start = max(4, $end - $perChunk);
            $start = 4 + intdiv($start - 4, $recordSize) * $recordSize;
            $data  = $this->readStaged($start, $end) . $data;

            if (strlen($data) >= $recordSize) {
                $oldest = self::decodeTime(unpack('V', substr($data, $timeAt, 4))[1]);
                if ($oldest->format('Y-m-d H:i:s') < $limit) {
                    break;
                }
            }
        }

        return $data;
    }

    private function readChunk(int $start, int $size): string
    {
        [$code, $data] = $this->command(self::CMD_READ_BUFFER, pack('VV', $start, $size));

        if ($code === self::CMD_DATA) {
            return $data;
        }

        // CMD_PREPARE_DATA: the chunk follows as CMD_DATA packets, ended by CMD_ACK_OK.
        $out = '';
        while (true) {
            [$header, $payload] = $this->receive();
            if ($header['command'] !== self::CMD_DATA) {
                break;
            }
            $out .= $payload;
        }

        return $out;
    }

    private static function checksum(string $packet): int
    {
        $sum   = self::USHRT_MAX;
        $bytes = array_values(unpack('C*', $packet));
        $count = count($bytes);

        for ($i = 0; $i + 1 < $count; $i += 2) {
            $sum += $bytes[$i] | ($bytes[$i + 1] << 8);
            if ($sum > self::USHRT_MAX) {
                $sum -= self::USHRT_MAX;
            }
        }
        if ($count % 2) {
            $sum += $bytes[$count - 1];
        }
        while ($sum > self::USHRT_MAX) {
            $sum -= self::USHRT_MAX;
        }

        $sum = ~$sum;
        while ($sum < 0) {
            $sum += self::USHRT_MAX;
        }

        return $sum & 0xFFFF;
    }

    /** Scrambled form of the device's communication password (Comm. → Password). */
    private static function commKey(int $key, int $sessionId): string
    {
        $k = 0;
        for ($i = 0; $i < 32; $i++) {
            $k = ($key & (1 << $i)) ? (($k << 1) | 1) : ($k << 1);
        }
        $k = ($k + $sessionId) & 0xFFFFFFFF;

        $b = array_values(unpack('C4', pack('V', $k)));
        $b = [$b[0] ^ ord('Z'), $b[1] ^ ord('K'), $b[2] ^ ord('S'), $b[3] ^ ord('O')];
        $b = [$b[2], $b[3], $b[0], $b[1]]; // swap the two 16-bit halves

        $ticks = 50;

        return pack('C4', $b[0] ^ $ticks, $b[1] ^ $ticks, $ticks, $b[3] ^ $ticks);
    }

    private static function decodeTime(int $t): \DateTimeImmutable
    {
        $second = $t % 60;
        $t      = intdiv($t, 60);
        $minute = $t % 60;
        $t      = intdiv($t, 60);
        $hour   = $t % 24;
        $t      = intdiv($t, 24);
        $day    = $t % 31 + 1;
        $t      = intdiv($t, 31);
        $month  = $t % 12 + 1;
        $year   = intdiv($t, 12) + 2000;

        return new \DateTimeImmutable(sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second));
    }

    private static function cString(string $raw): string
    {
        $raw = explode("\0", $raw, 2)[0];

        return mb_convert_encoding($raw, 'UTF-8', 'UTF-8');
    }
}

<?php

namespace App\Libraries;

use App\Models\AcademicBreakModel;
use App\Models\BiometricEmployeeModel;
use App\Models\HolidayModel;
use App\Models\TimeRecordModel;
use App\Models\UserModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Imports a biometric-scanner export (.xlsx / .xls) into `time_records`.
 * Two layouts are recognised from the header row:
 *
 *  - Punch log (one row per scan): Department, Name, No., Date/Time,
 *    Status (C/In, C/Out, ...), Location ID, ID Number, Workcode,
 *    VerifyCode, CardNo. Punches are grouped per employee per day.
 *  - Daily record (one row per employee per day): AC-No, Name,
 *    Department, Date, Time (space-separated punch list).
 *
 * Both feed the same deterministic present/late/absent rules engine.
 */
class TimeRecordImporter
{
    /** Time-in later than this is marked "Late" instead of "Present". */
    private const LATE_THRESHOLD = '07:30';

    /** Splits punches into morning (time-in) and afternoon (time-out) when the Status column was pressed wrong. */
    private const MIDDAY = '12:00';

    private BiometricEmployeeModel $employees;
    private HolidayModel $holidays;
    private TimeRecordModel $timeRecords;
    private UserModel $users;

    /** @var list<array{label:string,start_date:string,end_date:string}> cached academic breaks */
    private array $academicBreaks = [];

    public function __construct()
    {
        $this->employees   = new BiometricEmployeeModel();
        $this->holidays    = new HolidayModel();
        $this->timeRecords = new TimeRecordModel();
        $this->users       = new UserModel();

        $this->academicBreaks = (new AcademicBreakModel())->findAll();
    }

    /**
     * @return array{
     *   rows_read: int, inserted: int, updated: int,
     *   present: int, late: int, absent: int, incomplete: int,
     *   academic_break: int, skipped_non_school_day: int,
     *   placeholders_created: string[],
     *   errors: string[],
     *   latest_date: ?string,
     * }
     */
    public function import(string $filePath): array
    {
        $summary = [
            'rows_read'               => 0,
            'inserted'                => 0,
            'updated'                 => 0,
            'present'                 => 0,
            'late'                    => 0,
            'absent'                  => 0,
            'incomplete'              => 0,
            'academic_break'          => 0,
            'skipped_non_school_day'  => 0,
            'placeholders_created'    => [],
            'errors'                  => [],
            'latest_date'             => null,
        ];

        $biffRows = $this->readRawBiff($filePath);

        if ($biffRows !== null) {
            $header = $this->findPunchLogHeader($biffRows);

            if ($header !== null) {
                return $this->importPunchLog($biffRows, $header['row'], $header['columns'], $summary);
            }

            $rows = $biffRows;
        } else {
            $spreadsheet = IOFactory::load($filePath);

            // Prefer a sheet that looks like the per-punch biometric log. Raw
            // (unformatted) values, so Date/Time arrives as an Excel serial or
            // the scanner's own "m/d/Y h:i:s AM" text, never a locale format.
            foreach (array_merge([$spreadsheet->getActiveSheet()], $spreadsheet->getAllSheets()) as $candidate) {
                $rawRows = $candidate->toArray(null, true, false, false);
                $header  = $this->findPunchLogHeader($rawRows);

                if ($header !== null) {
                    return $this->importPunchLog($rawRows, $header['row'], $header['columns'], $summary);
                }
            }

            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        }

        // First row is the header (AC-No, Name, Department, Date, Time).
        array_shift($rows);

        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2; // account for the shifted header row

            [$acNoRaw, $nameRaw, $department, $dateRaw, $timeRaw] = array_pad($row, 5, null);
            $acNo = trim((string) $acNoRaw);
            if (is_numeric($acNo)) {
                $acNo = (string) (int) $acNo; // "037" / "37.0" -> "37", same as the punch-log layout and User Management
            }

            if ($acNo === '') {
                continue;
            }

            $summary['rows_read']++;

            try {
                $date = $this->parseDate($dateRaw);
            } catch (\Throwable $e) {
                $summary['errors'][] = "Row {$rowNumber}: unreadable date ({$e->getMessage()})";
                continue;
            }

            $employee = $this->resolveEmployee($acNo, trim((string) $nameRaw), trim((string) $department));
            if ($employee['is_new_placeholder']) {
                $summary['placeholders_created'][] = $acNo;
            }

            $times = $this->parsePunches((string) $timeRaw);
            $isSchoolDay = ! $this->isWeekendOrHoliday($date);

            if ($times === []) {
                if (! $isSchoolDay) {
                    $summary['skipped_non_school_day']++;
                    continue;
                }

                [$status, $remarks] = $this->noPunchStatus($date, $summary);
                $timeIn   = null;
                $timeOut  = null;
            } elseif (count($times) === 1) {
                $status  = 'Present';
                $timeIn  = $times[0];
                $timeOut = null;
                $remarks = 'Missing time-out (import)';
                $summary['incomplete']++;
                $summary['present']++;
            } else {
                $timeIn  = $times[0];
                $timeOut = $times[count($times) - 1];
                $status  = $this->statusForTimeIn($date, $timeIn);
                $remarks = count($times) > 2 ? 'Multiple punches collapsed to earliest/latest (import)' : '';
                $summary[$status === 'Late' ? 'late' : 'present']++;
            }

            $wasUpdate = $this->upsertTimeRecord(
                employeeId: 'AC-' . $acNo,
                employeeName: $employee['name'],
                userId: $employee['user_id'] ?? null,
                date: $date,
                timeIn: $timeIn,
                timeOut: $timeOut,
                status: $status,
                remarks: $remarks,
            );

            $summary[$wasUpdate ? 'updated' : 'inserted']++;

            if ($summary['latest_date'] === null || $date > $summary['latest_date']) {
                $summary['latest_date'] = $date;
            }
        }

        $summary['placeholders_created'] = array_values(array_unique($summary['placeholders_created']));

        return $summary;
    }

    /**
     * Reads a "raw BIFF" .xls: a bare worksheet record stream with no OLE
     * compound-document wrapper (typically BIFF2, all cells as text). Older
     * biometric software exports these; Excel opens them but PhpSpreadsheet
     * cannot. Returns null for anything else so the normal reader is used.
     *
     * @return list<list<string|float|int>>|null dense rows, 0-indexed
     */
    private function readRawBiff(string $filePath): ?array
    {
        $data = file_get_contents($filePath);

        // BOF record id: 0x0009 (BIFF2), 0x0209 (BIFF3), 0x0409 (BIFF4), 0x0809 (BIFF5+).
        // A real .xls starts with the OLE signature D0 CF 11 E0 instead.
        if ($data === false || strlen($data) < 4 || ! in_array(unpack('v', $data)[1], [0x0009, 0x0209, 0x0409, 0x0809], true)) {
            return null;
        }

        $cells  = [];
        $maxCol = -1;
        $pos    = 0;
        $length = strlen($data);

        while ($pos + 4 <= $length) {
            ['t' => $type, 'l' => $size] = unpack('vt/vl', substr($data, $pos, 4));
            $body = substr($data, $pos + 4, $size);
            $pos += 4 + $size;

            if ($type === 0x000A) { // EOF
                break;
            }
            if (strlen($body) < 4) {
                continue;
            }

            ['r' => $row, 'c' => $col] = unpack('vr/vc', $body);

            $value = match ($type) {
                0x0004  => strlen($body) >= 8 ? substr($body, 8, ord($body[7])) : null,                        // BIFF2 LABEL
                0x0204  => strlen($body) >= 8 ? substr($body, 8, unpack('v', substr($body, 6, 2))[1]) : null, // BIFF3/4 LABEL
                0x0002  => strlen($body) >= 9 ? unpack('v', substr($body, 7, 2))[1] : null,                    // BIFF2 INTEGER
                0x0003  => strlen($body) >= 15 ? unpack('e', substr($body, 7, 8))[1] : null,                   // BIFF2 NUMBER
                0x0203  => strlen($body) >= 14 ? unpack('e', substr($body, 6, 8))[1] : null,                   // BIFF3/4 NUMBER
                default => null,
            };

            if ($value === null) {
                continue;
            }

            $cells[$row][$col] = is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'Windows-1252') : $value;
            $maxCol            = max($maxCol, $col);
        }

        if ($cells === []) {
            return null;
        }

        $rows = [];
        for ($r = 0, $last = max(array_keys($cells)); $r <= $last; $r++) {
            $row = [];
            for ($c = 0; $c <= $maxCol; $c++) {
                $row[] = $cells[$r][$c] ?? null;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Locates the header row of a punch-log export (needs at least No.,
     * Date/Time and Status) within the first few rows.
     *
     * @return array{row: int, columns: array<string,?int>}|null
     */
    private function findPunchLogHeader(array $rows): ?array
    {
        foreach (array_slice($rows, 0, 15, true) as $rowIndex => $row) {
            $columns = [];

            foreach ($row as $col => $cell) {
                $key = preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $cell)));
                if ($key !== '' && ! isset($columns[$key])) {
                    $columns[$key] = $col;
                }
            }

            $noColumn = $columns['no'] ?? $columns['acno'] ?? $columns['enno'] ?? null;

            if (isset($columns['datetime'], $columns['status']) && $noColumn !== null) {
                return [
                    'row'     => $rowIndex,
                    'columns' => [
                        'no'         => $noColumn,
                        'name'       => $columns['name'] ?? null,
                        'department' => $columns['department'] ?? null,
                        'datetime'   => $columns['datetime'],
                        'status'     => $columns['status'],
                    ],
                ];
            }
        }

        return null;
    }

    /**
     * Groups punch-log rows by employee + day and derives time-in/out from
     * the C/In and C/Out punches. Then marks Absent the school days on which
     * an employee has no punch: only between their own first and last punch
     * in the file, only on days someone else did punch, and never over an
     * existing record (e.g. a manually entered "On Leave").
     */
    private function importPunchLog(array $rows, int $headerRow, array $columns, array $summary): array
    {
        set_time_limit(0);

        $cell = static fn (array $row, ?int $col): string => $col === null ? '' : trim((string) ($row[$col] ?? ''));

        /** @var array<string, array{name: string, department: string, days: array<string, list<array{time: string, kind: string}>>}> $byEmployee */
        $byEmployee = [];
        $activeDays = [];

        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex <= $headerRow) {
                continue;
            }

            $acNo = $cell($row, $columns['no']);
            if ($acNo === '') {
                continue;
            }
            if (is_numeric($acNo)) {
                $acNo = (string) (int) $acNo; // "1.0" / "001" -> "1"
            }

            $summary['rows_read']++;
            $rowNumber = $rowIndex + 1;

            try {
                $stamp = $this->parseDateTime($row[$columns['datetime']] ?? null);
            } catch (\Throwable $e) {
                $summary['errors'][] = "Row {$rowNumber}: unreadable Date/Time ({$e->getMessage()})";
                continue;
            }

            $date = $stamp->format('Y-m-d');

            $byEmployee[$acNo] ??= ['name' => '', 'department' => '', 'days' => []];
            $byEmployee[$acNo]['name']       = $byEmployee[$acNo]['name'] ?: $cell($row, $columns['name']);
            $byEmployee[$acNo]['department'] = $byEmployee[$acNo]['department'] ?: $cell($row, $columns['department']);
            $byEmployee[$acNo]['days'][$date][] = [
                'time' => $stamp->format('H:i'),
                'kind' => $this->punchKind($cell($row, $columns['status'])),
            ];

            $activeDays[$date] = true;
        }

        if ($byEmployee === []) {
            return $summary;
        }

        ksort($activeDays);
        $existingIds = $this->existingRecordIds(array_keys($byEmployee), array_key_first($activeDays), array_key_last($activeDays));

        $db = $this->timeRecords->db;
        $db->transStart();

        foreach ($byEmployee as $acNo => $info) {
            $acNo       = (string) $acNo;
            $employeeId = 'AC-' . $acNo;
            $employee   = $this->resolveEmployee($acNo, $info['name'], $info['department']);
            if ($employee['is_new_placeholder']) {
                $summary['placeholders_created'][] = $acNo;
            }

            ksort($info['days']);

            foreach ($info['days'] as $date => $punches) {
                [$timeIn, $timeOut, $remarks] = $this->resolveInOut($punches);

                $status = $timeIn !== null ? $this->statusForTimeIn($date, $timeIn) : 'Present';
                $summary[$status === 'Late' ? 'late' : 'present']++;
                if ($timeIn === null || $timeOut === null) {
                    $summary['incomplete']++;
                }

                $this->saveRecord($existingIds, $summary, $employeeId, $employee['name'], $date, $timeIn, $timeOut, $status, $remarks, $employee['user_id'] ?? null);
            }

            $first = array_key_first($info['days']);
            $last  = array_key_last($info['days']);

            foreach (array_keys($activeDays) as $date) {
                if ($date < $first || $date > $last || isset($info['days'][$date]) || isset($existingIds[$employeeId . '|' . $date])) {
                    continue;
                }
                if ($this->isWeekendOrHoliday($date)) {
                    continue;
                }

                [$status, $remarks] = $this->noPunchStatus($date, $summary);
                $this->saveRecord($existingIds, $summary, $employeeId, $employee['name'], $date, null, null, $status, $remarks, $employee['user_id'] ?? null);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new \RuntimeException('database error while saving time records');
        }

        $summary['latest_date']          = array_key_last($activeDays);
        $summary['placeholders_created'] = array_values(array_unique($summary['placeholders_created']));

        return $summary;
    }

    /** Maps the Status column to "in", "out" or "other" (break / overtime punches). */
    private function punchKind(string $status): string
    {
        return match (preg_replace('/[^a-z]/', '', strtolower($status))) {
            'cin', 'in', 'checkin', 'clockin', 'timein'      => 'in',
            'cout', 'out', 'checkout', 'clockout', 'timeout' => 'out',
            default                                          => 'other',
        };
    }

    /**
     * Picks time-in (earliest C/In) and time-out (latest C/Out) for one
     * employee-day. It tolerates the usual scanner mistakes: pressing C/In
     * when leaving, pressing C/Out when arriving, or punches with no in/out status.
     *
     * @param list<array{time: string, kind: string}> $punches
     * @return array{0: ?string, 1: ?string, 2: string} [time_in, time_out, remarks]
     */
    private function resolveInOut(array $punches): array
    {
        $byKind = ['in' => [], 'out' => [], 'other' => []];
        foreach ($punches as $p) {
            $byKind[$p['kind']][] = $p['time'];
        }
        foreach ($byKind as &$times) {
            sort($times);
        }
        unset($times);

        $ins  = $byKind['in'];
        $outs = $byKind['out'];

        $timeIn  = $ins[0] ?? null;
        $timeOut = $outs === [] ? null : end($outs);

        // Forgot to switch to C/Out: an afternoon C/In acts as the time-out.
        if ($timeOut === null && count($ins) > 1 && end($ins) >= self::MIDDAY) {
            $timeOut = end($ins);
        }

        // Forgot to switch to C/In: a morning C/Out acts as the time-in.
        if ($timeIn === null && $outs !== [] && $outs[0] < self::MIDDAY) {
            $timeIn  = $outs[0];
            $timeOut = count($outs) > 1 ? end($outs) : null;
        }

        // Only break/overtime punches: fall back to earliest/latest by clock.
        if ($timeIn === null && $timeOut === null) {
            $all = $byKind['other'];
            if (count($all) > 1) {
                [$timeIn, $timeOut] = [$all[0], end($all)];
            } elseif ($all[0] < self::MIDDAY) {
                $timeIn = $all[0];
            } else {
                $timeOut = $all[0];
            }
        }

        if ($timeIn !== null && $timeOut !== null && $timeOut <= $timeIn) {
            $timeOut = null;
        }

        $remarks = match (true) {
            $timeIn === null  => 'Missing time-in (import)',
            $timeOut === null => 'Missing time-out (import)',
            default           => '',
        };

        return [$timeIn, $timeOut, $remarks];
    }

    /**
     * @param string[] $acNos
     * @return array<string,int> "AC-x|Y-m-d" => time_records.id
     */
    private function existingRecordIds(array $acNos, string $from, string $to): array
    {
        $map = [];

        foreach (array_chunk(array_map(static fn ($n) => 'AC-' . $n, $acNos), 500) as $chunk) {
            $rows = $this->timeRecords->select('id, employee_id, date')
                ->whereIn('employee_id', $chunk)
                ->where('date >=', $from)
                ->where('date <=', $to)
                ->findAll();

            foreach ($rows as $r) {
                $map[$r['employee_id'] . '|' . $r['date']] = (int) $r['id'];
            }
        }

        return $map;
    }

    private function saveRecord(
        array &$existingIds,
        array &$summary,
        string $employeeId,
        string $employeeName,
        string $date,
        ?string $timeIn,
        ?string $timeOut,
        string $status,
        string $remarks,
        ?int $userId = null,
    ): void {
        $data = [
            'date'          => $date,
            'employee_name' => $employeeName,
            'employee_id'   => $employeeId,
            'user_id'       => $userId,
            'time_in'       => $timeIn,
            'time_out'      => $timeOut,
            'status'        => $status,
            'remarks'       => $remarks,
        ];

        $key = $employeeId . '|' . $date;

        if (isset($existingIds[$key])) {
            $this->timeRecords->update($existingIds[$key], $data);
            $summary['updated']++;

            return;
        }

        $existingIds[$key] = (int) $this->timeRecords->insert($data);
        $summary['inserted']++;
    }

    /** Accepts an Excel date serial or text such as "1/6/2026 7:00:31 AM". */
    private function parseDateTime(mixed $raw): \DateTimeInterface
    {
        if (is_numeric($raw)) {
            // Round to the nearest second to undo floating-point drift.
            return ExcelDate::excelToDateTimeObject(round((float) $raw * 86400) / 86400);
        }

        $raw = preg_replace('/\s+/', ' ', trim((string) $raw));
        if ($raw === '') {
            throw new \RuntimeException('empty cell');
        }

        // strtotime reads slashed dates as m/d/Y, matching the scanner export.
        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            throw new \RuntimeException("could not parse '{$raw}'");
        }

        return (new \DateTime())->setTimestamp($timestamp);
    }

    private function resolveEmployee(string $acNo, string $name, string $department): array
    {
        $user = $this->users->where('ac_no', $acNo)->first();
        if ($user !== null) {
            return ['name' => $user['name'], 'is_new_placeholder' => false, 'user_id' => (int) $user['id']];
        }

        $isRealName = $name !== '' && $name !== $acNo && ! ctype_digit($name);
        $existing   = $this->employees->findByAcNo($acNo);

        if ($existing === null) {
            $data = [
                'ac_no'          => $acNo,
                'name'           => $isRealName ? $name : "Unmapped (AC-{$acNo})",
                'department'     => $department !== '' ? $department : null,
                'is_placeholder' => $isRealName ? 0 : 1,
            ];
            $this->employees->insert($data);

            return ['name' => $data['name'], 'is_new_placeholder' => ! $isRealName, 'user_id' => null];
        }

        if ($isRealName && (bool) $existing['is_placeholder']) {
            $this->employees->update($existing['id'], [
                'name'           => $name,
                'department'     => $department !== '' ? $department : $existing['department'],
                'is_placeholder' => 0,
            ]);

            return ['name' => $name, 'is_new_placeholder' => false];
        }

        if ($isRealName && $name !== $existing['name']) {
            $this->employees->update($existing['id'], ['name' => $name]);

            return ['name' => $name, 'is_new_placeholder' => false, 'user_id' => null];
        }

        return ['name' => $existing['name'], 'is_new_placeholder' => false, 'user_id' => null];
    }

    private function parseDate(mixed $raw): string
    {
        if (is_numeric($raw)) {
            return ExcelDate::excelToDateTimeObject((float) $raw)->format('Y-m-d');
        }

        $raw = trim((string) $raw);
        if ($raw === '') {
            throw new \RuntimeException('empty date cell');
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            throw new \RuntimeException("could not parse '{$raw}'");
        }

        return date('Y-m-d', $timestamp);
    }

    /**
     * @return string[] sorted, deduped H:i punch times
     */
    private function parsePunches(string $raw): array
    {
        $tokens = preg_split('/\s+/', trim($raw)) ?: [];
        $valid  = [];

        foreach ($tokens as $token) {
            if (preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $token) === 1) {
                [$h, $m] = explode(':', $token);
                $valid[] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . $m;
            }
        }

        $valid = array_values(array_unique($valid));
        sort($valid);

        return $valid;
    }

    /**
     * A school day with no punches: Absent, unless it falls in an academic
     * break (then it's recorded as such, not held against anyone).
     *
     * @return array{0:string,1:string} [status, remarks]
     */
    private function noPunchStatus(string $date, array &$summary): array
    {
        foreach ($this->academicBreaks as $break) {
            if ($date >= $break['start_date'] && $date <= $break['end_date']) {
                $summary['academic_break']++;

                return ['Academic Break', TimeRecordModel::BREAK_REMARK . $break['label']];
            }
        }

        $summary['absent']++;

        return ['Absent', TimeRecordModel::NO_PUNCH_REMARK];
    }

    private function isWeekendOrHoliday(string $date): bool
    {
        // labelFor() covers one-off, every-year and movable (Holy Week, Heroes Day) holidays.
        return $this->isWeekend($date) || $this->holidays->labelFor($date) !== null;
    }

    /** Saturday or Sunday. */
    private function isWeekend(string $date): bool
    {
        return (int) date('N', strtotime($date)) >= 6;
    }

    /**
     * Present or Late for a day someone punched in. Weekends are never Late:
     * whoever comes in on a Saturday or Sunday is simply recorded as Present
     * (and nobody is marked Absent on one — see isWeekendOrHoliday()).
     */
    private function statusForTimeIn(string $date, string $timeIn): string
    {
        return ! $this->isWeekend($date) && $timeIn > self::LATE_THRESHOLD ? 'Late' : 'Present';
    }

    private function upsertTimeRecord(
        string $employeeId,
        string $employeeName,
        ?int $userId,
        string $date,
        ?string $timeIn,
        ?string $timeOut,
        string $status,
        string $remarks,
    ): bool {
        $existing = $this->timeRecords
            ->where('employee_id', $employeeId)
            ->where('date', $date)
            ->first();

        $data = [
            'date'          => $date,
            'employee_name' => $employeeName,
            'employee_id'   => $employeeId,
            'user_id'       => $userId,
            'time_in'       => $timeIn,
            'time_out'      => $timeOut,
            'status'        => $status,
            'remarks'       => $remarks,
        ];

        if ($existing !== null) {
            $this->timeRecords->update($existing['id'], $data);

            return true;
        }

        $this->timeRecords->insert($data);

        return false;
    }
}

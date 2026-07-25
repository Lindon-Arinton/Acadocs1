<?php

namespace App\Libraries;

use App\Models\BiometricEmployeeModel;
use App\Models\HolidayModel;
use App\Models\TimeRecordModel;
use App\Models\UserModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Imports a biometric-scanner "Daily Time Record" workbook (columns:
 * AC-No, Name, Department, Date, Time) into `time_records`, applying a
 * deterministic present/late/absent rules engine to the raw punch list
 * in the Time column.
 */
class TimeRecordImporter
{
    /** Time-in later than this is marked "Late" instead of "Present". */
    private const LATE_THRESHOLD = '07:30';

    private BiometricEmployeeModel $employees;
    private HolidayModel $holidays;
    private TimeRecordModel $timeRecords;
    private UserModel $users;

    /** @var array<string,bool> cached holiday dates (Y-m-d => true) */
    private array $holidayDates = [];

    public function __construct()
    {
        $this->employees   = new BiometricEmployeeModel();
        $this->holidays    = new HolidayModel();
        $this->timeRecords = new TimeRecordModel();
        $this->users       = new UserModel();

        $this->holidayDates = array_fill_keys($this->holidays->allDates(), true);
    }

    /**
     * @return array{
     *   rows_read: int, inserted: int, updated: int,
     *   present: int, late: int, absent: int, incomplete: int,
     *   skipped_non_school_day: int,
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
            'skipped_non_school_day'  => 0,
            'placeholders_created'    => [],
            'errors'                  => [],
            'latest_date'             => null,
        ];

        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, false);

        // First row is the header (AC-No, Name, Department, Date, Time).
        array_shift($rows);

        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2; // account for the shifted header row

            [$acNoRaw, $nameRaw, $department, $dateRaw, $timeRaw] = array_pad($row, 5, null);
            $acNo = trim((string) $acNoRaw);

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

                $status   = 'Absent';
                $timeIn   = null;
                $timeOut  = null;
                $remarks  = 'No punches recorded (import)';
                $summary['absent']++;
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
                $status  = $timeIn > self::LATE_THRESHOLD ? 'Late' : 'Present';
                $remarks = count($times) > 2 ? 'Multiple punches collapsed to earliest/latest (import)' : '';
                $summary[$status === 'Late' ? 'late' : 'present']++;
            }

            $wasUpdate = $this->upsertTimeRecord(
                employeeId: 'AC-' . $acNo,
                employeeName: $employee['name'],
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

    private function resolveEmployee(string $acNo, string $name, string $department): array
    {
        $user = $this->users->where('ac_no', $acNo)->first();
        if ($user !== null) {
            return ['name' => $user['name'], 'is_new_placeholder' => false];
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

            return ['name' => $data['name'], 'is_new_placeholder' => ! $isRealName];
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

            return ['name' => $name, 'is_new_placeholder' => false];
        }

        return ['name' => $existing['name'], 'is_new_placeholder' => false];
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

    private function isWeekendOrHoliday(string $date): bool
    {
        if (isset($this->holidayDates[$date])) {
            return true;
        }

        return (int) date('N', strtotime($date)) >= 6;
    }

    private function upsertTimeRecord(
        string $employeeId,
        string $employeeName,
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

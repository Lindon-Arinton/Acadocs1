<?php

namespace App\Database\Seeds\Demo;

/**
 * Time Records and the attendance widgets on every dashboard: a daily
 * record for each staff account with a biometric AC-No, every school day
 * from the start of the school year through yesterday, shaped like what
 * TimeRecordImporter produces from the biometric export. Also fills in the
 * 2026 Philippine public holidays, which the importer (and this seeder)
 * treat as non-school days.
 *
 * Only fills gaps: any (employee, date) that already has a record — e.g.
 * real biometric data — is left alone. A later real import of a seeded day
 * overwrites the row in place, and DemoDataSeeder's purge then keeps it.
 */
class AttendanceSeeder extends DemoSeeder
{
    private const SCHOOL_YEAR_START = '2026-06-08';

    // Regular and special non-working days with fixed 2026 dates (Proclamation-based;
    // movable Islamic holidays are left out since their dates vary).
    private const HOLIDAYS_2026 = [
        '2026-01-01' => "New Year's Day",
        '2026-04-02' => 'Maundy Thursday',
        '2026-04-03' => 'Good Friday',
        '2026-04-09' => 'Araw ng Kagitingan',
        '2026-05-01' => 'Labor Day',
        '2026-06-12' => 'Independence Day',
        '2026-08-21' => 'Ninoy Aquino Day',
        '2026-08-31' => 'National Heroes Day',
        '2026-11-01' => "All Saints' Day",
        '2026-11-30' => 'Bonifacio Day',
        '2026-12-08' => 'Feast of the Immaculate Conception',
        '2026-12-25' => 'Christmas Day',
        '2026-12-30' => 'Rizal Day',
        '2026-12-31' => 'Last Day of the Year',
    ];

    public function run()
    {
        $holidayRows = [];
        foreach (self::HOLIDAYS_2026 as $date => $label) {
            $holidayRows[] = ['date' => $date, 'label' => $label];
        }
        $this->db->table('holidays')->ignore(true)->insertBatch($holidayRows);

        $holidays = array_flip(array_column($this->db->table('holidays')->select('date')->get()->getResultArray(), 'date'));
        $staff    = array_filter($this->users(), static fn ($u) => $u['ac_no'] !== null && $u['ac_no'] !== '');

        $existing = [];
        foreach ($this->db->table('time_records')->select('employee_id, date')->where('date >=', self::SCHOOL_YEAR_START)->get()->getResultArray() as $r) {
            $existing[$r['employee_id'] . '|' . $r['date']] = true;
        }

        // Each person gets a steady habit (some always early, a few often
        // late) so a given teacher's month looks consistent, not random noise.
        $habits = [];
        foreach ($staff as $person) {
            $habits[$person['id']] = ['arrive' => mt_rand(6 * 60 + 5, 7 * 60 + 5), 'lateRate' => $this->pick([2, 4, 6, 10, 18]), 'absentRate' => $this->pick([1, 2, 3, 5])];
        }

        $rows = [];
        $end  = strtotime('yesterday');
        for ($ts = strtotime(self::SCHOOL_YEAR_START); $ts <= $end; $ts += 86400) {
            $date = date('Y-m-d', $ts);
            if ((int) date('N', $ts) >= 6 || isset($holidays[$date])) {
                continue;
            }

            foreach ($staff as $person) {
                $employeeId = 'AC-' . $person['ac_no'];
                if (isset($existing[$employeeId . '|' . $date])) {
                    continue;
                }

                $rows[] = $this->record($date, $employeeId, $person['name'], $habits[$person['id']]);
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            $this->db->table('time_records')->insertBatch($chunk);
        }
    }

    /** One day's record, using the importer's rules: late after 07:30, remarks for gaps. */
    private function record(string $date, string $employeeId, string $name, array $habit): array
    {
        $row = ['date' => $date, 'employee_name' => $name, 'employee_id' => $employeeId, 'time_in' => null, 'time_out' => null, 'status' => 'Present', 'remarks' => ''];

        $roll = mt_rand(1, 100);
        if ($roll <= 2) {
            return array_merge($row, ['status' => 'On Leave', 'remarks' => $this->pick(['Sick leave', 'Vacation leave', 'Official business'])]);
        }
        if ($roll <= 2 + $habit['absentRate']) {
            return array_merge($row, ['status' => 'Absent', 'remarks' => 'No punches recorded (import)']);
        }

        $in = $this->chance($habit['lateRate']) ? mt_rand(7 * 60 + 31, 8 * 60 + 20) : min($habit['arrive'] + mt_rand(-15, 20), 7 * 60 + 30);
        $row['time_in'] = sprintf('%02d:%02d:00', intdiv($in, 60), $in % 60);

        // A single punch is always "Present" in the importer, late or not.
        if ($this->chance(4)) {
            return array_merge($row, ['remarks' => 'Missing time-out (import)']);
        }

        $out = mt_rand(16 * 60, 17 * 60 + 45);
        $row['time_out'] = sprintf('%02d:%02d:00', intdiv($out, 60), $out % 60);
        $row['status']   = $in > 7 * 60 + 30 ? 'Late' : 'Present';

        return $row;
    }
}

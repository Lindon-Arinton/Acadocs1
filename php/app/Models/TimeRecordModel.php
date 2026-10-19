<?php

namespace App\Models;

use CodeIgniter\Model;

class TimeRecordModel extends Model
{
    public const STATUSES = ['Present', 'Late', 'Absent', 'On Leave', 'Academic Break'];

    /** Remarks the importer writes, so adding/removing a break only touches records it produced. */
    public const NO_PUNCH_REMARK = 'No punches recorded (import)';
    public const BREAK_REMARK    = 'Academic break: ';

    protected $table = 'time_records';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['date', 'employee_name', 'employee_id', 'user_id', 'time_in', 'time_out', 'status', 'remarks'];

    /**
     * Attendance for one day, for the dashboards' "as of today" panel:
     * counts per status, who is late / absent, and the latest date that has
     * any records (so an empty day can say when the last import was).
     *
     * @return array{date:string,counts:array<string,int>,total:int,late:list<array>,absent:list<array>,lastDate:?string}
     */
    public function daySummary(string $date): array
    {
        $rows = $this->where('date', $date)
            ->where('employee_name NOT LIKE', 'Unmapped (%')
            ->orderBy('time_in', 'ASC')
            ->orderBy('employee_name', 'ASC')
            ->findAll();

        $counts = array_fill_keys(self::STATUSES, 0);
        $late   = [];
        $absent = [];
        foreach ($rows as $r) {
            if (isset($counts[$r['status']])) {
                $counts[$r['status']]++;
            }
            if ($r['status'] === 'Late') {
                $late[] = $r;
            } elseif ($r['status'] === 'Absent') {
                $absent[] = $r;
            }
        }

        $last = $this->selectMax('date', 'last_date')->first();

        return [
            'date'     => $date,
            'counts'   => $counts,
            'total'    => count($rows),
            'late'     => $late,
            'absent'   => $absent,
            'lastDate' => $last['last_date'] ?? null,
        ];
    }

    /**
     * Everyone expected to scan: active accounts with a biometric number,
     * plus named scanner users not linked to an account (scanner_only).
     *
     * @return array<string,array{ac_no:string,name:string,role:string,user_id:?int,scanner_only:bool}> keyed by AC-No
     */
    public static function expectedPeople(): array
    {
        $people = [];
        foreach ((new UserModel())->select('id, name, role, ac_no')->where('is_active', 1)->where('ac_no IS NOT NULL', null, false)->where('ac_no !=', '')->findAll() as $u) {
            $people[(string) $u['ac_no']] = ['ac_no' => (string) $u['ac_no'], 'name' => $u['name'], 'role' => ucfirst($u['role']), 'user_id' => (int) $u['id'], 'scanner_only' => false];
        }
        foreach ((new BiometricEmployeeModel())->where('is_placeholder', 0)->findAll() as $b) {
            $people[(string) $b['ac_no']] ??= ['ac_no' => (string) $b['ac_no'], 'name' => $b['name'], 'role' => 'Scanner only', 'user_id' => null, 'scanner_only' => true];
        }

        return $people;
    }

    /**
     * Gives an account the records scanned under its biometric number that
     * don't belong to anyone yet (e.g. imported before the number was set),
     * and refreshes the name shown on all of that account's records. Records
     * already tied to another person are never touched, so reusing an old
     * number can't pull in someone else's history.
     *
     * @return int records newly linked
     */
    public function claimForUser(int $userId, string $acNo, string $name): int
    {
        $this->set(['user_id' => $userId, 'employee_name' => $name])
            ->where('employee_id', 'AC-' . $acNo)
            ->where('user_id', null)
            ->update();
        $claimed = $this->db->affectedRows();

        $this->set(['employee_name' => $name])->where('user_id', $userId)->update();

        return $claimed;
    }
}

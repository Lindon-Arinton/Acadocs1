<?php

namespace App\Models;

use CodeIgniter\Model;

class TimeRecordModel extends Model
{
    public const STATUSES = ['Present', 'Late', 'Absent', 'On Leave'];

    protected $table = 'time_records';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['date', 'employee_name', 'employee_id', 'time_in', 'time_out', 'status', 'remarks'];

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
}

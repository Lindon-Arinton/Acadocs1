<?php

namespace App\Models;

use CodeIgniter\Model;

class TimeRecordModel extends Model
{
    protected $table = 'time_records';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['date', 'employee_name', 'employee_id', 'user_id', 'time_in', 'time_out', 'status', 'remarks'];

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

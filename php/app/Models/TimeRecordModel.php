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
    protected $allowedFields = ['date', 'employee_name', 'employee_id', 'time_in', 'time_out', 'status', 'remarks'];
}

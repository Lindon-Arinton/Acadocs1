<?php

namespace App\Models;

use CodeIgniter\Model;

class ParentMeetingModel extends Model
{
    protected $table = 'parent_meetings';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['title', 'date', 'expected_parents', 'actual_attendance', 'attendance_rate'];
}

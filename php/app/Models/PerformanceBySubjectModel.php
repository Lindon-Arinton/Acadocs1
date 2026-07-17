<?php

namespace App\Models;

use CodeIgniter\Model;

class PerformanceBySubjectModel extends Model
{
    protected $table = 'performance_by_subject';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['school_year', 'subject', 'grade_level', 'instructor', 'mps'];
}

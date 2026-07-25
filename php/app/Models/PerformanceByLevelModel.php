<?php

namespace App\Models;

use CodeIgniter\Model;

class PerformanceByLevelModel extends Model
{
    protected $table = 'performance_by_level';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['school_year', 'term', 'grade_level', 'mps', 'nds'];
}

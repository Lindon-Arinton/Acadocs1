<?php

namespace App\Models;

use CodeIgniter\Model;

class EnrollmentByLevelModel extends Model
{
    protected $table = 'enrollment_by_level';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['school_year', 'grade_level', 'students', 'male', 'female', 'sections'];
}

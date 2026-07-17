<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherSubjectModel extends Model
{
    protected $table = 'teacher_subjects';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['teacher_id', 'subject'];
}

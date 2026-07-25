<?php

namespace App\Models;

use CodeIgniter\Model;

class MpsTestScoreModel extends Model
{
    protected $table = 'mps_test_scores';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['school_year', 'term', 'grade_level', 'subject', 'test_period', 'mps'];

    public function forYearTerm(string $schoolYear, int $term): array
    {
        return $this->where('school_year', $schoolYear)->where('term', $term)->findAll();
    }
}

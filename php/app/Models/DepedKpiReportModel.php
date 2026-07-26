<?php

namespace App\Models;

use CodeIgniter\Model;

class DepedKpiReportModel extends Model
{
    protected $table = 'deped_kpi_reports';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'school_year', 'gross_enrolment_rate', 'net_enrolment_rate', 'cohort_survival_rate',
        'repetition_rate', 'promotion_rate', 'retention_rate', 'graduation_rate',
        'completion_rate', 'transition_rate', 'dropout_rate',
        'enrolment_total', 'enrolment_male', 'enrolment_female', 'source_file',
    ];

    public function allByYear(): array
    {
        return $this->orderBy('school_year', 'ASC')->findAll();
    }

    public function forYear(string $schoolYear): ?array
    {
        return $this->where('school_year', $schoolYear)->first();
    }
}

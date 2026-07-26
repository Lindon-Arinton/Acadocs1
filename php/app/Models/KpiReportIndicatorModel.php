<?php

namespace App\Models;

use CodeIgniter\Model;

class KpiReportIndicatorModel extends Model
{
    protected $table = 'kpi_report_indicators';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'school_year', 'gross_enrollment_rate', 'net_enrollment_rate', 'cohort_survival_rate',
        'repetition_rate', 'promotion_rate', 'retention_rate', 'graduation_rate',
        'completion_rate', 'transition_rate', 'dropout_rate',
        'enrollment_total', 'enrollment_male', 'enrollment_female',
    ];

    public function forYear(string $schoolYear): ?array
    {
        return $this->where('school_year', $schoolYear)->first();
    }
}

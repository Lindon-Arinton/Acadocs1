<?php

namespace App\Models;

use CodeIgniter\Model;

class KpiSnapshotModel extends Model
{
    protected $table = 'kpi_snapshots';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['school_year', 'total_enrollment', 'submission_compliance', 'average_mps', 'dropout_count', 'parent_attendance'];

    public function latest(): ?array
    {
        return $this->orderBy('recorded_at', 'DESC')->first();
    }

    public function forYear(string $schoolYear): ?array
    {
        return $this->where('school_year', $schoolYear)->orderBy('recorded_at', 'DESC')->first();
    }
}

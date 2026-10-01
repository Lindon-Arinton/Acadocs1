<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Per-grade enrollment, one snapshot per school year + month (YYYY-MM).
 * month = '' is a year-level figure with no month (e.g. from a DepEd KPI
 * report), used only when the year has no monthly snapshot.
 */
class EnrollmentByLevelModel extends Model
{
    protected $table = 'enrollment_by_level';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['school_year', 'month', 'grade_level', 'students', 'male', 'female', 'sections'];

    /** @return string[] months (YYYY-MM) with a snapshot for the school year, oldest first */
    public function monthsFor(string $schoolYear): array
    {
        $rows = $this->builder()
            ->select('month')->distinct()
            ->where('school_year', $schoolYear)->where('month !=', '')
            ->orderBy('month', 'ASC')
            ->get()->getResultArray();

        return array_column($rows, 'month');
    }

    /**
     * Rows for one snapshot: the given month, or by default the latest month
     * (falling back to the no-month figures when the year has no months).
     */
    public function forYear(string $schoolYear, ?string $month = null): array
    {
        if ($month === null) {
            $months = $this->monthsFor($schoolYear);
            $month  = $months === [] ? '' : end($months);
        }

        $rows = $this->where('school_year', $schoolYear)->where('month', $month)->findAll();

        // Grade 7, 8, 9, 10 — not the string order (which puts "Grade 10" first).
        usort($rows, static fn (array $a, array $b) => (int) preg_replace('/\D/', '', $a['grade_level']) <=> (int) preg_replace('/\D/', '', $b['grade_level']));

        return $rows;
    }

    /** @return array<string,int> school year => total students, from each year's latest snapshot */
    public function latestTotalsByYear(): array
    {
        $rows = $this->builder()
            ->select('school_year, month, SUM(students) AS total')
            ->groupBy(['school_year', 'month'])
            ->orderBy('school_year')->orderBy('month')
            ->get()->getResultArray();

        $totals = [];
        foreach ($rows as $row) {
            $totals[$row['school_year']] = (int) $row['total']; // later months overwrite earlier ('' sorts first)
        }

        return $totals;
    }
}

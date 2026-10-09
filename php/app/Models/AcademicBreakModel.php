<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Academic breaks (e.g. Christmas, EOSY): date ranges with no classes.
 * School days inside one are recorded as "Academic Break", not "Absent".
 */
class AcademicBreakModel extends Model
{
    protected $table = 'academic_breaks';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['label', 'start_date', 'end_date'];

    /** The break covering a date, if any. */
    public function covering(string $date): ?array
    {
        return $this->where('start_date <=', $date)->where('end_date >=', $date)->orderBy('start_date', 'ASC')->first();
    }
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class HolidayModel extends Model
{
    protected $table = 'holidays';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['date', 'label'];

    public function allDates(): array
    {
        return array_column($this->select('date')->findAll(), 'date');
    }
}

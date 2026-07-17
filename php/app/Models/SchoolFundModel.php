<?php

namespace App\Models;

use CodeIgniter\Model;

class SchoolFundModel extends Model
{
    protected $table = 'school_funds';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['date', 'category', 'description', 'particulars', 'amount', 'balance', 'prepared_by'];

    public function currentBalance(): float
    {
        $last = $this->orderBy('date', 'DESC')->first();

        return $last['balance'] ?? 0;
    }
}

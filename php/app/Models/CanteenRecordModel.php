<?php

namespace App\Models;

use CodeIgniter\Model;

class CanteenRecordModel extends Model
{
    protected $table = 'canteen_records';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['date', 'description', 'revenue', 'expenses', 'transaction_count'];

    public function totals(): array
    {
        return $this->select('SUM(revenue) AS rev, SUM(expenses) AS exp, SUM(net_income) AS net')->first() ?? [];
    }
}

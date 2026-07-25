<?php

namespace App\Models;

use CodeIgniter\Model;

class BiometricEmployeeModel extends Model
{
    protected $table = 'biometric_employees';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['ac_no', 'name', 'department', 'is_placeholder'];

    public function findByAcNo(string $acNo): ?array
    {
        return $this->where('ac_no', $acNo)->first();
    }
}

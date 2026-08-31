<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['name', 'email', 'password', 'role', 'photo', 'last_active_at'];

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }
}

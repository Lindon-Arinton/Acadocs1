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
    protected $allowedFields = ['name', 'email', 'password', 'role', 'photo', 'is_active', 'deactivated_at', 'last_active_at', 'last_viewed_announcements_at'];

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Limits the next query to accounts that haven't been deactivated — use
     * it for pickers and notification recipients.
     */
    public function active(): static
    {
        return $this->where('users.is_active', 1);
    }
}

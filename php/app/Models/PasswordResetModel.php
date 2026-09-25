<?php

namespace App\Models;

use CodeIgniter\Model;

class PasswordResetModel extends Model
{
    protected $table = 'password_resets';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['user_id', 'code_hash', 'attempts', 'expires_at', 'used_at', 'created_at'];

    /** The user's newest code that hasn't been used yet (may be expired). */
    public function latestOpenForUser(int $userId): ?array
    {
        return $this->where('user_id', $userId)
            ->where('used_at', null)
            ->orderBy('id', 'DESC')
            ->first();
    }

    /** Retires every outstanding code for the user (a new one was issued, or the password was reset). */
    public function closeAllForUser(int $userId): void
    {
        $this->where('user_id', $userId)->where('used_at', null)
            ->set('used_at', date('Y-m-d H:i:s'))
            ->update();
    }
}

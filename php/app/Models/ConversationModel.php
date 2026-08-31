<?php

namespace App\Models;

use CodeIgniter\Model;

class ConversationModel extends Model
{
    protected $table = 'conversations';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['type', 'name', 'created_by'];

    public function findDirectBetween(int $userA, int $userB): ?array
    {
        return $this->select('conversations.*')
            ->join('conversation_participants cp1', 'cp1.conversation_id = conversations.id')
            ->join('conversation_participants cp2', 'cp2.conversation_id = conversations.id')
            ->where('conversations.type', 'direct')
            ->where('cp1.user_id', $userA)
            ->where('cp2.user_id', $userB)
            ->first();
    }

    public function forUser(int $userId): array
    {
        return $this->select('conversations.*, cp.last_read_at, cp.muted')
            ->join('conversation_participants cp', 'cp.conversation_id = conversations.id')
            ->where('cp.user_id', $userId)
            ->orderBy('conversations.id', 'DESC')
            ->findAll();
    }
}

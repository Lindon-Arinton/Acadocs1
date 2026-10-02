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
    /** The direct conversation between two users, created if they've never chatted. */
    public function findOrCreateDirect(int $userId, int $otherId): int
    {
        $existing = $this->findDirectBetween($userId, $otherId);
        if ($existing) {
            return (int) $existing['id'];
        }

        $convoId = (int) $this->insert([
            'type'       => 'direct',
            'name'       => null,
            'created_by' => $userId,
        ]);
        $participants = new ConversationParticipantModel();
        $participants->insert(['conversation_id' => $convoId, 'user_id' => $userId]);
        $participants->insert(['conversation_id' => $convoId, 'user_id' => $otherId]);

        return $convoId;
    }
}

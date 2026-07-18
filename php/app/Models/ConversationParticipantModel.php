<?php

namespace App\Models;

use CodeIgniter\Model;

class ConversationParticipantModel extends Model
{
    protected $table = 'conversation_participants';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['conversation_id', 'user_id', 'last_read_at'];

    public function isParticipant(int $conversationId, int $userId): bool
    {
        return (bool) $this->where('conversation_id', $conversationId)->where('user_id', $userId)->first();
    }

    public function forConversation(int $conversationId): array
    {
        return $this->select('conversation_participants.*, users.name, users.role, users.photo')
            ->join('users', 'users.id = conversation_participants.user_id')
            ->where('conversation_id', $conversationId)
            ->findAll();
    }

    public function markRead(int $conversationId, int $userId): void
    {
        // Use the database's own clock (NOW()) rather than PHP's date() so that
        // last_read_at is directly comparable to messages.created_at (also
        // DB-generated) — mixing the two risks a timezone-offset mismatch that
        // makes messages look permanently unread.
        $this->set('last_read_at', 'NOW()', false)
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update();
    }
}

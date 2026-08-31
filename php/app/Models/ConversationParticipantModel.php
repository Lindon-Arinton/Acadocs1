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
    protected $allowedFields = ['conversation_id', 'user_id', 'last_read_at', 'muted'];

    public function isParticipant(int $conversationId, int $userId): bool
    {
        return (bool) $this->where('conversation_id', $conversationId)->where('user_id', $userId)->first();
    }

    public function isMuted(int $conversationId, int $userId): bool
    {
        $row = $this->where('conversation_id', $conversationId)->where('user_id', $userId)->first();

        return $row !== null && (bool) $row['muted'];
    }

    public function setMuted(int $conversationId, int $userId, bool $muted): void
    {
        $this->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->set('muted', $muted ? 1 : 0)
            ->update();
    }

    /** Removes the caller from a conversation (leaving a group). */
    public function leave(int $conversationId, int $userId): void
    {
        $this->where('conversation_id', $conversationId)->where('user_id', $userId)->delete();
    }

    public function remove(int $conversationId, int $userId): void
    {
        $this->where('conversation_id', $conversationId)->where('user_id', $userId)->delete();
    }

    public function countFor(int $conversationId): int
    {
        return $this->where('conversation_id', $conversationId)->countAllResults();
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

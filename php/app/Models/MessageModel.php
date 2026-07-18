<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageModel extends Model
{
    protected $table = 'messages';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['conversation_id', 'sender_id', 'body'];

    public function forConversation(int $conversationId, int $afterId = 0): array
    {
        $builder = $this->select('messages.*, users.name AS sender_name, users.photo AS sender_photo')
            ->join('users', 'users.id = messages.sender_id')
            ->where('conversation_id', $conversationId);

        if ($afterId > 0) {
            $builder->where('messages.id >', $afterId);
        }

        return $builder->orderBy('messages.id', 'ASC')->findAll();
    }

    public function lastForConversation(int $conversationId): ?array
    {
        return $this->where('conversation_id', $conversationId)
            ->orderBy('id', 'DESC')->first();
    }

    public function unreadCount(int $conversationId, int $userId, ?string $lastReadAt): int
    {
        $builder = $this->where('conversation_id', $conversationId)
            ->where('sender_id !=', $userId);

        if ($lastReadAt) {
            $builder->where('created_at >', $lastReadAt);
        }

        return $builder->countAllResults();
    }
}

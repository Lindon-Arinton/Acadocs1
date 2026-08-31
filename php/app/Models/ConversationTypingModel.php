<?php

namespace App\Models;

use CodeIgniter\Model;

class ConversationTypingModel extends Model
{
    protected $table = 'conversation_typing';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['conversation_id', 'user_id'];

    /** Records "still typing right now" for the caller in a conversation. */
    public function ping(int $conversationId, int $userId): void
    {
        $existing = $this->where('conversation_id', $conversationId)->where('user_id', $userId)->first();

        // DB-generated NOW() so this stays directly comparable to other
        // DB-generated timestamps, same reasoning as ConversationParticipantModel::markRead().
        if ($existing) {
            $this->set('updated_at', 'NOW()', false)->where('id', $existing['id'])->update();
        } else {
            $this->insert(['conversation_id' => $conversationId, 'user_id' => $userId]);
            $this->set('updated_at', 'NOW()', false)->where('id', $this->getInsertID())->update();
        }
    }

    /**
     * Other participants of $conversationId whose typing ping is still fresh
     * (within $freshSeconds), excluding the viewer themself.
     *
     * @return array<int,array{user_id:int,name:string}>
     */
    public function activeTypers(int $conversationId, int $viewerId, int $freshSeconds = 4): array
    {
        return $this->select('conversation_typing.user_id, users.name')
            ->join('users', 'users.id = conversation_typing.user_id')
            ->where('conversation_typing.conversation_id', $conversationId)
            ->where('conversation_typing.user_id !=', $viewerId)
            ->where('conversation_typing.updated_at >', date('Y-m-d H:i:s', time() - $freshSeconds))
            ->findAll();
    }
}

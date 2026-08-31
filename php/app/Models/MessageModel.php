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
    protected $allowedFields = [
        'conversation_id', 'sender_id', 'reply_to_id', 'body', 'attachment_path', 'attachment_name', 'attachment_ext',
        'edited_at', 'deleted_at',
    ];

    /**
     * Soft-deleted messages (deleted_at set) are still returned — "unsend"
     * replaces the bubble with a "This message was unsent" placeholder for
     * everyone (see Chat::messages()), it doesn't erase the fact that a
     * message was there, matching WhatsApp/Messenger. The row (and anything
     * that reply_to_id points at) stays intact in the database either way.
     */
    public function forConversation(int $conversationId, int $afterId = 0): array
    {
        $builder = $this->select('messages.*, users.name AS sender_name, users.photo AS sender_photo, users.role AS sender_role')
            ->join('users', 'users.id = messages.sender_id')
            ->where('conversation_id', $conversationId);

        if ($afterId > 0) {
            $builder->where('messages.id >', $afterId);
        }

        return $builder->orderBy('messages.id', 'ASC')->findAll();
    }

    /**
     * Reply-to previews for a set of messages, keyed by the *replying*
     * message's id — small enough (id/body/sender/attachment name) to quote
     * above the reply bubble without a second round trip per message.
     *
     * @param array<int,int> $replyToIds
     * @return array<int,array{id:int,body:?string,sender_name:string,attachment_name:?string}>
     */
    public function previewsFor(array $replyToIds): array
    {
        if ($replyToIds === []) {
            return [];
        }

        $rows = $this->select('messages.id, messages.body, messages.attachment_name, messages.deleted_at, users.name AS sender_name')
            ->join('users', 'users.id = messages.sender_id')
            ->whereIn('messages.id', $replyToIds)
            ->findAll();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['id']] = [
                'id'              => (int) $row['id'],
                'body'            => $row['deleted_at'] ? null : $row['body'],
                'sender_name'     => $row['sender_name'],
                'attachment_name' => $row['deleted_at'] ? null : $row['attachment_name'],
            ];
        }

        return $result;
    }

    public function lastForConversation(int $conversationId): ?array
    {
        return $this->where('conversation_id', $conversationId)
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')->first();
    }

    public function unreadCount(int $conversationId, int $userId, ?string $lastReadAt): int
    {
        $builder = $this->where('conversation_id', $conversationId)
            ->where('sender_id !=', $userId)
            ->where('deleted_at', null);

        if ($lastReadAt) {
            $builder->where('created_at >', $lastReadAt);
        }

        return $builder->countAllResults();
    }

    /** True if $messageId exists, belongs to $conversationId, and was sent by $userId. */
    public function isOwnedBy(int $messageId, int $conversationId, int $userId): bool
    {
        return (bool) $this->where('id', $messageId)
            ->where('conversation_id', $conversationId)
            ->where('sender_id', $userId)
            ->first();
    }

    public function editBody(int $messageId, string $body): void
    {
        $this->update($messageId, ['body' => $body, 'edited_at' => date('Y-m-d H:i:s')]);
    }

    public function softDelete(int $messageId): void
    {
        $this->update($messageId, ['deleted_at' => date('Y-m-d H:i:s')]);
    }
}

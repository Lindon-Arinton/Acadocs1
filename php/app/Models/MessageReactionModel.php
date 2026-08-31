<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageReactionModel extends Model
{
    protected $table = 'message_reactions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['message_id', 'user_id', 'emoji'];

    /**
     * Sets the caller's reaction on a message. Re-picking the same emoji
     * removes it (toggle-off); picking a different one replaces it — one
     * active reaction per user per message, matching Messenger.
     *
     * @return string 'added'|'removed'
     */
    public function toggle(int $messageId, int $userId, string $emoji): string
    {
        $existing = $this->where('message_id', $messageId)->where('user_id', $userId)->first();

        if ($existing && $existing['emoji'] === $emoji) {
            $this->delete($existing['id']);

            return 'removed';
        }

        if ($existing) {
            $this->update($existing['id'], ['emoji' => $emoji]);
        } else {
            $this->insert(['message_id' => $messageId, 'user_id' => $userId, 'emoji' => $emoji]);
        }

        return 'added';
    }

    /**
     * Reaction summary for a set of messages, grouped by emoji per message.
     *
     * @param array<int,int> $messageIds
     * @return array<int,array<int,array{emoji:string,count:int,mine:bool}>> keyed by message_id
     */
    public function forMessages(array $messageIds, int $viewerId): array
    {
        if ($messageIds === []) {
            return [];
        }

        $rows = $this->whereIn('message_id', $messageIds)->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $mid = (int) $row['message_id'];
            $key = $row['emoji'];
            $grouped[$mid][$key] ??= ['emoji' => $key, 'count' => 0, 'mine' => false];
            $grouped[$mid][$key]['count']++;
            if ((int) $row['user_id'] === $viewerId) {
                $grouped[$mid][$key]['mine'] = true;
            }
        }

        $result = [];
        foreach ($grouped as $mid => $emojis) {
            $result[$mid] = array_values($emojis);
        }

        return $result;
    }
}

<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Models\ConversationModel;
use App\Models\ConversationParticipantModel;
use App\Models\ConversationTypingModel;
use App\Models\MessageModel;
use App\Models\MessageReactionModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Chat extends BaseController
{
    private const ALLOWED_ATTACHMENT_EXT = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar',
    ];
    private const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const QUICK_REACTIONS = ['👍', '❤️', '😆', '😮', '😢', '🙏'];
    private const ONLINE_WINDOW_SECONDS = 120;

    public function index()
    {
        $user = currentUser();

        if ($this->request->getMethod() === 'POST') {
            return $this->create();
        }

        $conversationModel = new ConversationModel();
        $participantModel  = new ConversationParticipantModel();
        $messageModel      = new MessageModel();

        $conversations = $conversationModel->forUser((int) $user['id']);

        foreach ($conversations as &$c) {
            $participants = $participantModel->forConversation((int) $c['id']);
            $others       = array_values(array_filter(
                $participants,
                static fn (array $p): bool => (int) $p['user_id'] !== (int) $user['id']
            ));

            if ($c['type'] === 'group') {
                $c['display_name'] = $c['name'] !== null && $c['name'] !== ''
                    ? $c['name']
                    : implode(', ', array_column($others, 'name'));
                $c['other_photo'] = null;
                $c['other_role']  = null;
                $c['other_id']    = null;
                $c['member_count'] = count($participants);
            } else {
                $c['display_name'] = $others[0]['name'] ?? 'Unknown User';
                $c['other_photo']  = $this->existingPhoto($others[0]['photo'] ?? null);
                $c['other_role']   = $others[0]['role'] ?? null;
                $c['other_id']     = isset($others[0]['user_id']) ? (int) $others[0]['user_id'] : null;
                $c['member_count'] = 2;
            }

            $last = $messageModel->lastForConversation((int) $c['id']);

            $c['last_message'] = $last['body'] ?? null;
            $c['last_time']    = $last['created_at'] ?? $c['created_at'];
            $c['unread']       = $messageModel->unreadCount((int) $c['id'], (int) $user['id'], $c['last_read_at']);
            $c['muted']        = (bool) ($c['muted'] ?? false);
        }
        unset($c);

        usort(
            $conversations,
            static fn (array $a, array $b): int => strtotime($b['last_time']) <=> strtotime($a['last_time'])
        );

        // Everyone can start direct chats and see "All Users"; only admin
        // (the principal) can create a *group* chat.
        $canMessage     = true;
        $canCreateGroup = hasRole('admin');

        $users = $canMessage
            ? (new UserModel())->where('id !=', $user['id'])->orderBy('name', 'ASC')->findAll()
            : [];

        foreach ($users as &$u) {
            $u['photo'] = $this->existingPhoto($u['photo'] ?? null);
        }
        unset($u);

        $openId = (int) ($this->request->getGet('open') ?? 0);
        if ($openId && ! $participantModel->isParticipant($openId, (int) $user['id'])) {
            $openId = 0;
        }

        return view('pages/shared/chat', [
            'pageTitle'      => 'Chat',
            'conversations'  => $conversations,
            'users'          => $users,
            'canCreate'      => $canMessage,
            'canCreateGroup' => $canCreateGroup,
            'openId'         => $openId,
            'quickReactions' => self::QUICK_REACTIONS,
        ]);
    }

    private function create()
    {
        $isAjax = $this->request->isAJAX();
        $user   = currentUser();

        $action            = $this->request->getPost('action');
        $conversationModel = new ConversationModel();
        $participantModel  = new ConversationParticipantModel();
        $message           = null;
        $error             = null;
        $convoId           = null;

        try {
            if ($action === 'create_direct') {
                $otherId = (int) $this->request->getPost('user_id');
                $other   = (new UserModel())->find($otherId);

                if (! $other) {
                    $error = 'Please choose a valid user.';
                } else {
                    $existing = $conversationModel->findDirectBetween((int) $user['id'], $otherId);

                    if ($existing) {
                        $convoId = (int) $existing['id'];
                    } else {
                        $convoId = $conversationModel->insert([
                            'type'       => 'direct',
                            'name'       => null,
                            'created_by' => $user['id'],
                        ]);
                        $participantModel->insert(['conversation_id' => $convoId, 'user_id' => $user['id']]);
                        $participantModel->insert(['conversation_id' => $convoId, 'user_id' => $otherId]);
                    }
                    $message = 'Chat started with ' . $other['name'] . '.';
                }
            } elseif ($action === 'create_group') {
                if (! hasRole('admin')) {
                    return $isAjax ? $this->ajaxError('Only the principal can create group chats.', 403) : redirect()->to('/chat');
                }

                $name    = trim($this->request->getPost('name') ?? '');
                $userIds = $this->request->getPost('user_ids') ?? [];
                $userIds = array_map('intval', is_array($userIds) ? $userIds : []);
                $userIds = array_unique(array_filter($userIds));

                if ($name === '') {
                    $error = 'Please enter a group name.';
                } elseif (count($userIds) < 1) {
                    $error = 'Please select at least one member.';
                } else {
                    $convoId = $conversationModel->insert([
                        'type'       => 'group',
                        'name'       => $name,
                        'created_by' => $user['id'],
                    ]);
                    $participantModel->insert(['conversation_id' => $convoId, 'user_id' => $user['id']]);
                    foreach ($userIds as $uid) {
                        if ($uid !== (int) $user['id']) {
                            $participantModel->insert(['conversation_id' => $convoId, 'user_id' => $uid]);
                        }
                    }
                    $message = 'Group chat "' . $name . '" created.';
                }
            }
        } catch (\Throwable $e) {
            return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/chat');
        }

        if ($error) {
            return $isAjax ? $this->ajaxError($error) : redirect()->to('/chat')->with('flash', ['type' => 'danger', 'msg' => $error]);
        }

        if ($isAjax) {
            return $message
                ? $this->ajaxSuccess($message, [
                    'conversation_id' => $convoId,
                    'redirect'        => $convoId ? base_url('chat?open=' . $convoId) : null,
                ])
                : $this->ajaxError('Unknown action.');
        }

        return redirect()->to('/chat' . ($convoId ? '?open=' . $convoId : ''));
    }

    public function messages(int $id)
    {
        $user             = currentUser();
        $participantModel = new ConversationParticipantModel();

        if (! $participantModel->isParticipant($id, (int) $user['id'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        $afterId     = (int) ($this->request->getGet('after') ?? 0);
        $messageModel = new MessageModel();
        $messages     = $messageModel->forConversation($id, $afterId);

        $participantModel->markRead($id, (int) $user['id']);

        $messageIds = array_map(static fn (array $m): int => (int) $m['id'], $messages);
        $reactions  = (new MessageReactionModel())->forMessages($messageIds, (int) $user['id']);

        $replyToIds = array_values(array_filter(array_map(
            static fn (array $m) => $m['reply_to_id'] !== null ? (int) $m['reply_to_id'] : null,
            $messages
        )));
        $replyPreviews = $messageModel->previewsFor($replyToIds);

        $data = array_map(function (array $m) use ($user, $reactions, $replyPreviews): array {
            $photo    = $this->existingPhoto($m['sender_photo'] ?? null);
            $mid      = (int) $m['id'];
            $isMe     = (int) $m['sender_id'] === (int) $user['id'];
            $deleted  = $m['deleted_at'] !== null;

            // A deleted message still shows as a placeholder bubble for
            // everyone ("This message was unsent") — WhatsApp/Messenger-style
            // — rather than silently vanishing, so the other person knows
            // something was there. Everything actionable on it (attachment,
            // reactions, reply quote) is stripped since there's nothing left
            // to act on.
            $ext     = ! $deleted && $m['attachment_ext'] ? strtolower($m['attachment_ext']) : null;
            $isImage = $ext && in_array($ext, self::IMAGE_EXT, true);

            return [
                'id'                  => $mid,
                'body'                => $deleted ? null : $m['body'],
                'sender_id'           => (int) $m['sender_id'],
                'sender_name'         => $m['sender_name'],
                'sender_role'         => $m['sender_role'],
                'sender_photo'        => $photo ? base_url('uploads/avatars/' . $photo) : null,
                'is_me'               => $isMe,
                'time'                => date('h:i A', strtotime($m['created_at'])),
                'created_at'          => $m['created_at'],
                'attachment_url'      => (! $deleted && $m['attachment_path']) ? base_url('chat/attachment/' . $m['id']) : null,
                'attachment_name'     => $deleted ? null : $m['attachment_name'],
                'attachment_is_image' => $isImage,
                'edited'              => $m['edited_at'] !== null,
                'deleted'             => $deleted,
                'reactions'           => $deleted ? [] : ($reactions[$mid] ?? []),
                'reply_to'            => (! $deleted && $m['reply_to_id'] !== null) ? ($replyPreviews[(int) $m['reply_to_id']] ?? null) : null,
            ];
        }, $messages);

        return $this->ajaxSuccess('OK', [
            'messages'     => $data,
            'participants' => $this->participantStates($id, (int) $user['id']),
            'typing'       => (new ConversationTypingModel())->activeTypers($id, (int) $user['id']),
        ]);
    }

    /** Read-state + presence for every OTHER participant — drives Seen/Delivered and online dots. */
    private function participantStates(int $conversationId, int $viewerId): array
    {
        $rows = (new ConversationParticipantModel())
            ->select('conversation_participants.user_id, conversation_participants.last_read_at, users.last_active_at')
            ->join('users', 'users.id = conversation_participants.user_id')
            ->where('conversation_id', $conversationId)
            ->where('conversation_participants.user_id !=', $viewerId)
            ->findAll();

        $now = time();

        return array_map(static function (array $r) use ($now): array {
            $lastActive = $r['last_active_at'] ? strtotime($r['last_active_at']) : null;

            return [
                'user_id'       => (int) $r['user_id'],
                'last_read_at'  => $r['last_read_at'],
                'online'        => $lastActive !== null && ($now - $lastActive) <= self::ONLINE_WINDOW_SECONDS,
                'last_active_at' => $r['last_active_at'],
            ];
        }, $rows);
    }

    public function send(int $id)
    {
        $user             = currentUser();
        $participantModel = new ConversationParticipantModel();

        if (! $participantModel->isParticipant($id, (int) $user['id'])) {
            return $this->ajaxError('You are not part of this conversation.', 403);
        }

        $body = trim($this->request->getPost('body') ?? '');
        $file = $this->request->getFile('file');
        $hasFile = $file && $file->isValid() && ! $file->hasMoved();

        if ($body === '' && ! $hasFile) {
            return $this->ajaxError('Message cannot be empty.');
        }
        if (mb_strlen($body) > 2000) {
            return $this->ajaxError('Message is too long.');
        }

        $replyToId = (int) ($this->request->getPost('reply_to_id') ?? 0);
        if ($replyToId > 0 && ! (new MessageModel())->where('id', $replyToId)->where('conversation_id', $id)->first()) {
            $replyToId = 0;
        }

        $attachmentPath = null;
        $attachmentName = null;
        $attachmentExt  = null;

        if ($hasFile) {
            $rules = [
                'file' => [
                    'label'  => 'File',
                    'rules'  => 'max_size[file,10240]|ext_in[file,' . implode(',', self::ALLOWED_ATTACHMENT_EXT) . ']',
                    'errors' => [
                        'max_size' => 'File is too large (max 10MB).',
                        'ext_in'   => 'Unsupported file type.',
                    ],
                ],
            ];

            if (! $this->validate($rules)) {
                return $this->ajaxError(implode(' ', $this->validator->getErrors()));
            }

            $targetDir = WRITEPATH . 'uploads/chat/' . $id;

            if (! is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $newName = $file->getRandomName();
            $file->move($targetDir, $newName);

            $attachmentPath = $targetDir . DIRECTORY_SEPARATOR . $newName;
            $attachmentName = $file->getClientName();
            $attachmentExt  = strtolower(pathinfo($attachmentName, PATHINFO_EXTENSION));
        }

        try {
            (new MessageModel())->insert([
                'conversation_id' => $id,
                'sender_id'       => $user['id'],
                'reply_to_id'     => $replyToId > 0 ? $replyToId : null,
                'body'            => $body,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
                'attachment_ext'  => $attachmentExt,
            ]);
        } catch (\Throwable $e) {
            return $this->ajaxError('Something went wrong: ' . $e->getMessage());
        }

        $participantModel->markRead($id, (int) $user['id']);

        return $this->ajaxSuccess('Sent.');
    }

    public function react(int $conversationId, int $messageId)
    {
        $user = currentUser();

        if (! (new ConversationParticipantModel())->isParticipant($conversationId, (int) $user['id'])) {
            return $this->ajaxError('You are not part of this conversation.', 403);
        }

        $emoji = trim($this->request->getPost('emoji') ?? '');
        if (! in_array($emoji, self::QUICK_REACTIONS, true)) {
            return $this->ajaxError('Unsupported reaction.');
        }

        if (! (new MessageModel())->where('id', $messageId)->where('conversation_id', $conversationId)->first()) {
            throw PageNotFoundException::forPageNotFound();
        }

        $result = (new MessageReactionModel())->toggle($messageId, (int) $user['id'], $emoji);

        return $this->ajaxSuccess($result);
    }

    public function editMessage(int $conversationId, int $messageId)
    {
        $user        = currentUser();
        $messageModel = new MessageModel();

        if (! $messageModel->isOwnedBy($messageId, $conversationId, (int) $user['id'])) {
            return $this->ajaxError('You can only edit your own messages.', 403);
        }

        $body = trim($this->request->getPost('body') ?? '');
        if ($body === '') {
            return $this->ajaxError('Message cannot be empty.');
        }
        if (mb_strlen($body) > 2000) {
            return $this->ajaxError('Message is too long.');
        }

        $messageModel->editBody($messageId, $body);

        return $this->ajaxSuccess('Updated.');
    }

    public function deleteMessage(int $conversationId, int $messageId)
    {
        $user        = currentUser();
        $messageModel = new MessageModel();

        if (! $messageModel->isOwnedBy($messageId, $conversationId, (int) $user['id'])) {
            return $this->ajaxError('You can only delete your own messages.', 403);
        }

        $messageModel->softDelete($messageId);

        return $this->ajaxSuccess('Deleted.');
    }

    public function typing(int $id)
    {
        $user = currentUser();

        if (! (new ConversationParticipantModel())->isParticipant($id, (int) $user['id'])) {
            return $this->ajaxError('You are not part of this conversation.', 403);
        }

        (new ConversationTypingModel())->ping($id, (int) $user['id']);

        return $this->ajaxSuccess('OK');
    }

    public function members(int $id)
    {
        $user = currentUser();
        $participantModel = new ConversationParticipantModel();

        if (! $participantModel->isParticipant($id, (int) $user['id'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rows = $participantModel->forConversation($id);

        $members = array_map(function (array $r) use ($user): array {
            $photo = $this->existingPhoto($r['photo'] ?? null);

            return [
                'user_id' => (int) $r['user_id'],
                'name'    => $r['name'],
                'role'    => $r['role'],
                'photo'   => $photo ? base_url('uploads/avatars/' . $photo) : null,
                'is_me'   => (int) $r['user_id'] === (int) $user['id'],
            ];
        }, $rows);

        return $this->ajaxSuccess('OK', ['members' => $members]);
    }

    public function addMembers(int $id)
    {
        $user = currentUser();

        if (! hasRole('admin')) {
            return $this->ajaxError('Only the principal can manage group members.', 403);
        }

        $conversation = (new ConversationModel())->find($id);
        if (! $conversation || $conversation['type'] !== 'group') {
            throw PageNotFoundException::forPageNotFound();
        }

        $participantModel = new ConversationParticipantModel();
        if (! $participantModel->isParticipant($id, (int) $user['id'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        $userIds = $this->request->getPost('user_ids') ?? [];
        $userIds = array_unique(array_filter(array_map('intval', is_array($userIds) ? $userIds : [])));

        $added = 0;
        foreach ($userIds as $uid) {
            if (! $participantModel->isParticipant($id, $uid)) {
                $participantModel->insert(['conversation_id' => $id, 'user_id' => $uid]);
                $added++;
            }
        }

        return $this->ajaxSuccess($added . ' member(s) added.');
    }

    public function removeMember(int $id)
    {
        $user = currentUser();

        if (! hasRole('admin')) {
            return $this->ajaxError('Only the principal can manage group members.', 403);
        }

        $conversation = (new ConversationModel())->find($id);
        if (! $conversation || $conversation['type'] !== 'group') {
            throw PageNotFoundException::forPageNotFound();
        }

        $participantModel = new ConversationParticipantModel();
        if (! $participantModel->isParticipant($id, (int) $user['id'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        $targetId = (int) $this->request->getPost('user_id');
        if ($targetId === (int) $user['id']) {
            return $this->ajaxError('Use "Leave Group" to remove yourself.');
        }

        $participantModel->remove($id, $targetId);

        return $this->ajaxSuccess('Member removed.');
    }

    /**
     * Removes the caller's own participant row — "Leave Group" for a group
     * conversation, "Delete Chat" for a direct one. Either way this only
     * affects the caller's own view: the conversation and its message
     * history are untouched for whoever else is still in it. For a direct
     * chat, messaging the same person again starts a new conversation
     * (findDirectBetween() no longer matches once the row is gone) — the
     * same "fresh thread on your side" behavior Messenger has.
     */
    public function leave(int $id)
    {
        $user = currentUser();
        $participantModel = new ConversationParticipantModel();

        if (! $participantModel->isParticipant($id, (int) $user['id'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        $conversation = (new ConversationModel())->find($id);
        if (! $conversation) {
            throw PageNotFoundException::forPageNotFound();
        }

        $participantModel->leave($id, (int) $user['id']);

        return $this->ajaxSuccess($conversation['type'] === 'group' ? 'Left the group.' : 'Chat deleted.');
    }

    public function mute(int $id)
    {
        $user = currentUser();
        $participantModel = new ConversationParticipantModel();

        if (! $participantModel->isParticipant($id, (int) $user['id'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        $muted = ! $participantModel->isMuted($id, (int) $user['id']);
        $participantModel->setMuted($id, (int) $user['id'], $muted);

        return $this->ajaxSuccess($muted ? 'Muted.' : 'Unmuted.', ['muted' => $muted]);
    }

    public function attachment(int $messageId)
    {
        $user    = currentUser();
        $message = (new MessageModel())->find($messageId);

        if (! $message || ! $message['attachment_path']) {
            throw PageNotFoundException::forPageNotFound();
        }

        $participantModel = new ConversationParticipantModel();
        if (! $participantModel->isParticipant((int) $message['conversation_id'], (int) $user['id'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! is_file($message['attachment_path'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        $ext        = strtolower($message['attachment_ext'] ?? '');
        $imageMimes = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
        ];

        if (isset($imageMimes[$ext])) {
            return $this->response
                ->setHeader('Content-Type', $imageMimes[$ext])
                ->setHeader('Content-Disposition', 'inline; filename="' . $message['attachment_name'] . '"')
                ->setBody(file_get_contents($message['attachment_path']));
        }

        return $this->response->download($message['attachment_path'], null)->setFileName($message['attachment_name']);
    }

    /**
     * Returns the photo filename only if the file still exists on disk,
     * so a stale/orphaned DB reference (e.g. the upload was lost between
     * environments) degrades to the initials avatar instead of a broken image.
     */
    private function existingPhoto(?string $photo): ?string
    {
        if (! $photo) {
            return null;
        }

        return is_file(FCPATH . 'uploads/avatars/' . $photo) ? $photo : null;
    }
}

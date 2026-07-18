<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Models\ConversationModel;
use App\Models\ConversationParticipantModel;
use App\Models\MessageModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Chat extends BaseController
{
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
            } else {
                $c['display_name'] = $others[0]['name'] ?? 'Unknown User';
                $c['other_photo']  = $others[0]['photo'] ?? null;
            }

            $last = $messageModel->lastForConversation((int) $c['id']);

            $c['last_message'] = $last['body'] ?? null;
            $c['last_time']    = $last['created_at'] ?? $c['created_at'];
            $c['unread']       = $messageModel->unreadCount((int) $c['id'], (int) $user['id'], $c['last_read_at']);
        }
        unset($c);

        usort(
            $conversations,
            static fn (array $a, array $b): int => strtotime($b['last_time']) <=> strtotime($a['last_time'])
        );

        $users = hasRole('admin')
            ? (new UserModel())->where('id !=', $user['id'])->orderBy('role')->orderBy('name')->findAll()
            : [];

        return view('pages/shared/chat', [
            'pageTitle'     => 'Chat',
            'conversations' => $conversations,
            'users'         => $users,
            'canCreate'     => hasRole('admin'),
        ]);
    }

    private function create()
    {
        $isAjax = $this->request->isAJAX();
        $user   = currentUser();

        if (! hasRole('admin')) {
            return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to('/chat');
        }

        $action            = $this->request->getPost('action');
        $conversationModel = new ConversationModel();
        $participantModel  = new ConversationParticipantModel();
        $message           = null;
        $error             = null;

        try {
            if ($action === 'create_direct') {
                $otherId = (int) $this->request->getPost('user_id');
                $other   = (new UserModel())->find($otherId);

                if (! $other) {
                    $error = 'Please choose a valid user.';
                } else {
                    $existing = $conversationModel->findDirectBetween((int) $user['id'], $otherId);

                    if (! $existing) {
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
            return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
        }

        return redirect()->to('/chat');
    }

    public function messages(int $id)
    {
        $user              = currentUser();
        $participantModel  = new ConversationParticipantModel();

        if (! $participantModel->isParticipant($id, (int) $user['id'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        $afterId  = (int) ($this->request->getGet('after') ?? 0);
        $messages = (new MessageModel())->forConversation($id, $afterId);

        $participantModel->markRead($id, (int) $user['id']);

        $data = array_map(static function (array $m) use ($user): array {
            return [
                'id'           => (int) $m['id'],
                'body'         => $m['body'],
                'sender_id'    => (int) $m['sender_id'],
                'sender_name'  => $m['sender_name'],
                'sender_photo' => $m['sender_photo'] ? base_url('uploads/avatars/' . $m['sender_photo']) : null,
                'is_me'        => (int) $m['sender_id'] === (int) $user['id'],
                'time'         => date('h:i A', strtotime($m['created_at'])),
            ];
        }, $messages);

        return $this->ajaxSuccess('OK', ['messages' => $data]);
    }

    public function send(int $id)
    {
        $user             = currentUser();
        $participantModel = new ConversationParticipantModel();

        if (! $participantModel->isParticipant($id, (int) $user['id'])) {
            return $this->ajaxError('You are not part of this conversation.', 403);
        }

        $body = trim($this->request->getPost('body') ?? '');

        if ($body === '') {
            return $this->ajaxError('Message cannot be empty.');
        }
        if (mb_strlen($body) > 2000) {
            return $this->ajaxError('Message is too long.');
        }

        try {
            (new MessageModel())->insert([
                'conversation_id' => $id,
                'sender_id'       => $user['id'],
                'body'            => $body,
            ]);
        } catch (\Throwable $e) {
            return $this->ajaxError('Something went wrong: ' . $e->getMessage());
        }

        $participantModel->markRead($id, (int) $user['id']);

        return $this->ajaxSuccess('Sent.');
    }
}

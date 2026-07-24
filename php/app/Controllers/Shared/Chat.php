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
    private const ALLOWED_ATTACHMENT_EXT = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar',
    ];
    private const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

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
            } else {
                $c['display_name'] = $others[0]['name'] ?? 'Unknown User';
                $c['other_photo']  = $this->existingPhoto($others[0]['photo'] ?? null);
                $c['other_role']   = $others[0]['role'] ?? null;
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

        $users = hasRole('admin', 'secretary')
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
            'pageTitle'     => 'Chat',
            'conversations' => $conversations,
            'users'         => $users,
            'canCreate'     => hasRole('admin', 'secretary'),
            'openId'        => $openId,
        ]);
    }

    private function create()
    {
        $isAjax = $this->request->isAJAX();
        $user   = currentUser();

        if (! hasRole('admin', 'secretary')) {
            return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to('/chat');
        }

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
        $user              = currentUser();
        $participantModel  = new ConversationParticipantModel();

        if (! $participantModel->isParticipant($id, (int) $user['id'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        $afterId  = (int) ($this->request->getGet('after') ?? 0);
        $messages = (new MessageModel())->forConversation($id, $afterId);

        $participantModel->markRead($id, (int) $user['id']);

        $data = array_map(function (array $m) use ($user): array {
            $photo   = $this->existingPhoto($m['sender_photo'] ?? null);
            $ext     = $m['attachment_ext'] ? strtolower($m['attachment_ext']) : null;
            $isImage = $ext && in_array($ext, self::IMAGE_EXT, true);

            return [
                'id'                  => (int) $m['id'],
                'body'                => $m['body'],
                'sender_id'           => (int) $m['sender_id'],
                'sender_name'         => $m['sender_name'],
                'sender_role'         => $m['sender_role'],
                'sender_photo'        => $photo ? base_url('uploads/avatars/' . $photo) : null,
                'is_me'               => (int) $m['sender_id'] === (int) $user['id'],
                'time'                => date('h:i A', strtotime($m['created_at'])),
                'attachment_url'      => $m['attachment_path'] ? base_url('chat/attachment/' . $m['id']) : null,
                'attachment_name'     => $m['attachment_name'],
                'attachment_is_image' => $isImage,
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
        $file = $this->request->getFile('file');
        $hasFile = $file && $file->isValid() && ! $file->hasMoved();

        if ($body === '' && ! $hasFile) {
            return $this->ajaxError('Message cannot be empty.');
        }
        if (mb_strlen($body) > 2000) {
            return $this->ajaxError('Message is too long.');
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

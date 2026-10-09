<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Models\AnnouncementModel;
use App\Models\ConversationModel;
use App\Models\ConversationParticipantModel;
use App\Models\MessageModel;
use App\Models\NotificationModel;
use App\Models\UserModel;

class Announcements extends BaseController
{
    private const IMAGE_EXT       = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

    /** Announcement photos are public assets (like avatars), served straight from public/. */
    public static function imageDir(): string
    {
        return FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'announcements' . DIRECTORY_SEPARATOR;
    }

    /**
     * Shares an announcement into chats: to people (their direct chat with
     * the sharer, started if needed) and/or group chats the sharer is in.
     * Each message carries the announcement (shown as a card in the chat)
     * plus a plain-text fallback and the sharer's optional note.
     */
    public function share(int $id)
    {
        $announcement = (new AnnouncementModel())->find($id);
        if (! $announcement) {
            return $this->ajaxError('This announcement no longer exists.', 404);
        }

        $me       = (int) currentUser()['id'];
        $note     = trim((string) $this->request->getPost('note'));
        $userIds  = array_values(array_unique(array_filter(array_map('intval', (array) ($this->request->getPost('user_ids') ?? [])), static fn (int $u) => $u !== $me)));
        $groupIds = array_values(array_unique(array_filter(array_map('intval', (array) ($this->request->getPost('conversation_ids') ?? [])))));

        if ($userIds === [] && $groupIds === []) {
            return $this->ajaxError('Choose at least one person or group.');
        }
        if (mb_strlen($note) > 500) {
            return $this->ajaxError('The message is too long (500 characters max).');
        }

        $conversationModel = new ConversationModel();
        $participantModel  = new ConversationParticipantModel();
        $targets           = [];

        foreach ((new UserModel())->whereIn('id', $userIds ?: [0])->findAll() as $u) {
            $targets[] = $conversationModel->findOrCreateDirect($me, (int) $u['id']);
        }
        foreach ($groupIds as $cid) {
            if ($participantModel->isParticipant($cid, $me)) {
                $targets[] = $cid;
            }
        }
        $targets = array_values(array_unique($targets));

        if ($targets === []) {
            return $this->ajaxError('None of the selected chats are available.');
        }

        $body = '📢 ' . $announcement['title'] . ($note !== '' ? "\n\n" . $note : '');
        $messageModel = new MessageModel();
        foreach ($targets as $cid) {
            $messageModel->insert([
                'conversation_id' => $cid,
                'sender_id'       => $me,
                'body'            => $body,
                'announcement_id' => (int) $announcement['id'],
            ]);
            $participantModel->markRead($cid, $me);
        }

        $count = count($targets);

        return $this->ajaxSuccess('Shared to ' . $count . ' chat' . ($count === 1 ? '' : 's') . '.');
    }

    public function index()
    {
        $model = new AnnouncementModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX();

            if (! hasRole('admin', 'adas')) {
                return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to('/announcements');
            }

            $action  = $this->request->getPost('action');
            $message = null;

            try {
                if ($action === 'add') {
                    $type  = $this->request->getPost('type');
                    $title = trim((string) $this->request->getPost('title'));
                    $date  = $this->request->getPost('date');

                    // Only the title is required; content is optional.
                    if ($title === '') {
                        $error = 'Please enter a title.';

                        return $isAjax ? $this->ajaxError($error) : redirect()->to('/announcements')->with('flash', ['type' => 'danger', 'msg' => $error]);
                    }

                    if ($date < date('Y-m-d')) {
                        $error = 'Announcement date cannot be in the past.';

                        return $isAjax ? $this->ajaxError($error) : redirect()->to('/announcements')->with('flash', ['type' => 'danger', 'msg' => $error]);
                    }

                    // Optional schedule: publish later and/or archive automatically.
                    $publishAt = $this->request->getPost('publish_mode') === 'schedule'
                        ? $this->toDateTime($this->request->getPost('publish_at'))
                        : null;
                    $expiresAt = $this->toDateTime($this->request->getPost('expires_at'));
                    $scheduleError = match (true) {
                        $this->request->getPost('publish_mode') === 'schedule' && $publishAt === null => 'Choose when to publish the announcement.',
                        $publishAt !== null && $publishAt <= date('Y-m-d H:i:s') => 'The publish time must be in the future.',
                        $expiresAt !== null && $expiresAt <= ($publishAt ?? date('Y-m-d H:i:s')) => 'The archive time must be after the announcement is published.',
                        default => null,
                    };
                    if ($scheduleError !== null) {
                        return $isAjax ? $this->ajaxError($scheduleError) : redirect()->to('/announcements')->with('flash', ['type' => 'danger', 'msg' => $scheduleError]);
                    }

                    // Optional photo: validated before anything is saved.
                    $image = null;
                    $photo = $this->request->getFile('image');
                    if ($photo && $photo->getError() !== UPLOAD_ERR_NO_FILE) {
                        if (! $photo->isValid()) {
                            return $isAjax ? $this->ajaxError('The photo could not be uploaded.') : redirect()->to('/announcements');
                        }
                        if ($photo->getSize() > self::MAX_IMAGE_BYTES) {
                            return $isAjax ? $this->ajaxError('The photo must be 5 MB or smaller.') : redirect()->to('/announcements');
                        }
                        if (! in_array(strtolower($photo->getClientExtension()), self::IMAGE_EXT, true) || ! str_starts_with((string) $photo->getMimeType(), 'image/')) {
                            return $isAjax ? $this->ajaxError('The photo must be a JPG, PNG, GIF or WEBP image.') : redirect()->to('/announcements');
                        }

                        if (! is_dir(self::imageDir())) {
                            mkdir(self::imageDir(), 0755, true);
                        }
                        $image = $photo->getRandomName();
                        $photo->move(self::imageDir(), $image);
                    }

                    $announcementId = $model->insert([
                        'type'       => $type,
                        'title'      => $title,
                        'content'    => trim((string) $this->request->getPost('content')),
                        'image'      => $image,
                        'date'       => $date,
                        'status'     => 'active',
                        'publish_at' => $publishAt,
                        'expires_at' => $expiresAt,
                        'notified'   => $publishAt === null ? 1 : 0,
                        'created_by' => currentUser()['id'], // shown as "Posted by"
                    ]);

                    // Scheduled: everyone is notified when it goes live (App\Libraries\Automation).
                    if ($publishAt === null) {
                        $model->notifyEveryone($model->find($announcementId));
                        $message = 'Announcement posted successfully.';
                    } else {
                        $message = 'Announcement scheduled for ' . date('M d, Y · h:i A', strtotime($publishAt)) . '.';
                    }
                } elseif ($action === 'delete') {
                    $announcementId = (int) $this->request->getPost('id');
                    $existing       = $model->find($announcementId);
                    $model->delete($announcementId);
                    if (! empty($existing['image']) && is_file(self::imageDir() . $existing['image'])) {
                        unlink(self::imageDir() . $existing['image']);
                    }
                    (new NotificationModel())->deleteForRef('announcement', $announcementId);
                    $message = 'Announcement deleted.';
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/announcements');
            }

            if ($isAjax) {
                return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
            }

            session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message ?? '']);

            return redirect()->to('/announcements');
        }

        // Marks the "new announcements" badge (e.g. on the Teacher Dashboard)
        // as seen — anything posted before this moment no longer counts.
        (new UserModel())->update((int) currentUser()['id'], ['last_viewed_announcements_at' => date('Y-m-d H:i:s')]);

        $deletedNotice = null;
        $requestedId   = $this->request->getGet('id');
        if ($requestedId && ! $model->find((int) $requestedId)) {
            $deletedNotice = 'This announcement has already been deleted.';
        }

        $filter = $this->request->getGet('type') ?? 'all';
        $search = trim($this->request->getGet('q') ?? '');
        $sort   = $this->request->getGet('sort') ?? 'newest';

        $builder = $model->select('announcements.*, users.name AS poster_name, users.role AS poster_role')
            ->join('users', 'users.id = announcements.created_by', 'left');
        // Posters (principal / ADAS) also see scheduled and archived ones, tagged
        // as such; everyone else sees only what's live right now.
        if (! hasRole('admin', 'adas')) {
            $builder->live();
        }
        if ($filter !== 'all') {
            $builder->where('announcements.type', $filter);
        }
        if ($search !== '') {
            $builder->groupStart()
                ->like('announcements.title', $search)
                ->orLike('announcements.content', $search)
                ->groupEnd();
        }

        match ($sort) {
            'oldest'   => $builder->orderBy('announcements.date', 'ASC'),
            'title_az' => $builder->orderBy('announcements.title', 'ASC'),
            default    => $builder->orderBy('announcements.date', 'DESC')->orderBy('announcements.id', 'DESC'),
        };

        // "Share to chat" picker: everyone else, plus the group chats I'm in.
        $me          = (int) currentUser()['id'];
        $sharePeople = (new UserModel())->select('id, name, role')->where('id !=', $me)->orderBy('name', 'ASC')->findAll();
        $shareGroups = (new ConversationModel())->select('conversations.id, conversations.name')
            ->join('conversation_participants cp', 'cp.conversation_id = conversations.id')
            ->where('cp.user_id', $me)->where('conversations.type', 'group')
            ->orderBy('conversations.name', 'ASC')->findAll();

        return view('pages/shared/announcements', [
            'pageTitle'      => 'Announcements',
            'sharePeople'    => $sharePeople,
            'shareGroups'    => $shareGroups,
            'openId'         => (int) ($requestedId ?? 0),
            'announcements'  => $builder->findAll(),
            'filter'         => $filter,
            'search'         => $search,
            'sort'           => $sort,
            'flash'          => session()->getFlashdata('flash'),
            'deletedNotice'  => $deletedNotice,
            'requestedId'    => $requestedId ? (int) $requestedId : null,
        ]);
    }

    /** A datetime-local value ("2026-10-12T08:00") as "Y-m-d H:i:s", or null when blank/invalid. */
    private function toDateTime(?string $value): ?string
    {
        $value = trim((string) $value);
        $time  = $value !== '' ? strtotime($value) : false;

        return $time ? date('Y-m-d H:i:s', $time) : null;
    }
}

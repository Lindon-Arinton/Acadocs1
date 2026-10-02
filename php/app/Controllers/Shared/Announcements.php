<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Models\AnnouncementModel;
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
                        'created_by' => currentUser()['id'], // shown as "Posted by"
                    ]);

                    $poster     = currentUser();
                    $notifModel = new NotificationModel();
                    foreach ((new UserModel())->where('id !=', $poster['id'])->findAll() as $recipient) {
                        $notifModel->insert([
                            'user_id' => $recipient['id'],
                            'type'    => 'announcement',
                            'title'   => $title,
                            'sub'     => $type . ' · ' . date('M d', strtotime($date)),
                            'url'     => base_url('announcements') . '?id=' . $announcementId,
                            'ref_type' => 'announcement',
                            'ref_id'  => $announcementId,
                            'is_read' => 0,
                        ]);
                    }

                    $message = 'Announcement posted successfully.';
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

        return view('pages/shared/announcements', [
            'pageTitle'      => 'Announcements',
            'announcements'  => $builder->findAll(),
            'filter'         => $filter,
            'search'         => $search,
            'sort'           => $sort,
            'flash'          => session()->getFlashdata('flash'),
            'deletedNotice'  => $deletedNotice,
            'requestedId'    => $requestedId ? (int) $requestedId : null,
        ]);
    }
}

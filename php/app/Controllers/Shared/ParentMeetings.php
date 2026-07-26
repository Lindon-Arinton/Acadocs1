<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Libraries\ParentMeetingAttendanceImporter;
use App\Models\NotificationModel;
use App\Models\ParentMeetingModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class ParentMeetings extends BaseController
{
    public function index()
    {
        $model = new ParentMeetingModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX();

            if (! hasRole('admin', 'adas')) {
                return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to('/parent-meetings');
            }

            $action  = $this->request->getPost('action');
            $message = null;

            try {
                if ($action === 'add') {
                    $title = $this->request->getPost('title');
                    $date  = $this->request->getPost('date');

                    if ($date < date('Y-m-d')) {
                        $error = 'Meeting date cannot be in the past.';

                        return $isAjax ? $this->ajaxError($error) : redirect()->to('/parent-meetings')->with('flash', ['type' => 'danger', 'msg' => $error]);
                    }

                    $model->insert([
                        'title'            => $title,
                        'date'             => $date,
                        'expected_parents' => (int) $this->request->getPost('expected_parents'),
                    ]);

                    $this->notifyTeachers($title, $date);

                    $message = 'Meeting saved. Teachers have been notified.';
                } elseif ($action === 'upload_attendance') {
                    $meetingId = (int) $this->request->getPost('meeting_id');
                    $meeting   = $model->find($meetingId);

                    if (! $meeting) {
                        $error = 'Meeting not found.';

                        return $isAjax ? $this->ajaxError($error) : redirect()->to('/parent-meetings');
                    }

                    $file = $this->request->getFile('attendance_file');

                    if (! $file || ! $file->isValid() || $file->hasMoved()) {
                        $error = 'Please choose a valid Excel file to upload.';

                        return $isAjax ? $this->ajaxError($error) : redirect()->to('/parent-meetings')->with('flash', ['type' => 'danger', 'msg' => $error]);
                    }

                    if (! in_array(strtolower($file->getExtension() ?: ''), ['xlsx', 'xls'], true)) {
                        $error = 'Only .xlsx or .xls files are supported.';

                        return $isAjax ? $this->ajaxError($error) : redirect()->to('/parent-meetings')->with('flash', ['type' => 'danger', 'msg' => $error]);
                    }

                    $uploadPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'parent_meetings';
                    if (! is_dir($uploadPath)) {
                        mkdir($uploadPath, 0777, true);
                    }

                    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientName());
                    $safeName = $safeName ?: ('attendance_' . time() . '.' . $file->getExtension());
                    $fileName = date('Ymd_His') . '_' . uniqid() . '_' . $safeName;

                    if (! $file->move($uploadPath, $fileName)) {
                        $error = 'Could not save the uploaded file.';

                        return $isAjax ? $this->ajaxError($error) : redirect()->to('/parent-meetings');
                    }

                    $savedPath = $uploadPath . DIRECTORY_SEPARATOR . $fileName;

                    try {
                        $attended = (new ParentMeetingAttendanceImporter())->countAttendees($savedPath);
                    } catch (\Throwable $e) {
                        unlink($savedPath);
                        $error = 'Could not read that file: ' . $e->getMessage();

                        return $isAjax ? $this->ajaxError($error) : redirect()->to('/parent-meetings')->with('flash', ['type' => 'danger', 'msg' => $error]);
                    }

                    $expected = (int) $meeting['expected_parents'];
                    $rate     = $expected > 0 ? round($attended / $expected * 100, 2) : 0;

                    // Replacing a previously uploaded file for this meeting.
                    if (! empty($meeting['attendance_file_path']) && is_file($meeting['attendance_file_path'])) {
                        unlink($meeting['attendance_file_path']);
                    }

                    $model->update($meetingId, [
                        'actual_attendance'    => $attended,
                        'attendance_rate'      => $rate,
                        'attendance_file_path' => $savedPath,
                        'attendance_file_name' => $file->getClientName(),
                    ]);

                    $message = "Attendance file uploaded — {$attended} attendee(s) recorded.";
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/parent-meetings');
            }

            if ($isAjax) {
                return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
            }

            return redirect()->to('/parent-meetings?success=1');
        }

        $search = trim($this->request->getGet('q') ?? '');
        $sort   = $this->request->getGet('sort') ?? 'newest';

        $builder = $model;
        if ($search !== '') {
            $builder->like('title', $search);
        }

        match ($sort) {
            'oldest'        => $builder->orderBy('date', 'ASC'),
            'attendance_hi' => $builder->orderBy('attendance_rate', 'DESC'),
            'attendance_lo' => $builder->orderBy('attendance_rate', 'ASC'),
            default         => $builder->orderBy('date', 'DESC'),
        };

        $meetings = $builder->findAll();

        // Only meetings with an actually-recorded attendance file get plotted —
        // showing 0 for meetings still pending upload would be misleading.
        $chartMeetings = array_values(array_filter($meetings, static fn (array $m) => ! empty($m['attendance_file_path'])));
        usort($chartMeetings, static fn (array $a, array $b): int => strtotime($a['date']) <=> strtotime($b['date']));

        $todayMeetings = array_values(array_filter($meetings, static fn (array $m) => $m['date'] === date('Y-m-d')));

        return view('pages/shared/parent_meetings', [
            'pageTitle'     => 'Parent Meetings',
            'meetings'      => $meetings,
            'chartMeetings' => $chartMeetings,
            'todayMeetings' => $todayMeetings,
            'search'        => $search,
            'sort'          => $sort,
        ]);
    }

    public function downloadAttendance(int $id)
    {
        $meeting = (new ParentMeetingModel())->find($id);

        if (! $meeting || empty($meeting['attendance_file_path']) || ! is_file($meeting['attendance_file_path'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response->download($meeting['attendance_file_path'], null)->setFileName($meeting['attendance_file_name']);
    }

    /**
     * Teachers are the audience for parent meetings, so they're notified as
     * soon as one is posted.
     */
    private function notifyTeachers(string $title, string $date): void
    {
        $notifModel = new NotificationModel();

        foreach ((new UserModel())->where('role', 'teacher')->findAll() as $teacher) {
            $notifModel->insert([
                'user_id' => $teacher['id'],
                'type'    => 'parent_meeting',
                'title'   => 'Parent Meeting: ' . $title,
                'sub'     => date('M d, Y', strtotime($date)),
                'url'     => base_url('parent-meetings'),
                'is_read' => 0,
            ]);
        }
    }
}

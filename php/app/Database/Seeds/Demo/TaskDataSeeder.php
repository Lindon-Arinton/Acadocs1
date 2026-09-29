<?php

namespace App\Database\Seeds\Demo;

use App\Models\DocumentFolderModel;

/**
 * Tasks & Assignments, the teachers' To Do List and Manage Documents:
 * TASK_COUNT tasks posted by the principal over the last ~5 months and the
 * next month, submitted by the real teacher accounts with Templates files
 * attached, reviewed with feedback, plus the notifications each of those
 * steps sends in the app.
 */
class TaskDataSeeder extends DemoSeeder
{
    private const TASK_COUNT = 48;

    private const NOTES = [
        'Please see attached.',
        'Submitted for checking.',
        'Revised version attached.',
        'Kindly review. Thank you.',
        'Attached is my accomplished form.',
    ];

    private const FEEDBACK = [
        'Returned' => [
            'Please revise the highlighted sections and resubmit.',
            'Incomplete — some required fields are blank.',
            'Wrong template version used; please use the latest one from Templates.',
            'Signatures are missing. Kindly resubmit.',
            'Please attach the supporting documents.',
        ],
        'Reviewed' => [
            'Received and checked. Thank you!',
            'Complete and well-prepared. Good job.',
            'Noted. Filed for the division report.',
            'Checked — no issues found.',
        ],
    ];

    public function run()
    {
        $teachers  = $this->teachers();
        $templates = $this->templateFiles();
        $principal = $this->principal();
        $reviewers = $this->db->table('users')->select('id')->whereIn('role', ['admin', 'adas'])->get()->getResultArray();

        for ($i = 0; $i < self::TASK_COUNT; $i++) {
            $this->seedTask($i, $templates, $teachers, $principal, array_column($reviewers, 'id'));
        }

        $this->flushNotifications();
    }

    /**
     * One task plus its assignees, submissions, files, feedback, folder and
     * notifications. Tasks are laid out oldest -> newest by $i: deadlines run
     * from ~150 days ago to ~30 days ahead, so the set has closed, overdue,
     * due-soon and upcoming tasks.
     */
    private function seedTask(int $i, array $templates, array $teachers, array $principal, array $reviewerIds): void
    {
        $now      = time();
        $template = $templates[$i % count($templates)];
        $quarter  = min(4, intdiv($i * 4, self::TASK_COUNT) + 1);

        $dayOffset  = -150 + (int) round($i * 180 / (self::TASK_COUNT - 1)) + mt_rand(-2, 2);
        $deadlineTs = strtotime(date('Y-m-d', $this->day($dayOffset)) . ' ' . $this->pick(['12:00:00', '17:00:00', '23:59:00']));
        $createdTs  = min($deadlineTs - mt_rand(5, 14) * 86400, $now - mt_rand(1, 3) * 86400);
        $isPast     = $deadlineTs < $now;
        $isSpecific = $this->chance(20);

        $task = [
            'title'         => mb_substr('Q' . $quarter . ' ' . $template['title'], 0, 140),
            'description'   => 'Fill out the "' . $template['title'] . '" template (see the Templates page) and upload the accomplished file.',
            'assigned_role' => $isSpecific ? 'specific' : 'teacher',
            'deadline'      => $this->at($deadlineTs),
            'status'        => $isPast && $this->chance(65) ? 'Closed' : 'Open',
            'created_by'    => $principal['name'],
            'created_at'    => $this->at($createdTs),
        ];
        $this->db->table('tasks')->insert($task);
        $taskId = (int) $this->db->insertID();

        $assignees = $teachers;
        if ($isSpecific) {
            shuffle($assignees);
            $assignees = array_slice($assignees, 0, mt_rand(5, 12));
            $this->db->table('task_assignees')->insertBatch(array_map(
                static fn ($t) => ['task_id' => $taskId, 'user_id' => $t['id']],
                $assignees
            ));
        }

        foreach ($assignees as $teacher) {
            $this->notify((int) $teacher['id'], 'task_assigned', 'New Task: ' . $task['title'], 'Due ' . date('M d, Y h:i A', $deadlineTs),
                base_url('submit-documents'), 'task_assigned', $taskId, $createdTs);
        }

        $submitters = [];

        foreach ($assignees as $teacher) {
            if (! $this->chance($isPast ? 85 : 40)) {
                continue;
            }

            // Mostly on time; about 1 in 10 a few days late.
            $windowEnd   = min($this->chance(10) ? $deadlineTs + 3 * 86400 : $deadlineTs, $now);
            $submittedTs = mt_rand($createdTs + 3600, max($createdTs + 3600, $windowEnd));
            $submittedAt = $this->at($submittedTs);
            $submitters[$submittedTs . '-' . $teacher['id']] = $teacher['name'];

            // Older submissions have mostly been checked; recent ones mostly haven't.
            $roll   = mt_rand(1, 100);
            $status = $now - $submittedTs > 14 * 86400
                ? ($roll <= 70 ? 'Reviewed' : ($roll <= 82 ? 'Returned' : 'Pending'))
                : ($roll <= 25 ? 'Reviewed' : ($roll <= 35 ? 'Returned' : 'Pending'));

            $this->db->table('task_submissions')->insert([
                'task_id'      => $taskId,
                'user_id'      => $teacher['id'],
                'notes'        => $this->chance(30) ? $this->pick(self::NOTES) : '',
                'status'       => $status,
                'submitted_at' => $submittedAt,
                'created_at'   => $submittedAt,
            ]);
            $submissionId = (int) $this->db->insertID();

            $files = [$template];
            if ($this->chance(20) && count($templates) > 1) {
                $files[] = $this->pick($templates);
            }
            foreach ($files as $file) {
                $this->db->table('task_submission_files')->insert([
                    'task_submission_id' => $submissionId,
                    'file_path'          => $this->placeFile($file, WRITEPATH . 'uploads/tasks/' . $taskId, $submittedTs),
                    'file_name'          => $file['file_name'],
                    'created_at'         => $submittedAt,
                ]);
            }

            if ($status === 'Returned' || ($status === 'Reviewed' && $this->chance(35))) {
                $comment    = $this->pick(self::FEEDBACK[$status]);
                $feedbackTs = min($submittedTs + mt_rand(1, 4) * 86400, $now);
                $this->db->table('task_feedback')->insert([
                    'task_submission_id' => $submissionId,
                    'comment'            => $comment,
                    'date'               => date('Y-m-d', $feedbackTs),
                ]);
                $this->notify((int) $teacher['id'], 'task_feedback', 'New feedback on: ' . $task['title'], mb_strimwidth($comment, 0, 80, '…'),
                    base_url('submit-documents'), 'task_feedback', $submissionId, $feedbackTs);
            }
        }

        if ($submitters === []) {
            return;
        }

        // The app keeps one grouped "X and N others submitted" notification
        // per reviewer per task (NotificationModel::upsertGrouped()).
        ksort($submitters);
        $lastTs  = (int) array_key_last($submitters);
        $others  = count($submitters) - 1;
        $title   = end($submitters) . ($others > 0 ? ' and ' . $others . ' other' . ($others > 1 ? 's' : '') : '') . ' submitted';
        foreach ($reviewerIds as $reviewerId) {
            $this->notify((int) $reviewerId, 'task_submission', $title, 'Task: ' . $task['title'], base_url('tasks/' . $taskId), 'task_submission', $taskId, $lastTs);
        }

        // Same auto-filed folder a real first upload creates (see DocumentFolderModel::ensureForTask()).
        $this->db->table('document_folders')->insert([
            'task_id'    => $taskId,
            'name'       => DocumentFolderModel::nameForTask($task),
            'created_at' => $this->at((int) array_key_first($submitters)),
        ]);
    }
}

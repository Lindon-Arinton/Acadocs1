<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Models\AnnouncementModel;
use App\Models\DocumentLinkModel;
use App\Models\DocumentModel;
use App\Models\TaskAssigneeModel;
use App\Models\TaskFeedbackModel;
use App\Models\TaskModel;
use App\Models\TaskSubmissionModel;
use App\Models\TeacherModel;
use App\Models\TimeRecordModel;

class TeacherDashboard extends BaseController
{
    public function index()
    {
        $user         = currentUser();
        $teacherModel = new TeacherModel();
        $teacher      = $teacherModel->resolveForUser($user);

        $myAttendance       = ['Present' => 0, 'Absent' => 0];
        $myAttendanceMonths = [];
        $attMonth           = $this->request->getGet('att_month') ?: 'all';
        $attendanceThisMonth = ['Present' => 0, 'Absent' => 0];

        if (! empty($user['ac_no'])) {
            $trModel = new TimeRecordModel();

            $myAttendanceMonths = array_column(
                $trModel->select("DATE_FORMAT(date, '%Y-%m') as ym", false)
                    ->where('employee_id', 'AC-' . $user['ac_no'])
                    ->distinct()
                    ->orderBy('ym', 'DESC')
                    ->findAll(),
                'ym'
            );

            if ($attMonth !== 'all' && ! in_array($attMonth, $myAttendanceMonths, true)) {
                $attMonth = 'all';
            }

            $query = $trModel->where('employee_id', 'AC-' . $user['ac_no']);
            if ($attMonth !== 'all') {
                $query->where('date >=', $attMonth . '-01')
                    ->where('date <=', date('Y-m-t', strtotime($attMonth . '-01')));
            }

            foreach ($query->findAll() as $r) {
                if (isset($myAttendance[$r['status']])) {
                    $myAttendance[$r['status']]++;
                }
            }

            // Separate from the filterable widget above — the Insights panel
            // always reads the current calendar month, regardless of what
            // month the teacher has picked in the dropdown.
            foreach ((new TimeRecordModel())
                ->where('employee_id', 'AC-' . $user['ac_no'])
                ->where('date >=', date('Y-m-01'))
                ->where('date <=', date('Y-m-d'))
                ->findAll() as $r) {
                if (isset($attendanceThisMonth[$r['status']])) {
                    $attendanceThisMonth[$r['status']]++;
                }
            }
        }

        [$taskStats, $recentActivity, $overdueTasks, $upcomingTask] = $this->buildTaskData($user);

        $recentFeedback = $this->recentFeedback($user);

        $announcements = (new AnnouncementModel())->where('status', 'active')->orderBy('date', 'DESC')->findAll(5);
        $links         = (new DocumentLinkModel())->whereIn('access_level', ['All Users', 'Teachers'])
            ->orderBy('date_added', 'DESC')->findAll(6);

        $insights = $this->buildInsights($overdueTasks, $upcomingTask, $taskStats, $recentFeedback, $attendanceThisMonth);

        // Historical documents (from the retired Submit Documents workflow)
        // that carry private principal feedback — the submission form is
        // gone, but past feedback is still real data and still needs a home,
        // since document_feedback notifications link here.
        $docFeedback = [];
        if ($teacher) {
            foreach ((new DocumentModel())->myDocsWithFeedback($teacher['id']) as $doc) {
                if (! empty($doc['feedback_comments'])) {
                    $docFeedback[] = $doc;
                }
            }
            $docFeedback = array_slice($docFeedback, 0, 5);
        }

        return view('pages/teacher/teacher_dashboard', [
            'pageTitle'           => 'Teacher Dashboard',
            'user'                => $user,
            'teacher'             => $teacher,
            'taskStats'           => $taskStats,
            'recentActivity'      => $recentActivity,
            'myAttendance'        => $myAttendance,
            'myAttendanceMonths'  => $myAttendanceMonths,
            'attMonth'            => $attMonth,
            'announcements'       => $announcements,
            'links'               => $links,
            'insights'            => $insights,
            'docFeedback'         => $docFeedback,
        ]);
    }

    /**
     * Every task assigned to this teacher (role='teacher' or 'specific' to
     * them), bucketed against their own task_submissions row for each — the
     * same eligibility logic the To Do List page already uses.
     *
     * @return array{0:array<string,int>,1:array<int,array<string,mixed>>,2:array<int,array<string,mixed>>,3:?array<string,mixed>}
     */
    private function buildTaskData(array $user): array
    {
        $taskModel       = new TaskModel();
        $submissionModel = new TaskSubmissionModel();
        $specificTaskIds = (new TaskAssigneeModel())->taskIdsForUser((int) $user['id']);

        $taskQuery = $taskModel->groupStart()->where('assigned_role', 'teacher');
        if ($specificTaskIds) {
            $taskQuery->orGroupStart()->where('assigned_role', 'specific')->whereIn('id', $specificTaskIds)->groupEnd();
        }
        $tasks = $taskQuery->groupEnd()->orderBy('deadline', 'ASC')->findAll();

        $stats = ['total' => count($tasks), 'completed' => 0, 'pending' => 0, 'overdue' => 0];
        $recentActivity = [];
        $overdueTasks   = [];
        $upcomingTask   = null;

        foreach ($tasks as $t) {
            $submission = $submissionModel->findForTaskAndUser((int) $t['id'], (int) $user['id']);
            $isOverdue  = $t['status'] === 'Open' && ! $submission && strtotime($t['deadline']) < time();

            if ($submission) {
                $stats['completed']++;
                $recentActivity[] = [
                    'title'        => $t['title'],
                    'status'       => $submission['status'],
                    'submitted_at' => $submission['submitted_at'],
                ];
            } elseif ($isOverdue) {
                $stats['overdue']++;
                $overdueTasks[] = $t;
            } elseif ($t['status'] === 'Open') {
                $stats['pending']++;
                if ($upcomingTask === null || strtotime($t['deadline']) < strtotime($upcomingTask['deadline'])) {
                    $upcomingTask = $t;
                }
            }
        }

        usort($recentActivity, static fn ($a, $b) => strtotime($b['submitted_at']) <=> strtotime($a['submitted_at']));
        $recentActivity = array_slice($recentActivity, 0, 5);

        return [$stats, $recentActivity, $overdueTasks, $upcomingTask];
    }

    /**
     * Principal feedback left on this teacher's submissions in the last 7
     * days — surfaced as an insight so a new comment doesn't go unnoticed.
     *
     * @return array<int,array<string,mixed>>
     */
    private function recentFeedback(array $user): array
    {
        return (new TaskFeedbackModel())
            ->select('task_feedback.*, tasks.title AS task_title')
            ->join('task_submissions', 'task_submissions.id = task_feedback.task_submission_id')
            ->join('tasks', 'tasks.id = task_submissions.task_id')
            ->where('task_submissions.user_id', (int) $user['id'])
            ->where('task_feedback.date >=', date('Y-m-d', strtotime('-7 days')))
            ->orderBy('task_feedback.date', 'DESC')
            ->findAll();
    }

    /**
     * Rule-based, plain-English observations — same pattern as the Admin
     * Dashboard's Insights panel, scoped to what matters for a teacher.
     * Ordered most-actionable first and capped so the panel stays scannable.
     *
     * @param array<int,array<string,mixed>> $overdueTasks
     * @param array<string,mixed>|null       $upcomingTask
     * @param array<string,int>              $taskStats
     * @param array<int,array<string,mixed>> $recentFeedback
     * @param array<string,int>              $attendanceThisMonth
     * @return array<int,array{tone:string,icon:string,text:string}>
     */
    private function buildInsights(array $overdueTasks, ?array $upcomingTask, array $taskStats, array $recentFeedback, array $attendanceThisMonth): array
    {
        $insights = [];

        if ($overdueTasks !== []) {
            $n          = count($overdueTasks);
            $insights[] = [
                'tone' => 'danger',
                'icon' => 'bi-exclamation-triangle-fill',
                'text' => 'You have <strong>' . $n . ' overdue task' . ($n === 1 ? '' : 's') . '</strong>' . ($n === 1 ? ': <strong>' . e($overdueTasks[0]['title']) . '</strong>' : '') . '. Submit as soon as possible.',
            ];
        }

        if ($upcomingTask !== null) {
            $daysLeft = (int) ceil((strtotime($upcomingTask['deadline']) - strtotime(date('Y-m-d'))) / 86400);
            if ($daysLeft <= 2) {
                $due        = $daysLeft <= 0 ? 'today' : ($daysLeft === 1 ? 'tomorrow' : 'in ' . $daysLeft . ' days');
                $insights[] = [
                    'tone' => 'warning',
                    'icon' => 'bi-calendar-event-fill',
                    'text' => '<strong>' . e($upcomingTask['title']) . '</strong> is due <strong>' . $due . '</strong>.',
                ];
            }
        }

        if ($recentFeedback !== []) {
            $fb         = $recentFeedback[0];
            $insights[] = [
                'tone' => 'info',
                'icon' => 'bi-chat-dots-fill',
                'text' => 'New feedback from the principal on <strong>' . e($fb['task_title']) . '</strong>.',
            ];
        }

        if ($taskStats['total'] > 0) {
            $rate = round($taskStats['completed'] / $taskStats['total'] * 100, 1);
            if ($rate >= 80) {
                $insights[] = ['tone' => 'success', 'icon' => 'bi-check-circle-fill', 'text' => 'Task completion rate is <strong>' . $rate . '%</strong> — nice work staying on top of things.'];
            } elseif ($rate >= 50) {
                $insights[] = ['tone' => 'warning', 'icon' => 'bi-hourglass-split', 'text' => 'Task completion rate is <strong>' . $rate . '%</strong> — ' . $taskStats['pending'] . ' task' . ($taskStats['pending'] === 1 ? '' : 's') . ' still open.'];
            } else {
                $insights[] = ['tone' => 'danger', 'icon' => 'bi-hourglass-bottom', 'text' => 'Task completion rate is only <strong>' . $rate . '%</strong> — ' . $taskStats['pending'] . ' task' . ($taskStats['pending'] === 1 ? '' : 's') . ' still ' . ($taskStats['pending'] === 1 ? 'needs' : 'need') . ' submission.'];
            }
        }

        $absences = $attendanceThisMonth['Absent'] ?? 0;
        if ($absences > 0) {
            $insights[] = ['tone' => 'warning', 'icon' => 'bi-calendar-x-fill', 'text' => '<strong>' . $absences . '</strong> absence' . ($absences === 1 ? '' : 's') . ' recorded this month.'];
        } elseif (($attendanceThisMonth['Present'] ?? 0) > 0) {
            $insights[] = ['tone' => 'success', 'icon' => 'bi-calendar-check-fill', 'text' => 'Perfect attendance this month — <strong>' . $attendanceThisMonth['Present'] . '</strong> day' . ($attendanceThisMonth['Present'] === 1 ? '' : 's') . ' present.'];
        }

        $order = ['danger' => 0, 'warning' => 1, 'info' => 2, 'success' => 3];
        usort($insights, static fn ($a, $b) => $order[$a['tone']] <=> $order[$b['tone']]);

        return array_slice($insights, 0, 5);
    }
}

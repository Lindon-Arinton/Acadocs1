<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Models\DocumentFileModel;
use App\Models\DocumentModel;
use App\Models\TaskAssigneeModel;
use App\Models\TaskModel;
use App\Models\TeacherModel;

class SubmitDocuments extends BaseController
{
    public function index()
    {
        if (! hasRole('teacher')) {
            return redirect()->to('/dashboard');
        }

        $user = currentUser();
        $teacherModel = new TeacherModel();
        $teacher = $teacherModel->resolveForUser($user);

        // A submission must fulfill a task the principal actually assigned —
        // teachers no longer pick an arbitrary document type on their own.
        $assigneeModel   = new TaskAssigneeModel();
        $specificTaskIds = $assigneeModel->taskIdsForUser((int) $user['id']);

        $taskQuery = (new TaskModel())->groupStart()->where('assigned_role', 'teacher')->where('status', 'Open');
        if ($specificTaskIds) {
            $taskQuery->orGroupStart()->where('assigned_role', 'specific')->where('status', 'Open')->whereIn('id', $specificTaskIds)->groupEnd();
        }
        $openTasks = $taskQuery->groupEnd()->orderBy('deadline', 'ASC')->findAll();

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX();

            if (! $teacher) {
                return $isAjax ? $this->ajaxError('Your account is not linked to a teacher profile.') : redirect()->to('/submit-documents');
            }

            $taskId = (int) $this->request->getPost('task_id');
            $task   = null;
            foreach ($openTasks as $t) {
                if ((int) $t['id'] === $taskId) {
                    $task = $t;
                    break;
                }
            }

            if (! $task) {
                $error = 'Please select a valid task assigned to you.';

                return $isAjax ? $this->ajaxError($error) : redirect()->to('/submit-documents')->with('flash', ['type' => 'danger', 'msg' => $error]);
            }

            $files = array_filter(
                $this->request->getFileMultiple('document_file') ?? [],
                static fn ($f) => $f && $f->isValid() && ! $f->hasMoved()
            );

            if (empty($files)) {
                $error = 'Please choose at least one file to upload.';

                return $isAjax ? $this->ajaxError($error) : redirect()->to('/submit-documents')->with('flash', ['type' => 'danger', 'msg' => $error]);
            }

            try {
                $documentId = (new DocumentModel())->insert([
                    'teacher_id'     => $teacher['id'],
                    'type'           => $task['title'],
                    'subject'        => $this->request->getPost('subject'),
                    'grade_level'    => $this->request->getPost('grade_level'),
                    'date_submitted' => date('Y-m-d H:i:s'),
                    'status'         => 'Submitted',
                ]);

                $uploadPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'documents';
                if (! is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                $fileModel = new DocumentFileModel();

                foreach ($files as $file) {
                    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientName());
                    $safeName = $safeName ?: 'document_' . time() . '.' . $file->getExtension();
                    $fileName = date('Ymd_His') . '_' . uniqid() . '_' . $safeName;

                    if ($file->move($uploadPath, $fileName)) {
                        $fileModel->insert([
                            'document_id' => $documentId,
                            'file_path'   => 'writable/uploads/documents/' . $fileName,
                            'file_name'   => $file->getClientName(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/submit-documents');
            }

            if ($isAjax) {
                return $this->ajaxSuccess('Document submitted successfully.');
            }

            return redirect()->to('/submit-documents?success=1');
        }

        $myDocs = $teacher ? (new DocumentModel())->myDocsWithFeedback($teacher['id']) : [];

        $fileModel     = new DocumentFileModel();
        $filesGrouped  = $fileModel->forDocuments(array_column($myDocs, 'id'));
        foreach ($myDocs as &$doc) {
            $doc['files'] = $filesGrouped[$doc['id']] ?? [];
        }
        unset($doc);

        return view('pages/teacher/submit_documents', [
            'pageTitle' => 'Submit Documents',
            'teacher'   => $teacher,
            'myDocs'    => $myDocs,
            'openTasks' => $openTasks,
        ]);
    }
}
<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Models\DocumentModel;
use App\Models\TeacherModel;

class SubmitDocuments extends BaseController
{
    public function index()
    {
        if (! hasRole('teacher')) {
            return redirect()->to('/dashboard');
        }

        $user    = currentUser();
        $teacher = (new TeacherModel())->findByEmail($user['email']);

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX();

            if (! $teacher) {
                return $isAjax ? $this->ajaxError('Your account is not linked to a teacher profile.') : redirect()->to('/submit-documents');
            }

            try {
                (new DocumentModel())->insert([
                    'teacher_id'     => $teacher['id'],
                    'type'           => $this->request->getPost('type'),
                    'subject'        => $this->request->getPost('subject'),
                    'grade_level'    => $this->request->getPost('grade_level'),
                    'date_submitted' => date('Y-m-d H:i:s'),
                    'status'         => 'Submitted',
                ]);
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/submit-documents');
            }

            if ($isAjax) {
                return $this->ajaxSuccess('Document submitted successfully.');
            }

            return redirect()->to('/submit-documents?success=1');
        }

        $myDocs = $teacher ? (new DocumentModel())->myDocsWithFeedback($teacher['id']) : [];

        return view('pages/teacher/submit_documents', [
            'pageTitle' => 'Submit Documents',
            'teacher'   => $teacher,
            'myDocs'    => $myDocs,
        ]);
    }
}

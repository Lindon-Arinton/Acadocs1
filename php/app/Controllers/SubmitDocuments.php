<?php

namespace App\Controllers;

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

        if ($this->request->getMethod() === 'POST' && $teacher) {
            (new DocumentModel())->insert([
                'teacher_id'     => $teacher['id'],
                'type'           => $this->request->getPost('type'),
                'subject'        => $this->request->getPost('subject'),
                'grade_level'    => $this->request->getPost('grade_level'),
                'date_submitted' => date('Y-m-d H:i:s'),
                'status'         => 'Submitted',
            ]);

            return redirect()->to('/submit-documents?success=1');
        }

        $myDocs = $teacher ? (new DocumentModel())->myDocsWithFeedback($teacher['id']) : [];

        return view('pages/submit_documents', [
            'pageTitle' => 'Submit Documents',
            'teacher'   => $teacher,
            'myDocs'    => $myDocs,
        ]);
    }
}

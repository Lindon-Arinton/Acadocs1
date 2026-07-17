<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('documents')->insertBatch([
            ['teacher_id' => 1, 'type' => 'DLL',         'subject' => 'Mathematics', 'grade_level' => 'Grade 7', 'date_submitted' => '2026-03-28 09:30:00', 'status' => 'Submitted'],
            ['teacher_id' => 2, 'type' => 'Lesson Plan', 'subject' => 'Science',     'grade_level' => 'Grade 7', 'date_submitted' => '2026-03-27 14:15:00', 'status' => 'Reviewed'],
            ['teacher_id' => 3, 'type' => 'DLL',         'subject' => 'English',     'grade_level' => 'Grade 8', 'date_submitted' => '2026-03-29 11:20:00', 'status' => 'Submitted'],
            ['teacher_id' => 4, 'type' => 'Lesson Plan', 'subject' => 'Filipino',    'grade_level' => 'Grade 8', 'date_submitted' => '2026-03-26 16:45:00', 'status' => 'Reviewed'],
        ]);

        $this->db->table('document_feedback')->insertBatch([
            ['document_id' => 2, 'author' => 'Principal', 'comment' => 'Well structured. Please add more assessment criteria.', 'date' => '2026-03-28'],
            ['document_id' => 4, 'author' => 'Principal', 'comment' => 'Excellent integration of cultural elements.',            'date' => '2026-03-27'],
        ]);
    }
}

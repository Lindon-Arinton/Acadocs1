<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('announcements')->insertBatch([
            ['type' => 'Announcement',   'title' => 'Faculty Meeting - March 31, 2026',   'content' => 'All teachers are required to attend the faculty meeting in the conference room at 3:00 PM.', 'date' => '2026-03-28', 'status' => 'active'],
            ['type' => 'Questionnaires', 'title' => 'Teacher Performance Survey',         'content' => 'Please complete the term performance survey by April 5, 2026.', 'date' => '2026-03-25', 'status' => 'active'],
            ['type' => 'Forms',          'title' => 'Document Submission Reminder',       'content' => 'Reminder: All DLLs and Lesson Plans for March must be submitted by end of day.', 'date' => '2026-03-29', 'status' => 'active'],
        ]);
    }
}

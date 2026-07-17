<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ParentMeetingSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('parent_meetings')->insertBatch([
            ['title' => 'First Term Parent-Teacher Conference',  'date' => '2026-02-15', 'expected_parents' => 450, 'actual_attendance' => 342, 'attendance_rate' => 76.00],
            ['title' => 'Second Term Parent-Teacher Conference', 'date' => '2026-03-15', 'expected_parents' => 450, 'actual_attendance' => 389, 'attendance_rate' => 86.40],
        ]);
    }
}

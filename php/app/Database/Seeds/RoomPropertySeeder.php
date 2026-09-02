<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoomPropertySeeder extends Seeder
{
    public function run()
    {
        $this->db->table('room_properties')->insertBatch([
            ['section' => 'Rizal',     'grade' => 'Grade 7',  'item_name' => 'Student Chairs',    'condition_status' => 'Good'],
            ['section' => 'Rizal',     'grade' => 'Grade 7',  'item_name' => 'Teacher Desk',      'condition_status' => 'Fair'],
            ['section' => 'Bonifacio', 'grade' => 'Grade 7',  'item_name' => 'Whiteboard',        'condition_status' => 'Excellent'],
            ['section' => 'Mabini',    'grade' => 'Grade 8',  'item_name' => 'Microscopes',       'condition_status' => 'Excellent'],
            ['section' => 'Mabini',    'grade' => 'Grade 8',  'item_name' => 'Lab Tables',        'condition_status' => 'Good'],
            ['section' => 'Aguinaldo', 'grade' => 'Grade 9',  'item_name' => 'Desktop Computers', 'condition_status' => 'Fair'],
            ['section' => 'Luna',      'grade' => 'Grade 10', 'item_name' => 'Bookshelves',       'condition_status' => 'Good'],
            ['section' => 'Luna',      'grade' => 'Grade 10', 'item_name' => 'Reading Tables',    'condition_status' => 'Good'],
        ]);
    }
}

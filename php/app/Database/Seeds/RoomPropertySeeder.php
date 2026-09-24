<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoomPropertySeeder extends Seeder
{
    public function run()
    {
        $this->db->table('room_properties')->insertBatch([
            ['section' => 'Rizal',     'grade' => 'Grade 7',  'item_name' => 'Student Chairs',    'quantity' => 40, 'condition_status' => 'Good',      'uploaded_by' => 'Admin'],
            ['section' => 'Rizal',     'grade' => 'Grade 7',  'item_name' => 'Teacher Desk',      'quantity' => 1,  'condition_status' => 'Fair',      'uploaded_by' => 'Admin'],
            ['section' => 'Bonifacio', 'grade' => 'Grade 7',  'item_name' => 'Whiteboard',        'quantity' => 2,  'condition_status' => 'Excellent', 'uploaded_by' => 'Admin'],
            ['section' => 'Mabini',    'grade' => 'Grade 8',  'item_name' => 'Microscopes',       'quantity' => 10, 'condition_status' => 'Excellent', 'uploaded_by' => 'Admin'],
            ['section' => 'Mabini',    'grade' => 'Grade 8',  'item_name' => 'Lab Tables',        'quantity' => 8,  'condition_status' => 'Good',      'uploaded_by' => 'Admin'],
            ['section' => 'Aguinaldo', 'grade' => 'Grade 9',  'item_name' => 'Desktop Computers', 'quantity' => 20, 'condition_status' => 'Fair',      'uploaded_by' => 'Admin'],
            ['section' => 'Luna',      'grade' => 'Grade 10', 'item_name' => 'Bookshelves',       'quantity' => 5,  'condition_status' => 'Good',      'uploaded_by' => 'Admin'],
            ['section' => 'Luna',      'grade' => 'Grade 10', 'item_name' => 'Reading Tables',    'quantity' => 6,  'condition_status' => 'Good',      'uploaded_by' => 'Admin'],
        ]);
    }
}

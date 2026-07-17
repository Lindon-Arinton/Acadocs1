<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoomPropertySeeder extends Seeder
{
    public function run()
    {
        $this->db->table('room_properties')->insertBatch([
            ['room_number' => 'Room 101',    'building_name' => 'Building A',   'item_name' => 'Student Chairs',    'quantity' => 45, 'condition_status' => 'Good',      'last_inspection' => '2026-03-15', 'remarks' => 'Recently painted'],
            ['room_number' => 'Room 101',    'building_name' => 'Building A',   'item_name' => 'Teacher Desk',      'quantity' => 1,  'condition_status' => 'Fair',      'last_inspection' => '2026-03-15', 'remarks' => 'Minor scratches'],
            ['room_number' => 'Room 102',    'building_name' => 'Building A',   'item_name' => 'Whiteboard',        'quantity' => 1,  'condition_status' => 'Excellent', 'last_inspection' => '2026-03-15', 'remarks' => 'Newly installed'],
            ['room_number' => 'Science Lab', 'building_name' => 'Building B',   'item_name' => 'Microscopes',       'quantity' => 15, 'condition_status' => 'Excellent', 'last_inspection' => '2026-03-20', 'remarks' => 'Newly purchased'],
            ['room_number' => 'Science Lab', 'building_name' => 'Building B',   'item_name' => 'Lab Tables',        'quantity' => 8,  'condition_status' => 'Good',      'last_inspection' => '2026-03-20', 'remarks' => 'Heat-resistant surface'],
            ['room_number' => 'Computer Lab','building_name' => 'Building C',   'item_name' => 'Desktop Computers', 'quantity' => 30, 'condition_status' => 'Fair',      'last_inspection' => '2026-03-10', 'remarks' => '3 units need repair'],
            ['room_number' => 'Library',     'building_name' => 'Main Building','item_name' => 'Bookshelves',       'quantity' => 12, 'condition_status' => 'Good',      'last_inspection' => '2026-03-05', 'remarks' => 'Sturdy metal frames'],
            ['room_number' => 'Library',     'building_name' => 'Main Building','item_name' => 'Reading Tables',    'quantity' => 10, 'condition_status' => 'Good',      'last_inspection' => '2026-03-05', 'remarks' => 'Can seat 6 students each'],
        ]);
    }
}

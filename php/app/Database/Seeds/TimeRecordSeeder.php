<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TimeRecordSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('time_records')->insertBatch([
            ['date' => '2026-03-31', 'employee_name' => 'Maria Santos',    'employee_id' => 'T-001', 'time_in' => '07:15:00', 'time_out' => '16:30:00', 'status' => 'Present', 'remarks' => ''],
            ['date' => '2026-03-31', 'employee_name' => 'Juan dela Cruz',  'employee_id' => 'T-002', 'time_in' => '07:20:00', 'time_out' => '16:35:00', 'status' => 'Present', 'remarks' => ''],
            ['date' => '2026-03-31', 'employee_name' => 'Ana Reyes',       'employee_id' => 'T-003', 'time_in' => '07:10:00', 'time_out' => '16:25:00', 'status' => 'Present', 'remarks' => ''],
            ['date' => '2026-03-31', 'employee_name' => 'Pedro Garcia',    'employee_id' => 'T-004', 'time_in' => '07:25:00', 'time_out' => '16:40:00', 'status' => 'Present', 'remarks' => ''],
            ['date' => '2026-03-31', 'employee_name' => 'Rosa Mendoza',    'employee_id' => 'T-005', 'time_in' => '08:45:00', 'time_out' => '16:30:00', 'status' => 'Late',    'remarks' => 'Traffic due to road construction'],
            ['date' => '2026-03-31', 'employee_name' => 'Carlos Bautista', 'employee_id' => 'T-006', 'time_in' => null,       'time_out' => null,       'status' => 'Absent',  'remarks' => 'Sick Leave - Medical Certificate Submitted'],
        ]);
    }
}

<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CanteenRecordSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('canteen_records')->insertBatch([
            ['date' => '2026-03-01', 'description' => 'Daily Sales - Breakfast & Snacks', 'revenue' => 8450.00, 'expenses' => 3200.00, 'transaction_count' => 145],
            ['date' => '2026-03-08', 'description' => 'Daily Sales - Breakfast & Snacks', 'revenue' => 9100.00, 'expenses' => 3500.00, 'transaction_count' => 162],
            ['date' => '2026-03-15', 'description' => 'Daily Sales - Breakfast & Snacks', 'revenue' => 8750.00, 'expenses' => 3400.00, 'transaction_count' => 158],
            ['date' => '2026-03-22', 'description' => 'Daily Sales - Breakfast & Snacks', 'revenue' => 9300.00, 'expenses' => 3600.00, 'transaction_count' => 171],
            ['date' => '2026-03-29', 'description' => 'Daily Sales - Breakfast & Snacks', 'revenue' => 8950.00, 'expenses' => 3450.00, 'transaction_count' => 165],
        ]);
    }
}

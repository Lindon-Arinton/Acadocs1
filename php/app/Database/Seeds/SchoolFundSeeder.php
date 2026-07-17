<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SchoolFundSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('school_funds')->insertBatch([
            ['date' => '2026-03-05', 'category' => 'MOOE',           'description' => 'Office Supplies Purchase', 'particulars' => 'Bond paper, ink, pens, folders',   'amount' => -15420.00, 'balance' => 284580.00, 'prepared_by' => 'Linda Aquino'],
            ['date' => '2026-03-12', 'category' => 'Capital Outlay', 'description' => 'Laboratory Equipment',     'particulars' => 'Science lab microscopes (5 units)', 'amount' => -85000.00, 'balance' => 199580.00, 'prepared_by' => 'Linda Aquino'],
            ['date' => '2026-03-15', 'category' => 'MOOE',           'description' => 'Utilities Payment',        'particulars' => 'Electricity - March 2026',          'amount' => -42300.00, 'balance' => 157280.00, 'prepared_by' => 'Linda Aquino'],
            ['date' => '2026-03-20', 'category' => 'Maintenance',    'description' => 'Building Repairs',         'particulars' => 'Roof repair - Building A',          'amount' => -28500.00, 'balance' => 128780.00, 'prepared_by' => 'Linda Aquino'],
            ['date' => '2026-03-25', 'category' => 'MOOE',           'description' => 'Cleaning Supplies',        'particulars' => 'Janitorial and sanitation supplies', 'amount' => -8750.00,  'balance' => 120030.00, 'prepared_by' => 'Linda Aquino'],
        ]);
    }
}

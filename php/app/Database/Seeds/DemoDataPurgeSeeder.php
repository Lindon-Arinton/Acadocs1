<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * Removes everything DemoDataSeeder created (rows and files):
 *
 *   php spark db:seed DemoDataPurgeSeeder
 */
class DemoDataPurgeSeeder extends Seeder
{
    public function run()
    {
        $removed = DemoDataSeeder::purge($this->db);

        CLI::write("Removed {$removed} demo rows (plus their cascaded child rows) and the demo files.");
    }
}

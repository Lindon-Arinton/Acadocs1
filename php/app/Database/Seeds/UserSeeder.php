<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('users')->insertBatch([
            ['name' => 'Principal',      'email' => 'principal@school.edu',     'password' => '$2y$10$mZiXiBtaycSs2DmiVudkq.NT6PlyGsTBSLisKR0G6tH6HQzsVL2Au', 'role' => 'admin'],
            ['name' => 'Maria Santos',   'email' => 'maria.santos@school.edu',  'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher'],
            ['name' => 'Juan dela Cruz', 'email' => 'juan.delacruz@school.edu', 'password' => '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'role' => 'teacher'],
            ['name' => 'Carmen Lopez',   'email' => 'secretary@school.edu',     'password' => '$2y$10$iVemOAuYsrWtDhAe3BmrJe2qYKXSUFiTNFDH851mKd31CMTTxmDYi', 'role' => 'secretary'],
            ['name' => 'Roberto Santos', 'email' => 'canteen@school.edu',       'password' => '$2y$10$.PHXEGPC7nwrTiWmOaIRHetxLP7qLKBJ0Hhd.1CrwXf8T1SCgKyXy', 'role' => 'canteen'],
            ['name' => 'Linda Aquino',   'email' => 'disbursing@school.edu',    'password' => '$2y$10$hziMkTHuEGpUYmsCvwjb8emAgc4IPNXX5AV3V5QR5zGFOQXFD.M9e', 'role' => 'disbursing'],
            ['name' => 'Jose Ramirez',   'email' => 'adas@school.edu',          'password' => '$2y$10$EoWuuEchqWy.jPRi50J/8e1ucyvKvJFL6xcq3YJAshBThctyEzOz.', 'role' => 'adas'],
        ]);

        // Plain-text passwords for reference (never store these):
        // principal@school.edu  -> admin123
        // *@school.edu teachers -> teacher123
        // secretary@school.edu  -> sec123
        // canteen@school.edu    -> canteen123
        // disbursing@school.edu -> disb123
        // adas@school.edu       -> adas123
    }
}

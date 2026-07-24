<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(UserSeeder::class);
        $this->call(TeacherSeeder::class);
        $this->call(AnnouncementSeeder::class);
        $this->call(DocumentSeeder::class);
        $this->call(KpiSeeder::class);
        $this->call(PerformanceSeeder::class);
        $this->call(ParentMeetingSeeder::class);
        $this->call(DocumentLinkSeeder::class);
        $this->call(TimeRecordSeeder::class);
        $this->call(RoomPropertySeeder::class);
    }
}

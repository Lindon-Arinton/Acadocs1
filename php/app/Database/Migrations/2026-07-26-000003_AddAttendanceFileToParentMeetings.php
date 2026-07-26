<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAttendanceFileToParentMeetings extends Migration
{
    public function up()
    {
        // actual_attendance / attendance_rate are no longer entered manually — they're
        // derived from the uploaded attendance excel file, so a meeting starts without them.
        $this->forge->modifyColumn('parent_meetings', [
            'actual_attendance' => ['name' => 'actual_attendance', 'type' => 'INT', 'unsigned' => true, 'null' => true],
            'attendance_rate'   => ['name' => 'attendance_rate', 'type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
        ]);

        $this->forge->addColumn('parent_meetings', [
            'attendance_file_path' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'attendance_rate'],
            'attendance_file_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'attendance_file_path'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('parent_meetings', ['attendance_file_path', 'attendance_file_name']);

        $this->forge->modifyColumn('parent_meetings', [
            'actual_attendance' => ['name' => 'actual_attendance', 'type' => 'INT', 'unsigned' => true, 'null' => false],
            'attendance_rate'   => ['name' => 'attendance_rate', 'type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false],
        ]);
    }
}

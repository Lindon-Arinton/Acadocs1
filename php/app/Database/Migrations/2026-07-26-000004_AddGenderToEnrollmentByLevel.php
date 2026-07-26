<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * DepEd KPI reports break enrollment down as Male/Female per grade level
 * (not just a single total), so `enrollment_by_level` needs columns for
 * that split. `students` remains the total headcount.
 */
class AddGenderToEnrollmentByLevel extends Migration
{
    public function up()
    {
        $this->forge->addColumn('enrollment_by_level', [
            'male' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'students'],
            'female' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'male'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('enrollment_by_level', ['male', 'female']);
    }
}

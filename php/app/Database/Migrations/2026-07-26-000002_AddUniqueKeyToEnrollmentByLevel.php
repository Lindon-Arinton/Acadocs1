<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueKeyToEnrollmentByLevel extends Migration
{
    public function up()
    {
        $this->forge->addUniqueKey(['school_year', 'grade_level'], 'enrollment_year_grade');
        $this->forge->processIndexes('enrollment_by_level');
    }

    public function down()
    {
        $this->forge->dropKey('enrollment_by_level', 'enrollment_year_grade', false);
    }
}

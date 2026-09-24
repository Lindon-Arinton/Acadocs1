<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGradeAndSectionToTeacherSubjects extends Migration
{
    public function up()
    {
        $this->forge->addColumn('teacher_subjects', [
            'grade_level' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'subject'],
            'section'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'grade_level'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('teacher_subjects', ['grade_level', 'section']);
    }
}

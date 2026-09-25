<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAdvisoryToTeachers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('teachers', [
            'advisory' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'grade_level'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('teachers', 'advisory');
    }
}

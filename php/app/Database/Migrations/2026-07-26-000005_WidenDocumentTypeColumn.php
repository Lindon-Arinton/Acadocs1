<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class WidenDocumentTypeColumn extends Migration
{
    public function up()
    {
        // 'type' used to be a fixed category the teacher picked freely
        // (DLL/Lesson Plan/Assessment/Report). Submissions are now tied to a
        // real task assigned by the principal, so this holds that task's
        // title instead — an ENUM can't hold arbitrary task titles.
        $this->forge->modifyColumn('documents', [
            'type' => ['name' => 'type', 'type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('documents', [
            'type' => ['name' => 'type', 'type' => 'ENUM', 'constraint' => ['DLL', 'Lesson Plan', 'Assessment', 'Report'], 'null' => false],
        ]);
    }
}

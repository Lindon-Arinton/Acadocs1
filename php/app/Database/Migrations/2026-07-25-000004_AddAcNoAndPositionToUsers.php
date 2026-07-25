<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAcNoAndPositionToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'ac_no' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'role'],
            'position' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'ac_no'],
        ]);
        $this->forge->addUniqueKey('ac_no');
        $this->forge->processIndexes('users');

        $this->forge->modifyColumn('teachers', [
            'grade_level' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropKey('users', 'users_ac_no', false);
        $this->forge->dropColumn('users', ['ac_no', 'position']);

        $this->forge->modifyColumn('teachers', [
            'grade_level' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
        ]);
    }
}

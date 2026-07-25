<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateMpsTestScores extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'school_year' => ['type' => 'VARCHAR', 'constraint' => 20],
            'term' => ['type' => 'TINYINT', 'unsigned' => true],
            'grade_level' => ['type' => 'VARCHAR', 'constraint' => 50],
            'subject' => ['type' => 'VARCHAR', 'constraint' => 100],
            'test_period' => ['type' => 'ENUM', 'constraint' => ['Summative Test 1', 'Summative Test 2', 'Term Examination']],
            'mps' => ['type' => 'DECIMAL', 'constraint' => '5,2'],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['school_year', 'term', 'grade_level', 'subject', 'test_period']);
        $this->forge->createTable('mps_test_scores');
    }

    public function down()
    {
        $this->forge->dropTable('mps_test_scores', true);
    }
}

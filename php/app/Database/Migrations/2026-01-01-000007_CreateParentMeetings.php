<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateParentMeetings extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'date' => ['type' => 'DATE'],
            'expected_parents' => ['type' => 'INT', 'unsigned' => true],
            'actual_attendance' => ['type' => 'INT', 'unsigned' => true],
            'attendance_rate' => ['type' => 'DECIMAL', 'constraint' => '5,2'],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('parent_meetings');
    }

    public function down()
    {
        $this->forge->dropTable('parent_meetings', true);
    }
}

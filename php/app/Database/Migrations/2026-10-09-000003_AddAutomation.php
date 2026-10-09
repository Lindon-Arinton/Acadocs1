<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Automation support:
 *  - announcements.publish_at / expires_at: scheduled publishing and auto-archive;
 *    `notified` marks whether everyone was told (scheduled posts notify on publish).
 *  - automation_log: one row per job run that must happen only once
 *    (a term reminder, a pack notice, a day's cleanup), see App\Libraries\Automation.
 */
class AddAutomation extends Migration
{
    public function up()
    {
        $this->forge->addColumn('announcements', [
            'publish_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'status'],
            'expires_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'publish_at'],
            'notified'   => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1, 'after' => 'expires_at'],
        ]);

        $this->forge->addField([
            'id'     => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'job'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'note'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ran_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('job');
        $this->forge->createTable('automation_log');
    }

    public function down()
    {
        $this->forge->dropTable('automation_log', true);
        $this->forge->dropColumn('announcements', ['publish_at', 'expires_at', 'notified']);
    }
}

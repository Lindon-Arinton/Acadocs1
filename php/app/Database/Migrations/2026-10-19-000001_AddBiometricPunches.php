<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * biometric_punches: every punch read off the ZKTeco terminal, one row per
 * scan (see App\Libraries\BiometricSync). Only new punches are fetched from
 * the device on each sync, so a day's time-in/out is rebuilt from here.
 */
class AddBiometricPunches extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'ac_no'      => ['type' => 'VARCHAR', 'constraint' => 24],
            'punched_at' => ['type' => 'DATETIME'],
            'punch'      => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0, 'comment' => '0 check-in, 1 check-out, 2/3 break, 4/5 overtime'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['ac_no', 'punched_at']);
        $this->forge->addKey('punched_at');
        $this->forge->createTable('biometric_punches');
    }

    public function down()
    {
        $this->forge->dropTable('biometric_punches', true);
    }
}

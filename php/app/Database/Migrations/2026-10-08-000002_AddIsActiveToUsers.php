<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Accounts can be deactivated instead of deleted — e.g. the outgoing
 * principal when a new one takes over. A deactivated user can't sign in or
 * reset their password, is signed out of any open session, and drops out of
 * pickers and notification recipients, but their records stay intact.
 */
class AddIsActiveToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'null' => false, 'after' => 'photo'],
            'deactivated_at' => ['type' => 'TIMESTAMP', 'null' => true, 'after' => 'is_active'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', ['is_active', 'deactivated_at']);
    }
}

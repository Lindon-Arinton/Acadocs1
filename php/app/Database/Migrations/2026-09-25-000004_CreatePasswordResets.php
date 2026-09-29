<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * One-time codes for "Forgot password": a 6-digit code emailed to the user,
 * stored hashed, valid for a short window and a limited number of tries.
 */
class CreatePasswordResets extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'code_hash'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'attempts'   => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
            'expires_at' => ['type' => 'DATETIME'],
            'used_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE', 'password_resets_user_fk');
        $this->forge->createTable('password_resets', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('password_resets', true);
    }
}

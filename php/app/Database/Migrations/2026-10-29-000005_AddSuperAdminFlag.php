<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Super admin: an admin (principal-level) account that also has every ADAS
 * permission — property, templates, PARs, concerns — see hasRole() and
 * isSuperAdmin() in acadocs_helper.php. Lindon Arinton is the first one.
 */
class AddSuperAdminFlag extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'is_super_admin' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0, 'after' => 'role'],
        ]);
        $this->db->query("UPDATE users SET is_super_admin = 1 WHERE name = 'Lindon Arinton' AND role = 'admin'");
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'is_super_admin');
    }
}

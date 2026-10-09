<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * tasks.assigned_role lost its 'specific' value on some databases (the app
 * assigns tasks to "Specific People" with it, but those rows were saved with
 * an empty role). Restores the full set of values.
 */
class AddSpecificToTaskAssignedRole extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE tasks MODIFY assigned_role ENUM('teacher','secretary','adas','specific') NOT NULL");
    }

    public function down()
    {
        // Leaving 'specific' in place: removing it would break existing "Specific People" tasks.
    }
}

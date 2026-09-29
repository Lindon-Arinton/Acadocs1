<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Subject loads change every term, so teachers set their own per school
 * year + term from My Profile. Rows with no school_year/term are the base
 * load the admin sets in User Management — the fallback for any term the
 * teacher hasn't filled in.
 */
class AddTermToTeacherSubjects extends Migration
{
    public function up()
    {
        $this->forge->addColumn('teacher_subjects', [
            'school_year' => ['type' => 'VARCHAR', 'constraint' => 9, 'null' => true, 'after' => 'section'],
            'term'        => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'null' => true, 'after' => 'school_year'],
        ]);
        $this->db->query('ALTER TABLE teacher_subjects ADD KEY teacher_term (teacher_id, school_year, term)');
    }

    public function down()
    {
        $this->db->query("DELETE FROM teacher_subjects WHERE school_year IS NOT NULL");
        $this->db->query('ALTER TABLE teacher_subjects DROP KEY teacher_term');
        $this->forge->dropColumn('teacher_subjects', ['school_year', 'term']);
    }
}

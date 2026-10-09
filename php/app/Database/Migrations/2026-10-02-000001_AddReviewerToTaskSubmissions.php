<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Records who reviewed a task submission and who wrote each feedback
 * comment, so feedback is credited to the person who actually gave it
 * (rather than always "the principal"). Both stay NULL for rows written
 * before this existed; they're set NULL if that user is later deleted.
 */
class AddReviewerToTaskSubmissions extends Migration
{
    public function up()
    {
        $this->forge->addColumn('task_submissions', [
            'reviewed_by' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'status'],
            'reviewed_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'reviewed_by'],
        ]);
        $this->db->query('ALTER TABLE task_submissions ADD CONSTRAINT task_submissions_reviewed_by_fk FOREIGN KEY (reviewed_by) REFERENCES users (id) ON DELETE SET NULL');

        $this->forge->addColumn('task_feedback', [
            'author_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'comment'],
        ]);
        $this->db->query('ALTER TABLE task_feedback ADD CONSTRAINT task_feedback_author_fk FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE task_feedback DROP FOREIGN KEY task_feedback_author_fk');
        $this->forge->dropColumn('task_feedback', 'author_id');

        $this->db->query('ALTER TABLE task_submissions DROP FOREIGN KEY task_submissions_reviewed_by_fk');
        $this->forge->dropColumn('task_submissions', ['reviewed_by', 'reviewed_at']);
    }
}

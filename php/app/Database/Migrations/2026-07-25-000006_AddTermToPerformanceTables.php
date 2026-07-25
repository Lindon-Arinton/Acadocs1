<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTermToPerformanceTables extends Migration
{
    public function up()
    {
        $this->forge->addColumn('performance_by_subject', [
            'term' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true, 'after' => 'school_year'],
        ]);
        $this->forge->addUniqueKey(['school_year', 'term', 'subject', 'grade_level'], 'perf_subject_year_term_subject_grade');
        $this->forge->processIndexes('performance_by_subject');

        $this->forge->addColumn('performance_by_level', [
            'term' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true, 'after' => 'school_year'],
        ]);
        $this->forge->addUniqueKey(['school_year', 'term', 'grade_level'], 'perf_level_year_term_grade');
        $this->forge->processIndexes('performance_by_level');

        $this->forge->modifyColumn('performance_by_level', [
            'nds' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropKey('performance_by_subject', 'perf_subject_year_term_subject_grade', false);
        $this->forge->dropColumn('performance_by_subject', 'term');

        $this->forge->dropKey('performance_by_level', 'perf_level_year_term_grade', false);
        $this->forge->dropColumn('performance_by_level', 'term');

        $this->forge->modifyColumn('performance_by_level', [
            'nds' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false],
        ]);
    }
}

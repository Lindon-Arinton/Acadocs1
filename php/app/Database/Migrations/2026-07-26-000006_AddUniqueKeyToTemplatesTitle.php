<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueKeyToTemplatesTitle extends Migration
{
    public function up()
    {
        // Duplicate template titles are confusing (which "Certificate" is the
        // real one?) and title-based lookups elsewhere (e.g. the certificate
        // generator) assume titles are unique — the app already blocks this
        // on upload, this is the DB-level backstop against the same race.
        $this->forge->addUniqueKey('title', 'templates_title');
        $this->forge->processIndexes('templates');
    }

    public function down()
    {
        $this->forge->dropKey('templates', 'templates_title', false);
    }
}

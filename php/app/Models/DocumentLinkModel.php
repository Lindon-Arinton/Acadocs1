<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentLinkModel extends Model
{
    protected $table = 'document_links';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['category', 'title', 'description', 'url', 'added_by', 'date_added', 'access_level'];
}

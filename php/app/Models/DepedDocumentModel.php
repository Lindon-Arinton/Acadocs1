<?php

namespace App\Models;

use CodeIgniter\Model;

class DepedDocumentModel extends Model
{
    protected $table = 'deped_documents';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['document_type', 'description', 'due_date', 'status', 'completion_rate', 'prepared_by', 'last_updated'];
}

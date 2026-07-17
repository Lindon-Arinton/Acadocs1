<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentFeedbackModel extends Model
{
    protected $table = 'document_feedback';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['document_id', 'author', 'comment', 'date'];
}

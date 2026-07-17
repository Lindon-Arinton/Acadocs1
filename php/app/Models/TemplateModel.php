<?php

namespace App\Models;

use CodeIgniter\Model;

class TemplateModel extends Model
{
    protected $table = 'templates';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'category_id', 'title', 'file_path', 'file_name', 'file_ext', 'file_size', 'uploaded_by', 'date_added',
    ];

    public function distinctExtensions(): array
    {
        return array_column(
            $this->distinct()->select('file_ext')->orderBy('file_ext', 'ASC')->findAll(),
            'file_ext'
        );
    }
}

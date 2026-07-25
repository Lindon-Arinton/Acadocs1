<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentFileModel extends Model
{
    protected $table = 'document_files';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['document_id', 'file_path', 'file_name'];

    public function forDocument(int $documentId): array
    {
        return $this->where('document_id', $documentId)->orderBy('id', 'ASC')->findAll();
    }

    public function forDocuments(array $documentIds): array
    {
        if ($documentIds === []) {
            return [];
        }

        $grouped = [];
        foreach ($this->whereIn('document_id', $documentIds)->orderBy('id', 'ASC')->findAll() as $f) {
            $grouped[$f['document_id']][] = $f;
        }

        return $grouped;
    }
}

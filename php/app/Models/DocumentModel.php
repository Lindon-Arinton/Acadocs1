<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentModel extends Model
{
    protected $table = 'documents';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['teacher_id', 'type', 'subject', 'grade_level', 'date_submitted', 'status'];

    public function allWithTeacher(?int $teacherId = null, int $limit = 0): array
    {
        $builder = $this->select('documents.*, teachers.name AS teacher_name')
            ->join('teachers', 'teachers.id = documents.teacher_id')
            ->orderBy('documents.date_submitted', 'DESC');

        if ($teacherId) {
            $builder->where('documents.teacher_id', $teacherId);
        }

        return $builder->findAll($limit);
    }

    public function myDocsWithFeedback(int $teacherId): array
    {
        return $this->select("documents.*, GROUP_CONCAT(document_feedback.comment ORDER BY document_feedback.date SEPARATOR '|||') AS feedback_comments")
            ->join('document_feedback', 'document_feedback.document_id = documents.id', 'left')
            ->where('documents.teacher_id', $teacherId)
            ->groupBy('documents.id')
            ->orderBy('documents.date_submitted', 'DESC')
            ->findAll();
    }

    public function statusCounts(?int $teacherId = null): array
    {
        $builder = $this->select('status, COUNT(*) AS cnt')->groupBy('status');

        if ($teacherId) {
            $builder->where('teacher_id', $teacherId);
        }

        $counts = [];
        foreach ($builder->findAll() as $row) {
            $counts[$row['status']] = (int) $row['cnt'];
        }

        return $counts;
    }

    public function findWithTeacher(int $id): ?array
    {
        return $this->select('documents.*, teachers.name AS teacher_name')
            ->join('teachers', 'teachers.id = documents.teacher_id')
            ->where('documents.id', $id)
            ->first();
    }
}

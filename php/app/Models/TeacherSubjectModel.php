<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherSubjectModel extends Model
{
    protected $table = 'teacher_subjects';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['teacher_id', 'subject', 'grade_level', 'section'];

    public function forTeacher(int $teacherId): array
    {
        return $this->where('teacher_id', $teacherId)
            ->orderBy('grade_level')->orderBy('subject')->orderBy('section')
            ->findAll();
    }

    /** @return array<int,array<int,array<string,mixed>>> subject rows grouped by teacher_id */
    public function groupedByTeacherId(): array
    {
        $rows = $this->orderBy('teacher_id')->orderBy('grade_level')->orderBy('subject')->orderBy('section')->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['teacher_id']][] = $row;
        }

        return $grouped;
    }
}

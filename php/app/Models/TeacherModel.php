<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherModel extends Model
{
    protected $table = 'teachers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['employee_id', 'name', 'email', 'grade_level', 'submission_rate', 'user_id'];

    private function withSubjectsQuery()
    {
        return $this->select('teachers.*, GROUP_CONCAT(teacher_subjects.subject ORDER BY teacher_subjects.subject SEPARATOR ",") AS subjects')
            ->join('teacher_subjects', 'teacher_subjects.teacher_id = teachers.id', 'left')
            ->groupBy('teachers.id');
    }

    public function allWithSubjects(): array
    {
        $rows = $this->withSubjectsQuery()->orderBy('teachers.name')->findAll();

        return array_map(static function (array $row) {
            $row['subjects'] = $row['subjects'] ? explode(',', $row['subjects']) : [];

            return $row;
        }, $rows);
    }

    public function findWithSubjects(int $id): ?array
    {
        $row = $this->withSubjectsQuery()->where('teachers.id', $id)->first();
        if (! $row) {
            return null;
        }

        $row['subjects'] = $row['subjects'] ? explode(',', $row['subjects']) : [];

        return $row;
    }

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }
}

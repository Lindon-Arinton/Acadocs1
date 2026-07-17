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

    public static function buildProfilePayload(array $user): array
    {
        $email = strtolower(trim($user['email'] ?? ''));
        $name  = trim($user['name'] ?? '');

        return [
            'employee_id'     => 'T-' . str_pad((string) ($user['id'] ?? 0), 3, '0', STR_PAD_LEFT),
            'name'            => $name ?: ($user['email'] ?? 'Teacher'),
            'email'           => $email,
            'grade_level'     => 'All Levels',
            'submission_rate' => 0.00,
            'user_id'         => (int) ($user['id'] ?? 0),
        ];
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

    public function findByUserId(int $userId): ?array
    {
        return $this->where('user_id', $userId)->first();
    }

    public function resolveForUser(array $user): ?array
    {
        if (! $user) {
            return null;
        }

        $email = trim($user['email'] ?? '');
        if ($email === '') {
            return null;
        }

        $teacher = $this->findByEmail($email);
        if ($teacher) {
            return $teacher;
        }

        $teacherByUser = $this->findByUserId((int) ($user['id'] ?? 0));
        if ($teacherByUser) {
            return $teacherByUser;
        }

        $payload = self::buildProfilePayload($user);
        $id = $this->insert($payload);

        return $this->find($id);
    }
}

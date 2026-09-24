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
    protected $allowedFields = ['employee_id', 'name', 'email', 'grade_level', 'advisory', 'submission_rate', 'user_id'];

    public static function buildProfilePayload(array $user): array
    {
        $email = strtolower(trim($user['email'] ?? ''));
        $name  = trim($user['name'] ?? '');

        return [
            'employee_id'     => 'T-' . str_pad((string) ($user['id'] ?? 0), 3, '0', STR_PAD_LEFT),
            'name'            => $name ?: ($user['email'] ?? 'Teacher'),
            'email'           => $email,
            'grade_level'     => 'All Levels',
            'advisory'        => null,
            'submission_rate' => 0.00,
            'user_id'         => (int) ($user['id'] ?? 0),
        ];
    }

    /**
     * @return array<int,array<string,mixed>> each row's 'subjects' is a list of
     *   ['id','teacher_id','subject','grade_level','section'] rows (see TeacherSubjectModel).
     */
    public function allWithSubjects(): array
    {
        $rows              = $this->orderBy('name')->findAll();
        $subjectsByTeacher = (new TeacherSubjectModel())->groupedByTeacherId();

        foreach ($rows as &$row) {
            $row['subjects'] = $subjectsByTeacher[(int) $row['id']] ?? [];
        }

        return $rows;
    }

    public function findWithSubjects(int $id): ?array
    {
        $row = $this->find($id);
        if (! $row) {
            return null;
        }

        $row['subjects'] = (new TeacherSubjectModel())->forTeacher($id);

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

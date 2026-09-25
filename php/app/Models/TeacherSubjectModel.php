<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * A teacher's subject load. Rows with school_year/term set are the load a
 * teacher entered for that term (from My Profile); rows with both NULL are
 * the base load the admin sets in User Management, used for any term the
 * teacher hasn't filled in yet.
 */
class TeacherSubjectModel extends Model
{
    protected $table = 'teacher_subjects';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['teacher_id', 'subject', 'grade_level', 'section', 'school_year', 'term'];

    /**
     * The teacher's load for a school year + term: that term's own rows if
     * the teacher entered any, otherwise the latest earlier term they did
     * enter, otherwise the admin's base load. With no year/term given it
     * resolves the teacher's most recent term.
     */
    public function forTeacher(int $teacherId, ?string $schoolYear = null, ?int $term = null): array
    {
        $period = $this->latestTermFor($teacherId, $schoolYear, $term);

        return $period
            ? $this->forTerm($teacherId, $period['school_year'], (int) $period['term'])
            : $this->baseForTeacher($teacherId);
    }

    /** Only the rows entered for exactly this school year + term (may be empty). */
    public function forTerm(int $teacherId, string $schoolYear, int $term): array
    {
        return $this->where('teacher_id', $teacherId)
            ->where('school_year', $schoolYear)->where('term', $term)
            ->orderBy('grade_level')->orderBy('subject')->orderBy('section')
            ->findAll();
    }

    /** The admin-set base load (rows not tied to a term). */
    public function baseForTeacher(int $teacherId): array
    {
        return $this->where('teacher_id', $teacherId)
            ->where('school_year', null)
            ->orderBy('grade_level')->orderBy('subject')->orderBy('section')
            ->findAll();
    }

    /**
     * Most recent term (at or before the given one, if given) that the
     * teacher entered a load for.
     *
     * @return array{school_year:string,term:int|string}|null
     */
    public function latestTermFor(int $teacherId, ?string $schoolYear = null, ?int $term = null): ?array
    {
        $builder = $this->select('school_year, term')
            ->where('teacher_id', $teacherId)
            ->where('school_year IS NOT NULL');

        if ($schoolYear !== null && $term !== null) {
            $builder->groupStart()
                ->where('school_year <', $schoolYear)
                ->orGroupStart()->where('school_year', $schoolYear)->where('term <=', $term)->groupEnd()
                ->groupEnd();
        }

        return $builder->orderBy('school_year', 'DESC')->orderBy('term', 'DESC')->first();
    }

    /** Replaces the teacher's load for one school year + term. */
    public function replaceTerm(int $teacherId, string $schoolYear, int $term, array $rows): void
    {
        $this->db->transStart();

        $this->where('teacher_id', $teacherId)->where('school_year', $schoolYear)->where('term', $term)->delete();

        foreach ($rows as $row) {
            $this->insert([
                'teacher_id'  => $teacherId,
                'subject'     => $row['subject'],
                'grade_level' => $row['grade_level'],
                'section'     => $row['section'],
                'school_year' => $schoolYear,
                'term'        => $term,
            ]);
        }

        $this->db->transComplete();
    }

    /** @return array<int,array<int,array<string,mixed>>> base-load rows grouped by teacher_id */
    public function groupedByTeacherId(): array
    {
        $rows = $this->where('school_year', null)
            ->orderBy('teacher_id')->orderBy('grade_level')->orderBy('subject')->orderBy('section')->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['teacher_id']][] = $row;
        }

        return $grouped;
    }
}

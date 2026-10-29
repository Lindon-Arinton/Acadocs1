<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * A teacher's request to add, change or remove ONE subject in their load for
 * a term, filled out on a form (with a reason) and approved or rejected by
 * the principal / ADAS. See Shared\Profile and Admin\SubjectRequests.
 *
 * `proposed` holds the change as JSON:
 *   {"action":"add",    "to":{subject,grade_level,section}}
 *   {"action":"change", "from":{...}, "to":{...}}
 *   {"action":"remove", "from":{...}}
 */
class SubjectLoadRequestModel extends Model
{
    public const ACTIONS = ['add' => 'Add', 'change' => 'Change', 'remove' => 'Remove'];

    protected $table         = 'subject_load_requests';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['teacher_id', 'user_id', 'school_year', 'term', 'proposed', 'reason', 'status', 'reviewed_by', 'reviewed_at', 'review_note'];

    /** A teacher's requests for one term: pending first, then the newest decided ones ($decidedLimit 0 = pending only). */
    public function forTeacherTerm(int $teacherId, string $year, int $term, int $decidedLimit = 5): array
    {
        $base    = fn () => $this->where('teacher_id', $teacherId)->where('school_year', $year)->where('term', $term);
        $pending = $base()->where('status', 'Pending')->orderBy('id', 'ASC')->findAll();

        return $decidedLimit > 0
            ? array_merge($pending, $base()->where('status !=', 'Pending')->orderBy('id', 'DESC')->findAll($decidedLimit))
            : $pending;
    }

    public function pendingCount(): int
    {
        return $this->where('status', 'Pending')->countAllResults();
    }

    /** @return array{action:string,from:?array,to:?array} */
    public static function change(array $request): array
    {
        $c = json_decode((string) $request['proposed'], true);

        return [
            'action' => is_array($c) && isset(self::ACTIONS[$c['action'] ?? '']) ? $c['action'] : 'add',
            'from'   => is_array($c['from'] ?? null) ? $c['from'] : null,
            'to'     => is_array($c['to'] ?? null) ? $c['to'] : null,
        ];
    }

    /** Case-insensitive identity of a load row (subject + grade + section). */
    public static function rowKey(array $r): string
    {
        return strtolower(trim((string) ($r['subject'] ?? '')) . '|' . trim((string) ($r['grade_level'] ?? '')) . '|' . trim((string) ($r['section'] ?? '')));
    }

    public static function hasRow(array $rows, array $row): bool
    {
        $key = self::rowKey($row);
        foreach ($rows as $r) {
            if (self::rowKey($r) === $key) {
                return true;
            }
        }

        return false;
    }

    public static function label(?array $r): string
    {
        return $r ? trim($r['subject'] . ' · ' . ($r['grade_level'] ?? '') . ' · ' . ($r['section'] ?? '')) : '';
    }

    /**
     * The load after applying a change, or an error when it no longer fits
     * (the subject to change/remove is gone, or the one to add is already there).
     *
     * @param list<array> $current teacher_subjects rows (or plain subject/grade_level/section rows)
     * @return array{0:?list<array>,1:?string} [rows, error]
     */
    public static function apply(array $current, array $change): array
    {
        $rows = array_map(static fn ($r) => [
            'subject'     => $r['subject'],
            'grade_level' => $r['grade_level'] ?? null,
            'section'     => $r['section'] ?? null,
        ], $current);

        if ($change['action'] !== 'add' && ! self::hasRow($rows, $change['from'] ?? [])) {
            return [null, self::label($change['from']) . ' is not in the load.'];
        }
        if ($change['action'] !== 'remove' && self::hasRow($rows, $change['to'] ?? [])) {
            return [null, self::label($change['to']) . ' is already in the load.'];
        }

        if ($change['action'] !== 'add') {
            $fromKey = self::rowKey($change['from']);
            $rows    = array_values(array_filter($rows, static fn ($r) => self::rowKey($r) !== $fromKey));
        }
        if ($change['action'] !== 'remove') {
            $rows[] = $change['to'];
        }

        return [$rows, null];
    }
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class RoomPropertyModel extends Model
{
    public const CONDITIONS   = ['Working', 'Not Working'];
    public const ACQUISITIONS = ['New', 'Donated', 'Second-hand', 'Other'];

    protected $table = 'room_properties';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'section', 'grade', 'item_name', 'quantity', 'condition_status',
        'acquisition_type', 'acquisition_other', 'notes', 'issued_to', 'par_id',
        'last_checked_at', 'last_checked_by', 'uploaded_by', 'updated_at',
    ];

    /**
     * How the item was acquired, as one label — "Other" shows what was specified,
     * e.g. "Other: Transferred from DepEd Division".
     */
    public static function acquisitionLabel(array $item): string
    {
        $type = $item['acquisition_type'] ?? 'New';

        if ($type === 'Other' && trim((string) ($item['acquisition_other'] ?? '')) !== '') {
            return 'Other: ' . trim($item['acquisition_other']);
        }

        return $type;
    }

    /**
     * The adviser (user) of each section, keyed by lower-cased section name —
     * from teachers.advisory. Co-advisers are skipped; the room's property goes
     * to the main adviser.
     *
     * @return array<string,array{id:int,name:string}>
     */
    public static function advisersBySection(): array
    {
        $rows = db_connect()->table('teachers t')
            ->select('t.advisory, u.id, u.name')
            ->join('users u', 'u.id = t.user_id')
            ->where('u.role', 'teacher')
            ->where('t.advisory IS NOT NULL')
            ->where('t.advisory !=', '')
            ->notLike('t.advisory', 'Co-Adviser')
            ->get()->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[strtolower(trim($row['advisory']))] = ['id' => (int) $row['id'], 'name' => $row['name']];
        }

        return $map;
    }
}

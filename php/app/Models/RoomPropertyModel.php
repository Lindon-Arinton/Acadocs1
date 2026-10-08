<?php

namespace App\Models;

use CodeIgniter\Model;

class RoomPropertyModel extends Model
{
    public const CONDITIONS   = ['Serviceable', 'Non-serviceable'];
    public const ACQUISITIONS = ['New', 'Donated', 'Second-hand', 'Other'];

    protected $table = 'room_properties';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'section', 'grade', 'item_name', 'quantity', 'condition_status',
        'acquisition_type', 'acquisition_other', 'notes', 'issued_to', 'par_id',
        'uploaded_by', 'updated_at',
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
}

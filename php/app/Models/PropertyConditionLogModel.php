<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * One row per condition change of a property item (Serviceable ⇄
 * Non-serviceable), including the initial condition when the item was added.
 */
class PropertyConditionLogModel extends Model
{
    protected $table = 'property_condition_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['property_id', 'from_status', 'to_status', 'remarks', 'updated_by', 'updated_by_name'];

    public function record(int $propertyId, ?string $from, string $to, ?string $remarks = null): void
    {
        $user = currentUser();

        $this->insert([
            'property_id'     => $propertyId,
            'from_status'     => $from,
            'to_status'       => $to,
            'remarks'         => $remarks !== null && trim($remarks) !== '' ? trim($remarks) : null,
            'updated_by'      => $user['id'] ?? null,
            'updated_by_name' => $user['name'] ?? null,
        ]);
    }

    /**
     * History for the given items, newest first, grouped by property id.
     *
     * @param list<int> $propertyIds
     * @return array<int,list<array>>
     */
    public function historyFor(array $propertyIds): array
    {
        if ($propertyIds === []) {
            return [];
        }

        $grouped = [];
        foreach ($this->whereIn('property_id', $propertyIds)->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->findAll() as $row) {
            $grouped[(int) $row['property_id']][] = $row;
        }

        return $grouped;
    }
}

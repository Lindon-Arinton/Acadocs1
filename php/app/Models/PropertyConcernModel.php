<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * A concern an adviser reports about a property item issued to them (not
 * working, missing, wrong quantity...). ADAS resolves it with a response.
 */
class PropertyConcernModel extends Model
{
    public const CATEGORIES = ['Not working / damaged', 'Missing / not received', 'Wrong quantity', 'Other'];

    protected $table = 'property_concerns';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['property_id', 'reported_by', 'category', 'details', 'status', 'response', 'resolved_by', 'resolved_at'];

    /**
     * Concerns with item, reporter and resolver details, open ones first.
     * Pass a user id to limit to the concerns that user reported.
     */
    public function listing(?int $reportedBy = null): array
    {
        $builder = $this->db->table('property_concerns c')
            ->select('c.*, rp.item_name, rp.grade, rp.section, rp.quantity, rp.condition_status, ur.name AS reported_by_name, us.name AS resolved_by_name')
            ->join('room_properties rp', 'rp.id = c.property_id')
            ->join('users ur', 'ur.id = c.reported_by', 'left')
            ->join('users us', 'us.id = c.resolved_by', 'left')
            ->orderBy("c.status = 'Open'", 'DESC', false)
            ->orderBy('c.created_at', 'DESC')
            ->orderBy('c.id', 'DESC');

        if ($reportedBy !== null) {
            $builder->where('c.reported_by', $reportedBy);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Number of open concerns per property id.
     *
     * @return array<int,int>
     */
    public function openCountByProperty(): array
    {
        $out = [];
        foreach ($this->select('property_id, COUNT(*) AS n')->where('status', 'Open')->groupBy('property_id')->findAll() as $row) {
            $out[(int) $row['property_id']] = (int) $row['n'];
        }

        return $out;
    }
}

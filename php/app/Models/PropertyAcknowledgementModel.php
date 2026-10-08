<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Property Acknowledgment Receipt (PAR): ADAS issues one to a teacher for the
 * items they are accountable for; the teacher approves it or returns it.
 */
class PropertyAcknowledgementModel extends Model
{
    protected $table = 'property_acknowledgements';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['par_no', 'issued_to', 'issued_by', 'status', 'remarks', 'responded_at'];

    /**
     * Next PAR number for the current year, e.g. "PAR-2026-0007".
     */
    public function nextParNo(): string
    {
        $prefix = 'PAR-' . date('Y') . '-';
        $last   = $this->like('par_no', $prefix, 'after')->orderBy('par_no', 'DESC')->first();
        $seq    = $last ? ((int) substr($last['par_no'], strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * PARs with the names of who they were issued to / by and their item count.
     * Pass a user id to limit to the PARs issued to that teacher, or a PAR id
     * to fetch just that one.
     */
    public function listing(?int $issuedTo = null, ?int $id = null): array
    {
        $builder = $this->db->table('property_acknowledgements p')
            ->select('p.*, ut.name AS issued_to_name, ub.name AS issued_by_name, COUNT(i.id) AS item_count, COALESCE(SUM(i.quantity), 0) AS total_qty')
            ->join('users ut', 'ut.id = p.issued_to', 'left')
            ->join('users ub', 'ub.id = p.issued_by', 'left')
            ->join('property_acknowledgement_items i', 'i.par_id = p.id', 'left')
            ->groupBy('p.id')
            ->orderBy('p.created_at', 'DESC')
            ->orderBy('p.id', 'DESC');

        if ($issuedTo !== null) {
            $builder->where('p.issued_to', $issuedTo);
        }
        if ($id !== null) {
            $builder->where('p.id', $id);
        }

        return $builder->get()->getResultArray();
    }
}

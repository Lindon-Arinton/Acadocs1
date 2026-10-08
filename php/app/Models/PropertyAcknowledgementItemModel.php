<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Snapshot of each item on a PAR, taken when the PAR is issued, so the
 * receipt stays accurate after the inventory row is edited or removed.
 */
class PropertyAcknowledgementItemModel extends Model
{
    protected $table = 'property_acknowledgement_items';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'par_id', 'property_id', 'item_name', 'grade', 'section',
        'quantity', 'condition_status', 'acquisition', 'date_acquired',
    ];
}

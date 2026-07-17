<?php

namespace App\Models;

use CodeIgniter\Model;

class RoomPropertyModel extends Model
{
    protected $table = 'room_properties';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['room_number', 'building_name', 'item_name', 'quantity', 'condition_status', 'last_inspection', 'remarks'];
}

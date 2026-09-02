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
    protected $allowedFields = ['section', 'grade', 'item_name', 'condition_status'];
}

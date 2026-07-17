<?php

namespace App\Models;

use CodeIgniter\Model;

class TemplateCategoryModel extends Model
{
    protected $table = 'template_categories';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['name', 'created_by'];
}

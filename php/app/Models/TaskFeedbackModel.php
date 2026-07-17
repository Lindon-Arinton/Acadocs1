<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskFeedbackModel extends Model
{
    protected $table = 'task_feedback';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['task_submission_id', 'comment', 'date'];
}

<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Jobs that must run only once (a term reminder, a pack notice, a day's
 * cleanup), keyed by a unique job name such as "eot-start:2026-2027:2".
 */
class AutomationLogModel extends Model
{
    protected $table = 'automation_log';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['job', 'note'];

    public function hasRun(string $job): bool
    {
        return $this->where('job', $job)->countAllResults() > 0;
    }

    /**
     * Claims a job before running it. False when it already ran (or another
     * request claimed it a moment ago — the unique key makes this race-safe).
     */
    public function claim(string $job, ?string $note = null): bool
    {
        $this->db->query('INSERT IGNORE INTO automation_log (job, note) VALUES (?, ?)', [$job, $note]);

        return $this->db->affectedRows() === 1;
    }
}

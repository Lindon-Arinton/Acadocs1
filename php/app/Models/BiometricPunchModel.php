<?php

namespace App\Models;

use CodeIgniter\Model;

class BiometricPunchModel extends Model
{
    protected $table = 'biometric_punches';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['ac_no', 'punched_at', 'punch'];

    /**
     * Stores punches, skipping any already on record (same AC-No and time).
     *
     * @param list<array{ac_no:string,time:\DateTimeInterface,punch:int}> $punches
     */
    public function store(array $punches): void
    {
        foreach (array_chunk($punches, 500) as $chunk) {
            $this->db->table($this->table)->ignore(true)->insertBatch(array_map(static fn (array $p) => [
                'ac_no'      => $p['ac_no'],
                'punched_at' => $p['time']->format('Y-m-d H:i:s'),
                'punch'      => $p['punch'],
            ], $chunk));
        }
    }

    /** @return list<array{ac_no:string,time:\DateTimeImmutable,punch:int}> punches from $date (Y-m-d) on — up to and including $until if given — oldest first */
    public function since(string $date, ?string $until = null): array
    {
        $this->where('punched_at >=', $date . ' 00:00:00');
        if ($until !== null) {
            $this->where('punched_at <=', $until . ' 23:59:59');
        }

        return array_map(static fn (array $r) => [
            'ac_no' => $r['ac_no'],
            'time'  => new \DateTimeImmutable($r['punched_at']),
            'punch' => (int) $r['punch'],
        ], $this->orderBy('punched_at', 'ASC')->findAll());
    }
}

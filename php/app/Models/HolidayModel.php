<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Holidays: school days never counted as absences.
 *  - recurring = 1: same month and day every year (the stored year doesn't matter)
 *  - recurring = 0: that one date only (e.g. Chinese New Year, a local holiday)
 *  - movable national holidays (Holy Week, National Heroes Day) are computed for
 *    any year by movableFor(), so they never need to be entered.
 */
class HolidayModel extends Model
{
    protected $table = 'holidays';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['date', 'label', 'recurring'];

    /** @var array{dates: array<string,string>, monthDays: array<string,string>}|null */
    private ?array $lookup = null;

    /** The holiday on a date (Y-m-d), or null when it's a regular day. */
    public function labelFor(string $date): ?string
    {
        if ($this->lookup === null) {
            $this->lookup = ['dates' => [], 'monthDays' => []];
            foreach ($this->findAll() as $h) {
                $label = $h['label'] ?: 'Holiday';
                if ((int) $h['recurring']) {
                    $this->lookup['monthDays'][substr($h['date'], 5)] = $label;
                } else {
                    $this->lookup['dates'][$h['date']] = $label;
                }
            }
        }

        return $this->lookup['dates'][$date]
            ?? $this->lookup['monthDays'][substr($date, 5)]
            ?? self::movableFor((int) substr($date, 0, 4))[$date]
            ?? null;
    }

    /**
     * Movable national holidays for a year: Maundy Thursday, Good Friday and
     * Black Saturday (from Easter) and National Heroes Day (last Monday of August).
     *
     * @return array<string,string> Y-m-d => label
     */
    public static function movableFor(int $year): array
    {
        static $cache = [];
        if (isset($cache[$year])) {
            return $cache[$year];
        }

        $easter = self::easterSunday($year);
        $before = static fn (int $days) => date('Y-m-d', strtotime($easter . ' -' . $days . ' days'));

        return $cache[$year] = [
            $before(3) => 'Maundy Thursday',
            $before(2) => 'Good Friday',
            $before(1) => 'Black Saturday',
            date('Y-m-d', strtotime('last monday of august ' . $year)) => 'National Heroes Day',
        ];
    }

    /** Easter Sunday (Gregorian calendar), by the anonymous Gregorian algorithm. */
    private static function easterSunday(int $year): string
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $h = (19 * $a + $b - intdiv($b, 4) - intdiv($b - intdiv($b + 8, 25) + 1, 3) + 15) % 30;
        $l = (32 + 2 * ($b % 4) + 2 * intdiv($c, 4) - $h - ($c % 4)) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $n = $h + $l - 7 * $m + 114;

        return sprintf('%04d-%02d-%02d', $year, intdiv($n, 31), $n % 31 + 1);
    }
}

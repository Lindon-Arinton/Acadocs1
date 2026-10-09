<?php

namespace App\Libraries;

use App\Models\AcademicBreakModel;
use Config\SchoolCalendar as CalendarConfig;

/**
 * Where a date falls in the DepEd school calendar (Config\SchoolCalendar):
 * which school year and term, which block, and whether it's an academic break.
 */
class SchoolCalendar
{
    private CalendarConfig $config;

    public function __construct(?CalendarConfig $config = null)
    {
        $this->config = $config ?? config('SchoolCalendar');
    }

    /**
     * @return array{
     *   schoolYear: string, term: ?int, termStart: ?string, termEnd: ?string,
     *   block: ?string, daysLeft: ?int, termProgress: ?int,
     *   classDaysThisMonth: ?int, totalClassDays: int,
     *   break: ?array, nextTerm: ?array{term: int, start: string}
     * }|null null when the date is outside every configured school year
     */
    public function on(?string $date = null): ?array
    {
        $date ??= date('Y-m-d');

        foreach ($this->config->years as $schoolYear => $year) {
            $terms = $year['terms'];
            $first = reset($terms)['start'];
            // A school year runs from its first class day until the next one starts
            // (or, for the latest year, through the end of its last term's EOSY break).
            $yearEnd = $this->yearEnd($schoolYear, $year);
            if ($date < $first || $date > $yearEnd) {
                continue;
            }

            $info = [
                'schoolYear'         => $schoolYear,
                'term'               => null,
                'termStart'          => null,
                'termEnd'            => null,
                'block'              => null,
                'daysLeft'           => null,
                'termProgress'       => null,
                'classDaysThisMonth' => $year['classDays'][substr($date, 0, 7)] ?? null,
                'totalClassDays'     => $year['totalClassDays'],
                'break'              => (new AcademicBreakModel())->covering($date),
                'nextTerm'           => null,
            ];

            foreach ($terms as $termNo => $term) {
                if ($date >= $term['start'] && $date <= $term['end']) {
                    $info['term']      = $termNo;
                    $info['termStart'] = $term['start'];
                    $info['termEnd']   = $term['end'];
                    $info['daysLeft']  = (int) ((strtotime($term['end']) - strtotime($date)) / 86400);
                    $span              = max(1, strtotime($term['end']) - strtotime($term['start']));
                    $info['termProgress'] = (int) round((strtotime($date) - strtotime($term['start'])) / $span * 100);
                    // Weekends between blocks belong to the block that comes next.
                    foreach ($term['blocks'] as $block) {
                        if ($date <= $block['end']) {
                            $info['block'] = $block['name'];
                            break;
                        }
                    }
                    break;
                }
                if ($date < $term['start'] && $info['nextTerm'] === null) {
                    $info['nextTerm'] = ['term' => $termNo, 'start' => $term['start']];
                }
            }

            return $info;
        }

        return null;
    }

    /** The term in progress on a date (null on breaks and outside the calendar). */
    public function termOn(?string $date = null): ?array
    {
        $info = $this->on($date);

        return $info && $info['term'] !== null ? ['schoolYear' => $info['schoolYear'], 'term' => $info['term']] : null;
    }

    private function yearEnd(string $schoolYear, array $year): string
    {
        $nextStart = null;
        foreach ($this->config->years as $otherYear => $other) {
            $start = reset($other['terms'])['start'];
            if ($otherYear !== $schoolYear && $start > reset($year['terms'])['start'] && ($nextStart === null || $start < $nextStart)) {
                $nextStart = $start;
            }
        }

        // Without a following year on file, assume a month of EOSY break after the last term.
        return $nextStart !== null
            ? date('Y-m-d', strtotime($nextStart . ' -1 day'))
            : date('Y-m-d', strtotime(end($year['terms'])['end'] . ' +1 month +1 day'));
    }
}

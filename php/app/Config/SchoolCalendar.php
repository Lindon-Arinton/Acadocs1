<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * DepEd three-term school calendar, one entry per school year. Add the next
 * year here when DepEd releases its calendar — App\Libraries\SchoolCalendar
 * reads it to know the current term and block.
 *
 * Breaks that matter for attendance (no absences) live in the
 * academic_breaks table instead, so admin/ADAS can adjust them; the
 * migration that created it seeded this year's Christmas and EOSY breaks.
 */
class SchoolCalendar extends BaseConfig
{
    /**
     * @var array<string, array{
     *   totalClassDays: int,
     *   terms: array<int, array{start: string, end: string, blocks: list<array{name: string, start: string, end: string}>}>,
     *   classDays: array<string, int>
     * }>
     */
    public array $years = [
        '2026-2027' => [
            'totalClassDays' => 201,
            'terms'          => [
                1 => [
                    'start'  => '2026-06-08',
                    'end'    => '2026-09-15',
                    'blocks' => [
                        ['name' => 'Opening Block',       'start' => '2026-06-08', 'end' => '2026-06-11'],
                        ['name' => 'Instructional Block', 'start' => '2026-06-15', 'end' => '2026-09-01'],
                        ['name' => 'End-of-Term Block',   'start' => '2026-09-02', 'end' => '2026-09-15'],
                    ],
                ],
                2 => [
                    'start'  => '2026-09-16',
                    'end'    => '2026-12-18',
                    'blocks' => [
                        ['name' => 'Instructional Block', 'start' => '2026-09-16', 'end' => '2026-12-04'],
                        ['name' => 'End-of-Term Block',   'start' => '2026-12-07', 'end' => '2026-12-18'],
                    ],
                ],
                3 => [
                    'start'  => '2027-01-04',
                    'end'    => '2027-04-08',
                    'blocks' => [
                        ['name' => 'Instructional Block', 'start' => '2027-01-04', 'end' => '2027-03-23'],
                        ['name' => 'End-of-Term Block',   'start' => '2027-03-24', 'end' => '2027-04-08'],
                    ],
                ],
            ],
            // Class days per month across all terms. December isn't on the
            // DepEd poster; 13 is what's left of the 201-day total.
            'classDays' => [
                '2026-06' => 16, '2026-07' => 23, '2026-08' => 19, '2026-09' => 22,
                '2026-10' => 22, '2026-11' => 19, '2026-12' => 13,
                '2027-01' => 20, '2027-02' => 20, '2027-03' => 21, '2027-04' => 6,
            ],
        ],
    ];
}

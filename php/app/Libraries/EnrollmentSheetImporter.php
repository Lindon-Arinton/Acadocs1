<?php

namespace App\Libraries;

use App\Models\EnrollmentByLevelModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Imports the school's per-section enrollment sheet (the "SY 2026-2027
 * Enrollment" layout; see Admin\Enrollment::template()):
 *
 *   GRADE 7                    | MALE | FEMALE | TOTAL
 *   MAPAGMAHAL - JBD - SPFL    |  15  |   18   |  33
 *   ...one row per section...
 *                        TOTAL |  97  |   84   |  181
 *   GRADE 8 ...
 *   GRAND TOTAL                | 366  |  390   |  756
 *
 * Section rows are summed per grade level into `enrollment_by_level`
 * (students, male, female, number of sections). The sheet's own TOTAL /
 * GRAND TOTAL rows are only used to cross-check the section rows.
 *
 * Every sheet in the workbook is read: the school keeps one sheet per count
 * date ("June 9", "June 10", ... "AUG 11"), and the date in the sheet name
 * (or a title row like "Enrollment - June 10") says which month it is.
 * Sheets with no GRADE blocks (summaries, notes) are skipped.
 */
class EnrollmentSheetImporter
{
    private const GRADE_LEVELS = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];

    /**
     * @return array{
     *   sheets: list<array{title:string, month:?int, day:?int, grades:array<string, array{male:int, female:int, total:int, sections:int}>, warnings:string[]}>,
     *   skipped: string[],
     *   file_year: ?string,
     *   errors: string[],
     * }
     */
    public function parse(string $filePath): array
    {
        $book = ['sheets' => [], 'skipped' => [], 'file_year' => null, 'errors' => []];

        foreach (IOFactory::load($filePath)->getAllSheets() as $sheet) {
            $title  = trim($sheet->getTitle());
            $result = $this->parseRows($sheet->toArray(null, true, false, false));

            if ($result['errors'] !== []) {
                foreach ($result['errors'] as $error) {
                    $book['errors'][] = "Sheet \"{$title}\": {$error}";
                }

                continue;
            }

            if ($result['grades'] === []) {
                $book['skipped'][] = $title; // no GRADE blocks, or no numbers filled in

                continue;
            }

            if ($result['file_year'] !== null) {
                if ($book['file_year'] !== null && $book['file_year'] !== $result['file_year']) {
                    $book['errors'][] = "Sheet \"{$title}\" is labelled SY {$result['file_year']}, but another sheet says SY {$book['file_year']}.";

                    continue;
                }
                $book['file_year'] = $result['file_year'];
            }

            $book['sheets'][] = [
                'title'    => $title,
                // The sheet name ("June 23", "AUG 11") wins over a title row inside it.
                'month'    => self::monthNumber($title) ?? $result['file_month'],
                'day'      => self::monthNumber($title) !== null ? self::dayNumber($title) : $result['file_day'],
                'grades'   => $result['grades'],
                'warnings' => array_map(static fn (string $w) => "[{$title}] {$w}", $result['warnings']),
            ];
        }

        if ($book['sheets'] === [] && $book['errors'] === []) {
            $book['errors'][] = 'No enrollment numbers found in any sheet. Expected GRADE 7–10 blocks with MALE / FEMALE / TOTAL columns (download the template to see the layout).';
        }

        return $book;
    }

    /**
     * Parses one sheet's rows.
     *
     * @return array{
     *   grades: array<string, array{male:int, female:int, total:int, sections:int}>,
     *   file_year: ?string,
     *   file_month: ?int,
     *   file_day: ?int,
     *   warnings: string[],
     *   errors: string[],
     * }
     */
    private function parseRows(array $rows): array
    {
        $result = ['grades' => [], 'file_year' => null, 'file_month' => null, 'file_day' => null, 'warnings' => [], 'errors' => []];

        $grade         = null;
        $columns       = null; // ['male' => col, 'female' => col, 'total' => col]
        $grandTotal    = null;
        $blankSections = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;
            $label     = $this->firstText($row);

            if ($label === null) {
                continue;
            }

            if ($result['file_year'] === null && preg_match('/\bS\.?Y\.?\s*(\d{4})\s*[-–]\s*(\d{4})\b/i', $label, $m)) {
                $result['file_year'] = $m[1] . '-' . $m[2];
            }

            // Title like "SY 2026-2027 Enrollment - June 10": remember the month.
            if ($result['file_month'] === null && stripos($label, 'ENROL') !== false) {
                $result['file_month'] = self::monthNumber($label);
                $result['file_day']   = self::dayNumber($label);
            }

            // "GRADE 7" block header (with the MALE/FEMALE/TOTAL column titles).
            if (preg_match('/^GRADE\s*(7|8|9|10)\b/i', $label, $m)) {
                $grade   = 'Grade ' . $m[1];
                $columns = $this->headerColumns($row) ?? $columns;

                if ($columns === null) {
                    $result['errors'][] = "Row {$rowNumber}: no MALE / FEMALE / TOTAL column titles next to {$label}.";

                    return $result;
                }

                if (isset($result['grades'][$grade])) {
                    $result['errors'][] = "Row {$rowNumber}: {$grade} appears more than once.";

                    return $result;
                }

                $result['grades'][$grade] = ['male' => 0, 'female' => 0, 'total' => 0, 'sections' => 0];

                continue;
            }

            $normalized = strtoupper(preg_replace('/\s+/', ' ', $label));

            if ($normalized === 'GRAND TOTAL') {
                $grandTotal = $this->numbers($row, $columns);
                $grade      = null;

                continue;
            }

            if ($grade === null || $columns === null) {
                continue; // title rows etc. before the first GRADE block
            }

            [$male, $female, $total] = $this->numbers($row, $columns);

            if ($normalized === 'TOTAL') {
                $sum = $result['grades'][$grade];
                if ($total !== null && $total !== $sum['total']) {
                    $result['warnings'][] = "{$grade}: the sheet's TOTAL says {$total}, but its sections add up to {$sum['total']} (used {$sum['total']}).";
                }
                $grade = null; // block finished

                continue;
            }

            // Section listed but MALE/FEMALE left blank (a TOTAL formula still
            // shows 0): not counted, but flagged — usually a partial count.
            if ($male === null && $female === null && ! $total) {
                $blankSections[] = $label;

                continue;
            }

            $male   ??= 0;
            $female ??= 0;

            if ($total === null) {
                $total = $male + $female;
            } elseif ($total !== $male + $female) {
                $result['warnings'][] = "Row {$rowNumber} ({$label}): male {$male} + female {$female} ≠ total {$total} (used {$total}).";
            }

            $result['grades'][$grade]['male']   += $male;
            $result['grades'][$grade]['female'] += $female;
            $result['grades'][$grade]['total']  += $total;
            $result['grades'][$grade]['sections']++;
        }

        // Grades listed with no section numbers at all aren't imported.
        $result['grades'] = array_filter($result['grades'], static fn (array $g) => $g['sections'] > 0);

        if ($result['grades'] === []) {
            return $result; // caller skips the sheet
        }

        if ($blankSections !== []) {
            $result['warnings'][] = count($blankSections) . ' section(s) have no numbers and were left out: ' . implode(', ', $blankSections) . '.';
        }

        if ($grandTotal !== null && $grandTotal[2] !== null) {
            $sum = array_sum(array_column($result['grades'], 'total'));
            if ($grandTotal[2] !== $sum) {
                $result['warnings'][] = "The sheet's GRAND TOTAL says {$grandTotal[2]}, but the sections add up to {$sum}.";
            }
        }

        // Keep the usual Grade 7 → 10 order regardless of the sheet's order.
        $ordered = [];
        foreach (self::GRADE_LEVELS as $level) {
            if (isset($result['grades'][$level])) {
                $ordered[$level] = $result['grades'][$level];
            }
        }
        $result['grades'] = $ordered;

        return $result;
    }

    /**
     * Upserts one row per grade level for the school year's monthly snapshot (YYYY-MM).
     *
     * @param array<string, array{male:int, female:int, total:int, sections:int}> $grades
     */
    public function save(string $schoolYear, string $month, array $grades): void
    {
        $model = new EnrollmentByLevelModel();
        $db    = $model->db;
        $db->transStart();

        foreach ($grades as $gradeLevel => $g) {
            $data = [
                'school_year' => $schoolYear,
                'month'       => $month,
                'grade_level' => $gradeLevel,
                'students'    => $g['total'],
                'male'        => $g['male'],
                'female'      => $g['female'],
                'sections'    => $g['sections'],
            ];

            $existing = $model->where('school_year', $schoolYear)->where('month', $month)->where('grade_level', $gradeLevel)->first();

            if ($existing !== null) {
                $model->update($existing['id'], $data);
            } else {
                $model->insert($data);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new \RuntimeException('database error while saving enrollment');
        }
    }

    /** Month number (1-12) of the first month name ("June", "Jun", "SEPT") in the text, or null. */
    public static function monthNumber(string $text): ?int
    {
        if (preg_match('/\b(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\.?\b/i', $text, $m)) {
            return array_search(strtolower($m[1]), ['', 'jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'], true) ?: null;
        }

        return null;
    }

    /** Day of month right after the month name ("June 23" -> 23, "AUG 11" -> 11), or null. */
    public static function dayNumber(string $text): ?int
    {
        if (preg_match('/\b(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\.?\s*(\d{1,2})\b/i', $text, $m)) {
            $day = (int) $m[1];

            return $day >= 1 && $day <= 31 ? $day : null;
        }

        return null;
    }

    /** First non-empty text-ish cell in the row (labels may be indented or right-aligned). */
    private function firstText(array $row): ?string
    {
        foreach ($row as $cell) {
            $text = trim((string) $cell);
            if ($text !== '' && ! is_numeric($text)) {
                return $text;
            }
        }

        return null;
    }

    /** @return array{male:int, female:int, total:int}|null column indexes from a GRADE header row */
    private function headerColumns(array $row): ?array
    {
        $cols = [];
        foreach ($row as $col => $cell) {
            $key = strtoupper(trim((string) $cell));
            if (in_array($key, ['MALE', 'FEMALE', 'TOTAL'], true) && ! isset($cols[strtolower($key)])) {
                $cols[strtolower($key)] = $col;
            }
        }

        return isset($cols['male'], $cols['female']) ? $cols + ['total' => null] : null;
    }

    /** @return array{0:?int, 1:?int, 2:?int} [male, female, total] */
    private function numbers(array $row, ?array $columns): array
    {
        $read = static function ($value): ?int {
            $value = trim(str_replace(',', '', (string) $value));

            return is_numeric($value) ? (int) round((float) $value) : null;
        };

        if ($columns === null) {
            return [null, null, null];
        }

        return [
            $read($row[$columns['male']] ?? null),
            $read($row[$columns['female']] ?? null),
            $columns['total'] === null ? null : $read($row[$columns['total']] ?? null),
        ];
    }
}

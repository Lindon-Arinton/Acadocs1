<?php

namespace App\Libraries;

use App\Controllers\Teacher\PerformanceMps;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Imports MPS scores from the school's actual "MPS" workbook layout: a
 * sheet (matched by name, e.g. "MPS ") containing one grid per test period
 * ("SUMMATIVE TEST 1/2", "TERM EXAMINATION" section titles), each grid
 * being a subject header row followed by one row per grade level — the
 * same shape as the printed/manual MPS spreadsheet the school already
 * uses. A trailing "AVERAGE MPS" section (computed roll-up, not raw
 * scores) is ignored because its title doesn't match a test period.
 *
 * The school year and term aren't reliably present in the sheet itself
 * (only a loose "TERM n" label), so they're supplied by the caller —
 * whatever the teacher has selected on the Enter MPS Scores page.
 */
class MpsScoreImporter
{
    /** How many rows below a matched section title to search for its subject header row. */
    private const HEADER_SEARCH_WINDOW = 6;

    /** Minimum recognized subject columns for a row to count as a header row. */
    private const MIN_SUBJECT_MATCHES = 3;

    /**
     * @return array{
     *   periods_found: string[], saved: int,
     *   warnings: string[], errors: string[],
     * }
     */
    public function import(string $filePath, string $schoolYear, int $term): array
    {
        $summary = [
            'periods_found' => [],
            'saved'         => 0,
            'warnings'      => [],
            'errors'        => [],
        ];

        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $this->findMpsSheet($spreadsheet);
        $rows        = $sheet->toArray(null, true, true, false);

        $this->checkSheetTerm($rows, $term, $summary);

        $periodByTitle = [];
        foreach (PerformanceMps::PERIOD_MAP as $label) {
            $periodByTitle[strtoupper($label)] = $label;
        }

        $subjectByTitle = [];
        foreach (PerformanceMps::SUBJECTS as $subject) {
            $subjectByTitle[strtoupper($subject)] = $subject;
        }

        $gradeByTitle = [];
        foreach (PerformanceMps::GRADE_LEVELS as $grade) {
            $gradeByTitle[strtoupper($grade)] = $grade;
        }

        $rowCount       = count($rows);
        $scoresByPeriod = [];

        for ($i = 0; $i < $rowCount; $i++) {
            $label = $periodByTitle[strtoupper(trim((string) ($rows[$i][0] ?? '')))] ?? null;
            if ($label === null) {
                continue;
            }

            $summary['periods_found'][] = $label;

            $headerRow      = null;
            $columnSubjects = [];

            for ($j = $i + 1, $limit = min($i + self::HEADER_SEARCH_WINDOW, $rowCount); $j < $limit; $j++) {
                $matches = [];
                foreach ($rows[$j] as $col => $value) {
                    $normalized = strtoupper(trim((string) $value));
                    if ($normalized !== '' && isset($subjectByTitle[$normalized])) {
                        $matches[$col] = $subjectByTitle[$normalized];
                    }
                }
                if (count($matches) >= self::MIN_SUBJECT_MATCHES) {
                    $headerRow      = $j;
                    $columnSubjects = $matches;
                    break;
                }
            }

            if ($headerRow === null) {
                $summary['warnings'][] = "Could not find a subject header row for '{$label}'.";
                continue;
            }

            for ($k = $headerRow + 1; $k < $rowCount; $k++) {
                $gradeLevel = $gradeByTitle[strtoupper(trim((string) ($rows[$k][0] ?? '')))] ?? null;
                if ($gradeLevel === null) {
                    break; // blank/non-grade row ends this section's grid
                }

                foreach ($columnSubjects as $col => $subject) {
                    $raw = trim((string) ($rows[$k][$col] ?? ''));
                    if ($raw === '' || ! is_numeric($raw)) {
                        continue;
                    }

                    $value = round((float) $raw, 2);
                    if ($value < 0 || $value > 100) {
                        $summary['warnings'][] = "Skipped {$gradeLevel} {$subject} ({$label}): value '{$raw}' out of range.";
                        continue;
                    }

                    $scoresByPeriod[$label][$gradeLevel][$subject] = $value;
                    $summary['saved']++;
                }
            }
        }

        $summary['periods_found'] = array_values(array_unique($summary['periods_found']));

        if ($scoresByPeriod === []) {
            $summary['errors'][] = 'No recognizable MPS grade/subject grid was found in the uploaded file.';

            return $summary;
        }

        (new MpsCalculator())->saveScores($schoolYear, $term, $scoresByPeriod);

        return $summary;
    }

    private function findMpsSheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): Worksheet
    {
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if (str_contains(strtoupper($sheet->getTitle()), 'MPS')) {
                return $sheet;
            }
        }

        return $spreadsheet->getActiveSheet();
    }

    /** @param array<int,array<int,mixed>> $rows */
    private function checkSheetTerm(array $rows, int $term, array &$summary): void
    {
        foreach (array_slice($rows, 0, 3) as $row) {
            $cell = strtoupper(trim((string) ($row[0] ?? '')));
            if (preg_match('/^TERM\s*(\d+)$/', $cell, $m) === 1) {
                $sheetTerm = (int) $m[1];
                if ($sheetTerm !== $term) {
                    $summary['warnings'][] = "The file is labeled 'Term {$sheetTerm}' but scores were saved under Term {$term} (as selected on the page).";
                }

                return;
            }
        }
    }
}

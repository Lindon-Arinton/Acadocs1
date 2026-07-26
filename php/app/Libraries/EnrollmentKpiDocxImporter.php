<?php

namespace App\Libraries;

use App\Models\DepedKpiReportModel;
use App\Models\EnrollmentByLevelModel;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\IOFactory;

/**
 * Parses a DepEd-style "Key Performance Indicator" Word report:
 *  - an Indicator table (Gross/Net Enrolment Rate, Cohort Survival,
 *    Repetition, Promotion, Retention, Graduation, Completion, Transition,
 *    Drop Out Rate, and sometimes a combined "Enrolment" row like
 *    "698 = male 367 female 331") — upserted into `deped_kpi_reports`.
 *  - an optional "Enrolment per Grade Level" table (Grade Level / Total
 *    rows) — upserted into `enrollment_by_level`.
 * The document doesn't reliably state its own school year, so the caller
 * supplies it, same as the MPS Excel importer.
 */
class EnrollmentKpiDocxImporter
{
    /** Normalized (uppercase, spelling-tolerant) indicator title => DB column. */
    private const INDICATOR_COLUMNS = [
        'GROSS ENROLMENT RATE'  => 'gross_enrolment_rate',
        'GROSS ENROLLMENT RATE' => 'gross_enrolment_rate',
        'NET ENROLMENT RATE'    => 'net_enrolment_rate',
        'NET ENROLLMENT RATE'   => 'net_enrolment_rate',
        'COHORT SURVIVAL RATE'  => 'cohort_survival_rate',
        'REPETITION RATE'       => 'repetition_rate',
        'PROMOTION RATE'        => 'promotion_rate',
        'RETENTION RATE'        => 'retention_rate',
        'GRADUATION RATE'       => 'graduation_rate',
        'COMPLETION RATE'       => 'completion_rate',
        'TRANSITION RATE'       => 'transition_rate',
        'DROP OUT RATE'         => 'dropout_rate',
        'DROPOUT RATE'          => 'dropout_rate',
    ];

    private const ENROLLMENT_TITLES = ['ENROLMENT', 'ENROLLMENT'];

    private const GRADE_LEVELS = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];

    /**
     * @return array{matched: string[], warnings: string[], errors: string[]}
     */
    public function import(string $filePath, string $schoolYear): array
    {
        $summary = [
            'matched'  => [],
            'warnings' => [],
            'errors'   => [],
        ];

        $phpWord = IOFactory::load($filePath);

        $data              = [];
        $enrollmentByGrade = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($this->findTables($section) as $table) {
                $gradeData = $this->tryParseGradeLevelTable($table);
                if ($gradeData !== null) {
                    $enrollmentByGrade = array_merge($enrollmentByGrade, $gradeData);
                    continue;
                }

                foreach ($table->getRows() as $row) {
                    $cells = $row->getCells();
                    if (count($cells) < 2) {
                        continue;
                    }

                    $label = strtoupper(trim($this->cellText($cells[0])));
                    $value = trim($this->cellText($cells[1]));

                    if ($label === '' || $value === '') {
                        continue;
                    }

                    if (in_array($label, self::ENROLLMENT_TITLES, true)) {
                        $this->parseEnrollment($value, $data, $summary);
                        continue;
                    }

                    $column = self::INDICATOR_COLUMNS[$label] ?? null;
                    if ($column === null) {
                        continue; // header row ("Indicator"/"Percentage") or an unrecognized label
                    }

                    $numeric = $this->parsePercent($value);
                    if ($numeric === null) {
                        $summary['warnings'][] = "Could not parse a number from '{$value}' for '{$label}'.";
                        continue;
                    }

                    $data[$column]        = $numeric;
                    $summary['matched'][] = $label;
                }
            }
        }

        if ($data === [] && $enrollmentByGrade === []) {
            $summary['errors'][] = 'No recognizable KPI indicators were found in the uploaded document.';

            return $summary;
        }

        if ($data !== []) {
            $model    = new DepedKpiReportModel();
            $existing = $model->forYear($schoolYear);
            $data['school_year'] = $schoolYear;
            $data['source_file'] = basename($filePath);

            if ($existing !== null) {
                $model->update($existing['id'], $data);
            } else {
                $model->insert($data);
            }
        }

        if ($enrollmentByGrade !== []) {
            $this->saveEnrollmentByGrade($schoolYear, $enrollmentByGrade, $summary);
        }

        $summary['matched'] = array_values(array_unique($summary['matched']));

        return $summary;
    }

    /** @return Table[] */
    private function findTables(AbstractContainer $container): array
    {
        $tables = [];
        foreach ($container->getElements() as $element) {
            if ($element instanceof Table) {
                $tables[] = $element;
            } elseif ($element instanceof AbstractContainer) {
                $tables = array_merge($tables, $this->findTables($element));
            }
        }

        return $tables;
    }

    private function cellText(Cell $cell): string
    {
        $text = '';
        foreach ($cell->getElements() as $element) {
            if (method_exists($element, 'getText')) {
                $value = $element->getText();
                $text .= is_array($value) ? implode(' ', $value) : (string) $value;
            }
        }

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    private function parsePercent(string $value): ?float
    {
        $clean = trim(str_replace('%', '', $value));
        if (! is_numeric($clean)) {
            return null;
        }

        return round((float) $clean, 2);
    }

    private function parseInt(string $value): ?int
    {
        $clean = trim($value);
        if ($clean === '' || ! is_numeric($clean)) {
            return null;
        }

        return (int) $clean;
    }

    /** @param array<string,mixed> $data */
    private function parseEnrollment(string $value, array &$data, array &$summary): void
    {
        // e.g. "698" or "698 = male 367 female 331" (older document format)
        if (preg_match('/(\d+)/', $value, $totalMatch) !== 1) {
            $summary['warnings'][] = "Could not parse enrolment total from '{$value}'.";

            return;
        }
        $data['enrolment_total'] = (int) $totalMatch[1];

        $summary['matched'][] = 'ENROLMENT';
    }

    /**
     * Detects a "Grade Level / Total" grid and parses its data rows.
     * Returns null if the table doesn't look like this grid.
     *
     * @return array<string,int>|null grade level => total students
     */
    private function tryParseGradeLevelTable(Table $table): ?array
    {
        $rows        = $table->getRows();
        $headerIndex = null;
        $columnRoles = [];

        foreach ($rows as $i => $row) {
            $roles = [];
            foreach ($row->getCells() as $col => $cell) {
                $normalized = strtoupper(trim($this->cellText($cell)));
                if (in_array($normalized, ['GRADE LEVEL', 'GRADE'], true)) {
                    $roles[$col] = 'grade';
                } elseif ($normalized === 'TOTAL') {
                    $roles[$col] = 'total';
                }
            }

            $found = array_values($roles);
            if (in_array('grade', $found, true) && in_array('total', $found, true)) {
                $headerIndex = $i;
                $columnRoles = $roles;
                break;
            }
        }

        if ($headerIndex === null) {
            return null;
        }

        $gradeByTitle = [];
        foreach (self::GRADE_LEVELS as $grade) {
            $gradeByTitle[strtoupper($grade)] = $grade;
        }

        $gradeCol = array_search('grade', $columnRoles, true);
        $totalCol = array_search('total', $columnRoles, true);

        $result = [];

        for ($k = $headerIndex + 1, $rowCount = count($rows); $k < $rowCount; $k++) {
            $cells      = $rows[$k]->getCells();
            $gradeText  = isset($cells[$gradeCol]) ? strtoupper(trim($this->cellText($cells[$gradeCol]))) : '';
            $gradeLevel = $gradeByTitle[$gradeText] ?? null;

            if ($gradeLevel === null) {
                break; // end of the grid
            }

            $total = isset($cells[$totalCol]) ? $this->parseInt($this->cellText($cells[$totalCol])) : null;

            if ($total === null) {
                continue; // blank row — not filled in yet
            }

            $result[$gradeLevel] = $total;
        }

        return $result;
    }

    /** @param array<string,int> $enrollmentByGrade grade level => total students */
    private function saveEnrollmentByGrade(string $schoolYear, array $enrollmentByGrade, array &$summary): void
    {
        $model = new EnrollmentByLevelModel();

        foreach ($enrollmentByGrade as $gradeLevel => $total) {
            $existing = $model->where('school_year', $schoolYear)->where('grade_level', $gradeLevel)->first();

            $rowData = [
                'school_year' => $schoolYear,
                'grade_level' => $gradeLevel,
                'students'    => $total,
            ];

            if ($existing !== null) {
                $model->update($existing['id'], $rowData);
            } else {
                $rowData['sections'] = 0; // not reported by this document
                $model->insert($rowData);
            }

            $summary['matched'][] = strtoupper($gradeLevel) . ' ENROLLMENT';
        }
    }
}

<?php

namespace App\Libraries;

use App\Models\KpiReportIndicatorModel;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\IOFactory;

/**
 * Parses a DepEd-style "Key Performance Indicator" Word report — a table of
 * Indicator/Percentage rows (Gross/Net Enrolment Rate, Cohort Survival,
 * Repetition, Promotion, Retention, Graduation, Completion, Transition,
 * Drop Out Rate, and an "Enrolment" row like "698 = male 367 female 331")
 * — and upserts the matched values into `kpi_report_indicators` for a
 * given school year (the document doesn't reliably state its own year, so
 * the caller supplies it, same as the MPS Excel importer).
 */
class EnrollmentKpiDocxImporter
{
    /** Normalized (uppercase, spelling-tolerant) indicator title => DB column. */
    private const INDICATOR_COLUMNS = [
        'GROSS ENROLMENT RATE'  => 'gross_enrollment_rate',
        'GROSS ENROLLMENT RATE' => 'gross_enrollment_rate',
        'NET ENROLMENT RATE'    => 'net_enrollment_rate',
        'NET ENROLLMENT RATE'   => 'net_enrollment_rate',
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

        $data = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($this->findTables($section) as $table) {
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

                    $data[$column]       = $numeric;
                    $summary['matched'][] = $label;
                }
            }
        }

        if ($data === []) {
            $summary['errors'][] = 'No recognizable KPI indicators were found in the uploaded document.';

            return $summary;
        }

        $model    = new KpiReportIndicatorModel();
        $existing = $model->forYear($schoolYear);
        $data['school_year'] = $schoolYear;

        if ($existing !== null) {
            $model->update($existing['id'], $data);
        } else {
            $model->insert($data);
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

    /** @param array<string,mixed> $data */
    private function parseEnrollment(string $value, array &$data, array &$summary): void
    {
        // e.g. "698 = male 367 female 331"
        if (preg_match('/(\d+)/', $value, $totalMatch) !== 1) {
            $summary['warnings'][] = "Could not parse enrolment total from '{$value}'.";

            return;
        }
        $data['enrollment_total'] = (int) $totalMatch[1];

        if (preg_match('/\bmale\s+(\d+)/i', $value, $maleMatch) === 1) {
            $data['enrollment_male'] = (int) $maleMatch[1];
        }
        if (preg_match('/\bfemale\s+(\d+)/i', $value, $femaleMatch) === 1) {
            $data['enrollment_female'] = (int) $femaleMatch[1];
        }

        $summary['matched'][] = 'ENROLMENT';
    }
}

<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\EnrollmentSheetImporter;
use App\Models\TeacherSubjectModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * "Add Enrollment" on the admin dashboard: upload the school's per-section
 * enrollment sheet (GRADE 7–10 blocks of MALE / FEMALE / TOTAL), or download
 * a blank template in that same layout.
 */
class Enrollment extends BaseController
{
    private const GRADE_LEVELS = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];

    // Colours from the school's own sheet.
    private const HEADER_FILL = '93C47D'; // green MALE/FEMALE/TOTAL titles
    private const TOTAL_FILL  = '6FA8DC'; // blue per-grade TOTAL row
    private const GRAND_FILL  = 'FFFF00'; // yellow GRAND TOTAL

    /** A school year runs June to May: June–Dec fall in its first year, Jan–May in its second. */
    private const SCHOOL_YEAR_START_MONTH = 6;

    public function import()
    {
        if (! hasRole('admin')) {
            return redirect()->to('/dashboard');
        }

        $isAjax   = $this->request->isAJAX();
        $redirect = '/dashboard';
        $fail     = fn (string $msg) => $isAjax
            ? $this->ajaxError($msg)
            : redirect()->to($redirect)->with('flash', ['type' => 'danger', 'msg' => $msg]);

        $year = trim((string) $this->request->getPost('school_year'));
        if (! self::isValidYear($year)) {
            return $fail('School year must be in YYYY-YYYY format with consecutive years (e.g. 2026-2027).');
        }

        $redirect = '/dashboard?year=' . urlencode($year);
        $file     = $this->request->getFile('import_file');

        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return $fail('Please choose a valid Excel file to upload.');
        }

        // Client extension, not getExtension(): .xls/.csv exports often sniff as octet-stream/text.
        if (! in_array(strtolower($file->getClientExtension()), ['xlsx', 'xls', 'csv'], true)) {
            return $fail('Only .xlsx, .xls or .csv files are supported.');
        }

        $uploadPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'enrollment_imports';
        if (! is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $fileName = date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientName());
        if (! $file->move($uploadPath, $fileName)) {
            return $fail('Could not save the uploaded file.');
        }

        $importer = new EnrollmentSheetImporter();

        try {
            $parsed = $importer->parse($uploadPath . DIRECTORY_SEPARATOR . $fileName);
        } catch (\Throwable $e) {
            return $fail('Could not read the file: ' . $e->getMessage());
        }

        if ($parsed['errors'] !== []) {
            return $fail(implode(' ', $parsed['errors']));
        }

        // A title row like "SY 2026-2027 Enrollment" must agree with the chosen year.
        if ($parsed['file_year'] !== null && $parsed['file_year'] !== $year) {
            return $fail("This file is labelled SY {$parsed['file_year']}, but you chose SY {$year}. Pick the matching school year, or fix the title in the file.");
        }

        // One snapshot per month: the latest-dated sheet of each month wins
        // (e.g. "June 9" … "June 23" -> June uses "June 23"); on a tie, the later sheet.
        $byMonth = [];
        foreach ($parsed['sheets'] as $order => $sheet) {
            $monthNumber = $sheet['month'];
            if ($monthNumber === null) {
                return $fail("Sheet \"{$sheet['title']}\" has no date in its name or title. Rename the sheet to its count date (e.g. \"June 10\") and upload again.");
            }

            $month = self::yearMonth($year, $monthNumber);
            $rank  = [$sheet['day'] ?? 0, $order];
            if (! isset($byMonth[$month]) || $rank >= $byMonth[$month]['rank']) {
                $byMonth[$month] = $sheet + ['rank' => $rank];
            }
        }
        ksort($byMonth);

        try {
            foreach ($byMonth as $month => $sheet) {
                $importer->save($year, $month, $sheet['grades']);
            }
        } catch (\Throwable $e) {
            return $fail('Import failed: ' . $e->getMessage());
        }

        $parts    = [];
        $warnings = [];
        foreach ($byMonth as $month => $sheet) {
            $parts[]  = self::monthLabel($month) . ': ' . number_format(array_sum(array_column($sheet['grades'], 'total'))) . " students (sheet \"{$sheet['title']}\")";
            $warnings = array_merge($warnings, $sheet['warnings']);
        }

        $sheetCount = count($parsed['sheets']);
        $message    = 'Enrollment saved for SY ' . $year . ' — ' . count($byMonth) . ' month(s) from ' . $sheetCount . ' sheet(s): ' . implode('; ', $parts) . '.';
        if ($sheetCount > count($byMonth)) {
            $message .= ' For months with several sheets, the latest date was used.';
        }
        if ($parsed['skipped'] !== []) {
            $message .= ' Skipped (no enrollment numbers): ' . implode(', ', $parsed['skipped']) . '.';
        }
        if ($warnings !== []) {
            $message .= ' ' . implode(' ', $warnings);
        }

        $redirect .= '&month=' . array_key_last($byMonth);

        if ($isAjax) {
            return $this->ajaxSuccess($message, ['redirect' => $redirect]);
        }

        session()->setFlashdata('flash', ['type' => $warnings === [] ? 'success' : 'warning', 'msg' => $message]);

        return redirect()->to($redirect);
    }

    /** Blank enrollment sheet in the school's layout, sections pre-filled from teachers' subject loads. */
    public function template()
    {
        if (! hasRole('admin')) {
            return redirect()->to('/dashboard');
        }

        $year = trim((string) $this->request->getGet('year'));
        if (! self::isValidYear($year)) {
            $year = null;
        }

        $monthNumber = (int) $this->request->getGet('month');
        $monthName   = $monthNumber >= 1 && $monthNumber <= 12 ? strtoupper(date('F', mktime(0, 0, 0, $monthNumber, 1))) : null;

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Enrollment');

        $sheet->setCellValue('A1', ($year ? "SY {$year} ENROLLMENT" : 'SY ____-____ ENROLLMENT') . ($monthName ? " - {$monthName}" : ''));
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sections   = $this->knownSections();
        $row        = 3;
        $totalRows  = [];

        foreach (self::GRADE_LEVELS as $grade) {
            $sheet->fromArray([strtoupper($grade), 'MALE', 'FEMALE', 'TOTAL'], null, "A{$row}");
            $this->fill($sheet, "B{$row}:D{$row}", self::HEADER_FILL);
            $headerRow = $row++;

            // Pre-filled section names, plus a couple of spare rows for new sections.
            $names = $sections[$grade] ?? [];
            $names = array_merge($names, array_fill(0, max(2, 4 - count($names)), ''));
            $first = $row;

            foreach ($names as $name) {
                $sheet->setCellValue("A{$row}", $name);
                $sheet->setCellValue("D{$row}", "=IF(COUNT(B{$row}:C{$row})=0,\"\",SUM(B{$row}:C{$row}))");
                $row++;
            }
            $last = $row - 1;

            $sheet->setCellValue("A{$row}", 'TOTAL');
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            foreach (['B', 'C', 'D'] as $col) {
                $sheet->setCellValue("{$col}{$row}", "=SUM({$col}{$first}:{$col}{$last})");
            }
            $this->fill($sheet, "B{$row}:D{$row}", self::TOTAL_FILL);
            $totalRows[] = $row;

            $this->borders($sheet, "A{$headerRow}:D{$row}");
            $row++;
        }

        $sheet->setCellValue("A{$row}", 'GRAND TOTAL');
        foreach (['B', 'C', 'D'] as $col) {
            $sheet->setCellValue("{$col}{$row}", '=' . implode('+', array_map(static fn (int $r) => "{$col}{$r}", $totalRows)));
        }
        $this->fill($sheet, "B{$row}:D{$row}", self::GRAND_FILL);
        $this->borders($sheet, "A{$row}:D{$row}");

        $sheet->getStyle("B3:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getColumnDimension('A')->setWidth(34);
        foreach (['B', 'C', 'D'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(14);
        }

        $note = $row + 2;
        $sheet->setCellValue("A{$note}", 'Fill in MALE and FEMALE per section; TOTAL columns add up automatically. Add or remove section rows inside a grade as needed.');
        $sheet->getStyle("A{$note}")->getFont()->setItalic(true)->setSize(9);

        $tempFile = tempnam(sys_get_temp_dir(), 'enr') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tempFile);

        $downloadName = ($year ? "SY {$year} Enrollment" : 'Enrollment Template') . ($monthName ? ' - ' . ucfirst(strtolower($monthName)) : '') . '.xlsx';

        return $this->response->download($tempFile, null)->setFileName($downloadName);
    }

    /** "2026-2027" + 6 -> "2026-06"; "2026-2027" + 3 -> "2027-03". */
    private static function yearMonth(string $schoolYear, int $monthNumber): string
    {
        $calendarYear = (int) substr($schoolYear, $monthNumber >= self::SCHOOL_YEAR_START_MONTH ? 0 : 5, 4);

        return sprintf('%04d-%02d', $calendarYear, $monthNumber);
    }

    private static function monthLabel(string $yearMonth): string
    {
        return date('F Y', strtotime($yearMonth . '-01'));
    }

    private static function isValidYear(string $year): bool
    {
        return preg_match('/^(\d{4})-(\d{4})$/', $year, $m) === 1 && (int) $m[2] === (int) $m[1] + 1;
    }

    /** @return array<string, string[]> grade level => upper-cased section names from teachers' subject loads */
    private function knownSections(): array
    {
        $rows = (new TeacherSubjectModel())
            ->select('grade_level, section')
            ->where('section IS NOT NULL')->where('section !=', '')
            ->distinct()
            ->orderBy('section')
            ->findAll();

        $out = [];
        foreach ($rows as $r) {
            $out[$r['grade_level']][] = strtoupper($r['section']);
        }

        return array_map(static fn (array $names) => array_values(array_unique($names)), $out);
    }

    private function fill($sheet, string $range, string $rgb): void
    {
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
    }

    private function borders($sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }
}

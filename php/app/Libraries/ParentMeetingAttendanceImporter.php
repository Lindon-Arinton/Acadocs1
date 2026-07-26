<?php

namespace App\Libraries;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Reads an attendance sheet where each data row is one parent/guardian who
 * attended (e.g. Name, Student, Grade/Section, Signature columns) with a
 * single header row. Actual attendance = count of non-blank data rows.
 */
class ParentMeetingAttendanceImporter
{
    public function countAttendees(string $filePath): int
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, false);

        // First row is the header (e.g. Name, Student, Grade/Section, Signature).
        array_shift($rows);

        $count = 0;
        foreach ($rows as $row) {
            $firstCell = trim((string) ($row[0] ?? ''));
            if ($firstCell !== '') {
                $count++;
            }
        }

        return $count;
    }
}

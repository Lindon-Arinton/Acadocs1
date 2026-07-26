<?php

namespace App\Libraries;

use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpWord\Settings as PhpWordSettings;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;
use ZipArchive;

/**
 * Bulk-fills a .docx certificate template (containing ${Field} merge
 * placeholders) once per row of an uploaded Excel recipient list, and
 * bundles every generated certificate into a single zip.
 */
class CertificateGenerator
{
    /**
     * Merge fields (the ${...} placeholders) found in a .docx template.
     *
     * @return string[]
     */
    public function detectFields(string $templatePath): array
    {
        $fields = (new TemplateProcessor($templatePath))->getVariables();
        $fields = array_values(array_unique($fields));
        sort($fields);

        return $fields;
    }

    /**
     * @return array{count:int, zipPath:string, matchedFields:string[], unmatchedFields:string[]}
     */
    public function generateZip(string $templatePath, string $excelPath, string $workDir, ?OfficeConverter $converter = null): array
    {
        $fields = $this->detectFields($templatePath);

        if ($fields === []) {
            throw new RuntimeException('This template has no ${Field} placeholders to fill in. Open it in Word and add some (e.g. ${Name}) first.');
        }

        $rows = SpreadsheetIOFactory::load($excelPath)->getActiveSheet()->toArray(null, true, true, false);

        if (count($rows) < 2) {
            throw new RuntimeException('The Excel file needs a header row plus at least one recipient row.');
        }

        $headers = array_map(static fn ($h) => trim((string) $h), array_shift($rows));

        // Match template fields to spreadsheet columns by normalized name, so
        // punctuation/spacing differences (a "School/Office" column heading vs
        // a ${School_Office} placeholder — "/" isn't legal inside a macro name)
        // don't force the admin to make the two spellings identical.
        $normalize = static fn (string $s): string => strtolower(preg_replace('/[^a-z0-9]+/i', '', $s) ?? '');

        $columnForField = [];
        foreach ($fields as $field) {
            $key = $normalize($field);
            foreach ($headers as $col => $header) {
                if ($normalize($header) === $key) {
                    $columnForField[$field] = $col;
                    break;
                }
            }
        }

        $unmatchedFields = array_values(array_diff($fields, array_keys($columnForField)));

        $nameField = null;
        foreach ($fields as $field) {
            if ($normalize($field) === 'name') {
                $nameField = $field;
                break;
            }
        }

        // PhpWord leaves merge values unescaped by default, which corrupts the
        // document's XML the moment a recipient's name/school contains "&",
        // "<", etc. This makes it escape them the same way any XML writer would.
        PhpWordSettings::setOutputEscapingEnabled(true);

        $outputDir = $workDir . DIRECTORY_SEPARATOR . 'output';
        mkdir($outputDir, 0755, true);

        $useConverter = $converter !== null && $converter->isAvailable();
        $usedNames    = [];
        $count        = 0;

        foreach ($rows as $row) {
            if (implode('', array_map('trim', array_map('strval', $row))) === '') {
                continue; // blank trailing row
            }

            $processor = new TemplateProcessor($templatePath);

            foreach ($fields as $field) {
                $value = isset($columnForField[$field]) ? trim((string) ($row[$columnForField[$field]] ?? '')) : '';
                $processor->setValue($field, $value);
            }

            $label    = $nameField !== null ? ($row[$columnForField[$nameField] ?? ''] ?? '') : '';
            $baseName = $this->uniqueFileName((string) $label ?: ('Recipient_' . ($count + 1)), $usedNames);

            $docxPath = $outputDir . DIRECTORY_SEPARATOR . $baseName . '.docx';
            $processor->saveAs($docxPath);

            if ($useConverter) {
                $pdfPath = $converter->convert($docxPath, 'pdf', $outputDir);
                if ($pdfPath) {
                    unlink($docxPath);
                }
            }

            $count++;
        }

        if ($count === 0) {
            throw new RuntimeException('No recipient rows found in the uploaded Excel file.');
        }

        $zipPath = $workDir . DIRECTORY_SEPARATOR . 'certificates.zip';
        $zip     = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach (glob($outputDir . DIRECTORY_SEPARATOR . '*') as $generated) {
            $zip->addFile($generated, basename($generated));
        }
        $zip->close();

        return [
            'count'           => $count,
            'zipPath'         => $zipPath,
            'matchedFields'   => array_keys($columnForField),
            'unmatchedFields' => $unmatchedFields,
        ];
    }

    /**
     * @param array<string,true> $used
     */
    private function uniqueFileName(string $label, array &$used): string
    {
        $base = preg_replace('/[^A-Za-z0-9 _-]/', '', $label) ?? '';
        $base = trim(preg_replace('/\s+/', '_', $base) ?? '');
        if ($base === '') {
            $base = 'Recipient';
        }

        $name = $base;
        $i    = 2;
        while (isset($used[$name])) {
            $name = $base . '_' . $i;
            $i++;
        }
        $used[$name] = true;

        return $name;
    }
}

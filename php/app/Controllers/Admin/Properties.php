<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Teacher\PerformanceMps;
use App\Models\RoomPropertyModel;
use App\Models\TeacherSubjectModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class Properties extends BaseController
{
    public function index()
    {
        $model = new RoomPropertyModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX();

            if (! hasRole('teacher')) {
                return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to('/property-management');
            }

            $action  = $this->request->getPost('action');
            $message = null;

            try {
                if ($action === 'add') {
                    $grade   = (string) $this->request->getPost('grade');
                    $section = (string) $this->request->getPost('section');
                    if (! in_array($section, $this->sectionsByGrade()[$grade] ?? [], true)) {
                        return $isAjax ? $this->ajaxError('Please pick a valid grade and section.') : redirect()->to('/property-management');
                    }

                    $model->insert([
                        'section'          => $section,
                        'grade'            => $grade,
                        'item_name'        => $this->request->getPost('item_name'),
                        'quantity'         => (int) ($this->request->getPost('quantity') ?: 1),
                        'condition_status' => $this->request->getPost('condition_status'),
                        'uploaded_by'      => currentUser()['name'] ?? null,
                    ]);
                    $message = 'Item added successfully.';
                } elseif ($action === 'delete') {
                    $model->delete((int) $this->request->getPost('id'));
                    $message = 'Item removed.';
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/property-management');
            }

            if ($isAjax) {
                return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
            }

            session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message ?? '']);

            return redirect()->to('/property-management');
        }

        $grade     = $this->request->getGet('grade') ?? 'all';
        $section   = $this->request->getGet('section') ?? 'all';
        $condition = $this->request->getGet('condition') ?? 'all';

        // Section choices follow the grade filter: that grade's sections, or every
        // grade+section when "All Grades" is picked. A section that doesn't belong
        // to the chosen grade (e.g. left over after switching grade) is ignored.
        $sectionOptions = (new RoomPropertyModel())->distinct()->select('grade, section')
            ->orderBy('grade')->orderBy('section')->findAll();
        if ($grade !== 'all') {
            $sectionOptions = array_values(array_filter($sectionOptions, static fn ($o) => $o['grade'] === $grade));
        }
        if ($section !== 'all' && ! in_array($section, array_column($sectionOptions, 'section'), true)) {
            $section = 'all';
        }
        $search    = trim($this->request->getGet('q') ?? '');
        $sort      = $this->request->getGet('sort') ?? 'grade_az';

        $items = $this->filteredItems($grade, $section, $condition, $search, $sort);

        $grades     = array_column((new RoomPropertyModel())->distinct()->select('grade')->orderBy('grade')->findAll(), 'grade');
        $conditions = ['Excellent', 'Good', 'Fair', 'Poor'];

        $condStats = array_count_values(array_column($items, 'condition_status'));

        return view('pages/admin/properties', [
            'pageTitle'  => 'Property Management',
            'items'      => $items,
            'grade'      => $grade,
            'section'    => $section,
            'sectionOptions' => $sectionOptions,
            'condition'  => $condition,
            'search'     => $search,
            'sort'       => $sort,
            'grades'     => $grades,
            'sectionsByGrade' => $this->sectionsByGrade(),
            'conditions' => $conditions,
            'condStats'  => $condStats,
            'flash'      => session()->getFlashdata('flash'),
        ]);
    }

    /**
     * Downloads the inventory as a list report — Excel (?format=xlsx) or Word
     * (?format=docx) — honouring the same grade / section / condition / search
     * filters as the page. Items are grouped by grade and section, numbered,
     * with a per-condition summary at the end.
     */
    public function export()
    {
        $format = $this->request->getGet('format') === 'docx' ? 'docx' : 'xlsx';

        $grade     = $this->request->getGet('grade') ?: 'all';
        $section   = $this->request->getGet('section') ?: 'all';
        $condition = $this->request->getGet('condition') ?: 'all';
        $search    = trim($this->request->getGet('q') ?? '');

        // Always grouped by grade → section → item, whatever the on-screen sort.
        $items = $this->filteredItems($grade, $section, $condition, $search, 'grade_az');

        $groups = [];
        foreach ($items as $item) {
            $groups[$item['grade'] . ' — ' . $item['section']][] = $item;
        }

        $scope = array_filter([
            $grade !== 'all' ? $grade : 'All grades',
            $section !== 'all' ? 'Section ' . $section : null,
            $condition !== 'all' ? $condition . ' condition' : null,
            $search !== '' ? 'Search: "' . $search . '"' : null,
        ]);
        $meta = [
            'title'     => 'Property Inventory Report',
            'scope'     => implode(' · ', $scope),
            'generated' => 'Generated ' . date('F d, Y h:i A') . ' by ' . (currentUser()['name'] ?? ''),
            'totals'    => [
                'lines'      => count($items),
                'quantity'   => array_sum(array_map(static fn ($i) => (int) ($i['quantity'] ?? 1), $items)),
                'conditions' => array_count_values(array_column($items, 'condition_status')),
            ],
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'prop') . '.' . $format;
        $format === 'docx' ? $this->writeDocx($tempFile, $groups, $meta) : $this->writeXlsx($tempFile, $groups, $meta);

        return $this->response->download($tempFile, null)->setFileName('Property Inventory - ' . date('Y-m-d') . '.' . $format);
    }

    private const REPORT_COLUMNS = ['No.', 'Item', 'Qty', 'Condition', 'Date Added', 'Uploaded By'];

    /** @param array<string,list<array<string,mixed>>> $groups */
    private function writeXlsx(string $path, array $groups, array $meta): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet()->setTitle('Inventory');

        $sheet->setCellValue('A1', $meta['title'])->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('800000');
        $sheet->setCellValue('A2', $meta['scope'])->mergeCells('A2:F2');
        $sheet->setCellValue('A3', $meta['generated'])->mergeCells('A3:F3');
        $sheet->getStyle('A2:A3')->getFont()->setSize(9)->getColor()->setRGB('555555');

        $row = 5;
        if ($groups === []) {
            $sheet->setCellValue("A{$row}", 'No items match the selected filters.');
        }
        foreach ($groups as $label => $rows) {
            $sheet->setCellValue("A{$row}", $label)->mergeCells("A{$row}:F{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('800000');
            $row++;

            $sheet->fromArray(self::REPORT_COLUMNS, null, "A{$row}");
            $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3E8E8');
            $first = $row;
            $row++;

            foreach ($rows as $n => $item) {
                $sheet->fromArray([
                    $n + 1,
                    $item['item_name'],
                    (int) ($item['quantity'] ?? 1),
                    $item['condition_status'],
                    date('M d, Y', strtotime($item['created_at'])),
                    $item['uploaded_by'] ?? '—',
                ], null, "A{$row}");
                $row++;
            }
            $sheet->getStyle("A{$first}:F" . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A{$first}:A" . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$first}:D" . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $sheet->setCellValue("A{$row}", 'Summary')->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        foreach ($this->summaryLines($meta['totals']) as [$label, $value]) {
            $sheet->setCellValue("B{$row}", $label);
            $sheet->setCellValue("C{$row}", $value);
            $row++;
        }

        foreach (['A' => 7, 'B' => 36, 'C' => 8, 'D' => 13, 'E' => 15, 'F' => 26] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        (new Xlsx($spreadsheet))->save($path);
    }

    /** @param array<string,list<array<string,mixed>>> $groups */
    private function writeDocx(string $path, array $groups, array $meta): void
    {
        $word    = new PhpWord();
        $word->setDefaultFontName('Calibri');
        $word->setDefaultFontSize(10);
        $doc     = $word->addSection(['marginLeft' => 900, 'marginRight' => 900, 'marginTop' => 900, 'marginBottom' => 900]);

        $doc->addText($meta['title'], ['bold' => true, 'size' => 16, 'color' => '800000'], ['alignment' => 'center']);
        $doc->addText($meta['scope'], ['size' => 9, 'color' => '555555'], ['alignment' => 'center']);
        $doc->addText($meta['generated'], ['size' => 9, 'color' => '555555'], ['alignment' => 'center', 'spaceAfter' => 240]);

        if ($groups === []) {
            $doc->addText('No items match the selected filters.', ['italic' => true]);
        }

        $widths = [700, 3600, 700, 1300, 1500, 2200]; // twips, ~10,000 total
        foreach ($groups as $label => $rows) {
            $doc->addText($label, ['bold' => true, 'size' => 12, 'color' => '800000'], ['spaceBefore' => 200, 'spaceAfter' => 80, 'keepNext' => true]);

            $table = $doc->addTable(['borderSize' => 6, 'borderColor' => 'BBBBBB', 'cellMargin' => 60]);
            $table->addRow(null, ['tblHeader' => true]);
            foreach (self::REPORT_COLUMNS as $i => $heading) {
                $table->addCell($widths[$i], ['bgColor' => 'F3E8E8'])->addText($heading, ['bold' => true]);
            }
            foreach ($rows as $n => $item) {
                $table->addRow();
                $cells = [
                    (string) ($n + 1),
                    $item['item_name'],
                    (string) (int) ($item['quantity'] ?? 1),
                    $item['condition_status'],
                    date('M d, Y', strtotime($item['created_at'])),
                    $item['uploaded_by'] ?? '—',
                ];
                foreach ($cells as $i => $text) {
                    $table->addCell($widths[$i])->addText((string) $text, null, in_array($i, [0, 2, 3], true) ? ['alignment' => 'center'] : []);
                }
            }
        }

        $doc->addText('Summary', ['bold' => true, 'size' => 12], ['spaceBefore' => 300, 'spaceAfter' => 80]);
        foreach ($this->summaryLines($meta['totals']) as [$label, $value]) {
            $doc->addText($label . ': ' . $value);
        }

        IOFactory::createWriter($word, 'Word2007')->save($path);
    }

    /** @return list<array{0:string,1:int}> */
    private function summaryLines(array $totals): array
    {
        $lines = [['Items listed', $totals['lines']], ['Total quantity', $totals['quantity']]];
        foreach (['Excellent', 'Good', 'Fair', 'Poor'] as $c) {
            $lines[] = [$c, $totals['conditions'][$c] ?? 0];
        }

        return $lines;
    }

    /** The inventory rows for the page's filters (shared by the list and its export). */
    private function filteredItems(string $grade, string $section, string $condition, string $search, string $sort): array
    {
        $builder = new RoomPropertyModel();
        if ($grade !== 'all') {
            $builder->where('grade', $grade);
        }
        if ($section !== 'all') {
            $builder->where('section', $section);
        }
        if ($condition !== 'all') {
            $builder->where('condition_status', $condition);
        }
        if ($search !== '') {
            $builder->groupStart()
                ->like('section', $search)
                ->orLike('item_name', $search)
                ->groupEnd();
        }

        match ($sort) {
            'item_az'   => $builder->orderBy('item_name', 'ASC'),
            'condition' => $builder->orderBy('condition_status', 'ASC')->orderBy('item_name', 'ASC'),
            'newest'    => $builder->orderBy('created_at', 'DESC'),
            default     => $builder->orderBy('grade', 'ASC')->orderBy('section', 'ASC')->orderBy('item_name', 'ASC'),
        };

        return $builder->findAll();
    }

    /**
     * Every section the school has, grouped by grade level — taken from the
     * teachers' subject loads (teacher_subjects), which is where sections are
     * defined. Drives the Add Item grade/section dropdowns.
     *
     * @return array<string,list<string>>
     */
    private function sectionsByGrade(): array
    {
        $map = array_fill_keys(PerformanceMps::GRADE_LEVELS, []);

        $rows = (new TeacherSubjectModel())->distinct()
            ->select('grade_level, section')
            ->where('section IS NOT NULL')
            ->where('section !=', '')
            ->orderBy('section')
            ->findAll();

        foreach ($rows as $row) {
            if (isset($map[$row['grade_level']])) {
                $map[$row['grade_level']][] = $row['section'];
            }
        }

        return $map;
    }
}

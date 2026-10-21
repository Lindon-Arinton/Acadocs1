<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Teacher\PerformanceMps;
use App\Models\NotificationModel;
use App\Models\PropertyAcknowledgementItemModel;
use App\Models\PropertyAcknowledgementModel;
use App\Models\PropertyConcernModel;
use App\Models\PropertyConditionLogModel;
use App\Models\RoomPropertyModel;
use App\Models\TeacherSubjectModel;
use App\Models\UserModel;
use CodeIgniter\Database\RawSql;
use CodeIgniter\Exceptions\PageNotFoundException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

/**
 * Property Management.
 *
 * - ADAS runs the inventory: adds/edits items, audits their condition
 *   (Working / Not Working), distributes each room's items to the section's
 *   adviser, issues Property Acknowledgment Receipts (PAR) and resolves
 *   the concerns advisers report.
 * - Teachers only see the items issued to them: they confirm receipt by
 *   approving the PAR (or return it), and report concerns about an item.
 * - Principal (admin) and secretary have read-only access.
 *
 * Timestamps use the database clock (NOW()), same as the tables'
 * CURRENT_TIMESTAMP defaults.
 */
class Properties extends BaseController
{
    public function index()
    {
        $model = new RoomPropertyModel();

        if ($this->request->getMethod() === 'POST') {
            return $this->handlePost($model);
        }

        $user      = currentUser();
        $userId    = (int) ($user['id'] ?? 0);
        $isTeacher = hasRole('teacher');
        $tab       = in_array($this->request->getGet('tab'), ['par', 'concerns'], true) ? $this->request->getGet('tab') : 'items';
        $grade     = $this->request->getGet('grade') ?? 'all';
        $section   = $this->request->getGet('section') ?? 'all';
        $condition = $this->request->getGet('condition') ?? 'all';
        $issued    = $isTeacher ? 'me' : ($this->request->getGet('issued') ?? 'all');
        $search    = trim($this->request->getGet('q') ?? '');
        $sort      = $this->request->getGet('sort') ?? 'grade_az';

        // Section choices follow the grade filter: that grade's sections, or every
        // grade+section when "All Grades" is picked. A section that doesn't belong
        // to the chosen grade (e.g. left over after switching grade) is ignored.
        // Teachers only get the grades/sections of their own items.
        $scoped = function () use ($isTeacher, $userId) {
            $m = new RoomPropertyModel();
            return $isTeacher ? $m->where('issued_to', $userId) : $m;
        };
        $sectionOptions = $scoped()->distinct()->select('grade, section')
            ->orderBy('grade')->orderBy('section')->findAll();
        if ($grade !== 'all') {
            $sectionOptions = array_values(array_filter($sectionOptions, static fn ($o) => $o['grade'] === $grade));
        }
        if ($section !== 'all' && ! in_array($section, array_column($sectionOptions, 'section'), true)) {
            $section = 'all';
        }

        $items = $this->filteredItems($grade, $section, $condition, $issued, $search, $sort);

        $grades    = array_column($scoped()->distinct()->select('grade')->orderBy('grade')->findAll(), 'grade');
        $condStats = array_count_values(array_column($items, 'condition_status'));

        $parModel = new PropertyAcknowledgementModel();
        $pars     = $parModel->listing($isTeacher ? $userId : null);
        $pendingParCount = count(array_filter($pars, static fn ($p) => $p['status'] === 'Pending'));

        $concernModel     = new PropertyConcernModel();
        $concerns         = $concernModel->listing($isTeacher ? $userId : null);
        $openConcernCount = count(array_filter($concerns, static fn ($c) => $c['status'] === 'Open'));
        $openByProperty   = $concernModel->openCountByProperty();

        $unassignedCount = (new RoomPropertyModel())->where('issued_to IS NULL')->countAllResults();

        // ADAS: how many items each teacher has that are not on a PAR yet.
        $unacknowledged = [];
        if (hasRole('adas')) {
            $rows = (new RoomPropertyModel())->select('issued_to, COUNT(*) AS n')
                ->where('issued_to IS NOT NULL')->where('par_id IS NULL')
                ->groupBy('issued_to')->findAll();
            foreach ($rows as $row) {
                $unacknowledged[(int) $row['issued_to']] = (int) $row['n'];
            }
        }

        return view('pages/admin/properties', [
            'pageTitle'       => 'Property Management',
            'tab'             => $tab,
            'items'           => $items,
            'grade'           => $grade,
            'section'         => $section,
            'sectionOptions'  => $sectionOptions,
            'condition'       => $condition,
            'issued'          => $issued,
            'search'          => $search,
            'sort'            => $sort,
            'grades'          => $grades,
            'sectionsByGrade' => $this->sectionsByGrade(),
            'conditions'      => RoomPropertyModel::CONDITIONS,
            'acquisitions'    => RoomPropertyModel::ACQUISITIONS,
            'teachers'        => $this->teachers(),
            'condStats'       => $condStats,
            'pars'            => $pars,
            'pendingParCount' => $pendingParCount,
            'concerns'         => $concerns,
            'openConcernCount' => $openConcernCount,
            'openByProperty'   => $openByProperty,
            'concernCategories' => PropertyConcernModel::CATEGORIES,
            'advisers'        => hasRole('adas') ? RoomPropertyModel::advisersBySection() : [],
            'unassignedCount' => $unassignedCount,
            'unacknowledged'  => $unacknowledged,
            'canManage'       => hasRole('adas'),
            'userId'          => $userId,
            'flash'           => session()->getFlashdata('flash'),
        ]);
    }

    /**
     * Condition history of one item, for the History modal.
     */
    public function history(int $id)
    {
        $item = (new RoomPropertyModel())->find($id);
        if (! $item || (hasRole('teacher') && (int) $item['issued_to'] !== (int) (currentUser()['id'] ?? 0))) {
            return $this->ajaxError('Item not found.', 404);
        }

        $logs = (new PropertyConditionLogModel())->historyFor([$id])[$id] ?? [];

        return $this->response->setJSON([
            'status' => 'success',
            'item'   => ['name' => $item['item_name'], 'grade' => $item['grade'], 'section' => $item['section']],
            'logs'   => array_map(static fn ($l) => [
                'from'    => $l['from_status'],
                'to'      => $l['to_status'],
                'remarks' => $l['remarks'],
                'by'      => $l['updated_by_name'] ?? '—',
                'at'      => date('M d, Y h:i A', strtotime($l['created_at'])),
            ], $logs),
        ]);
    }

    /**
     * Printable Property Acknowledgment Receipt.
     */
    public function par(int $id)
    {
        $par = (new PropertyAcknowledgementModel())->listing(null, $id)[0] ?? null;

        // Teachers may only open PARs issued to them.
        if (! $par || (hasRole('teacher') && (int) $par['issued_to'] !== (int) (currentUser()['id'] ?? 0))) {
            throw PageNotFoundException::forPageNotFound();
        }

        $users = new UserModel();

        return view('pages/admin/property_par', [
            'par'      => $par,
            'items'    => (new PropertyAcknowledgementItemModel())->where('par_id', $id)->orderBy('id')->findAll(),
            'issuedTo' => $users->find((int) $par['issued_to']),
            'issuedBy' => $par['issued_by'] ? $users->find((int) $par['issued_by']) : null,
        ]);
    }

    /**
     * Downloads the inventory as a list report — Excel (?format=xlsx) or Word
     * (?format=docx) — honouring the same grade / section / condition /
     * issued-to / search filters as the page. Items are grouped by grade and
     * section, numbered, with a per-condition summary at the end.
     */
    public function export()
    {
        $format = $this->request->getGet('format') === 'docx' ? 'docx' : 'xlsx';

        $grade     = $this->request->getGet('grade') ?: 'all';
        $section   = $this->request->getGet('section') ?: 'all';
        $condition = $this->request->getGet('condition') ?: 'all';
        $issued    = hasRole('teacher') ? 'me' : ($this->request->getGet('issued') ?: 'all');
        $search    = trim($this->request->getGet('q') ?? '');

        // Always grouped by grade → section → item, whatever the on-screen sort.
        $items = $this->filteredItems($grade, $section, $condition, $issued, $search, 'grade_az');

        $groups = [];
        foreach ($items as $item) {
            $groups[$item['grade'] . ' — ' . $item['section']][] = $item;
        }

        $issuedLabel = match (true) {
            $issued === 'me'                => 'Issued to me',
            $issued === 'none'              => 'Not yet issued',
            ctype_digit((string) $issued)   => 'Issued to ' . ((new UserModel())->find((int) $issued)['name'] ?? 'teacher'),
            default                         => null,
        };
        $scope = array_filter([
            $grade !== 'all' ? $grade : 'All grades',
            $section !== 'all' ? 'Section ' . $section : null,
            $condition !== 'all' ? $condition : null,
            $issuedLabel,
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

    private const REPORT_COLUMNS = ['No.', 'Item', 'Qty', 'Condition', 'Acquired As', 'Adviser', 'Last Checked'];

    /** One report row (same order as REPORT_COLUMNS). */
    private function reportRow(int $n, array $item): array
    {
        return [
            $n,
            $item['item_name'] . ($item['notes'] ? ' (' . $item['notes'] . ')' : ''),
            (int) ($item['quantity'] ?? 1),
            $item['condition_status'],
            RoomPropertyModel::acquisitionLabel($item),
            $item['issued_to_name'] ?? '—',
            date('M d, Y', strtotime($item['last_checked_at'] ?? $item['created_at'])),
        ];
    }

    /** @param array<string,list<array<string,mixed>>> $groups */
    private function writeXlsx(string $path, array $groups, array $meta): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet()->setTitle('Inventory');

        $sheet->setCellValue('A1', $meta['title'])->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('800000');
        $sheet->setCellValue('A2', $meta['scope'])->mergeCells('A2:G2');
        $sheet->setCellValue('A3', $meta['generated'])->mergeCells('A3:G3');
        $sheet->getStyle('A2:A3')->getFont()->setSize(9)->getColor()->setRGB('555555');

        $row = 5;
        if ($groups === []) {
            $sheet->setCellValue("A{$row}", 'No items match the selected filters.');
        }
        foreach ($groups as $label => $rows) {
            $sheet->setCellValue("A{$row}", $label)->mergeCells("A{$row}:G{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('800000');
            $row++;

            $sheet->fromArray(self::REPORT_COLUMNS, null, "A{$row}");
            $sheet->getStyle("A{$row}:G{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3E8E8');
            $first = $row;
            $row++;

            foreach ($rows as $n => $item) {
                $sheet->fromArray($this->reportRow($n + 1, $item), null, "A{$row}");
                $row++;
            }
            $sheet->getStyle("A{$first}:G" . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
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

        foreach (['A' => 7, 'B' => 36, 'C' => 8, 'D' => 16, 'E' => 20, 'F' => 24, 'G' => 14] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        (new Xlsx($spreadsheet))->save($path);
    }

    /** @param array<string,list<array<string,mixed>>> $groups */
    private function writeDocx(string $path, array $groups, array $meta): void
    {
        $word = new PhpWord();
        $word->setDefaultFontName('Calibri');
        $word->setDefaultFontSize(10);
        $doc = $word->addSection(['orientation' => 'landscape', 'marginLeft' => 900, 'marginRight' => 900, 'marginTop' => 900, 'marginBottom' => 900]);

        $doc->addText($meta['title'], ['bold' => true, 'size' => 16, 'color' => '800000'], ['alignment' => 'center']);
        $doc->addText($meta['scope'], ['size' => 9, 'color' => '555555'], ['alignment' => 'center']);
        $doc->addText($meta['generated'], ['size' => 9, 'color' => '555555'], ['alignment' => 'center', 'spaceAfter' => 240]);

        if ($groups === []) {
            $doc->addText('No items match the selected filters.', ['italic' => true]);
        }

        $widths = [700, 4200, 700, 1700, 2200, 2600, 1600]; // twips, landscape page
        foreach ($groups as $label => $rows) {
            $doc->addText($label, ['bold' => true, 'size' => 12, 'color' => '800000'], ['spaceBefore' => 200, 'spaceAfter' => 80, 'keepNext' => true]);

            $table = $doc->addTable(['borderSize' => 6, 'borderColor' => 'BBBBBB', 'cellMargin' => 60]);
            $table->addRow(null, ['tblHeader' => true]);
            foreach (self::REPORT_COLUMNS as $i => $heading) {
                $table->addCell($widths[$i], ['bgColor' => 'F3E8E8'])->addText($heading, ['bold' => true]);
            }
            foreach ($rows as $n => $item) {
                $table->addRow();
                foreach ($this->reportRow($n + 1, $item) as $i => $text) {
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
        foreach (RoomPropertyModel::CONDITIONS as $c) {
            $lines[] = [$c, $totals['conditions'][$c] ?? 0];
        }

        return $lines;
    }

    /**
     * The inventory rows for the page's filters (shared by the list and its
     * export), with the accountable teacher's name and current PAR attached.
     */
    private function filteredItems(string $grade, string $section, string $condition, string $issued, string $search, string $sort): array
    {
        $builder = (new RoomPropertyModel())->select('room_properties.*, u.name AS issued_to_name, p.par_no, p.status AS par_status')
            ->join('users u', 'u.id = room_properties.issued_to', 'left')
            ->join('property_acknowledgements p', 'p.id = room_properties.par_id', 'left');

        if ($grade !== 'all') {
            $builder->where('room_properties.grade', $grade);
        }
        if ($section !== 'all') {
            $builder->where('room_properties.section', $section);
        }
        if (in_array($condition, RoomPropertyModel::CONDITIONS, true)) {
            $builder->where('room_properties.condition_status', $condition);
        }
        if ($issued === 'me') {
            $builder->where('room_properties.issued_to', (int) (currentUser()['id'] ?? 0));
        } elseif ($issued === 'none') {
            $builder->where('room_properties.issued_to IS NULL');
        } elseif (ctype_digit((string) $issued)) {
            $builder->where('room_properties.issued_to', (int) $issued);
        }
        if ($search !== '') {
            $builder->groupStart()
                ->like('room_properties.section', $search)
                ->orLike('room_properties.item_name', $search)
                ->orLike('room_properties.notes', $search)
                ->orLike('u.name', $search)
                ->groupEnd();
        }

        match ($sort) {
            'item_az'   => $builder->orderBy('room_properties.item_name', 'ASC'),
            'condition' => $builder->orderBy('room_properties.condition_status', 'ASC')->orderBy('room_properties.item_name', 'ASC'),
            'issued_to' => $builder->orderBy('u.name', 'ASC')->orderBy('room_properties.item_name', 'ASC'),
            'newest'    => $builder->orderBy('room_properties.created_at', 'DESC'),
            default     => $builder->orderBy('room_properties.grade', 'ASC')->orderBy('room_properties.section', 'ASC')->orderBy('room_properties.item_name', 'ASC'),
        };

        return $builder->findAll();
    }

    private function handlePost(RoomPropertyModel $model)
    {
        $isAjax = $this->request->isAJAX();
        $fail   = fn (string $msg, int $code = 400) => $isAjax ? $this->ajaxError($msg, $code) : redirect()->to('/property-management');

        $action  = $this->request->getPost('action');
        $userId  = (int) (currentUser()['id'] ?? 0);
        $message = null;
        $extra   = [];

        // Teachers can only answer their PARs and report concerns; everything
        // else (inventory, audits, distribution) is the ADAS's job.
        $teacherActions = ['par_respond', 'report_concern'];
        if (! hasRole('adas') && ! (hasRole('teacher') && in_array($action, $teacherActions, true))) {
            return $fail('Only the ADAS can do this.', 403);
        }

        try {
            switch ($action) {
                case 'add':
                    $data = $this->itemFromPost();
                    if (is_string($data)) {
                        return $fail($data);
                    }
                    $condition = (string) $this->request->getPost('condition_status');
                    if (! in_array($condition, RoomPropertyModel::CONDITIONS, true)) {
                        return $fail('Please pick a valid condition.');
                    }

                    $id = (int) $model->insert($data + [
                        'condition_status' => $condition,
                        'uploaded_by'      => currentUser()['name'] ?? null,
                        'last_checked_at'  => new RawSql('NOW()'),
                        'last_checked_by'  => currentUser()['name'] ?? null,
                    ]);
                    (new PropertyConditionLogModel())->record($id, null, $condition, 'Item added');
                    $message = 'Item added' . ($data['issued_to'] ? '' : ' (no adviser assigned yet)') . '.';
                    break;

                case 'edit':
                    $item = $model->find((int) $this->request->getPost('id'));
                    if (! $item) {
                        return $fail('Item not found.', 404);
                    }
                    $data = $this->itemFromPost();
                    if (is_string($data)) {
                        return $fail($data);
                    }
                    // A reassigned item needs a new PAR from its new teacher.
                    if ((int) ($data['issued_to'] ?? 0) !== (int) ($item['issued_to'] ?? 0)) {
                        $data['par_id'] = null;
                    }
                    $model->update((int) $item['id'], $data + ['updated_at' => new RawSql('NOW()')]);
                    $message = 'Item updated.';
                    break;

                // ADAS audit: records the checked condition. The same condition
                // is allowed — it logs that the item was checked and still is.
                case 'update_condition':
                    $item = $model->find((int) $this->request->getPost('id'));
                    if (! $item) {
                        return $fail('Item not found.', 404);
                    }
                    $condition = (string) $this->request->getPost('condition_status');
                    $remarks   = trim((string) $this->request->getPost('remarks'));
                    if (! in_array($condition, RoomPropertyModel::CONDITIONS, true)) {
                        return $fail('Please pick a valid condition.');
                    }
                    $changed = $condition !== $item['condition_status'];
                    if ($changed && $condition === 'Not Working' && $remarks === '') {
                        return $fail('Please describe what is wrong with the item.');
                    }

                    $model->update((int) $item['id'], [
                        'condition_status' => $condition,
                        'last_checked_at'  => new RawSql('NOW()'),
                        'last_checked_by'  => currentUser()['name'] ?? null,
                        'updated_at'       => new RawSql('NOW()'),
                    ]);
                    (new PropertyConditionLogModel())->record(
                        (int) $item['id'],
                        $item['condition_status'],
                        $condition,
                        $changed ? $remarks : trim('Audited — still ' . $condition . '. ' . $remarks)
                    );
                    $message = $changed ? 'Condition updated to ' . $condition . '.' : 'Audit recorded — still ' . $condition . '.';
                    break;

                case 'delete':
                    $item = $model->find((int) $this->request->getPost('id'));
                    if (! $item) {
                        return $fail('Item not found.', 404);
                    }
                    $model->delete((int) $item['id']);
                    $message = 'Item removed.';
                    break;

                case 'report_concern':
                    $item = $model->find((int) $this->request->getPost('id'));
                    if (! $item || (int) $item['issued_to'] !== $userId) {
                        return $fail('This item is not issued to you.', 403);
                    }
                    $category = (string) $this->request->getPost('category');
                    $details  = trim((string) $this->request->getPost('details'));
                    if (! in_array($category, PropertyConcernModel::CATEGORIES, true)) {
                        return $fail('Please pick what the concern is about.');
                    }
                    if ($details === '') {
                        return $fail('Please describe the concern.');
                    }

                    $concernId = (int) (new PropertyConcernModel())->insert([
                        'property_id' => (int) $item['id'],
                        'reported_by' => $userId,
                        'category'    => $category,
                        'details'     => $details,
                        'status'      => 'Open',
                    ]);
                    $notifications = new NotificationModel();
                    foreach ((new UserModel())->active()->select('id')->where('role', 'adas')->findAll() as $adas) {
                        $notifications->insert([
                            'user_id'  => (int) $adas['id'],
                            'type'     => 'property_concern',
                            'title'    => 'Property concern: ' . $item['item_name'] . ' (' . $item['section'] . ')',
                            'sub'      => $category . ' — reported by ' . (currentUser()['name'] ?? 'adviser'),
                            'url'      => base_url('property-management?tab=concerns'),
                            'ref_type' => 'property_concern',
                            'ref_id'   => $concernId,
                        ]);
                    }
                    $message = 'Concern sent to the ADAS.';
                    break;

                case 'resolve_concern':
                    $concernModel = new PropertyConcernModel();
                    $concern      = $concernModel->find((int) $this->request->getPost('concern_id'));
                    if (! $concern) {
                        return $fail('Concern not found.', 404);
                    }
                    if ($concern['status'] !== 'Open') {
                        return $fail('This concern was already resolved.');
                    }
                    $response = trim((string) $this->request->getPost('response'));
                    if ($response === '') {
                        return $fail('Please say how the concern was resolved.');
                    }

                    $concernModel->update((int) $concern['id'], [
                        'status'      => 'Resolved',
                        'response'    => $response,
                        'resolved_by' => $userId ?: null,
                        'resolved_at' => new RawSql('NOW()'),
                    ]);
                    if ($concern['reported_by']) {
                        $item = $model->find((int) $concern['property_id']);
                        (new NotificationModel())->insert([
                            'user_id'  => (int) $concern['reported_by'],
                            'type'     => 'property_concern',
                            'title'    => 'Concern resolved: ' . ($item['item_name'] ?? 'property item'),
                            'sub'      => mb_strimwidth($response, 0, 120, '…'),
                            'url'      => base_url('property-management?tab=concerns'),
                            'ref_type' => 'property_concern',
                            'ref_id'   => (int) $concern['id'],
                        ]);
                    }
                    $message = 'Concern marked as resolved.';
                    break;

                case 'generate_par':
                    if (! hasRole('adas')) {
                        return $fail('Only the ADAS can issue a PAR.', 403);
                    }
                    $teacherId = (int) $this->request->getPost('teacher_id');
                    $teacher   = (new UserModel())->where('role', 'teacher')->find($teacherId);
                    if (! $teacher) {
                        return $fail('Please pick a teacher.');
                    }

                    $parItems = (new RoomPropertyModel())->where('issued_to', $teacherId)->where('par_id IS NULL')
                        ->orderBy('grade')->orderBy('section')->orderBy('item_name')->findAll();
                    if ($parItems === []) {
                        return $fail($teacher['name'] . ' has no items waiting for a PAR.');
                    }

                    [$parId, $parNo] = $this->issuePar($teacherId, $userId, $parItems);
                    (new NotificationModel())->insert([
                        'user_id'  => $teacherId,
                        'type'     => 'property_par',
                        'title'    => 'Confirm receipt: ' . $parNo,
                        'sub'      => count($parItems) . ' ' . (count($parItems) === 1 ? 'item' : 'items') . ' issued to you',
                        'url'      => base_url('property-management?tab=par'),
                        'ref_type' => 'property_par',
                        'ref_id'   => $parId,
                    ]);
                    $message = $parNo . ' issued to ' . $teacher['name'] . ' to confirm receipt.';
                    $extra   = ['redirect' => base_url('property-management?tab=par')];
                    break;

                case 'par_respond':
                    $parModel = new PropertyAcknowledgementModel();
                    $par      = $parModel->find((int) $this->request->getPost('par_id'));
                    if (! $par || (int) $par['issued_to'] !== $userId) {
                        return $fail('This PAR was not issued to you.', 403);
                    }
                    if ($par['status'] !== 'Pending') {
                        return $fail('This PAR was already ' . strtolower($par['status']) . '.');
                    }
                    $decision = $this->request->getPost('decision') === 'return' ? 'Returned' : 'Approved';
                    $remarks  = trim((string) $this->request->getPost('remarks'));
                    if ($decision === 'Returned' && $remarks === '') {
                        return $fail('Please say why you are returning the PAR.');
                    }

                    $parModel->update((int) $par['id'], [
                        'status'       => $decision,
                        'remarks'      => $remarks !== '' ? $remarks : null,
                        'responded_at' => new RawSql('NOW()'),
                    ]);
                    // Returned items go back to "waiting for a PAR" so ADAS can fix and reissue.
                    if ($decision === 'Returned') {
                        (new RoomPropertyModel())->where('par_id', (int) $par['id'])->set(['par_id' => null])->update();
                    }
                    if ($par['issued_by']) {
                        (new NotificationModel())->insert([
                            'user_id'  => (int) $par['issued_by'],
                            'type'     => 'property_par',
                            'title'    => $par['par_no'] . ' ' . ($decision === 'Approved' ? 'received (confirmed)' : 'returned') . ' by ' . (currentUser()['name'] ?? 'teacher'),
                            'sub'      => $remarks !== '' ? mb_strimwidth($remarks, 0, 120, '…') : null,
                            'url'      => base_url('property-management?tab=par'),
                            'ref_type' => 'property_par',
                            'ref_id'   => (int) $par['id'],
                        ]);
                    }
                    $message = $decision === 'Approved' ? 'Receipt confirmed for ' . $par['par_no'] . '.' : $par['par_no'] . ' returned to the ADAS.';
                    break;
            }
        } catch (\Throwable $e) {
            return $fail('Something went wrong: ' . $e->getMessage());
        }

        if ($isAjax) {
            return $message ? $this->ajaxSuccess($message, $extra) : $this->ajaxError('Unknown action.');
        }

        session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message ?? '']);

        return redirect()->to('/property-management');
    }

    /**
     * Validated item fields from the Add / Edit forms, or an error message.
     *
     * @return array<string,mixed>|string
     */
    private function itemFromPost(): array|string
    {
        $grade    = (string) $this->request->getPost('grade');
        $section  = (string) $this->request->getPost('section');
        $name     = trim((string) $this->request->getPost('item_name'));
        $qty      = (int) ($this->request->getPost('quantity') ?: 1);
        $acqType  = (string) ($this->request->getPost('acquisition_type') ?: 'New');
        $acqOther = trim((string) $this->request->getPost('acquisition_other'));
        $notes    = trim((string) $this->request->getPost('notes'));

        if (! in_array($section, $this->sectionsByGrade()[$grade] ?? [], true)) {
            return 'Please pick a valid grade and section.';
        }
        if ($name === '') {
            return 'Please enter the item name.';
        }
        if ($qty < 1) {
            return 'Quantity must be at least 1.';
        }
        if (! in_array($acqType, RoomPropertyModel::ACQUISITIONS, true)) {
            return 'Please pick how the item was acquired.';
        }
        if ($acqType === 'Other' && $acqOther === '') {
            return 'Please specify how the item was acquired.';
        }

        // The room's property goes to the section's adviser. ADAS can pick
        // someone else (e.g. a room with no adviser yet).
        $issuedTo = (int) $this->request->getPost('issued_to');
        if ($issuedTo && ! (new UserModel())->where('role', 'teacher')->find($issuedTo)) {
            return 'Please pick a valid teacher.';
        }
        if (! $issuedTo) {
            $issuedTo = RoomPropertyModel::advisersBySection()[strtolower(trim($section))]['id'] ?? 0;
        }

        return [
            'grade'             => $grade,
            'section'           => $section,
            'item_name'         => $name,
            'quantity'          => $qty,
            'acquisition_type'  => $acqType,
            'acquisition_other' => $acqType === 'Other' ? $acqOther : null,
            'notes'             => $notes !== '' ? $notes : null,
            'issued_to'         => $issuedTo ?: null,
        ];
    }

    /**
     * Creates the PAR with a snapshot of its items and links the items to it.
     *
     * @return array{0:int,1:string} [PAR id, PAR number]
     */
    private function issuePar(int $teacherId, int $issuedBy, array $items): array
    {
        $db = db_connect();
        $db->transException(true)->transStart();

        $parModel = new PropertyAcknowledgementModel();
        $parNo    = $parModel->nextParNo();
        $parId    = (int) $parModel->insert([
            'par_no'    => $parNo,
            'issued_to' => $teacherId,
            'issued_by' => $issuedBy ?: null,
            'status'    => 'Pending',
        ]);

        $snapshot = array_map(static fn ($item) => [
            'par_id'           => $parId,
            'property_id'      => (int) $item['id'],
            'item_name'        => $item['item_name'],
            'grade'            => $item['grade'],
            'section'          => $item['section'],
            'quantity'         => (int) $item['quantity'],
            'condition_status' => $item['condition_status'],
            'acquisition'      => RoomPropertyModel::acquisitionLabel($item),
            'date_acquired'    => date('Y-m-d', strtotime($item['created_at'])),
        ], $items);
        (new PropertyAcknowledgementItemModel())->insertBatch($snapshot);

        (new RoomPropertyModel())->whereIn('id', array_column($items, 'id'))->set(['par_id' => $parId])->update();

        $db->transComplete();

        return [$parId, $parNo];
    }

    /**
     * @return list<array{id:int,name:string}>
     */
    private function teachers(): array
    {
        return (new UserModel())->active()->select('id, name')->where('role', 'teacher')->orderBy('name')->findAll();
    }

    /**
     * Every section the school has, grouped by grade level — taken from the
     * teachers' subject loads (teacher_subjects), which is where sections are
     * defined. Drives the Add / Edit Item grade/section dropdowns.
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

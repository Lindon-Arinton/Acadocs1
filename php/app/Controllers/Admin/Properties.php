<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Teacher\PerformanceMps;
use App\Models\NotificationModel;
use App\Models\PropertyAcknowledgementItemModel;
use App\Models\PropertyAcknowledgementModel;
use App\Models\PropertyConditionLogModel;
use App\Models\RoomPropertyModel;
use App\Models\TeacherSubjectModel;
use App\Models\UserModel;
use CodeIgniter\Database\RawSql;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Property Management.
 *
 * - ADAS manages the inventory: adds/edits items, assigns each one to the
 *   accountable teacher, and issues Property Acknowledgment Receipts (PAR).
 * - Teachers can add items for themselves, update the condition of the
 *   items issued to them, and approve or return the PARs issued to them.
 * - Principal (admin) and secretary have read-only access.
 *
 * Timestamps use the database clock (NOW()) — the app runs on UTC while the
 * DB's CURRENT_TIMESTAMP defaults are in local time.
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
        $tab       = $this->request->getGet('tab') === 'par' ? 'par' : 'items';
        $grade     = $this->request->getGet('grade') ?? 'all';
        $condition = $this->request->getGet('condition') ?? 'all';
        $issued    = $this->request->getGet('issued') ?? 'all';
        $search    = trim($this->request->getGet('q') ?? '');
        $sort      = $this->request->getGet('sort') ?? 'grade_az';

        $builder = $model->select('room_properties.*, u.name AS issued_to_name, p.par_no, p.status AS par_status')
            ->join('users u', 'u.id = room_properties.issued_to', 'left')
            ->join('property_acknowledgements p', 'p.id = room_properties.par_id', 'left');

        if ($grade !== 'all') {
            $builder->where('room_properties.grade', $grade);
        }
        if (in_array($condition, RoomPropertyModel::CONDITIONS, true)) {
            $builder->where('room_properties.condition_status', $condition);
        }
        if ($issued === 'me') {
            $builder->where('room_properties.issued_to', $userId);
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

        $items = $builder->findAll();

        $grades    = array_column((new RoomPropertyModel())->distinct()->select('grade')->orderBy('grade')->findAll(), 'grade');
        $condStats = array_count_values(array_column($items, 'condition_status'));

        $parModel = new PropertyAcknowledgementModel();
        $pars     = $parModel->listing(hasRole('teacher') ? $userId : null);
        $pendingParCount = count(array_filter($pars, static fn ($p) => $p['status'] === 'Pending'));

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
            'unassignedCount' => $unassignedCount,
            'unacknowledged'  => $unacknowledged,
            'canManage'       => hasRole('adas', 'teacher'),
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
        if (! $item) {
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

    private function handlePost(RoomPropertyModel $model)
    {
        $isAjax = $this->request->isAJAX();
        $fail   = fn (string $msg, int $code = 400) => $isAjax ? $this->ajaxError($msg, $code) : redirect()->to('/property-management');

        if (! hasRole('adas', 'teacher')) {
            return $fail('You are not authorized to do this.', 403);
        }

        $action  = $this->request->getPost('action');
        $userId  = (int) (currentUser()['id'] ?? 0);
        $message = null;
        $extra   = [];

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
                    ]);
                    (new PropertyConditionLogModel())->record($id, null, $condition, 'Item added');
                    $message = 'Item added successfully.';
                    break;

                case 'edit':
                    $item = $this->findManageable($model);
                    if (is_string($item)) {
                        return $fail($item, 403);
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

                case 'update_condition':
                    $item = $this->findManageable($model);
                    if (is_string($item)) {
                        return $fail($item, 403);
                    }
                    $condition = (string) $this->request->getPost('condition_status');
                    $remarks   = trim((string) $this->request->getPost('remarks'));
                    if (! in_array($condition, RoomPropertyModel::CONDITIONS, true)) {
                        return $fail('Please pick a valid condition.');
                    }
                    if ($condition === $item['condition_status']) {
                        return $fail('The item is already ' . $condition . '.');
                    }
                    if ($condition === 'Non-serviceable' && $remarks === '') {
                        return $fail('Please describe what happened to the item.');
                    }

                    $model->update((int) $item['id'], ['condition_status' => $condition, 'updated_at' => new RawSql('NOW()')]);
                    (new PropertyConditionLogModel())->record((int) $item['id'], $item['condition_status'], $condition, $remarks);
                    $message = 'Condition updated to ' . $condition . '.';
                    break;

                case 'delete':
                    $item = $this->findManageable($model);
                    if (is_string($item)) {
                        return $fail($item, 403);
                    }
                    $model->delete((int) $item['id']);
                    $message = 'Item removed.';
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
                        'title'    => 'PAR for approval: ' . $parNo,
                        'sub'      => count($parItems) . ' ' . (count($parItems) === 1 ? 'item' : 'items') . ' issued to you',
                        'url'      => base_url('property-management?tab=par'),
                        'ref_type' => 'property_par',
                        'ref_id'   => $parId,
                    ]);
                    $message = $parNo . ' issued to ' . $teacher['name'] . ' for approval.';
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
                            'title'    => $par['par_no'] . ' ' . strtolower($decision) . ' by ' . (currentUser()['name'] ?? 'teacher'),
                            'sub'      => $remarks !== '' ? mb_strimwidth($remarks, 0, 120, '…') : null,
                            'url'      => base_url('property-management?tab=par'),
                            'ref_type' => 'property_par',
                            'ref_id'   => (int) $par['id'],
                        ]);
                    }
                    $message = $par['par_no'] . ($decision === 'Approved' ? ' approved.' : ' returned to the ADAS.');
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

        // ADAS assigns the accountable teacher; a teacher's own items are issued to them.
        if (hasRole('adas')) {
            $issuedTo = (int) $this->request->getPost('issued_to');
            if ($issuedTo && ! (new UserModel())->where('role', 'teacher')->find($issuedTo)) {
                return 'Please pick a valid teacher.';
            }
        } else {
            $issuedTo = (int) (currentUser()['id'] ?? 0);
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
     * The posted item if the current user may change it, or an error message.
     * ADAS can change any item; a teacher only the items issued to them (or
     * unassigned items they added themselves).
     */
    private function findManageable(RoomPropertyModel $model): array|string
    {
        $item = $model->find((int) $this->request->getPost('id'));
        if (! $item) {
            return 'Item not found.';
        }
        if (hasRole('adas')) {
            return $item;
        }

        $user = currentUser();
        $mine = (int) $item['issued_to'] === (int) ($user['id'] ?? 0)
            || ($item['issued_to'] === null && $item['uploaded_by'] === ($user['name'] ?? null));

        return $mine ? $item : 'You can only change items issued to you.';
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

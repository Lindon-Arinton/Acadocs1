<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Libraries\BiometricSync;
use App\Libraries\TimeRecordImporter;
use App\Models\AcademicBreakModel;
use App\Models\HolidayModel;
use App\Models\TimeRecordModel;
use App\Models\UserModel;

class TimeRecords extends BaseController
{
    public function index()
    {
        $model = new TimeRecordModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax   = $this->request->isAJAX();
            $redirect = '/time-records?date=' . ($this->request->getPost('date') ?? date('Y-m-d'));

            if (! hasRole('admin', 'adas')) {
                return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to($redirect);
            }

            $action  = $this->request->getPost('action');
            $message = null;

            try {
                if ($action === 'update') {
                    $model->update((int) $this->request->getPost('id'), [
                        'time_in'  => $this->request->getPost('time_in') ?: null,
                        'time_out' => $this->request->getPost('time_out') ?: null,
                        'status'   => $this->request->getPost('status'),
                        'remarks'  => $this->request->getPost('remarks') ?? '',
                    ]);
                    $message = 'Record updated.';
                } elseif ($action === 'holiday_add') {
                    (new HolidayModel())->insert([
                        'date'      => $this->request->getPost('holiday_date'),
                        'label'     => $this->request->getPost('holiday_label') ?: null,
                        'recurring' => $this->request->getPost('holiday_recurring') ? 1 : 0,
                    ]);
                    $message = 'Holiday added.';
                } elseif ($action === 'holiday_delete') {
                    (new HolidayModel())->delete((int) $this->request->getPost('holiday_id'));
                    $message = 'Holiday removed.';
                } elseif ($action === 'break_add') {
                    $label = trim((string) $this->request->getPost('break_label'));
                    $start = (string) $this->request->getPost('break_start');
                    $end   = (string) $this->request->getPost('break_end');
                    if ($label === '' || ! strtotime($start) || ! strtotime($end) || $end < $start) {
                        return $isAjax ? $this->ajaxError('Enter a name and a start date on or before the end date.') : redirect()->to($redirect);
                    }

                    (new AcademicBreakModel())->insert(['label' => $label, 'start_date' => $start, 'end_date' => $end]);
                    // Imported "no punches" absences inside the break weren't absences after all.
                    $model->set(['status' => 'Academic Break', 'remarks' => TimeRecordModel::BREAK_REMARK . $label])
                        ->where('date >=', $start)->where('date <=', $end)
                        ->where('status', 'Absent')->where('remarks', TimeRecordModel::NO_PUNCH_REMARK)
                        ->update();
                    $changed = db_connect()->affectedRows();
                    $message = 'Academic break added.' . ($changed ? ' ' . $changed . ' absence' . ($changed === 1 ? '' : 's') . ' in that range changed to Academic Break.' : '');
                } elseif ($action === 'break_delete') {
                    $breaks = new AcademicBreakModel();
                    $break  = $breaks->find((int) $this->request->getPost('break_id'));
                    if ($break) {
                        $breaks->delete($break['id']);
                        // Undo only what adding the break changed (records an admin set by hand stay put).
                        $model->set(['status' => 'Absent', 'remarks' => TimeRecordModel::NO_PUNCH_REMARK])
                            ->where('date >=', $break['start_date'])->where('date <=', $break['end_date'])
                            ->where('status', 'Academic Break')->where('remarks', TimeRecordModel::BREAK_REMARK . $break['label'])
                            ->update();
                    }
                    $message = 'Academic break removed.';
                } elseif ($action === 'leave_add') {
                    $from  = (string) $this->request->getPost('date');
                    $until = (string) ($this->request->getPost('leave_until') ?: $from);
                    $acNos = array_filter(array_map('strval', (array) $this->request->getPost('leave_ac_no')));
                    if (! strtotime($from) || ! strtotime($until) || $until < $from) {
                        return $isAjax ? $this->ajaxError('The "until" date must be on or after the start date.') : redirect()->to($redirect);
                    }
                    if ($acNos === []) {
                        return $isAjax ? $this->ajaxError('Choose at least one person.') : redirect()->to($redirect);
                    }
                    if ((strtotime($until) - strtotime($from)) / 86400 > 366) {
                        return $isAjax ? $this->ajaxError('A leave can span at most a year.') : redirect()->to($redirect);
                    }

                    [$marked, $message] = $this->markOnLeave($acNos, $from, $until, trim((string) $this->request->getPost('leave_reason')));
                    if ($marked === 0) {
                        return $isAjax ? $this->ajaxError($message) : redirect()->to($redirect);
                    }
                } elseif ($action === 'leave_remove') {
                    $record = $model->find((int) $this->request->getPost('record_id'));
                    if ($record && $record['status'] === 'On Leave') {
                        // A leave record never has punches (see markOnLeave()), so removing it just clears the day;
                        // the next biometric sync marks it Absent again if it was a school day with no punch.
                        $model->delete($record['id']);
                    }
                    $message = 'Leave removed.';
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to($redirect);
            }

            if ($isAjax) {
                return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
            }

            session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message ?? '']);

            return redirect()->to($redirect);
        }

        $dateFilter   = $this->request->getGet('date') ?? date('Y-m-d');
        $search       = trim($this->request->getGet('q') ?? '');
        $sort         = $this->request->getGet('sort') ?? 'name_az';
        $statusFilter = $this->request->getGet('status') ?? 'all';

        // Academic Break isn't offered as a filter (breaks are managed under Academic Breaks).
        if ($statusFilter !== 'all' && ! in_array($statusFilter, ['Present', 'Late', 'Absent', 'On Leave'], true)) {
            $statusFilter = 'all';
        }

        $scanFilter = $this->request->getGet('scan') ?? 'all';
        if (! in_array($scanFilter, ['all', 'in', 'out', 'none', 'no_in', 'no_out'], true)) {
            $scanFilter = 'all';
        }

        // Summary counts always reflect every status for the date (still
        // respecting search), so every card stays visible/clickable no
        // matter which status is currently selected in the filter.
        $model->where('date', $dateFilter)->where('employee_name NOT LIKE', 'Unmapped (%');
        if ($search !== '') {
            $model->groupStart()
                ->like('employee_name', $search)
                ->orLike('employee_id', $search)
                ->orLike('remarks', $search)
                ->groupEnd();
        }
        $summaryRows = $model->findAll();

        $summary = array_fill_keys(TimeRecordModel::STATUSES, 0);
        foreach ($summaryRows as $r) {
            if (isset($summary[$r['status']])) {
                $summary[$r['status']]++;
            }
        }

        $builder = $model->where('date', $dateFilter)
            ->where('employee_name NOT LIKE', 'Unmapped (%');

        if ($search !== '') {
            $builder->groupStart()
                ->like('employee_name', $search)
                ->orLike('employee_id', $search)
                ->orLike('remarks', $search)
                ->groupEnd();
        }

        if ($statusFilter !== 'all') {
            $builder->where('status', $statusFilter);
        }

        // Scan filter: who has already timed in / timed out, or hasn't scanned at all.
        match ($scanFilter) {
            'in'    => $builder->where('time_in IS NOT NULL', null, false),
            'out'   => $builder->where('time_out IS NOT NULL', null, false),
            'none'  => $builder->where('time_in IS NULL', null, false)->where('time_out IS NULL', null, false),
            // Incomplete scans: timed out without a time-in, or timed in without a time-out.
            'no_in'  => $builder->where('time_in IS NULL', null, false)->where('time_out IS NOT NULL', null, false),
            'no_out' => $builder->where('time_in IS NOT NULL', null, false)->where('time_out IS NULL', null, false),
            default => null,
        };

        match ($sort) {
            'status'  => $builder->orderBy('status', 'ASC')->orderBy('employee_name', 'ASC'),
            'time_in' => $builder->orderBy('time_in', 'ASC'),
            default   => $builder->orderBy('employee_name', 'ASC'),
        };

        $records = $builder->findAll();

        // Each record carries its owner's account (time_records.user_id) — map it so
        // the name can open the person card (personLink()); unlinked scans stay plain.
        $userIdsByEmployeeId = [];
        foreach ($records as $r) {
            if (! empty($r['user_id'])) {
                $userIdsByEmployeeId[$r['employee_id']] = (int) $r['user_id'];
            }
        }

        return view('pages/shared/time_records', [
            'notYetIn'     => $statusFilter === 'all' && in_array($scanFilter, ['all', 'none'], true) ? $this->notYetTimedIn($dateFilter, $search) : [],
            'scanFilter'   => $scanFilter,
            'userIdsByEmployeeId' => $userIdsByEmployeeId,
            'pageTitle'    => 'Time Records',
            'records'      => $records,
            'summary'      => $summary,
            'dateFilter'   => $dateFilter,
            'search'       => $search,
            'sort'         => $sort,
            'statusFilter' => $statusFilter,
            'flash'        => session()->getFlashdata('flash'),
            // Every-year holidays first (by month/day), then one-off dates.
            'holidays'     => (new HolidayModel())->orderBy('recurring', 'DESC')->orderBy("DATE_FORMAT(date, '%m-%d')", 'ASC', false)->orderBy('date', 'ASC')->findAll(),
            'movableHolidays' => HolidayModel::movableFor((int) substr($dateFilter, 0, 4)),
            'holidayLabel'    => (new HolidayModel())->labelFor($dateFilter),
            'academicBreaks' => (new AcademicBreakModel())->orderBy('start_date', 'ASC')->findAll(),
            'activeBreak'    => (new AcademicBreakModel())->covering($dateFilter),
            'biometricSync'  => config('Biometric')->enabled ? BiometricSync::lastStatus() ?? ['ok' => null] : null,
            // Online server: the scanner is synced from the school PC, so show the newest punch it sent.
            'remoteLastScan' => config('Biometric')->enabled ? null : $this->latestPunch(),
            'leaveCandidates' => hasRole('admin', 'adas') ? $this->leaveCandidates($dateFilter) : [],
            'onLeave'         => (new TimeRecordModel())->where('date', $dateFilter)->where('status', 'On Leave')->orderBy('employee_name', 'ASC')->findAll(),
        ]);
    }

    /**
     * Today only (a school day): everyone expected to scan who has no record
     * yet. Past days don't need this — the sync marks those people Absent
     * (TimeRecordImporter::fillNoScanDays()).
     *
     * @return list<array{ac_no:string,name:string,user_id:?int}>
     */
    private function notYetTimedIn(string $date, string $search): array
    {
        if ($date !== date('Y-m-d') || (int) date('N') >= 6 || (new HolidayModel())->labelFor($date) !== null || (new AcademicBreakModel())->covering($date)) {
            return [];
        }

        $people = TimeRecordModel::expectedPeople();
        foreach ((new TimeRecordModel())->select('employee_id')->where('date', $date)->findAll() as $r) {
            unset($people[substr($r['employee_id'], 3)]);
        }

        // Only people the scanner has seen before; someone who never scanned has nothing to go on.
        $seen = array_flip(array_column(db_connect()->query('SELECT DISTINCT ac_no FROM biometric_punches')->getResultArray(), 'ac_no'));
        $out  = [];
        foreach ($people as $acNo => $p) {
            if (! isset($seen[$acNo]) || ($p['scanner_only'] && ! $this->scannedSince($acNo, date('Y-m-d', strtotime('-31 days'))))) {
                continue;
            }
            if ($search !== '' && stripos($p['name'] . ' AC-' . $acNo, $search) === false) {
                continue;
            }
            $out[] = $p;
        }
        usort($out, static fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $out;
    }

    private function scannedSince(string $acNo, string $date): bool
    {
        return db_connect()->query('SELECT 1 FROM biometric_punches WHERE ac_no = ? AND punched_at >= ? LIMIT 1', [$acNo, $date . ' 00:00:00'])->getRowArray() !== null;
    }

    /**
     * Everyone who can be marked On Leave for a day: active accounts with a
     * biometric number, plus named scanner users not linked to an account
     * yet — minus anyone who has already timed in (or out) that day or is
     * already on leave.
     *
     * @return list<array{ac_no:string,name:string,role:string,user_id:?int}>
     */
    private function leaveCandidates(string $date): array
    {
        $people = TimeRecordModel::expectedPeople();

        $taken = (new TimeRecordModel())->select('employee_id')->where('date', $date)
            ->groupStart()->where('time_in IS NOT NULL', null, false)->orWhere('time_out IS NOT NULL', null, false)->orWhere('status', 'On Leave')->groupEnd()
            ->findAll();
        foreach ($taken as $r) {
            unset($people[substr($r['employee_id'], 3)]); // "AC-12" -> "12"
        }

        usort($people, static fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $people;
    }

    /**
     * Records On Leave for each person on each school day from $from to
     * $until (weekends and holidays skipped), over an Absent or empty day —
     * never over a day they timed in.
     *
     * @return array{0:int,1:string} [days marked, message]
     */
    private function markOnLeave(array $acNos, string $from, string $until, string $reason): array
    {
        $candidates = array_column($this->leaveCandidates($from), null, 'ac_no');
        $model      = new TimeRecordModel();
        $holidays   = new HolidayModel();
        $remarks    = 'On leave' . ($reason !== '' ? ': ' . $reason : '');

        $marked  = 0;
        $skipped = [];
        foreach ($acNos as $acNo) {
            $person = $candidates[$acNo] ?? null;
            if ($person === null) {
                $skipped[] = 'AC-' . $acNo;

                continue; // timed in already, already on leave, or not a known person
            }

            for ($day = $from; $day <= $until; $day = date('Y-m-d', strtotime($day . ' +1 day'))) {
                $isSchoolDay = (int) date('N', strtotime($day)) < 6 && $holidays->labelFor($day) === null;
                if (! $isSchoolDay && $from !== $until) {
                    continue;
                }

                $existing = $model->where('employee_id', 'AC-' . $acNo)->where('date', $day)->first();
                if ($existing && ($existing['time_in'] !== null || $existing['time_out'] !== null)) {
                    continue;
                }

                $data = [
                    'date'          => $day,
                    'employee_name' => $person['name'],
                    'employee_id'   => 'AC-' . $acNo,
                    'user_id'       => $person['user_id'],
                    'time_in'       => null,
                    'time_out'      => null,
                    'status'        => 'On Leave',
                    'remarks'       => $remarks,
                ];
                $existing ? $model->update($existing['id'], $data) : $model->insert($data);
                $marked++;
            }
        }

        $people = count($acNos) - count($skipped);
        $message = $marked === 0
            ? 'No one was marked on leave.'
            : sprintf('Marked %d %s on leave (%d day%s).', $people, $people === 1 ? 'person' : 'people', $marked, $marked === 1 ? '' : 's in total');

        return [$marked, $skipped === [] ? $message : $message . ' Skipped (already timed in or on leave): ' . implode(', ', $skipped) . '.'];
    }

    /**
     * Polled by an open Time Records page: syncs new punches from the
     * device when one is due (see BiometricSync::isDue()) and reports the
     * latest punch, so the page can refresh itself when it changes.
     */
    public function live()
    {
        // Syncing is off here (online server): another computer sends the
        // punches, so just report the newest one stored.
        if (! config('Biometric')->enabled) {
            $latest = $this->latestPunch();

            return $this->response->setJSON([
                'ok'           => null,
                'at'           => null,
                'message'      => null,
                'latest_punch' => $latest,
                'latest_label' => $latest ? date('M d, h:i A', strtotime($latest)) : null,
            ]);
        }

        $sync = new BiometricSync();
        $status = $sync->isDue() ? $sync->pull() : (BiometricSync::lastStatus() ?? []);

        return $this->response->setJSON([
            'ok'           => $status['ok'] ?? null,
            'at'           => isset($status['at']) ? date('M d, h:i A', strtotime($status['at'])) : null,
            'message'      => $status['message'] ?? null,
            'latest_punch' => $status['latest_punch'] ?? null,
        ]);
    }

    /**
     * Attendance report for one person over a date range: every record,
     * totals per status, attendance rate and total tardiness. Admin/ADAS can
     * pick anyone; everyone else sees only their own.
     */
    public function report()
    {
        $canPickAnyone = hasRole('admin', 'adas');
        $people        = TimeRecordModel::expectedPeople();

        // "BAUTISTA, JORGE C. · AC-25" (from the search box) or a bare "25".
        $raw  = trim((string) $this->request->getGet('person'));
        $acNo = preg_match('/AC-?(\w+)\s*$/i', $raw, $m) ? $m[1] : $raw;

        if (! $canPickAnyone) {
            $acNo = (string) (currentUser()['ac_no'] ?? '');
        }

        $from = (string) ($this->request->getGet('from') ?: date('Y-m-01'));
        $to   = (string) ($this->request->getGet('to') ?: date('Y-m-d'));
        if (! strtotime($from) || ! strtotime($to)) {
            [$from, $to] = [date('Y-m-01'), date('Y-m-d')];
        }
        if ($to < $from) {
            [$from, $to] = [$to, $from];
        }

        $person  = null;
        $records = [];
        $totals  = array_fill_keys(TimeRecordModel::STATUSES, 0);
        $lateMinutes = 0;

        if ($acNo !== '') {
            $records = (new TimeRecordModel())->where('employee_id', 'AC-' . $acNo)
                ->where('date >=', $from)->where('date <=', $to)
                ->orderBy('date', 'ASC')->findAll();

            $person = $people[$acNo] ?? ($records !== [] ? ['ac_no' => $acNo, 'name' => end($records)['employee_name'], 'role' => 'Former / unlinked', 'user_id' => null] : null);

            foreach ($records as $r) {
                $totals[$r['status']] = ($totals[$r['status']] ?? 0) + 1;
                if ($r['status'] === 'Late' && $r['time_in']) {
                    $lateMinutes += max(0, (int) round((strtotime($r['time_in']) - strtotime(TimeRecordImporter::LATE_THRESHOLD)) / 60));
                }
            }
        }

        $attended = $totals['Present'] + $totals['Late'];
        $expected = $attended + $totals['Absent'];

        return view('pages/shared/time_records_report', [
            'pageTitle'     => 'Attendance Report',
            'canPickAnyone' => $canPickAnyone,
            'people'        => $people,
            'acNo'          => $acNo,
            'person'        => $person,
            'from'          => $from,
            'to'            => $to,
            'records'       => $records,
            'totals'        => $totals,
            'rate'          => $expected > 0 ? round($attended / $expected * 100, 1) : null,
            'lateMinutes'   => $lateMinutes,
        ]);
    }

    /** Newest fingerprint punch stored (Y-m-d H:i:s), whichever computer synced it. */
    private function latestPunch(): ?string
    {
        return db_connect()->query('SELECT MAX(punched_at) AS t FROM biometric_punches')->getRowArray()['t'] ?? null;
    }

    /** "Sync from Device": pulls new punches now instead of waiting for the next automatic sync. */
    public function sync()
    {
        if (! hasRole('admin', 'adas')) {
            return $this->ajaxError('You are not authorized to do this.', 403);
        }
        if (! config('Biometric')->enabled) {
            return $this->ajaxError("This server can't reach the scanner. Attendance is sent up from the school computer that is on the scanner's network.");
        }

        $result = (new BiometricSync())->pull();
        if (! $result['ok']) {
            return $this->ajaxError('Could not sync: ' . $result['message']);
        }

        $date = $result['summary']['latest_date'] ?? date('Y-m-d');

        return $this->ajaxSuccess($result['message'], ['redirect' => '/time-records?date=' . $date]);
    }

    public function import()
    {
        $isAjax   = $this->request->isAJAX();
        $redirect = '/time-records';

        if (! hasRole('admin', 'adas')) {
            return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to($redirect);
        }

        $file = $this->request->getFile('import_file');

        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return $isAjax ? $this->ajaxError('Please choose a valid Excel file to upload.') : redirect()->to($redirect);
        }

        // Use the client's extension: biometric .xls exports are often raw
        // BIFF streams whose MIME sniffs as octet-stream, so getExtension()
        // guesses "bin". The importer validates the actual content.
        if (! in_array(strtolower($file->getClientExtension()), ['xlsx', 'xls'], true)) {
            return $isAjax ? $this->ajaxError('Only .xlsx or .xls files are supported.') : redirect()->to($redirect);
        }

        $uploadPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'time_records_imports';
        if (! is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $fileName = date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientName());

        if (! $file->move($uploadPath, $fileName)) {
            return $isAjax ? $this->ajaxError('Could not save the uploaded file.') : redirect()->to($redirect);
        }

        try {
            $summary = (new TimeRecordImporter())->import($uploadPath . DIRECTORY_SEPARATOR . $fileName);
        } catch (\Throwable $e) {
            return $isAjax ? $this->ajaxError('Import failed: ' . $e->getMessage()) : redirect()->to($redirect);
        }

        $message = sprintf(
            'Imported %d attendance record(s) from %d row(s): %d present, %d late, %d absent (%d incomplete).',
            $summary['inserted'] + $summary['updated'],
            $summary['rows_read'],
            $summary['present'],
            $summary['late'],
            $summary['absent'],
            $summary['incomplete'],
        );

        if ($summary['academic_break'] > 0) {
            $message .= ' ' . $summary['academic_break'] . ' day(s) recorded as Academic Break.';
        }

        if ($summary['skipped_non_school_day'] > 0) {
            $message .= ' ' . $summary['skipped_non_school_day'] . ' weekend/holiday entr' . ($summary['skipped_non_school_day'] === 1 ? 'y' : 'ies') . ' skipped.';
        }

        if ($summary['placeholders_created'] !== []) {
            $message .= ' Unmapped AC-No (no name in scanner) recorded as placeholders: ' . implode(', ', $summary['placeholders_created']) . '.';
        }

        if ($summary['errors'] !== []) {
            $message .= ' ' . count($summary['errors']) . ' row(s) had errors and were skipped.';
        }

        if ($summary['latest_date']) {
            $redirect = '/time-records?date=' . $summary['latest_date'];
            $message .= ' Showing ' . date('M d, Y', strtotime($summary['latest_date'])) . '.';
        }

        if ($isAjax) {
            return $this->ajaxSuccess($message, ['summary' => $summary, 'redirect' => $redirect]);
        }

        session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message]);

        return redirect()->to($redirect);
    }
}

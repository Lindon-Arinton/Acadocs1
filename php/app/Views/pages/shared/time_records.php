<?php include APPPATH . 'Views/layout/header.php'; ?>

<?php
// The selected date is the page's headline; prev / next / today step through days.
$dtrIsToday = $dateFilter === date('Y-m-d');
$dtrDayUrl  = static function (string $date) use ($sort, $statusFilter): string {
    return base_url('time-records') . '?' . http_build_query(array_filter([
        'date'   => $date,
        'sort'   => $sort !== 'name_az' ? $sort : null,
        'status' => $statusFilter !== 'all' ? $statusFilter : null,
    ]));
};
?>
<!-- Page Header: the selected date is the headline -->
<div class="page-header">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3" style="position:relative;z-index:1">
    <div>
      <div class="small fw-semibold mb-1" style="opacity:.8;letter-spacing:.04em;text-transform:uppercase;">
        <i class="bi bi-clock me-1"></i>Daily Time Records
      </div>
      <h4 class="dtr-date-title"><?= date('l, F j, Y', strtotime($dateFilter)) ?></h4>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php if ($dtrIsToday): ?>
        <div class="live-badge"><div class="live-dot"></div>Today · <span id="dtrLiveClock"><?= date('g:i:s A') ?></span></div>
        <?php else: ?>
        <div class="live-badge"><i class="bi bi-calendar3"></i><?= e(\CodeIgniter\I18n\Time::parse($dateFilter)->humanize()) ?></div>
        <?php endif; ?>
        <p class="mb-0">Employee attendance, time-in &amp; time-out monitoring</p>
      </div>
    </div>
    <div class="d-flex gap-2 align-items-center">
      <a href="<?= $dtrDayUrl(date('Y-m-d', strtotime($dateFilter . ' -1 day'))) ?>" class="btn btn-sm btn-outline-light" title="Previous day"><i class="bi bi-chevron-left"></i></a>
      <?php if (! $dtrIsToday): ?>
      <a href="<?= $dtrDayUrl(date('Y-m-d')) ?>" class="btn btn-sm btn-light fw-semibold" style="color:var(--primary);">Today</a>
      <?php endif; ?>
      <a href="<?= $dtrDayUrl(date('Y-m-d', strtotime($dateFilter . ' +1 day'))) ?>" class="btn btn-sm btn-outline-light" title="Next day"><i class="bi bi-chevron-right"></i></a>
    </div>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-check-circle-fill"></i><?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Date filter + search + sort + actions -->
<div class="card mb-4">
  <div class="card-body py-3">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <form method="GET" action="<?= base_url('time-records') ?>" id="filterForm" class="d-flex align-items-center gap-2 flex-wrap">
        <div class="maroon-dp" data-autosubmit style="width:150px;">
          <input type="text" class="form-control form-control-sm maroon-dp-display" placeholder="Select date" readonly>
          <input type="hidden" name="date" value="<?= e($dateFilter) ?>">
          <div class="maroon-dp-panel">
            <div class="maroon-dp-header">
              <button type="button" class="maroon-dp-nav" data-dir="-1"><i class="bi bi-chevron-left"></i></button>
              <span class="maroon-dp-month-label"></span>
              <button type="button" class="maroon-dp-nav" data-dir="1"><i class="bi bi-chevron-right"></i></button>
            </div>
            <div class="maroon-dp-dow"><span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span></div>
            <div class="maroon-dp-grid"></div>
          </div>
        </div>

        <div class="input-group input-group-sm" style="max-width:220px;">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="q" id="timeSearchInput" value="<?= e($search) ?>"
                 class="form-control border-start-0 ps-0" placeholder="Search name, ID, remarks...">
        </div>

        <div class="maroon-select maroon-select-sm" style="width:auto;">
          <select name="sort" class="maroon-select-native" onchange="this.form.requestSubmit()">
            <option value="name_az" <?= $sort==='name_az' ? 'selected' : '' ?>>Name A-Z</option>
            <option value="status"  <?= $sort==='status'  ? 'selected' : '' ?>>Status</option>
            <option value="time_in" <?= $sort==='time_in' ? 'selected' : '' ?>>Time In</option>
          </select>
          <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
          <div class="maroon-select-panel"></div>
        </div>

        <div class="maroon-select maroon-select-sm" style="width:auto;">
          <select name="status" id="statusFilterSelect" class="maroon-select-native" onchange="this.form.requestSubmit()">
            <option value="all"      <?= $statusFilter==='all'      ? 'selected' : '' ?>>All</option>
            <option value="Present"  <?= $statusFilter==='Present'  ? 'selected' : '' ?>>Present</option>
            <option value="Late"     <?= $statusFilter==='Late'     ? 'selected' : '' ?>>Late</option>
            <option value="Absent"   <?= $statusFilter==='Absent'   ? 'selected' : '' ?>>Absent</option>
            <option value="On Leave" <?= $statusFilter==='On Leave' ? 'selected' : '' ?>>On Leave</option>
            <option value="Academic Break" <?= $statusFilter==='Academic Break' ? 'selected' : '' ?>>Academic Break</option>
          </select>
          <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
          <div class="maroon-select-panel"></div>
        </div>
      </form>
      <div class="ms-auto d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="exportTable('time-table','time_records')">
          <i class="bi bi-download me-1"></i>Export
        </button>
        <?php if (hasRole('admin','adas')): ?>
        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#holidaysModal">
          <i class="bi bi-calendar-x me-1"></i>Manage Holidays
        </button>
        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#breaksModal">
          <i class="bi bi-calendar-range me-1"></i>Academic Breaks
        </button>
        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
          <i class="bi bi-upload me-1"></i>Import
        </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if ($activeBreak): ?>
<div class="alert alert-academic-break d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-calendar-range-fill flex-shrink-0"></i>
  <span><strong><?= e($activeBreak['label']) ?></strong> · <?= date('M j', strtotime($activeBreak['start_date'])) ?> – <?= date('M j, Y', strtotime($activeBreak['end_date'])) ?>.
    School days with no punches in this range are recorded as <strong>Academic Break</strong>, not Absent.</span>
</div>
<?php endif; ?>

<!-- Summary stats: click a card to filter the table by that status; click it again to go back to All -->
<div class="row row-cols-2 row-cols-sm-3 row-cols-lg-5 g-3 mb-4">
  <?php foreach ([
    ['Present',        'present',        'bi-check-circle-fill',    $summary['Present']],
    ['Late',           'late',           'bi-alarm-fill',           $summary['Late']],
    ['Absent',         'absent',         'bi-x-circle-fill',        $summary['Absent']],
    ['On Leave',       'on-leave',       'bi-calendar-check',       $summary['On Leave']],
    ['Academic Break', 'academic-break', 'bi-calendar-range-fill',  $summary['Academic Break']],
  ] as [$label,$cls,$icon,$cnt]):
    $isActive = $statusFilter === $label;
  ?>
  <div class="col">
    <div class="card text-center py-3 status-filter-card <?= $isActive ? 'active' : '' ?>" role="button"
         onclick="filterByStatus('<?= e($label) ?>')">
      <div class="mb-2">
        <span class="badge badge-<?= $cls ?>" style="font-size:.8rem;padding:.4rem .8rem;">
          <i class="bi <?= $icon ?> me-1"></i><?= $label ?>
        </span>
      </div>
      <div class="fw-bold" style="font-size:1.8rem;color:var(--text)"><?= $cnt ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Records table -->
<div class="card">
  <div class="card-header">
    <div class="card-title">Attendance Records</div>
    <div class="card-description">
      <?= date('l, F d, Y', strtotime($dateFilter)) ?> ·
      <span id="time-records-count"><?= count($records) ?></span> total employees
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table mb-0" id="time-table">
        <thead>
          <tr>
            <th>Employee ID</th><th>Name</th>
            <th class="text-center">Time In</th>
            <th class="text-center">Time Out</th>
            <th class="text-center">Status</th>
            <th>Remarks</th>
            <?php if (hasRole('admin','adas')): ?><th>Action</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $r): ?>
          <tr>
            <td class="text-muted"><?= e($r['employee_id']) ?></td>
            <td class="fw-semibold"><?= personLink($userIdsByEmployeeId[$r['employee_id']] ?? null, $r['employee_name']) ?></td>
            <td class="text-center">
              <?php if ($r['time_in']): ?>
              <span class="d-inline-flex align-items-center gap-1">
                <i class="bi bi-clock text-muted"></i>
                <?= date('h:i A', strtotime($r['time_in'])) ?>
              </span>
              <?php else: ?>
              <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php if ($r['time_out']): ?>
              <span class="d-inline-flex align-items-center gap-1">
                <i class="bi bi-clock text-muted"></i>
                <?= date('h:i A', strtotime($r['time_out'])) ?>
              </span>
              <?php else: ?>
              <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php
              $cls = strtolower(str_replace(' ','-',$r['status']));
              $icons = ['Present'=>'bi-check-circle-fill','Late'=>'bi-exclamation-circle-fill','Absent'=>'bi-x-circle-fill','On Leave'=>'bi-calendar-check-fill','Academic Break'=>'bi-calendar-range-fill'];
              ?>
              <span class="badge badge-<?= $cls ?>">
                <i class="bi <?= $icons[$r['status']] ?? 'bi-circle' ?>"></i>
                <?= e($r['status']) ?>
              </span>
            </td>
            <td class="text-muted" style="font-size:.78rem;"><?= e($r['remarks']) ?></td>
            <?php if (hasRole('admin','adas')): ?>
            <td>
              <button class="btn btn-ghost btn-sm"
                      onclick="editRecord(<?= htmlspecialchars(json_encode($r),ENT_QUOTES) ?>)"
                      title="Edit">
                <i class="bi bi-pencil"></i>
              </button>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($records)): ?>
          <tr>
            <td colspan="7" class="text-center py-5">
              <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
              <span class="text-muted">No records for <?= date('M d, Y', strtotime($dateFilter)) ?></span>
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Edit Record Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Record</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('time-records') ?>" class="ajax-form">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="date" value="<?= e($dateFilter) ?>">
        <input type="hidden" name="id" id="editId">
        <div class="modal-body">
          <p class="text-muted mb-3" style="font-size:.82rem;"><strong id="editName"></strong></p>
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label">Time In</label>
              <input type="time" name="time_in" id="editTimeIn" class="form-control">
            </div>
            <div class="col-6">
              <label class="form-label">Time Out</label>
              <input type="time" name="time_out" id="editTimeOut" class="form-control">
            </div>
            <div class="col-6">
              <label class="form-label">Status</label>
              <div class="maroon-select" style="width:100%;">
                <select name="status" id="editStatus" class="maroon-select-native">
                  <option>Present</option><option>Late</option>
                  <option>Absent</option><option>On Leave</option>
                  <option>Academic Break</option>
                </select>
                <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
                <div class="maroon-select-panel"></div>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Remarks</label>
              <input type="text" name="remarks" id="editRemarks" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if (hasRole('admin','adas')): ?>
<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-upload me-2"></i>Import Time Records</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('time-records/import') ?>" class="ajax-form" enctype="multipart/form-data" data-confirm-title="Import this file?" data-confirm-text="Existing records for the same employee &amp; date will be overwritten.">
        <div class="modal-body">
          <p class="text-muted" style="font-size:.82rem;">
            Upload the biometric scanner attendance log as exported, one row per punch, with the columns
            <em>Department, Name, No., Date/Time, Status, Location ID, ID Number, Workcode, VerifyCode, CardNo</em>.
            For each employee and day, the earliest <strong>C/In</strong> becomes Time In and the latest <strong>C/Out</strong> becomes Time Out.
            A Time In after 7:30 AM is marked <strong>Late</strong>, and a missing In or Out is marked <strong>Present (incomplete)</strong>.
            A school day with no punch, within that employee's range in the file, is marked <strong>Absent</strong> unless a record already exists.
            Weekends and dates listed under Manage Holidays are never counted as absences, and school days
            inside an <strong>Academic Break</strong> are recorded as Academic Break instead.
          </p>
          <p class="text-muted mb-3" style="font-size:.75rem;">
            The older daily layout (AC-No, Name, Department, Date, Time) is still accepted.
          </p>
          <div class="mb-3">
            <label class="form-label">Excel file (.xlsx, .xls)</label>
            <input type="file" name="import_file" class="form-control" accept=".xlsx,.xls" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Import</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Manage Holidays Modal -->
<div class="modal fade" id="holidaysModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-calendar-x me-2"></i>Manage Holidays</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted" style="font-size:.82rem;">Dates listed here (plus every Saturday &amp; Sunday) are excluded from absence detection during import.</p>
        <form method="POST" action="<?= base_url('time-records') ?>" class="ajax-form row g-2 align-items-end mb-3">
          <input type="hidden" name="action" value="holiday_add">
          <input type="hidden" name="date" value="<?= e($dateFilter) ?>">
          <div class="col-5">
            <label class="form-label">Date</label>
            <div class="maroon-dp">
              <input type="text" class="form-control form-control-sm maroon-dp-display" placeholder="Select date" readonly required>
              <input type="hidden" name="holiday_date">
              <div class="maroon-dp-panel">
                <div class="maroon-dp-header">
                  <button type="button" class="maroon-dp-nav" data-dir="-1"><i class="bi bi-chevron-left"></i></button>
                  <span class="maroon-dp-month-label"></span>
                  <button type="button" class="maroon-dp-nav" data-dir="1"><i class="bi bi-chevron-right"></i></button>
                </div>
                <div class="maroon-dp-dow"><span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span></div>
                <div class="maroon-dp-grid"></div>
              </div>
            </div>
          </div>
          <div class="col-5">
            <label class="form-label">Label</label>
            <input type="text" name="holiday_label" class="form-control form-control-sm" placeholder="e.g. Independence Day">
          </div>
          <div class="col-2">
            <button type="submit" class="btn btn-primary btn-sm w-100">Add</button>
          </div>
        </form>
        <div class="table-responsive" style="max-height:260px;overflow-y:auto;">
          <table class="table table-sm mb-0">
            <thead><tr><th>Date</th><th>Label</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($holidays as $h): ?>
              <tr>
                <td><?= date('M d, Y', strtotime($h['date'])) ?></td>
                <td class="text-muted"><?= e($h['label'] ?? '') ?></td>
                <td class="text-end">
                  <form method="POST" action="<?= base_url('time-records') ?>" class="ajax-form d-inline" data-confirm-title="Remove this holiday?" data-confirm-icon="warning">
                    <input type="hidden" name="action" value="holiday_delete">
                    <input type="hidden" name="date" value="<?= e($dateFilter) ?>">
                    <input type="hidden" name="holiday_id" value="<?= (int) $h['id'] ?>">
                    <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Remove"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($holidays)): ?>
              <tr><td colspan="3" class="text-center text-muted py-3">No holidays added yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Academic Breaks Modal -->
<?php $breakDatePicker = static fn (string $name) => '
            <div class="maroon-dp">
              <input type="text" class="form-control form-control-sm maroon-dp-display" placeholder="Select date" readonly required>
              <input type="hidden" name="' . $name . '">
              <div class="maroon-dp-panel">
                <div class="maroon-dp-header">
                  <button type="button" class="maroon-dp-nav" data-dir="-1"><i class="bi bi-chevron-left"></i></button>
                  <span class="maroon-dp-month-label"></span>
                  <button type="button" class="maroon-dp-nav" data-dir="1"><i class="bi bi-chevron-right"></i></button>
                </div>
                <div class="maroon-dp-dow"><span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span></div>
                <div class="maroon-dp-grid"></div>
              </div>
            </div>'; ?>
<div class="modal fade" id="breaksModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-calendar-range me-2"></i>Academic Breaks</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted" style="font-size:.82rem;">
          School days with no punches inside a break are recorded as <strong>Academic Break</strong>, not Absent.
          Adding a break also changes imported absences already in that range; removing it changes them back.
        </p>
        <form method="POST" action="<?= base_url('time-records') ?>" class="ajax-form row g-2 align-items-end mb-3">
          <input type="hidden" name="action" value="break_add">
          <input type="hidden" name="date" value="<?= e($dateFilter) ?>">
          <div class="col-md-4">
            <label class="form-label">Name</label>
            <input type="text" name="break_label" class="form-control form-control-sm" placeholder="e.g. EOSY Break" maxlength="150" required>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label">Start</label><?= $breakDatePicker('break_start') ?>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label">End</label><?= $breakDatePicker('break_end') ?>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary btn-sm w-100">Add</button>
          </div>
        </form>
        <div class="table-responsive" style="max-height:260px;overflow-y:auto;">
          <table class="table table-sm mb-0">
            <thead><tr><th>Name</th><th>Dates</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($academicBreaks as $b): ?>
              <tr>
                <td class="fw-semibold"><?= e($b['label']) ?></td>
                <td class="text-muted"><?= date('M j, Y', strtotime($b['start_date'])) ?> – <?= date('M j, Y', strtotime($b['end_date'])) ?></td>
                <td class="text-end">
                  <form method="POST" action="<?= base_url('time-records') ?>" class="ajax-form d-inline" data-confirm-title="Remove this academic break?" data-confirm-text="Imported records it marked as Academic Break go back to Absent." data-confirm-icon="warning">
                    <input type="hidden" name="action" value="break_delete">
                    <input type="hidden" name="date" value="<?= e($dateFilter) ?>">
                    <input type="hidden" name="break_id" value="<?= (int) $b['id'] ?>">
                    <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Remove"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($academicBreaks)): ?>
              <tr><td colspan="3" class="text-center text-muted py-3">No academic breaks added yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$holidayMap = [];
foreach ($holidays as $h) {
    $holidayMap[$h['date']] = $h['label'] ?: 'Holiday';
}
$holidayMapJson  = json_encode($holidayMap);
$dateFilterJson  = json_encode($dateFilter);
$canManageHolidays = hasRole('admin', 'adas') ? 'true' : 'false';

$extraScript = <<<HTML
<script>
// Live clock in the header when viewing today. One shared timer, reset on
// each (re)injection so AJAX navigation doesn't stack intervals.
clearInterval(window.dtrClockTimer);
window.dtrClockTimer = setInterval(function () {
    var el = document.getElementById('dtrLiveClock');
    if (!el) { clearInterval(window.dtrClockTimer); return; }
    el.textContent = new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true });
}, 1000);

// Values are read inside the function body (not as top-level const/let)
// because this script gets re-injected on every AJAX filter navigation on
// this same page — a top-level const here would collide with the one the
// first real page load already declared in the shared global scope.
function maybeShowHolidayAlert() {
    var holidayMap        = {$holidayMapJson};
    var currentDateFilter = {$dateFilterJson};
    var canManageHolidays = {$canManageHolidays};
    var label = holidayMap[currentDateFilter];
    if (label === undefined) return;

    var niceDate = new Date(currentDateFilter + 'T00:00:00')
        .toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });

    Swal.fire({
        icon: 'info',
        title: niceDate + ' is a holiday',
        text: label,
        showDenyButton: canManageHolidays,
        confirmButtonText: canManageHolidays ? 'Manage Holidays' : 'Close',
        denyButtonText: 'Close',
        confirmButtonColor: '#800000',
        denyButtonColor: '#6b7280',
    }).then(function (result) {
        if (canManageHolidays && result.isConfirmed) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('holidaysModal')).show();
        }
    });
}
maybeShowHolidayAlert();

function filterByStatus(status) {
    var select = document.getElementById('statusFilterSelect');
    if (!select) return;
    select.value = (select.value === status) ? 'all' : status;
    select.form.requestSubmit();
}

function editRecord(r) {
    document.getElementById('editId').value      = r.id;
    document.getElementById('editName').textContent = r.employee_name;
    document.getElementById('editTimeIn').value  = r.time_in  || '';
    document.getElementById('editTimeOut').value = r.time_out || '';
    var editStatus = document.getElementById('editStatus');
    editStatus.value = r.status;
    editStatus.dispatchEvent(new Event('change'));
    document.getElementById('editRemarks').value = r.remarks  || '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal')).show();
}
function exportTable(tableId, filename) {
    const rows = [...document.getElementById(tableId).querySelectorAll('tr')].map(r =>
        [...r.querySelectorAll('th,td')].map(c => JSON.stringify(c.innerText.trim())).join(',')
    );
    const a = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(new Blob([rows.join('\\n')], {type:'text/csv'})),
        download: filename + '.csv'
    });
    a.click();
}
initInstantFilter('timeSearchInput', 'time-table', {
    emptyText: 'No matching records.',
    counterId: 'time-records-count',
    counterLabel: function (n) { return n; },
});
</script>
HTML;
include APPPATH . 'Views/layout/footer.php';
?>

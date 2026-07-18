<?php include APPPATH . 'Views/layout/header.php'; ?>

<!-- Page Header -->
<div class="page-header">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3" style="position:relative;z-index:1">
    <div>
      <h4><i class="bi bi-clock me-2"></i>Daily Time Records</h4>
      <p>Employee attendance, time-in &amp; time-out monitoring</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
      <div class="live-badge"><div class="live-dot"></div><?= date('F d, Y', strtotime($dateFilter)) ?></div>
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
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-calendar3 text-muted"></i>
          <input type="date" name="date" value="<?= e($dateFilter) ?>"
                 class="form-control form-control-sm" style="width:auto;"
                 onchange="this.form.submit()">
        </div>

        <div class="input-group input-group-sm" style="max-width:220px;">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="q" id="timeSearchInput" value="<?= e($search) ?>"
                 class="form-control border-start-0 ps-0" placeholder="Search name, ID, remarks...">
        </div>

        <select name="sort" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
          <option value="name_az" <?= $sort==='name_az' ? 'selected' : '' ?>>Name A-Z</option>
          <option value="status"  <?= $sort==='status'  ? 'selected' : '' ?>>Status</option>
          <option value="time_in" <?= $sort==='time_in' ? 'selected' : '' ?>>Time In</option>
        </select>
      </form>
      <div class="ms-auto d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="exportTable('time-table','time_records')">
          <i class="bi bi-download me-1"></i>Export
        </button>
        <?php if (hasRole('admin','adas')): ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
          <i class="bi bi-plus-lg me-1"></i>Add Record
        </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Summary stats -->
<div class="row g-3 mb-4">
  <?php foreach ([
    ['Present',  'present',  'bi-check-circle-fill', $summary['Present']],
    ['Late',     'late',     'bi-alarm-fill',        $summary['Late']],
    ['Absent',   'absent',   'bi-x-circle-fill',     $summary['Absent']],
    ['On Leave', 'on-leave', 'bi-calendar-check',    $summary['On Leave']],
  ] as [$label,$cls,$icon,$cnt]): ?>
  <div class="col-6 col-sm-3">
    <div class="card text-center py-3">
      <div class="mb-2">
        <span class="badge badge-<?= $cls ?>" style="font-size:.8rem;padding:.4rem .8rem;">
          <i class="bi <?= $icon ?> me-1"></i><?= $label ?>
        </span>
      </div>
      <div class="fw-bold" style="font-size:1.8rem;color:#111"><?= $cnt ?></div>
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
      <?= array_sum($summary) ?> total employees
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
            <td class="fw-semibold"><?= e($r['employee_name']) ?></td>
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
              $icons = ['Present'=>'bi-check-circle-fill','Late'=>'bi-exclamation-circle-fill','Absent'=>'bi-x-circle-fill','On Leave'=>'bi-calendar-check-fill'];
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

<!-- Add Record Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-clock me-2"></i>Add Time Record</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('time-records') ?>" class="ajax-form">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label">Date</label>
              <input type="date" name="date" class="form-control" value="<?= e($dateFilter) ?>" required>
            </div>
            <div class="col-6">
              <label class="form-label">Employee ID</label>
              <input type="text" name="employee_id" class="form-control" placeholder="T-001" required>
            </div>
            <div class="col-12">
              <label class="form-label">Employee Name</label>
              <input type="text" name="employee_name" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label">Time In</label>
              <input type="time" name="time_in" class="form-control">
            </div>
            <div class="col-6">
              <label class="form-label">Time Out</label>
              <input type="time" name="time_out" class="form-control">
            </div>
            <div class="col-6">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option>Present</option><option>Late</option>
                <option>Absent</option><option>On Leave</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Remarks</label>
              <input type="text" name="remarks" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Record</button>
        </div>
      </form>
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
              <select name="status" id="editStatus" class="form-select">
                <option>Present</option><option>Late</option>
                <option>Absent</option><option>On Leave</option>
              </select>
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

<?php
$extraScript = "<script>
function editRecord(r) {
    document.getElementById('editId').value      = r.id;
    document.getElementById('editName').textContent = r.employee_name;
    document.getElementById('editTimeIn').value  = r.time_in  || '';
    document.getElementById('editTimeOut').value = r.time_out || '';
    document.getElementById('editStatus').value  = r.status;
    document.getElementById('editRemarks').value = r.remarks  || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
function exportTable(tableId, filename) {
    const rows = [...document.getElementById(tableId).querySelectorAll('tr')].map(r =>
        [...r.querySelectorAll('th,td')].map(c => JSON.stringify(c.innerText.trim())).join(',')
    );
    const a = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(new Blob([rows.join('\n')], {type:'text/csv'})),
        download: filename + '.csv'
    });
    a.click();
}
initLiveSearch('timeSearchInput', 'filterForm');
</script>";
include APPPATH . 'Views/layout/footer.php';
?>

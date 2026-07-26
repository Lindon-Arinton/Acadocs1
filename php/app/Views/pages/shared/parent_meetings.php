<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <h4><i class="bi bi-people me-2"></i>Parent Meetings</h4>
  <p>Parent-Teacher Conference attendance tracking</p>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
  <i class="bi bi-check-circle me-2"></i>Meeting saved.
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-body py-3">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <form method="GET" action="<?= base_url('parent-meetings') ?>" id="filterForm" class="d-flex align-items-center gap-2 flex-wrap">
        <div class="input-group input-group-sm" style="max-width:260px;">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="q" id="meetingSearchInput" value="<?= e($search) ?>"
                 class="form-control border-start-0 ps-0" placeholder="Search meeting title...">
        </div>
        <select name="sort" class="form-select form-select-sm" style="width:auto;" onchange="this.form.requestSubmit()">
          <option value="newest"        <?= $sort==='newest'        ? 'selected' : '' ?>>Newest First</option>
          <option value="oldest"        <?= $sort==='oldest'        ? 'selected' : '' ?>>Oldest First</option>
          <option value="attendance_hi" <?= $sort==='attendance_hi' ? 'selected' : '' ?>>Attendance Rate (High-Low)</option>
          <option value="attendance_lo" <?= $sort==='attendance_lo' ? 'selected' : '' ?>>Attendance Rate (Low-High)</option>
        </select>
      </form>
      <?php if (hasRole('admin','adas')): ?>
      <button class="btn btn-maroon btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#addMeetingModal">
        <i class="bi bi-plus-lg me-1"></i>Add Meeting
      </button>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Today's Meeting(s) -->
<?php if (!empty($todayMeetings)): ?>
<?php foreach ($todayMeetings as $tm): ?>
<div class="card mb-4 border-0" style="background:linear-gradient(135deg,#9d174d,#800000,#c2410c);">
  <div class="card-body text-white d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <div class="small text-uppercase fw-semibold" style="opacity:.85;letter-spacing:.03em;"><i class="bi bi-calendar-event me-1"></i>Today's Meeting</div>
      <h5 class="fw-bold mb-1"><?= e($tm['title']) ?></h5>
      <div class="small" style="opacity:.9;">Expected Parents: <?= number_format($tm['expected_parents']) ?></div>
    </div>
    <?php if (! empty($tm['attendance_file_path'])): ?>
    <div class="text-end">
      <span class="badge bg-light text-success mb-2 d-inline-block"><i class="bi bi-check-circle-fill me-1"></i>Attendance recorded</span><br>
      <a href="<?= base_url('parent-meetings/' . $tm['id'] . '/attendance-file') ?>" class="btn btn-light btn-sm">
        <i class="bi bi-download me-1"></i>Download File
      </a>
    </div>
    <?php elseif (hasRole('admin', 'adas')): ?>
    <button type="button" class="btn btn-light btn-sm" onclick="openUploadModal(<?= (int) $tm['id'] ?>, <?= htmlspecialchars(json_encode($tm['title']), ENT_QUOTES) ?>)">
      <i class="bi bi-file-earmark-arrow-up me-1"></i>Add Attendance File
    </button>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- Chart -->
<?php if (!empty($chartMeetings)): ?>
<div class="card mb-4">
  <div class="card-header bg-white py-3 fw-semibold">
    <i class="bi bi-bar-chart me-2 text-muted"></i>Attendance Comparison
  </div>
  <div class="card-body"><canvas id="meetingChart" height="120"></canvas></div>
</div>
<?php endif; ?>

<div class="row g-4">
  <?php foreach ($meetings as $m):
    $hasFile = ! empty($m['attendance_file_path']);
    $pct     = $hasFile ? (float) $m['attendance_rate'] : null;
    $color   = $pct === null ? '#6b7280' : ($pct >= 85 ? '#065f46' : ($pct >= 70 ? '#713f12' : '#991b1b'));
    $bg      = $pct === null ? '#f3f4f6' : ($pct >= 85 ? '#d1fae5' : ($pct >= 70 ? '#fef9c3' : '#fee2e2'));
  ?>
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <h6 class="fw-bold mb-0"><?= e($m['title']) ?></h6>
          <span class="badge rounded-pill px-3 py-2" style="background:<?= $bg ?>;color:<?= $color ?>;">
            <?= $pct === null ? 'Pending' : $pct . '%' ?>
          </span>
        </div>
        <p class="text-muted small mb-3"><i class="bi bi-calendar3 me-1"></i><?= date('F d, Y', strtotime($m['date'])) ?></p>

        <?php if ($hasFile): ?>
        <div class="d-flex justify-content-between small mb-2">
          <span class="text-muted">Attendance Rate</span>
          <strong style="color:<?= $color ?>"><?= $pct ?>%</strong>
        </div>
        <div class="progress mb-4" style="height:10px;">
          <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $color ?>!important;border-radius:8px;"></div>
        </div>

        <div class="row g-2 text-center mb-3">
          <div class="col-6">
            <div class="p-3 rounded-3" style="background:#f8f9fa;">
              <div class="fw-bold fs-4"><?= number_format($m['actual_attendance']) ?></div>
              <div class="text-muted small">Attended</div>
            </div>
          </div>
          <div class="col-6">
            <div class="p-3 rounded-3" style="background:#f8f9fa;">
              <div class="fw-bold fs-4"><?= number_format($m['expected_parents']) ?></div>
              <div class="text-muted small">Expected</div>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <a href="<?= base_url('parent-meetings/' . $m['id'] . '/attendance-file') ?>" class="btn btn-outline-secondary btn-sm flex-grow-1">
            <i class="bi bi-download me-1"></i><?= e($m['attendance_file_name']) ?>
          </a>
          <?php if (hasRole('admin', 'adas')): ?>
          <button type="button" class="btn btn-outline-secondary btn-sm" title="Replace file" onclick="openUploadModal(<?= (int) $m['id'] ?>, <?= htmlspecialchars(json_encode($m['title']), ENT_QUOTES) ?>)">
            <i class="bi bi-arrow-repeat"></i>
          </button>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <p class="text-muted small mb-3"><i class="bi bi-hourglass-split me-1"></i>Attendance not yet recorded. Expected Parents: <?= number_format($m['expected_parents']) ?>.</p>
        <?php if (hasRole('admin', 'adas')): ?>
        <button type="button" class="btn btn-maroon btn-sm w-100" onclick="openUploadModal(<?= (int) $m['id'] ?>, <?= htmlspecialchars(json_encode($m['title']), ENT_QUOTES) ?>)">
          <i class="bi bi-file-earmark-arrow-up me-1"></i>Upload Attendance File
        </button>
        <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($meetings)): ?>
  <div class="col-12">
    <div class="card"><div class="card-body text-center py-5">
      <i class="bi bi-inbox fs-1 d-block mb-3 text-muted"></i>
      <p class="text-muted mb-0">No meetings found matching your search.</p>
    </div></div>
  </div>
  <?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addMeetingModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header" style="background:var(--maroon);color:#fff;">
      <h6 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Add Meeting Record</h6>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST" action="<?= base_url('parent-meetings') ?>" class="ajax-form">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12"><label class="form-label small fw-semibold">Meeting Title</label>
            <input type="text" name="title" class="form-control" required></div>
          <div class="col-6">
            <label class="form-label small fw-semibold">Date</label>
            <div class="maroon-dp" data-min="<?= date('Y-m-d') ?>">
              <input type="text" class="form-control maroon-dp-display" placeholder="Select date" readonly required>
              <input type="hidden" name="date">
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
          <div class="col-6"><label class="form-label small fw-semibold">Expected Parents</label>
            <input type="number" name="expected_parents" class="form-control" value="450" required></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-maroon">Save</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Upload Attendance Modal -->
<div class="modal fade" id="uploadAttendanceModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header" style="background:var(--maroon);color:#fff;">
      <h6 class="modal-title fw-bold"><i class="bi bi-file-earmark-arrow-up me-2"></i>Upload Attendance File</h6>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST" action="<?= base_url('parent-meetings') ?>" class="ajax-form" enctype="multipart/form-data">
      <input type="hidden" name="action" value="upload_attendance">
      <input type="hidden" name="meeting_id" id="uploadMeetingId" value="">
      <div class="modal-body">
        <p class="text-muted small mb-3">Meeting: <strong id="uploadMeetingTitle"></strong></p>
        <label class="form-label small fw-semibold">Attendance Excel File</label>
        <input type="file" name="attendance_file" class="form-control" accept=".xlsx,.xls" required>
        <p class="text-muted small mt-2 mb-0">One row per parent/guardian who attended, with a header row (e.g. Name, Student, Grade/Section).</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-maroon">Upload</button>
      </div>
    </form>
  </div></div>
</div>

<?php
$chartScript = '';
if (! empty($chartMeetings)) {
    $chartScript = '
new Chart(document.getElementById("meetingChart"),{
  type:"bar",
  data:{
    labels:' . json_encode(array_map(fn($m)=>date('M Y',strtotime($m['date'])),$chartMeetings)) . ',
    datasets:[
      {label:"Expected",data:' . json_encode(array_column($chartMeetings,'expected_parents')) . ',backgroundColor:"#e5e7eb",borderRadius:6},
      {label:"Attended",data:' . json_encode(array_column($chartMeetings,'actual_attendance')) . ',backgroundColor:"#800000",borderRadius:6}
    ]
  },
  options:{responsive:true,scales:{y:{beginAtZero:true,grid:{color:"#f0f0f0"}},x:{grid:{display:false}}}}
});';
}

$extraScript = '<script>' . $chartScript . '
function openUploadModal(meetingId, title) {
  document.getElementById("uploadMeetingId").value = meetingId;
  document.getElementById("uploadMeetingTitle").textContent = title;
  const modalEl = document.getElementById("uploadAttendanceModal");
  bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

initLiveSearch("meetingSearchInput", "filterForm");
</script>';
include APPPATH . 'Views/layout/footer.php';
?>

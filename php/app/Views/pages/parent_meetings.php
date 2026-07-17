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

<div class="d-flex justify-content-end mb-4">
  <?php if (hasRole('admin','secretary')): ?>
  <button class="btn btn-maroon btn-sm" data-bs-toggle="modal" data-bs-target="#addMeetingModal">
    <i class="bi bi-plus-lg me-1"></i>Add Meeting
  </button>
  <?php endif; ?>
</div>

<!-- Chart -->
<?php if (!empty($meetings)): ?>
<div class="card mb-4">
  <div class="card-header bg-white py-3 fw-semibold">
    <i class="bi bi-bar-chart me-2 text-muted"></i>Attendance Comparison
  </div>
  <div class="card-body"><canvas id="meetingChart" height="120"></canvas></div>
</div>
<?php endif; ?>

<div class="row g-4">
  <?php foreach ($meetings as $m):
    $pct  = (float)$m['attendance_rate'];
    $color = $pct >= 85 ? '#065f46' : ($pct >= 70 ? '#713f12' : '#991b1b');
    $bg    = $pct >= 85 ? '#d1fae5' : ($pct >= 70 ? '#fef9c3' : '#fee2e2');
  ?>
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <h6 class="fw-bold mb-0"><?= e($m['title']) ?></h6>
          <span class="badge rounded-pill px-3 py-2" style="background:<?= $bg ?>;color:<?= $color ?>;">
            <?= $pct ?>%
          </span>
        </div>
        <p class="text-muted small mb-3"><i class="bi bi-calendar3 me-1"></i><?= date('F d, Y', strtotime($m['date'])) ?></p>

        <div class="d-flex justify-content-between small mb-2">
          <span class="text-muted">Attendance Rate</span>
          <strong style="color:<?= $color ?>"><?= $pct ?>%</strong>
        </div>
        <div class="progress mb-4" style="height:10px;">
          <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $color ?>!important;border-radius:8px;"></div>
        </div>

        <div class="row g-2 text-center">
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
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addMeetingModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header" style="background:var(--maroon);color:#fff;">
      <h6 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Add Meeting Record</h6>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST" action="<?= base_url('parent-meetings') ?>">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12"><label class="form-label small fw-semibold">Meeting Title</label>
            <input type="text" name="title" class="form-control" required></div>
          <div class="col-6"><label class="form-label small fw-semibold">Date</label>
            <input type="date" name="date" class="form-control" required></div>
          <div class="col-6"><label class="form-label small fw-semibold">Expected Parents</label>
            <input type="number" name="expected_parents" class="form-control" value="450" required></div>
          <div class="col-6"><label class="form-label small fw-semibold">Actual Attendance</label>
            <input type="number" name="actual_attendance" class="form-control" required></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-maroon">Save</button>
      </div>
    </form>
  </div></div>
</div>

<?php
$extraScript = '<script>
new Chart(document.getElementById("meetingChart"),{
  type:"bar",
  data:{
    labels:' . json_encode(array_map(fn($m)=>date('M Y',strtotime($m['date'])),array_reverse($meetings))) . ',
    datasets:[
      {label:"Expected",data:' . json_encode(array_column(array_reverse($meetings),'expected_parents')) . ',backgroundColor:"#e5e7eb",borderRadius:6},
      {label:"Attended",data:' . json_encode(array_column(array_reverse($meetings),'actual_attendance')) . ',backgroundColor:"#800000",borderRadius:6}
    ]
  },
  options:{responsive:true,scales:{y:{beginAtZero:true,grid:{color:"#f0f0f0"}},x:{grid:{display:false}}}}
});
</script>';
include APPPATH . 'Views/layout/footer.php';
?>

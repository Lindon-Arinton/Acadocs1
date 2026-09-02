<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <h4><i class="bi bi-house-fill me-2"></i>Welcome, <?= e($user['name']) ?>!</h4>
  <p>ADAS Dashboard · <?= date('F d, Y', strtotime($dateFilter)) ?></p>
</div>

<!-- Time Records Summary -->
<div class="row g-3 mb-4">
  <?php foreach ([
    ['Present','success','bi-check-circle'],
    ['Late','warning','bi-clock-history'],
    ['Absent','danger','bi-x-circle'],
    ['On Leave','secondary','bi-calendar-x'],
  ] as [$s,$c,$i]): ?>
  <div class="col-6 col-lg-3">
    <div class="kpi-card text-center">
      <i class="bi <?= $i ?> text-<?= $c ?> fs-2 mb-2"></i>
      <div class="fw-bold fs-3"><?= $timeSummary[$s] ?></div>
      <div class="text-muted small"><?= $s ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <!-- Time Records shortcut -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-clock me-2 text-muted"></i>Today's Time Records</span>
        <a href="<?= base_url('time-records') ?>" class="btn btn-maroon btn-sm">
          <i class="bi bi-arrow-right me-1"></i>Manage
        </a>
      </div>
      <div class="card-body">
        <p class="text-muted small mb-0">
          <?= array_sum($timeSummary) ?> employee record<?= array_sum($timeSummary) === 1 ? '' : 's' ?> logged for today.
        </p>
      </div>
    </div>
  </div>

  <!-- My Attendance -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-calendar-check me-2 text-muted"></i>My Attendance</span>
        <?php if (! empty($myAttendanceMonths)): ?>
        <form method="GET" action="<?= base_url('adas-dashboard') ?>">
          <div class="maroon-select maroon-select-sm" style="width:auto;">
            <select name="att_month" class="maroon-select-native" onchange="this.form.requestSubmit()">
              <option value="all" <?= $attMonth === 'all' ? 'selected' : '' ?>>All Time</option>
              <?php foreach ($myAttendanceMonths as $ym): ?>
              <option value="<?= e($ym) ?>" <?= $attMonth === $ym ? 'selected' : '' ?>><?= date('F Y', strtotime($ym . '-01')) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
            <div class="maroon-select-panel"></div>
          </div>
        </form>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <div class="row g-3 text-center">
          <div class="col-6">
            <div class="fw-bold fs-4 text-success"><?= $myAttendance['Present'] ?></div>
            <div class="text-muted small">Present</div>
          </div>
          <div class="col-6">
            <div class="fw-bold fs-4 text-danger"><?= $myAttendance['Absent'] ?></div>
            <div class="text-muted small">Absent</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include APPPATH . 'Views/layout/footer.php'; ?>

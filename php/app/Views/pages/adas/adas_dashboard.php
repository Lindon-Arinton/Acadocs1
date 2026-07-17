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

  <!-- DepEd Documents -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-clipboard2-check me-2 text-muted"></i>DepEd Documents</span>
        <a href="<?= base_url('deped-documents') ?>" class="btn btn-sm btn-outline-secondary">View All</a>
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($depedDocs as $d): ?>
        <li class="list-group-item py-2 px-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold small"><?= e($d['document_type']) ?></div>
              <div class="text-muted" style="font-size:.72rem;">Due <?= date('M d', strtotime($d['due_date'])) ?></div>
            </div>
            <span class="text-muted small"><?= $d['completion_rate'] ?>%</span>
          </div>
        </li>
        <?php endforeach; ?>
        <?php if (empty($depedDocs)): ?>
        <li class="list-group-item py-4 text-center text-muted">No documents yet.</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>

<?php include APPPATH . 'Views/layout/footer.php'; ?>

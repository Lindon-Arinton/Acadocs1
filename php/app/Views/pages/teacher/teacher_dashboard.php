<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <h4><i class="bi bi-house-fill me-2"></i>Welcome, <?= e($user['name']) ?>!</h4>
  <p><?= $teacher ? e($teacher['grade_level']) . ' · ' . e(implode(', ', json_decode($teacher['subjects'] ?? '[]', true) ?: [])) : 'Teacher Dashboard' ?></p>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <?php foreach ([
    ['Submitted','primary','bi-upload'],
    ['Reviewed','success','bi-check-circle'],
    ['Pending','warning','bi-clock'],
  ] as [$s,$c,$i]): ?>
  <div class="col-4">
    <div class="kpi-card text-center">
      <i class="bi <?= $i ?> text-<?= $c ?> fs-2 mb-2"></i>
      <div class="fw-bold fs-3"><?= $stats[$s] ?></div>
      <div class="text-muted small"><?= $s ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php if ($teacher): ?>
<!-- Submission Rate -->
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-2">
      <span class="fw-semibold">Submission Rate</span>
      <strong style="color:var(--maroon)"><?= $teacher['submission_rate'] ?>%</strong>
    </div>
    <div class="progress" style="height:12px;">
      <div class="progress-bar" style="width:<?= $teacher['submission_rate'] ?>%;background:var(--maroon)!important;border-radius:8px;"></div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="row g-4">
  <!-- Recent Submissions -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-file-earmark-check me-2 text-muted"></i>My Submissions</span>
        <a href="<?= base_url('submit-documents') ?>" class="btn btn-maroon btn-sm">
          <i class="bi bi-plus-lg me-1"></i>New
        </a>
      </div>
      <div class="card-body p-0">
        <table class="table table-hover mb-0">
          <thead><tr><th>Type</th><th>Subject</th><th>Date</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($myDocs as $d):
              $bm = ['Submitted'=>'badge-submitted','Reviewed'=>'badge-reviewed','Pending'=>'badge-pending','Returned'=>'badge-returned'];
            ?>
            <tr>
              <td><?= e($d['type']) ?></td>
              <td><?= e($d['subject']) ?></td>
              <td class="small text-muted"><?= date('M d', strtotime($d['date_submitted'])) ?></td>
              <td><span class="status-pill <?= $bm[$d['status']] ?? '' ?>"><?= e($d['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($myDocs)): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">No submissions yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Announcements + Links -->
  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-header bg-white py-3 fw-semibold">
        <i class="bi bi-megaphone me-2 text-muted"></i>Announcements
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($announcements as $a): ?>
        <li class="list-group-item py-2 px-3">
          <div class="fw-semibold small"><?= e($a['title']) ?></div>
          <div class="text-muted" style="font-size:.72rem;"><?= date('M d', strtotime($a['date'])) ?> · <?= e($a['type']) ?></div>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="card">
      <div class="card-header bg-white py-3 fw-semibold">
        <i class="bi bi-link-45deg me-2 text-muted"></i>Quick Links
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($links as $l): ?>
        <li class="list-group-item py-2 px-3">
          <a href="<?= e($l['url']) ?>" target="_blank" rel="noopener" class="text-decoration-none small fw-semibold d-flex align-items-center gap-2">
            <i class="bi bi-box-arrow-up-right text-muted"></i><?= e($l['title']) ?>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<?php include APPPATH . 'Views/layout/footer.php'; ?>

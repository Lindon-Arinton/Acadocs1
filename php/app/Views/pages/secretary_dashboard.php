<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <h4><i class="bi bi-house-fill me-2"></i>Welcome, <?= e($user['name']) ?>!</h4>
  <p>Secretary Dashboard</p>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-6">
    <div class="kpi-card text-center">
      <i class="bi bi-megaphone-fill text-primary fs-2 mb-2"></i>
      <div class="fw-bold fs-3"><?= $announcementCount ?></div>
      <div class="text-muted small">Active Announcements</div>
    </div>
  </div>
  <div class="col-6">
    <div class="kpi-card text-center">
      <i class="bi bi-link-45deg text-success fs-2 mb-2"></i>
      <div class="fw-bold fs-3"><?= $linkCount ?></div>
      <div class="text-muted small">Document Links</div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Announcements -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-megaphone me-2 text-muted"></i>Recent Announcements</span>
        <a href="<?= base_url('announcements') ?>" class="btn btn-sm btn-outline-secondary">View All</a>
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($announcements as $a): ?>
        <li class="list-group-item py-2 px-3">
          <div class="fw-semibold small"><?= e($a['title']) ?></div>
          <div class="text-muted" style="font-size:.72rem;"><?= date('M d', strtotime($a['date'])) ?> · <?= e($a['type']) ?></div>
        </li>
        <?php endforeach; ?>
        <?php if (empty($announcements)): ?>
        <li class="list-group-item py-4 text-center text-muted">No announcements yet.</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <!-- Document Links -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-link-45deg me-2 text-muted"></i>Recent Document Links</span>
        <a href="<?= base_url('document-links') ?>" class="btn btn-sm btn-outline-secondary">View All</a>
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($links as $l): ?>
        <li class="list-group-item py-2 px-3">
          <a href="<?= e($l['url']) ?>" target="_blank" rel="noopener" class="text-decoration-none small fw-semibold d-flex align-items-center gap-2">
            <i class="bi bi-box-arrow-up-right text-muted"></i><?= e($l['title']) ?>
          </a>
        </li>
        <?php endforeach; ?>
        <?php if (empty($links)): ?>
        <li class="list-group-item py-4 text-center text-muted">No document links yet.</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>

<?php include APPPATH . 'Views/layout/footer.php'; ?>

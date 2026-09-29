<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <h4><i class="bi bi-file-earmark-check me-2"></i>Document Management</h4>
  <p>Task uploads, filed into one folder per task</p>
</div>

<div class="card mb-4">
  <div class="card-body py-3">
    <form method="GET" action="<?= base_url('documents') ?>" id="filterForm" class="d-flex flex-wrap gap-2 align-items-center">
      <h6 class="fw-bold mb-0"><i class="bi bi-folder2 me-2"></i>Task Folders</h6>
      <div class="input-group input-group-sm ms-auto" style="max-width:260px;">
        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
        <input type="text" name="q" id="docSearchInput" value="<?= e($search) ?>"
               class="form-control border-start-0 ps-0" placeholder="Search folders...">
      </div>
    </form>
  </div>
</div>

<!-- Task folders: one per task, auto-created on the first upload to it -->
<?php if (empty($folders)): ?>
<div class="card">
  <div class="card-body text-center py-5 text-muted small">
    <?= $search !== ''
        ? 'No folders match "' . e($search) . '".'
        : 'No task folders yet. A folder is created automatically when someone uploads to a task.' ?>
  </div>
</div>
<?php else: ?>
<div class="row g-3">
  <?php foreach ($folders as $folder): ?>
  <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
    <a href="<?= base_url('documents?folder=' . $folder['id']) ?>" class="card doc-folder-card h-100 text-decoration-none">
      <div class="card-body d-flex gap-3 align-items-start">
        <i class="bi bi-folder-fill doc-folder-icon"></i>
        <div class="min-w-0">
          <div class="fw-semibold text-truncate" style="color:var(--text);" title="<?= e($folder['name']) ?>"><?= e($folder['name']) ?></div>
          <div class="small text-muted">
            <?= (int) $folder['file_count'] ?> file<?= (int) $folder['file_count'] === 1 ? '' : 's' ?>
            · <?= (int) $folder['submitter_count'] ?> uploader<?= (int) $folder['submitter_count'] === 1 ? '' : 's' ?>
          </div>
          <?php if ((int) $folder['to_review_count'] > 0): ?>
          <span class="status-pill badge-pending mt-1 d-inline-block"><?= (int) $folder['to_review_count'] ?> pending</span>
          <?php elseif ($folder['last_upload']): ?>
          <div class="small text-muted">Updated <?= date('M d, Y', strtotime($folder['last_upload'])) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$extraScript = "<script>initLiveSearch('docSearchInput', 'filterForm');</script>";
include APPPATH . 'Views/layout/footer.php';
?>

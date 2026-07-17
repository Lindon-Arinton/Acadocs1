<?php
$extCfg = [
    'pdf'  => ['bi-file-earmark-pdf-fill',   '#991b1b', '#fee2e2'],
    'doc'  => ['bi-file-earmark-word-fill',  '#1e40af', '#dbeafe'],
    'docx' => ['bi-file-earmark-word-fill',  '#1e40af', '#dbeafe'],
    'xls'  => ['bi-file-earmark-excel-fill', '#065f46', '#d1fae5'],
    'xlsx' => ['bi-file-earmark-excel-fill', '#065f46', '#d1fae5'],
    'csv'  => ['bi-filetype-csv',            '#065f46', '#d1fae5'],
    'ppt'  => ['bi-file-earmark-ppt-fill',   '#c2410c', '#ffedd5'],
    'pptx' => ['bi-file-earmark-ppt-fill',   '#c2410c', '#ffedd5'],
    'txt'  => ['bi-file-earmark-text-fill',  '#374151', '#f3f4f6'],
    'zip'  => ['bi-file-earmark-zip-fill',   '#713f12', '#fef9c3'],
    'rar'  => ['bi-file-earmark-zip-fill',   '#713f12', '#fef9c3'],
    'jpg'  => ['bi-file-earmark-image-fill', '#3730a3', '#eff6ff'],
    'jpeg' => ['bi-file-earmark-image-fill', '#3730a3', '#eff6ff'],
    'png'  => ['bi-file-earmark-image-fill', '#3730a3', '#eff6ff'],
];

if (! function_exists('tplFormatBytes')) {
    function tplFormatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }
}

$grouped = [];
foreach ($templates as $t) {
    $grouped[$t['category_id']][] = $t;
}
$hasActiveFilter = $search !== '' || $fileType !== 'all';
$canManage       = hasRole('admin', 'adas');

include APPPATH . 'Views/layout/header.php';
?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3" style="position:relative;z-index:1">
    <div>
      <h4><i class="bi bi-folder2-open me-2"></i>Templates</h4>
      <p>Certificates, forms, checklists &amp; reference documents organized by section</p>
    </div>
    <?php if ($canManage): ?>
    <div class="d-flex gap-2">
      <button class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);"
              data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="bi bi-folder-plus me-1"></i>Add Section
      </button>
      <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#uploadModal">
        <i class="bi bi-cloud-upload me-1"></i>Upload Template
      </button>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-check-circle-fill"></i><?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Search + Filters -->
<div class="card mb-4">
  <div class="card-body py-3">
    <form method="GET" action="<?= base_url('templates') ?>" class="d-flex gap-2 flex-wrap align-items-center">
      <div class="input-group input-group-sm" style="flex:1;min-width:220px;max-width:340px;">
        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search templates by title or file name..."
               class="form-control border-start-0 ps-0">
      </div>

      <select name="category" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
        <option value="0" <?= $catId === 0 ? 'selected' : '' ?>>All Sections</option>
        <?php foreach ($categories as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $catId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <select name="type" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
        <option value="all" <?= $fileType === 'all' ? 'selected' : '' ?>>All File Types</option>
        <?php foreach ($fileTypes as $ft): ?>
        <option value="<?= e($ft) ?>" <?= $fileType === $ft ? 'selected' : '' ?>><?= strtoupper(e($ft)) ?></option>
        <?php endforeach; ?>
      </select>

      <select name="sort" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
        <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Newest First</option>
        <option value="date_asc"  <?= $sort === 'date_asc'  ? 'selected' : '' ?>>Oldest First</option>
        <option value="section"   <?= $sort === 'section'   ? 'selected' : '' ?>>Sort by Section</option>
        <option value="type"      <?= $sort === 'type'      ? 'selected' : '' ?>>Sort by File Type</option>
      </select>

      <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-search me-1"></i>Search</button>
      <?php if ($search !== '' || $catId !== 0 || $fileType !== 'all' || $sort !== 'date_desc'): ?>
      <a href="<?= base_url('templates') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Sections -->
<?php $rendered = 0; foreach ($categories as $cat):
  if ($catId > 0 && $catId !== (int) $cat['id']) {
      continue;
  }
  $items = $grouped[$cat['id']] ?? [];
  if (empty($items) && $hasActiveFilter) {
      continue;
  }
  $rendered++;
?>
<div class="card mb-4">
  <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
    <div>
      <span class="fw-semibold"><i class="bi bi-folder2 me-2 text-muted"></i><?= e($cat['name']) ?></span>
      <span class="badge badge-secondary ms-2"><?= count($items) ?></span>
    </div>
    <?php if ($canManage && count($items) === 0): ?>
    <form method="POST" action="<?= base_url('templates') ?>" class="ajax-form"
          data-confirm-title="Delete this section?" data-confirm-text="This section has no templates and will be removed.">
      <input type="hidden" name="action" value="delete_category">
      <input type="hidden" name="id" value="<?= $cat['id'] ?>">
      <button class="btn btn-ghost btn-sm text-danger" title="Delete empty section"><i class="bi bi-trash"></i></button>
    </form>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <?php if (empty($items)): ?>
    <p class="text-muted mb-0 text-center py-3"><i class="bi bi-inbox fs-4 d-block mb-2"></i>No templates uploaded in this section yet.</p>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ($items as $t):
        [$icon, $tc, $bg] = $extCfg[$t['file_ext']] ?? ['bi-file-earmark-fill', '#374151', '#f3f4f6'];
      ?>
      <div class="col-md-6 col-xl-4">
        <div class="doclink-card h-100">
          <div class="d-flex gap-3 align-items-start">
            <div style="width:40px;height:40px;border-radius:10px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="bi <?= $icon ?>" style="color:<?= $tc ?>;font-size:1.1rem;"></i>
            </div>
            <div class="flex-grow-1" style="min-width:0;">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <span class="badge mb-1" style="background:<?= $bg ?>;color:<?= $tc ?>;border:1px solid <?= $tc ?>22;font-size:.68rem;">
                  <?= strtoupper(e($t['file_ext'])) ?>
                </span>
                <?php if ($canManage): ?>
                <form method="POST" action="<?= base_url('templates') ?>" class="ajax-form"
                      data-confirm-title="Delete this template?">
                  <input type="hidden" name="action" value="delete_template">
                  <input type="hidden" name="id" value="<?= $t['id'] ?>">
                  <button class="btn btn-ghost btn-sm text-danger py-0 px-1"><i class="bi bi-x-lg"></i></button>
                </form>
                <?php endif; ?>
              </div>
              <h6 class="fw-bold mb-1 text-truncate" style="font-size:.88rem" title="<?= e($t['title']) ?>"><?= e($t['title']) ?></h6>
              <p class="text-muted mb-3 text-truncate" style="font-size:.75rem;" title="<?= e($t['file_name']) ?>">
                <?= e($t['file_name']) ?> · <?= tplFormatBytes((int) $t['file_size']) ?>
              </p>
              <a href="<?= base_url('templates/download/' . $t['id']) ?>"
                 class="btn btn-sm w-100" style="border:1.5px solid <?= $tc ?>;color:<?= $tc ?>;background:transparent;">
                <i class="bi bi-download me-1"></i>Download
              </a>
            </div>
          </div>
          <div class="d-flex justify-content-between mt-3 pt-2 border-top" style="font-size:.7rem;color:#9ca3af;">
            <span><i class="bi bi-person me-1"></i><?= e($t['uploaded_by']) ?></span>
            <span><?= date('M d, Y', strtotime($t['date_added'])) ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<?php if ($rendered === 0): ?>
<div class="card"><div class="card-body text-center py-5">
  <i class="bi bi-inbox fs-1 d-block mb-3 text-muted"></i>
  <p class="text-muted mb-0">No templates found matching your search/filters.</p>
</div></div>
<?php endif; ?>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-folder-plus me-2"></i>Add Section</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('templates') ?>" class="ajax-form">
        <input type="hidden" name="action" value="add_category">
        <div class="modal-body">
          <label class="form-label">Section Name</label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Certificate, BAC Forms, COA Forms" required>
          <p class="text-muted mt-2 mb-0" style="font-size:.78rem;">Only add a section if it doesn't already exist below.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Section</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Upload Template Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-cloud-upload me-2"></i>Upload Template</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('templates') ?>" class="ajax-form" enctype="multipart/form-data"
            data-confirm-title="Upload this template?">
        <input type="hidden" name="action" value="upload">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Section</label>
            <select name="category_id" class="form-select" required>
              <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Title <span class="text-muted">(optional)</span></label>
            <input type="text" name="title" class="form-control" placeholder="Defaults to the file name">
          </div>
          <div class="mb-3">
            <label class="form-label">File</label>
            <input type="file" name="file" class="form-control"
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.txt,.zip,.rar,.jpg,.jpeg,.png" required>
            <p class="text-muted mt-1 mb-0" style="font-size:.75rem;">PDF, Word, Excel, CSV, PowerPoint, images or archives · Max 10MB</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include APPPATH . 'Views/layout/footer.php'; ?>

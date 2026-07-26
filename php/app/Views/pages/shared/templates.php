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
$previewableExt  = ['pdf', 'jpg', 'jpeg', 'png', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'odt', 'ods', 'odp'];
$hasActiveFilter = $search !== '' || $fileType !== 'all';
$canManage       = hasRole('adas');
$formatLabels    = ['pdf' => 'PDF', 'docx' => 'Word'];

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
    <form method="GET" action="<?= base_url('templates') ?>" id="filterForm" class="d-flex gap-2 flex-wrap align-items-center">
      <div class="input-group input-group-sm" style="flex:1;min-width:220px;max-width:340px;">
        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search templates by title or file name..."
               class="form-control border-start-0 ps-0" id="templateSearchInput" autocomplete="off">
      </div>

      <div class="maroon-select maroon-select-sm" style="width:auto;">
        <select name="category" class="maroon-select-native" onchange="this.form.requestSubmit()">
          <option value="0" <?= $catId === 0 ? 'selected' : '' ?>>All Sections</option>
          <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $catId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
        <div class="maroon-select-panel"></div>
      </div>

      <div class="maroon-select maroon-select-sm" style="width:auto;">
        <select name="type" class="maroon-select-native" onchange="this.form.requestSubmit()">
          <option value="all" <?= $fileType === 'all' ? 'selected' : '' ?>>All File Types</option>
          <?php foreach ($fileTypes as $ft): ?>
          <option value="<?= e($ft) ?>" <?= $fileType === $ft ? 'selected' : '' ?>><?= strtoupper(e($ft)) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
        <div class="maroon-select-panel"></div>
      </div>

      <div class="maroon-select maroon-select-sm" style="width:auto;">
        <select name="sort" class="maroon-select-native" onchange="this.form.requestSubmit()">
          <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Newest First</option>
          <option value="date_asc"  <?= $sort === 'date_asc'  ? 'selected' : '' ?>>Oldest First</option>
          <option value="section"   <?= $sort === 'section'   ? 'selected' : '' ?>>Sort by Section</option>
          <option value="type"      <?= $sort === 'type'      ? 'selected' : '' ?>>Sort by File Type</option>
        </select>
        <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
        <div class="maroon-select-panel"></div>
      </div>

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
    <button type="button" class="btn section-toggle-btn collapsed flex-grow-1 d-flex align-items-center text-start p-0 border-0 bg-transparent"
            data-bs-toggle="collapse" data-bs-target="#section-body-<?= $cat['id'] ?>"
            aria-expanded="false" aria-controls="section-body-<?= $cat['id'] ?>">
      <i class="bi bi-chevron-down section-toggle-arrow me-2 text-muted"></i>
      <span class="fw-semibold"><i class="bi bi-folder2 me-2 text-muted"></i><?= e($cat['name']) ?></span>
      <span class="badge badge-secondary ms-2"><?= count($items) ?></span>
    </button>
    <?php if ($canManage): ?>
    <div class="d-flex align-items-center gap-1">
      <button type="button" class="btn btn-ghost btn-sm" title="Upload to this section"
              onclick="openUploadModal(<?= $cat['id'] ?>)">
        <i class="bi bi-cloud-upload"></i>
      </button>
      <?php if (count($items) === 0): ?>
      <form method="POST" action="<?= base_url('templates') ?>" class="ajax-form"
            data-confirm-title="Delete this section?" data-confirm-text="This section has no templates and will be removed.">
        <input type="hidden" name="action" value="delete_category">
        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
        <button class="btn btn-ghost btn-sm text-danger" title="Delete empty section"><i class="bi bi-trash"></i></button>
      </form>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
  <div class="collapse" id="section-body-<?= $cat['id'] ?>">
  <div class="card-body">
    <?php if (empty($items)): ?>
    <p class="text-muted mb-0 text-center py-3"><i class="bi bi-inbox fs-4 d-block mb-2"></i>No templates uploaded in this section yet.</p>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ($items as $t):
        [$icon, $tc, $bg] = $extCfg[$t['file_ext']] ?? ['bi-file-earmark-fill', '#374151', '#f3f4f6'];
        $conversions      = \App\Controllers\Shared\Templates::conversionTargets($t['file_ext']);
        // Only the Download button that actually has a dropdown attached
        // switches to the brand red — a plain Download (no conversions)
        // isn't a dropdown, so it keeps its file-type color.
        $downloadColor    = ! empty($conversions) ? 'var(--primary)' : $tc;
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
              <p class="text-muted mb-1 text-truncate" style="font-size:.75rem;" title="<?= e($t['file_name']) ?>">
                <?= e($t['file_name']) ?> · <?= tplFormatBytes((int) $t['file_size']) ?>
              </p>
              <?php if (! empty($t['description'])): ?>
              <p class="text-muted mb-3" style="font-size:.72rem;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                <?= e($t['description']) ?>
              </p>
              <?php else: ?>
              <div class="mb-3"></div>
              <?php endif; ?>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm flex-fill" style="border:1.5px solid <?= $tc ?>;color:<?= $tc ?>;background:transparent;"
                        onclick='previewTemplate(<?= json_encode([
                            'id'          => (int) $t['id'],
                            'title'       => $t['title'],
                            'description' => $t['description'],
                            'ext'         => $t['file_ext'],
                            'category'    => $t['category_name'],
                            'uploader'    => $t['uploaded_by'],
                            'date'        => date('M d, Y', strtotime($t['date_added'])),
                            'fileName'    => $t['file_name'],
                            'fileSize'    => tplFormatBytes((int) $t['file_size']),
                            'previewable' => in_array($t['file_ext'], $previewableExt, true),
                            'conversions' => $conversions,
                        ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                  <i class="bi bi-eye me-1"></i>View
                </button>
                <div class="btn-group flex-fill" role="group">
                  <button type="button" class="btn btn-sm flex-fill" style="background:<?= $downloadColor ?>;color:#fff;"
                          onclick="downloadTemplate('<?= base_url('templates/download/' . $t['id']) ?>', '<?= e(addslashes($t['file_name'])) ?>')">
                    <i class="bi bi-download me-1"></i>Download
                  </button>
                  <?php if (! empty($conversions)): ?>
                  <button type="button" class="btn btn-sm dropdown-toggle dropdown-toggle-split" style="background:<?= $downloadColor ?>;color:#fff;"
                          data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Toggle download options</span>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <?php foreach ($conversions as $c): $dlName = pathinfo($t['file_name'], PATHINFO_FILENAME) . '.' . $c; ?>
                    <li>
                      <a class="dropdown-item" href="#"
                         onclick="downloadTemplate('<?= base_url('templates/download/' . $t['id'] . '?as=' . $c) ?>', '<?= e(addslashes($dlName)) ?>'); return false;">
                        <i class="bi bi-file-earmark-<?= $c === 'pdf' ? 'pdf' : 'word' ?> me-2"></i>Convert to <?= e($formatLabels[$c] ?? strtoupper($c)) ?>
                      </a>
                    </li>
                    <?php endforeach; ?>
                  </ul>
                  <?php endif; ?>
                </div>
                <?php if ($canManage && $t['file_ext'] === 'docx'): ?>
                <button type="button" class="btn btn-sm flex-fill" style="background:#15803d;color:#fff;"
                        onclick='openCertificateModal(<?= json_encode([
                            'id'     => (int) $t['id'],
                            'title'  => $t['title'],
                            'fields' => $certificateFields[$t['id']] ?? [],
                        ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                  <i class="bi bi-file-earmark-spreadsheet me-1"></i>Import Data
                </button>
                <?php endif; ?>
              </div>
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
</div>
<?php endforeach; ?>

<?php if ($rendered === 0): ?>
<div class="card"><div class="card-body text-center py-5">
  <i class="bi bi-inbox fs-1 d-block mb-3 text-muted"></i>
  <p class="text-muted mb-0">No templates found matching your search/filters.</p>
</div></div>
<?php endif; ?>

<?php if ($canManage): ?>
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
            <div class="maroon-select" style="width:100%;">
              <select name="category_id" id="uploadCategorySelect" class="maroon-select-native" required>
                <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
              <div class="maroon-select-panel"></div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">File</label>
            <input type="file" name="file" id="uploadFileInput" class="form-control"
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.txt,.zip,.rar,.jpg,.jpeg,.png"
                   onchange="autofillTemplateTitle(this)" required>
            <p class="text-muted mt-1 mb-0" style="font-size:.75rem;">PDF, Word, Excel, CSV, PowerPoint, images or archives · Max 10MB</p>
          </div>
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" id="uploadTitleInput" class="form-control" placeholder="Select a file to auto-fill, or type your own" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description <span class="text-muted">(optional)</span></label>
            <textarea name="description" class="form-control" rows="2" placeholder="What is this template for?"></textarea>
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
<?php endif; ?>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--maroon);color:#fff;">
        <h6 class="modal-title fw-bold" id="previewModalTitle"><i class="bi bi-eye me-2"></i>Template</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="previewModalBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <div class="btn-group">
          <button type="button" class="btn btn-primary" id="previewModalDownloadBtn">
            <i class="bi bi-download me-1"></i>Download
          </button>
          <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split d-none" id="previewModalDownloadCaret"
                  data-bs-toggle="dropdown" aria-expanded="false">
            <span class="visually-hidden">Toggle download options</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" id="previewModalDownloadMenu"></ul>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($canManage): ?>
<!-- Generate Certificates Modal -->
<div class="modal fade" id="certificateModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-patch-check me-2"></i>Generate Certificates</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="certificateForm" method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <p class="text-muted mb-3" style="font-size:.82rem;">
            Fills <strong id="certificateTemplateTitle"></strong> once for every row in the Excel file below,
            then bundles every generated certificate into one ZIP.
          </p>

          <div class="mb-3" id="certificateFieldsWrap">
            <label class="form-label small fw-semibold mb-1">Fields this template will fill in</label>
            <div id="certificateFieldsList"></div>
          </div>

          <div class="alert alert-warning py-2 px-3 mb-3 d-none" style="font-size:.8rem;" id="certificateNoFieldsAlert">
            <i class="bi bi-exclamation-triangle me-1"></i>
            This template has no <code>&#36;{Field}</code>-style placeholders yet, so there's nothing to fill in.
            Open it in Word and add some (e.g. <code>&#36;{Name}</code>) first.
          </div>

          <input type="hidden" name="template_id" id="certificateTemplateId">
          <div class="mb-1">
            <label class="form-label">Recipient list (Excel)</label>
            <input type="file" name="import_file" id="certificateExcelInput" class="form-control" accept=".xlsx,.xls" required>
            <p class="text-muted mt-1 mb-0" style="font-size:.75rem;">
              First row = column headers matching the field names above (punctuation/spacing is ignored when matching).
              One certificate is generated per row after that.
            </p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="certificateSubmitBtn">
            <i class="bi bi-file-earmark-zip me-1"></i>Generate &amp; Download ZIP
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$extraScript = "<script>
// var, not const: this script gets re-injected verbatim on every AJAX
// navigation to/within this page (see runPageScript() in footer.php), and a
// top-level const would collide with the one the first real load already
// declared in the shared global scope.
var TEMPLATES_PREVIEW_BASE = '" . base_url('templates/preview/') . "';

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

var OFFICE_PREVIEW_EXT = ['doc','docx','xls','xlsx','ppt','pptx','csv','odt','ods','odp'];
var FORMAT_LABELS = { pdf: 'PDF', docx: 'Word' };

function previewTemplate(t) {
    document.getElementById('previewModalTitle').innerHTML = '<i class=\"bi bi-eye me-2\"></i>' + escapeHtml(t.title);

    const previewUrl = TEMPLATES_PREVIEW_BASE + t.id;
    let previewHtml;
    if (t.ext === 'pdf' || OFFICE_PREVIEW_EXT.includes(t.ext)) {
        previewHtml = '<iframe src=\"' + previewUrl + '\" style=\"width:100%;height:65vh;border:1px solid #e5e7eb;border-radius:8px;\"></iframe>';
    } else if (['jpg','jpeg','png'].includes(t.ext)) {
        previewHtml = '<div class=\"text-center\"><img src=\"' + previewUrl + '\" style=\"max-width:100%;max-height:65vh;border-radius:8px;\"></div>';
    } else if (t.ext === 'txt') {
        previewHtml = '<iframe src=\"' + previewUrl + '\" style=\"width:100%;height:65vh;border:1px solid #e5e7eb;border-radius:8px;background:#fff;\"></iframe>';
    } else {
        previewHtml = '<div class=\"text-center text-muted py-4\"><i class=\"bi bi-file-earmark-fill fs-1 d-block mb-2\"></i>Preview isn\\'t available for this file type. Download it to view the contents.</div>';
    }

    document.getElementById('previewModalBody').innerHTML = `
      <div class=\"mb-3\">
        <table class=\"table table-sm mb-0\">
          <tr><th style=\"width:120px\">Section</th><td>\${escapeHtml(t.category)}</td></tr>
          <tr><th>File</th><td>\${escapeHtml(t.fileName)} · \${escapeHtml(t.fileSize)}</td></tr>
          <tr><th>Uploaded By</th><td>\${escapeHtml(t.uploader)}</td></tr>
          <tr><th>Date Added</th><td>\${escapeHtml(t.date)}</td></tr>
          \${t.description ? '<tr><th>Description</th><td>' + escapeHtml(t.description) + '</td></tr>' : ''}
        </table>
      </div>
      \${previewHtml}
    `;

    const downloadBase = '" . base_url('templates/download/') . "' + t.id;
    document.getElementById('previewModalDownloadBtn').onclick = () => downloadTemplate(downloadBase, t.fileName);

    const caret = document.getElementById('previewModalDownloadCaret');
    const menu  = document.getElementById('previewModalDownloadMenu');
    const conversions = t.conversions || [];

    menu.innerHTML = '';
    conversions.forEach(c => {
        const name = t.fileName.includes('.') ? t.fileName.substring(0, t.fileName.lastIndexOf('.')) : t.fileName;
        const li = document.createElement('li');
        const a  = document.createElement('a');
        a.className = 'dropdown-item';
        a.href = '#';
        a.innerHTML = '<i class=\"bi bi-file-earmark-' + (c === 'pdf' ? 'pdf' : 'word') + ' me-2\"></i>Convert to ' + (FORMAT_LABELS[c] || c.toUpperCase());
        a.onclick = (e) => { e.preventDefault(); downloadTemplate(downloadBase + '?as=' + c, name + '.' + c); };
        li.appendChild(a);
        menu.appendChild(li);
    });
    caret.classList.toggle('d-none', conversions.length === 0);

    new bootstrap.Modal(document.getElementById('previewModal')).show();
}

function notifyDownloadSuccess(fileName) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Download successful',
        text: fileName,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });
}

async function downloadTemplate(url, suggestedName) {
    if (window.showSaveFilePicker) {
        try {
            const handle = await window.showSaveFilePicker({ suggestedName });
            const res = await fetch(url);
            const blob = await res.blob();
            const writable = await handle.createWritable();
            await writable.write(blob);
            await writable.close();
            notifyDownloadSuccess(suggestedName);
            return;
        } catch (err) {
            if (err && err.name === 'AbortError') return;
        }
    }
    window.location.href = url;
    notifyDownloadSuccess(suggestedName);
}

function openUploadModal(categoryId) {
    const select = document.getElementById('uploadCategorySelect');
    if (select && categoryId) {
        select.value = categoryId;
        select.dispatchEvent(new Event('change'));
    }
    new bootstrap.Modal(document.getElementById('uploadModal')).show();
}

function autofillTemplateTitle(fileInput) {
    if (!fileInput.files || !fileInput.files[0]) return;
    const name = fileInput.files[0].name;
    const withoutExt = name.includes('.') ? name.substring(0, name.lastIndexOf('.')) : name;
    document.getElementById('uploadTitleInput').value = withoutExt;
}

function openCertificateModal(t) {
    document.getElementById('certificateTemplateTitle').textContent = t.title;
    document.getElementById('certificateTemplateId').value = t.id;
    document.getElementById('certificateExcelInput').value = '';

    const fields = t.fields || [];
    const listEl = document.getElementById('certificateFieldsList');
    listEl.innerHTML = fields.map(function (f) {
        return '<span class=\"badge me-1 mb-1\" style=\"background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;font-weight:500;\">' + escapeHtml(f) + '</span>';
    }).join('');

    document.getElementById('certificateFieldsWrap').classList.toggle('d-none', fields.length === 0);
    document.getElementById('certificateNoFieldsAlert').classList.toggle('d-none', fields.length > 0);
    document.getElementById('certificateSubmitBtn').disabled = fields.length === 0;

    new bootstrap.Modal(document.getElementById('certificateModal')).show();
}

function certificateFileNameFrom(disposition) {
    if (!disposition) return 'certificates.zip';
    const match = disposition.match(/filename=\"?([^\";]+)\"?/);
    return match ? match[1] : 'certificates.zip';
}

var certificateForm = document.getElementById('certificateForm');
if (certificateForm) {
    certificateForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(certificateForm);

        Swal.fire({
            title: 'Generating certificates...',
            html: 'Filling in the template for each recipient row. This can take a moment for larger lists.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function () { Swal.showLoading(); },
        });

        fetch('" . base_url('templates/certificates/generate') . "', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        }).then(function (res) {
            const contentType = res.headers.get('Content-Type') || '';

            if (contentType.indexOf('application/json') !== -1) {
                return res.json().then(function (data) {
                    throw new Error(data.message || 'Something went wrong.');
                });
            }
            if (!res.ok) {
                throw new Error('Something went wrong. Please try again.');
            }

            const count      = res.headers.get('X-Certificate-Count');
            const unmatched  = res.headers.get('X-Certificate-Unmatched-Fields');
            const fileName   = certificateFileNameFrom(res.headers.get('Content-Disposition'));

            return res.blob().then(function (blob) {
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                a.remove();

                const modalEl = document.getElementById('certificateModal');
                bootstrap.Modal.getInstance(modalEl) && bootstrap.Modal.getInstance(modalEl).hide();

                let text = (count || '') + ' certificate(s) downloaded as ' + fileName + '.';
                if (unmatched) {
                    text += ' No matching column for: ' + unmatched + ' (left blank in every certificate).';
                }

                Swal.fire({ icon: 'success', title: 'Certificates generated', text: text });
            });
        }).catch(function (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'Something went wrong. Please try again.' });
        });
    });
}

initLiveSearch('templateSearchInput', 'filterForm');
</script>";
include APPPATH . 'Views/layout/footer.php'; ?>

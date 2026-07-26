<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <h4><i class="bi bi-upload me-2"></i>Submit Documents</h4>
  <p>Upload your DLLs, Lesson Plans & assessments for review</p>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
  <i class="bi bi-check-circle me-2"></i>Document submitted successfully.
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
  <!-- Submit Form -->
  <div class="col-lg-4">
    <div class="card sticky-top" style="top:80px;">
      <div class="card-header fw-semibold" style="background:var(--maroon);color:#fff;">
        <i class="bi bi-file-earmark-plus me-2"></i>New Submission
      </div>
      <div class="card-body">
        <?php if (!$teacher): ?>
        <div class="alert alert-warning small">Your account is not linked to a teacher profile.</div>
        <?php elseif (empty($openTasks)): ?>
        <div class="alert alert-warning small mb-0">
          <i class="bi bi-info-circle me-1"></i>No open tasks assigned to you by the principal yet. Check
          <a href="<?= base_url('my-tasks') ?>">My Tasks</a> once one is posted.
        </div>
        <?php else: ?>
        <form method="POST" action="<?= base_url('submit-documents') ?>" class="ajax-form"
              enctype="multipart/form-data"
              data-confirm-action="add" data-confirm-title="Submit this document?"
              data-confirm-text="It will be sent for review and cannot be edited afterward.">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Task (assigned by principal)</label>
            <div class="maroon-select maroon-select-sm" style="width:100%;">
              <select name="task_id" class="maroon-select-native" required>
                <option value="">Select a task…</option>
                <?php foreach ($openTasks as $t): ?>
                <option value="<?= (int) $t['id'] ?>">
                  <?= e($t['title']) ?> — Due <?= date('M d, Y', strtotime($t['deadline'])) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
              <div class="maroon-select-panel"></div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Subject</label>
            <input type="text" name="subject" class="form-control form-control-sm"
                   value="<?= e(implode(', ', json_decode($teacher['subjects'] ?? '[]', true) ?: [])) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Grade Level</label>
            <input type="text" name="grade_level" class="form-control form-control-sm"
                   value="<?= e($teacher['grade_level'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Add Document(s)</label>
            <input type="file" name="document_file[]" id="documentFileInput" class="form-control form-control-sm"
                   accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png" required multiple
                   onchange="previewSelectedFiles(this)">
            <div class="form-text small">You can select multiple files to submit together.</div>
            <div id="filePreviewBox" class="mt-2 d-none"></div>
          </div>
          <button type="submit" class="btn btn-maroon w-100 btn-sm">
            <i class="bi bi-send me-2"></i>Submit Document
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- My Submissions -->
  <div class="col-lg-8">
    <h6 class="fw-bold mb-3 text-muted">My Submissions</h6>
    <?php foreach ($myDocs as $doc):
      $badgeMap = ['Submitted'=>'badge-submitted','Reviewed'=>'badge-reviewed','Pending'=>'badge-pending','Returned'=>'badge-returned'];
    ?>
    <div class="card mb-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <h6 class="fw-bold mb-1"><?= e($doc['type']) ?> — <?= e($doc['subject']) ?></h6>
            <span class="text-muted small"><?= e($doc['grade_level']) ?> · <?= date('M d, Y h:i A', strtotime($doc['date_submitted'])) ?></span>
          </div>
          <span class="status-pill <?= $badgeMap[$doc['status']] ?? 'badge-pending' ?>"><?= e($doc['status']) ?></span>
        </div>
        <?php if (! empty($doc['files'])): ?>
        <div class="d-flex flex-column gap-1">
          <?php foreach ($doc['files'] as $f): ?>
          <div class="d-flex justify-content-between align-items-center gap-2 p-2 rounded-3" style="background:#f8f9fa;">
            <span class="small text-truncate"><i class="bi bi-paperclip me-1 text-muted"></i><?= e($f['file_name']) ?></span>
            <div class="d-flex gap-1 flex-shrink-0">
              <a href="<?= base_url('document-files/' . $f['id'] . '/preview') ?>" target="_blank" rel="noopener"
                 class="btn btn-sm btn-outline-secondary py-0 px-2"><i class="bi bi-eye"></i></a>
              <a href="<?= base_url('document-files/' . $f['id'] . '/download') ?>"
                 class="btn btn-sm btn-outline-secondary py-0 px-2"><i class="bi bi-download"></i></a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php elseif (! empty($doc['file_path'])): ?>
        <a href="<?= base_url('documents/' . $doc['id'] . '/file') ?>" target="_blank" rel="noopener"
           class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-eye me-1"></i>View File
        </a>
        <a href="<?= base_url('documents/' . $doc['id'] . '/download') ?>"
           class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-download me-1"></i>Download
        </a>
        <?php endif; ?>
        <?php if ($doc['feedback_comments']): ?>
        <div class="mt-3 p-3 rounded-3" style="background:#f8f9fa;border-left:3px solid var(--maroon);">
          <p class="small fw-semibold mb-1 text-muted"><i class="bi bi-chat-dots me-1"></i>Principal Feedback:</p>
          <?php foreach (explode('|||',$doc['feedback_comments']) as $fb): ?>
          <p class="small mb-0"><?= e($fb) ?></p>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($myDocs)): ?>
    <div class="card"><div class="card-body text-center py-5 text-muted">
      <i class="bi bi-inbox fs-1 d-block mb-3"></i>No submissions yet.
    </div></div>
    <?php endif; ?>
  </div>
</div>

<?php
$extraScript = "<script>
const IMAGE_EXT = ['jpg','jpeg','png','gif','webp'];

function previewSelectedFiles(input) {
    const box = document.getElementById('filePreviewBox');
    const files = Array.from(input.files || []);

    if (! files.length) { box.classList.add('d-none'); box.innerHTML = ''; return; }

    box.innerHTML = files.map((file, i) => {
        const ext     = file.name.split('.').pop().toLowerCase();
        const iconCls = IMAGE_EXT.includes(ext) ? 'bi-file-earmark-image' : 'bi-file-earmark-text';
        const size    = (file.size / 1024).toFixed(1) + ' KB';
        return '<div class=\"d-flex align-items-center gap-2 p-2 rounded-3 mb-1\" style=\"background:#f8f9fa;border:1px solid var(--border);\">'
            + '<i class=\"bi ' + iconCls + ' fs-5 text-muted flex-shrink-0\"></i>'
            + '<div class=\"flex-grow-1 overflow-hidden\">'
            + '<div class=\"small fw-semibold text-truncate\">' + file.name + '</div>'
            + '<div class=\"text-muted\" style=\"font-size:.7rem;\">' + size + '</div>'
            + '</div></div>';
    }).join('');

    box.classList.remove('d-none');
}
</script>";
include APPPATH . 'Views/layout/footer.php';
?>

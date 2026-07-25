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
        <?php else: ?>
        <form method="POST" action="<?= base_url('submit-documents') ?>" class="ajax-form"
              enctype="multipart/form-data"
              data-confirm-action="add" data-confirm-title="Submit this document?"
              data-confirm-text="It will be sent for review and cannot be edited afterward.">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Document Type</label>
            <select name="type" class="form-select form-select-sm">
              <option>DLL</option>
              <option>Lesson Plan</option>
              <option>Assessment</option>
              <option>Report</option>
            </select>
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
            <label class="form-label small fw-semibold">Add Document</label>
            <input type="file" name="document_file" id="documentFileInput" class="form-control form-control-sm"
                   accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png" required
                   onchange="previewSelectedFile(this)">
            <div class="form-text small">Upload the file you want to submit for review.</div>
            <div id="filePreviewBox" class="mt-2 p-2 rounded-3 d-none align-items-center gap-2" style="background:#f8f9fa;border:1px solid var(--border);">
              <img id="filePreviewImg" class="d-none rounded" style="width:44px;height:44px;object-fit:cover;flex-shrink:0;">
              <i id="filePreviewIcon" class="bi bi-file-earmark-text fs-3 text-muted d-none" style="flex-shrink:0;"></i>
              <div class="flex-grow-1 overflow-hidden">
                <div id="filePreviewName" class="small fw-semibold text-truncate"></div>
                <div id="filePreviewSize" class="text-muted" style="font-size:.7rem;"></div>
              </div>
              <button type="button" class="btn btn-ghost btn-sm text-danger py-0 px-1" onclick="clearSelectedFile()">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
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
        <?php if (! empty($doc['file_path'])): ?>
        <a href="<?= base_url('documents/' . $doc['id'] . '/file') ?>" target="_blank" rel="noopener"
           class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-eye me-1"></i>View File
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

function previewSelectedFile(input) {
    const box  = document.getElementById('filePreviewBox');
    const img  = document.getElementById('filePreviewImg');
    const icon = document.getElementById('filePreviewIcon');
    const name = document.getElementById('filePreviewName');
    const size = document.getElementById('filePreviewSize');

    const file = input.files && input.files[0];
    if (! file) { box.classList.add('d-none'); return; }

    const ext = file.name.split('.').pop().toLowerCase();
    name.textContent = file.name;
    size.textContent = (file.size / 1024).toFixed(1) + ' KB';

    if (IMAGE_EXT.includes(ext)) {
        img.src = URL.createObjectURL(file);
        img.classList.remove('d-none');
        icon.classList.add('d-none');
    } else {
        img.classList.add('d-none');
        icon.classList.remove('d-none');
    }

    box.classList.remove('d-none');
    box.classList.add('d-flex');
}

function clearSelectedFile() {
    const input = document.getElementById('documentFileInput');
    input.value = '';
    document.getElementById('filePreviewBox').classList.add('d-none');
}
</script>";
include APPPATH . 'Views/layout/footer.php';
?>

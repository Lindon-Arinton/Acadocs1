<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <a href="<?= base_url('documents') ?>" class="text-white text-decoration-none small d-inline-flex align-items-center gap-1 mb-2" style="opacity:.85;">
    <i class="bi bi-arrow-left"></i>Back to Documents
  </a>
  <h4><i class="bi bi-folder2-open me-2"></i><?= e($folder['name']) ?></h4>
  <p>
    Uploads for task <a href="<?= base_url('tasks/' . $task['id']) ?>" class="text-white fw-semibold"><?= e($task['title']) ?></a>
    · Deadline <?= date('M d, Y h:i A', strtotime($task['deadline'])) ?>
  </p>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-info-circle-fill"></i><?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<?php
// Only the principal approves (Reviewed/Returned) and annotates; ADAS can view.
$canReview = hasRole('admin');
?>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead>
          <tr><th>#</th><th>Uploaded By</th><th>Files</th><th>Date Uploaded</th><th>Status</th><?php if ($canReview): ?><th>Review</th><?php endif; ?></tr>
        </thead>
        <tbody>
          <?php foreach ($submissions as $i => $submission): ?>
          <tr>
            <td class="text-muted small"><?= $i + 1 ?></td>
            <td class="fw-semibold"><?= personLink((int) $submission['user_id'], $submission['submitter_name']) ?></td>
            <td>
              <?php foreach ($submission['files'] as $file): ?>
              <div class="d-flex align-items-center gap-2 mb-1">
                <span class="small text-truncate" style="max-width:260px;"><i class="bi bi-paperclip me-1 text-muted"></i><?= e($file['file_name']) ?></span>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Preview"
                        onclick="previewFolderFile(<?= (int) $file['id'] ?>, <?= e(json_encode($file['file_name'])) ?>)"><i class="bi bi-eye"></i></button>
                <a href="<?= base_url('task-submissions/' . $file['id'] . '/download') ?>" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Download"><i class="bi bi-download"></i></a>
                <?php if ($canReview): ?>
                <a href="<?= base_url('task-submissions/' . $file['id'] . '/annotate') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-maroon py-0 px-2" title="Annotate (draw / write notes)"><i class="bi bi-pencil-square"></i></a>
                <?php endif; ?>
                <?php if (\App\Models\TaskSubmissionFileModel::hasAnnotation($file)): ?>
                <a href="<?= base_url('task-submissions/' . $file['id'] . '/annotated') ?>" target="_blank" rel="noopener" class="timing-badge annot-badge text-decoration-none" title="Open the marked-up copy"><i class="bi bi-pencil-fill me-1"></i>Marked up</a>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
              <?php if (empty($submission['files'])): ?>
              <span class="small text-muted">No files</span>
              <?php endif; ?>
              <?php if (! empty($submission['feedback'])): ?>
              <div class="small text-muted mt-1" title="Latest comment">
                <i class="bi bi-chat-left-text me-1"></i><?= e(mb_strimwidth($submission['feedback'][0]['comment'], 0, 90, '…')) ?><?= ! empty($submission['feedback'][0]['author_name']) ? ' <span class="fst-italic">— ' . e($submission['feedback'][0]['author_name']) . '</span>' : '' ?>
              </div>
              <?php endif; ?>
            </td>
            <td class="small text-muted text-nowrap">
              <div><?= date('M d, Y', strtotime($submission['submitted_at'])) ?></div>
              <div><?= date('h:i A', strtotime($submission['submitted_at'])) ?></div>
              <div class="mt-1"><?= submissionTimingBadge($submission['submitted_at'], $task['deadline']) ?></div>
            </td>
            <td>
              <span class="status-pill <?= submissionBadge($submission['status']) ?>"><?= e($submission['status']) ?></span>
              <?php if ($submission['status'] !== 'Pending' && ! empty($submission['reviewer_name'])): ?>
              <div class="small text-muted mt-1 text-nowrap">by <?= e($submission['reviewer_name']) ?><?= $submission['reviewed_at'] ? ' · ' . date('M d, Y', strtotime($submission['reviewed_at'])) : '' ?></div>
              <?php endif; ?>
            </td>
            <?php if ($canReview): ?>
            <td class="text-nowrap">
              <?php
              // Only the decisions that would change something: a Pending upload can be
              // approved or returned; a decided one can only be switched to the other.
              $status    = $submission['status'];
              $submitter = e(addslashes($submission['submitter_name']));
              ?>
              <?php if ($status !== 'Reviewed'): ?>
              <button type="button" class="btn btn-sm btn-outline-success" title="Approve this upload"
                      onclick="reviewUpload(<?= (int) $submission['id'] ?>, 'Reviewed', '<?= $submitter ?>')">
                <i class="bi bi-check-circle me-1"></i><?= $status === 'Returned' ? 'Mark Reviewed instead' : 'Mark Reviewed' ?>
              </button>
              <?php endif; ?>
              <?php if ($status !== 'Returned'): ?>
              <button type="button" class="btn btn-sm btn-outline-danger" title="Send back to the uploader for revision"
                      onclick="reviewUpload(<?= (int) $submission['id'] ?>, 'Returned', '<?= $submitter ?>')">
                <i class="bi bi-arrow-return-left me-1"></i><?= $status === 'Reviewed' ? 'Return instead' : 'Return' ?>
              </button>
              <?php endif; ?>
              <?php if ($status === 'Returned'): ?>
              <div class="small text-muted mt-1">Waiting for the uploader to resubmit</div>
              <?php endif; ?>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($submissions)): ?>
          <tr><td colspan="<?= $canReview ? 6 : 5 ?>" class="text-center py-5 text-muted">This folder is empty.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- File preview (eye button) — same in-page preview as the task pages -->
<div class="modal fade" id="filePreviewModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--maroon);color:#fff;">
        <h6 class="modal-title fw-bold text-truncate"><i class="bi bi-eye me-2"></i><span id="filePreviewName"></span></h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0" style="height:75vh;">
        <iframe id="filePreviewFrame" title="File preview" style="width:100%;height:100%;border:0;"></iframe>
      </div>
      <div class="modal-footer">
        <a id="filePreviewDownload" href="#" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Download</a>
        <?php if ($canReview): ?>
        <a id="filePreviewAnnotate" href="#" target="_blank" rel="noopener" class="btn btn-outline-maroon"><i class="bi bi-pencil-square me-1"></i>Annotate</a>
        <?php endif; ?>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php if ($canReview): ?>
<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--maroon);color:#fff;">
        <h6 class="modal-title fw-bold"><i class="bi bi-clipboard-check me-2"></i><span id="reviewModalTitle">Review Upload</span></h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('documents?folder=' . $folder['id']) ?>" class="ajax-form" id="reviewForm" data-confirm-action="update">
        <div class="modal-body">
          <input type="hidden" name="submission_id" id="reviewSubmissionId">
          <input type="hidden" name="status" id="reviewStatus">
          <p class="text-muted small mb-3">Upload by: <strong id="reviewSubmitter"></strong></p>
          <label class="form-label fw-semibold small" id="reviewCommentLabel">Comment</label>
          <textarea name="comment" id="reviewComment" class="form-control" rows="4"></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-maroon" id="reviewSubmitBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$extraScript = "<script>
const FOLDER_FILE_BASE = " . json_encode(base_url('task-submissions/')) . ";

function previewFolderFile(fileId, name) {
    document.getElementById('filePreviewName').textContent = name;
    document.getElementById('filePreviewFrame').src = FOLDER_FILE_BASE + fileId + '/preview';
    document.getElementById('filePreviewDownload').href = FOLDER_FILE_BASE + fileId + '/download';
    const annotate = document.getElementById('filePreviewAnnotate');
    if (annotate) annotate.href = FOLDER_FILE_BASE + fileId + '/annotate';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('filePreviewModal')).show();
}

// Stop loading / free the preview when the modal closes.
document.getElementById('filePreviewModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('filePreviewFrame').src = 'about:blank';
});

function reviewUpload(id, status, submitter) {
    const copy = {
        Reviewed: { title: 'Mark as Reviewed', label: 'Comment (optional)', placeholder: 'Any notes for the uploader...', btn: 'Mark Reviewed', confirm: 'The uploader will be told their upload was reviewed.' },
        Returned: { title: 'Return for Revision', label: 'What needs to be fixed?', placeholder: 'Explain what does not meet the requirements...', btn: 'Return Upload', confirm: 'The upload will be sent back to the uploader for revision.' },
    }[status];
    const form    = document.getElementById('reviewForm');
    const comment = document.getElementById('reviewComment');
    document.getElementById('reviewSubmissionId').value = id;
    document.getElementById('reviewStatus').value = status;
    document.getElementById('reviewSubmitter').textContent = submitter;
    document.getElementById('reviewModalTitle').textContent = copy.title;
    document.getElementById('reviewCommentLabel').textContent = copy.label;
    document.getElementById('reviewSubmitBtn').textContent = copy.btn;
    comment.placeholder = copy.placeholder;
    comment.required = status === 'Returned';
    comment.value = '';
    form.dataset.confirmTitle = copy.title + '?';
    form.dataset.confirmText = copy.confirm;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('reviewModal')).show();
}
</script>";
include APPPATH . 'Views/layout/footer.php';
?>

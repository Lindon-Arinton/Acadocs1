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
$canReview = hasRole('admin', 'adas');
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
            <td class="fw-semibold"><?= e($submission['submitter_name']) ?></td>
            <td>
              <?php foreach ($submission['files'] as $file): ?>
              <div class="d-flex align-items-center gap-2 mb-1">
                <span class="small text-truncate" style="max-width:260px;"><i class="bi bi-paperclip me-1 text-muted"></i><?= e($file['file_name']) ?></span>
                <a href="<?= base_url('task-submissions/' . $file['id'] . '/preview') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Preview"><i class="bi bi-eye"></i></a>
                <a href="<?= base_url('task-submissions/' . $file['id'] . '/download') ?>" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Download"><i class="bi bi-download"></i></a>
              </div>
              <?php endforeach; ?>
              <?php if (empty($submission['files'])): ?>
              <span class="small text-muted">No files</span>
              <?php endif; ?>
              <?php if (! empty($submission['feedback'])): ?>
              <div class="small text-muted mt-1" title="Latest comment">
                <i class="bi bi-chat-left-text me-1"></i><?= e(mb_strimwidth($submission['feedback'][0]['comment'], 0, 90, '…')) ?>
              </div>
              <?php endif; ?>
            </td>
            <td class="small text-muted"><?= date('M d, Y h:i A', strtotime($submission['submitted_at'])) ?></td>
            <td><span class="status-pill <?= submissionBadge($submission['status']) ?>"><?= e($submission['status']) ?></span></td>
            <?php if ($canReview): ?>
            <td class="text-nowrap">
              <button type="button" class="btn btn-sm btn-outline-success" title="Mark as Reviewed"
                      onclick="reviewUpload(<?= (int) $submission['id'] ?>, 'Reviewed', '<?= e(addslashes($submission['submitter_name'])) ?>')">
                <i class="bi bi-check-circle"></i>
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger" title="Return for revision"
                      onclick="reviewUpload(<?= (int) $submission['id'] ?>, 'Returned', '<?= e(addslashes($submission['submitter_name'])) ?>')">
                <i class="bi bi-arrow-return-left"></i>
              </button>
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
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
}
</script>";
include APPPATH . 'Views/layout/footer.php';
?>

<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <?php include APPPATH . 'Views/partials/doc_breadcrumb.php'; ?>
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

<?php
$statusCounts = array_count_values(array_column($submissions, 'status'));
?>
<!-- Search + status filter (Templates-style) -->
<div class="card mb-4">
  <div class="card-body py-3 d-flex flex-wrap gap-2 align-items-center">
    <div class="input-group input-group-sm" style="flex:1;min-width:220px;max-width:340px;">
      <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
      <input type="text" id="uploadSearchInput" class="form-control border-start-0 ps-0" placeholder="Search uploader or file name...">
    </div>
    <div class="tab-pills" id="uploadStatusPills">
      <button type="button" class="tab-pill active" data-status="all">All <span class="text-muted">(<?= count($submissions) ?>)</span></button>
      <?php foreach (['Pending', 'Reviewed', 'Returned'] as $st): ?>
      <button type="button" class="tab-pill" data-status="<?= $st ?>"><?= $st ?> <span class="text-muted">(<?= $statusCounts[$st] ?? 0 ?>)</span></button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
    <i class="bi bi-folder2-open text-muted"></i>
    <span class="fw-semibold">Uploads</span>
    <span class="badge badge-secondary" id="uploadCount"><?= count($submissions) ?></span>
  </div>
  <div class="card-body">
    <?php if (empty($submissions)): ?>
    <p class="text-muted mb-0 text-center py-4"><i class="bi bi-inbox fs-4 d-block mb-2"></i>No one has uploaded to this task yet.</p>
    <?php else: ?>
    <div class="list-group list-group-flush" id="uploadList">
      <?php foreach ($submissions as $submission): ?>
      <div class="list-group-item py-3 upload-row" data-status="<?= e($submission['status']) ?>"
           data-search="<?= e(mb_strtolower($submission['submitter_name'] . ' ' . implode(' ', array_column($submission['files'], 'file_name')))) ?>">
        <div class="d-flex align-items-start gap-3 flex-wrap">
          <div class="d-flex align-items-center justify-content-center flex-shrink-0 fw-bold"
               style="width:40px;height:40px;border-radius:50%;background:var(--surface-hover);color:var(--primary);">
            <?= e(mb_strtoupper(mb_substr($submission['submitter_name'], 0, 1))) ?>
          </div>
          <div class="flex-grow-1" style="min-width:240px;">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
              <h6 class="fw-bold mb-0" style="font-size:.9rem;"><?= personLink((int) $submission['user_id'], $submission['submitter_name']) ?></h6>
              <span class="status-pill <?= submissionBadge($submission['status']) ?>"><?= e($submission['status']) ?></span>
              <span class="text-muted" style="font-size:.72rem;"><i class="bi bi-clock me-1"></i><?= date('M d, Y h:i A', strtotime($submission['submitted_at'])) ?></span>
              <?= submissionTimingBadge($submission['submitted_at'], $task['deadline']) ?>
            </div>
            <?php if ($submission['status'] !== 'Pending' && ! empty($submission['reviewer_name'])): ?>
            <div class="small text-muted">
              <?= e($submission['status']) ?> by <?= e($submission['reviewer_name']) ?><?= $submission['reviewed_at'] ? ' · ' . date('M d, Y', strtotime($submission['reviewed_at'])) : '' ?>
            </div>
            <?php endif; ?>

            <?php foreach ($submission['files'] as $file):
              $ext = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));
              [$icon, $tc, $bg] = fileTypeStyle($ext);
            ?>
            <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
              <div style="width:30px;height:30px;border-radius:8px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi <?= $icon ?>" style="color:<?= $tc ?>;font-size:.9rem;"></i>
              </div>
              <span class="small text-truncate" style="max-width:320px;" title="<?= e($file['file_name']) ?>"><?= e($file['file_name']) ?></span>
              <?php if ($ext): ?>
              <span class="badge" style="background:<?= $bg ?>;color:<?= $tc ?>;border:1px solid <?= $tc ?>22;font-size:.62rem;"><?= strtoupper(e($ext)) ?></span>
              <?php endif; ?>
              <button type="button" class="btn btn-sm py-0 px-2" style="border:1.5px solid <?= $tc ?>;color:var(--text);" title="Preview"
                      onclick="previewFolderFile(<?= (int) $file['id'] ?>, <?= e(json_encode($file['file_name'])) ?>)"><i class="bi bi-eye me-1"></i>Preview</button>
              <a href="<?= base_url('task-submissions/' . $file['id'] . '/download') ?>" class="btn btn-sm py-0 px-2" style="background:<?= $tc ?>;color:#fff;" title="Download"><i class="bi bi-download me-1"></i>Download</a>
              <?php if ($canReview): ?>
              <a href="<?= base_url('task-submissions/' . $file['id'] . '/annotate') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-maroon py-0 px-2" title="Annotate (draw / write notes)"><i class="bi bi-pencil-square me-1"></i>Annotate</a>
              <?php endif; ?>
              <?php if (\App\Models\TaskSubmissionFileModel::hasAnnotation($file)): ?>
              <a href="<?= base_url('task-submissions/' . $file['id'] . '/annotated') ?>" target="_blank" rel="noopener" class="timing-badge annot-badge text-decoration-none" title="Open the marked-up copy"><i class="bi bi-pencil-fill me-1"></i>Marked up</a>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if (empty($submission['files'])): ?>
            <div class="small text-muted mt-1">No files</div>
            <?php endif; ?>

            <?php if (! empty($submission['feedback'])): ?>
            <div class="small text-muted mt-2 p-2 rounded-3" style="background:var(--surface-hover);" title="Latest comment">
              <i class="bi bi-chat-left-text me-1"></i><?= e(mb_strimwidth($submission['feedback'][0]['comment'], 0, 140, '…')) ?><?= ! empty($submission['feedback'][0]['author_name']) ? ' <span class="fst-italic">— ' . e($submission['feedback'][0]['author_name']) . '</span>' : '' ?>
            </div>
            <?php endif; ?>
          </div>
          <?php if ($canReview):
            // Only the decisions that would change something: a Pending upload can be
            // approved or returned; a decided one can only be switched to the other.
            $status    = $submission['status'];
            $submitter = e(addslashes($submission['submitter_name']));
          ?>
          <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
            <div class="d-flex gap-2">
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
            </div>
            <?php if ($status === 'Returned'): ?>
            <div class="small text-muted">Waiting for the uploader to resubmit</div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <p class="text-muted mb-0 text-center py-4 d-none" id="uploadNoMatch"><i class="bi bi-funnel fs-5 d-block mb-1"></i>No uploads match.</p>
    </div>
    <?php endif; ?>
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
$extraScript .= <<<'HTML'
<script>
// Instant search (uploader / file name) + status pills over the uploads list.
(function () {
    const search = document.getElementById('uploadSearchInput');
    const pills  = document.getElementById('uploadStatusPills');
    let status = 'all';
    function apply() {
        const q = (search?.value || '').trim().toLowerCase();
        let shown = 0;
        document.querySelectorAll('.upload-row').forEach(row => {
            const ok = (status === 'all' || row.dataset.status === status) && (!q || row.dataset.search.includes(q));
            row.classList.toggle('d-none', !ok);
            if (ok) shown++;
        });
        const count = document.getElementById('uploadCount');
        if (count) count.textContent = shown;
        document.getElementById('uploadNoMatch')?.classList.toggle('d-none', shown > 0);
    }
    search?.addEventListener('input', apply);
    pills?.querySelectorAll('[data-status]').forEach(btn => btn.addEventListener('click', () => {
        pills.querySelectorAll('.tab-pill').forEach(b => b.classList.toggle('active', b === btn));
        status = btn.dataset.status;
        apply();
    }));
})();
</script>
HTML;
include APPPATH . 'Views/layout/footer.php';
?>

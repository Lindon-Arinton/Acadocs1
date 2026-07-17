<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <a href="<?= base_url('tasks') ?>" class="text-white text-decoration-none small d-inline-flex align-items-center gap-1 mb-2" style="opacity:.85;">
        <i class="bi bi-arrow-left"></i>Back to Tasks
      </a>
      <h4><i class="bi bi-list-task me-2"></i><?= e($task['title']) ?></h4>
      <p>
        Assigned to <strong class="text-capitalize"><?= e($task['assigned_role']) ?></strong>
        · Deadline <?= date('M d, Y', strtotime($task['deadline'])) ?>
        · <span class="text-capitalize"><?= e($task['status']) ?></span>
      </p>
    </div>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-check-circle-fill"></i><?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<?php if ($task['description']): ?>
<div class="card mb-4">
  <div class="card-body">
    <p class="text-muted small mb-1 fw-semibold">Instructions</p>
    <p class="mb-0"><?= nl2br(e($task['description'])) ?></p>
  </div>
</div>
<?php endif; ?>

<div class="row g-4">
  <!-- Submissions -->
  <div class="col-lg-8">
    <h6 class="fw-bold mb-3 text-muted">Submissions (<?= count($submissions) ?>)</h6>

    <?php foreach ($submissions as $s): ?>
    <div class="card mb-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <h6 class="fw-bold mb-1"><?= e($s['submitter_name']) ?></h6>
            <span class="text-muted small">
              <i class="bi bi-paperclip me-1"></i><?= e($s['file_name']) ?>
              · Submitted <?= date('M d, Y h:i A', strtotime($s['submitted_at'])) ?>
            </span>
          </div>
          <span class="status-pill <?= $s['status'] === 'Reviewed' ? 'badge-reviewed' : 'badge-submitted' ?>"><?= e($s['status']) ?></span>
        </div>

        <?php if ($s['notes']): ?>
        <p class="small text-muted mb-3"><?= nl2br(e($s['notes'])) ?></p>
        <?php endif; ?>

        <a href="<?= base_url('task-submissions/' . $s['id'] . '/download') ?>" class="btn btn-sm btn-outline-secondary mb-3">
          <i class="bi bi-download me-1"></i>Download File
        </a>

        <?php if (! empty($s['feedback'])): ?>
        <div class="p-3 rounded-3 mb-3" style="background:#f8f9fa;border-left:3px solid var(--maroon);">
          <p class="small fw-semibold mb-2 text-muted"><i class="bi bi-chat-dots me-1"></i>Your Private Feedback</p>
          <?php foreach ($s['feedback'] as $fb): ?>
          <p class="small mb-1"><?= e($fb['comment']) ?> <span class="text-muted">— <?= date('M d, Y', strtotime($fb['date'])) ?></span></p>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <button class="btn btn-sm btn-outline-maroon" onclick="feedbackSubmission(<?= $s['id'] ?>, '<?= e(addslashes($s['submitter_name'])) ?>')">
          <i class="bi bi-chat-dots me-1"></i>Give Feedback
        </button>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($submissions)): ?>
    <div class="card"><div class="card-body text-center py-5 text-muted">
      <i class="bi bi-inbox fs-1 d-block mb-3"></i>No submissions yet.
    </div></div>
    <?php endif; ?>
  </div>

  <!-- Pending assignees -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header bg-white py-3 fw-semibold">
        <i class="bi bi-hourglass-split me-2 text-muted"></i>Not Yet Submitted
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($pendingUsers as $u): ?>
        <li class="list-group-item py-2 px-3 d-flex align-items-center gap-2">
          <div style="width:26px;height:26px;border-radius:50%;background:#f3f4f6;color:#374151;font-size:.65rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <?= strtoupper(substr($u['name'], 0, 1)) ?>
          </div>
          <span class="small"><?= e($u['name']) ?></span>
        </li>
        <?php endforeach; ?>
        <?php if (empty($pendingUsers)): ?>
        <li class="list-group-item py-4 text-center text-muted small">Everyone has submitted.</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--maroon);color:#fff;">
        <h6 class="modal-title fw-bold"><i class="bi bi-chat-dots me-2"></i>Give Private Feedback</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('tasks/' . $task['id']) ?>" class="ajax-form"
            data-confirm-action="update" data-confirm-title="Send this feedback?"
            data-confirm-text="Only the submitter will be able to see it.">
        <div class="modal-body">
          <input type="hidden" name="submission_id" id="feedbackSubmissionId">
          <p class="text-muted small mb-3">Feedback for: <strong id="feedbackSubmitter"></strong></p>
          <label class="form-label fw-semibold small">Private Comment</label>
          <textarea name="comment" class="form-control" rows="4" required placeholder="Only this person will see your feedback..."></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-maroon"><i class="bi bi-send me-2"></i>Send Feedback</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extraScript = "<script>
function feedbackSubmission(id, name) {
    document.getElementById('feedbackSubmissionId').value = id;
    document.getElementById('feedbackSubmitter').textContent = name;
    new bootstrap.Modal(document.getElementById('feedbackModal')).show();
}
</script>";
include APPPATH . 'Views/layout/footer.php';
?>

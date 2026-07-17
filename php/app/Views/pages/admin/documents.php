<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <h4><i class="bi bi-file-earmark-check me-2"></i>Document Management</h4>
  <p>Review and manage teacher-submitted DLLs, Lesson Plans & other documents</p>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
  <i class="bi bi-check-circle me-2"></i>Feedback submitted successfully.
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
  <div class="card-body py-3">
    <div class="d-flex gap-2 flex-wrap">
      <?php foreach (['all'=>'All','Submitted'=>'Submitted','Reviewed'=>'Reviewed','Pending'=>'Pending','Returned'=>'Returned'] as $val=>$lbl): ?>
      <a href="?status=<?= $val ?>"
         class="btn btn-sm <?= $statusFilter===$val ? 'btn-maroon' : 'btn-outline-secondary' ?>">
        <?= $lbl ?>
        <?php if ($val !== 'all'): ?>
        <span class="badge bg-secondary ms-1"><?= $statusCounts[$val] ?? 0 ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>#</th><th>Teacher</th><th>Type</th><th>Subject</th>
            <th>Grade</th><th>Date Submitted</th><th>Status</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($docs as $i => $doc):
            $badgeMap = ['Submitted'=>'badge-submitted','Reviewed'=>'badge-reviewed','Pending'=>'badge-pending','Returned'=>'badge-returned'];
          ?>
          <tr>
            <td class="text-muted small"><?= $i+1 ?></td>
            <td class="fw-semibold"><?= e($doc['teacher_name']) ?></td>
            <td><?= e($doc['type']) ?></td>
            <td><?= e($doc['subject']) ?></td>
            <td><?= e($doc['grade_level']) ?></td>
            <td class="small text-muted"><?= date('M d, Y', strtotime($doc['date_submitted'])) ?></td>
            <td><span class="status-pill <?= $badgeMap[$doc['status']] ?? 'badge-pending' ?>"><?= e($doc['status']) ?></span></td>
            <td>
              <button class="btn btn-sm btn-outline-secondary"
                      onclick="viewDoc(<?= htmlspecialchars(json_encode($doc), ENT_QUOTES) ?>)">
                <i class="bi bi-eye"></i>
              </button>
              <?php if (hasRole('admin') && $doc['status'] === 'Submitted'): ?>
              <button class="btn btn-sm btn-outline-maroon"
                      onclick="feedbackDoc(<?= $doc['id'] ?>, '<?= e(addslashes($doc['teacher_name'])) ?>')">
                <i class="bi bi-chat-dots"></i> Review
              </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($docs)): ?>
          <tr><td colspan="8" class="text-center py-5 text-muted">No documents found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--maroon);color:#fff;">
        <h6 class="modal-title fw-bold"><i class="bi bi-file-earmark me-2"></i>Document Details</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="viewModalBody"></div>
    </div>
  </div>
</div>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--maroon);color:#fff;">
        <h6 class="modal-title fw-bold"><i class="bi bi-chat-dots me-2"></i>Add Feedback</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('documents') ?>" class="ajax-form"
            data-confirm-action="update" data-confirm-title="Submit this feedback?"
            data-confirm-text="The document status will be marked as Reviewed.">
        <div class="modal-body">
          <input type="hidden" name="doc_id" id="feedbackDocId">
          <input type="hidden" name="decision" id="feedbackDecision" value="approve">
          <p class="text-muted small mb-3">Reviewing document for: <strong id="feedbackTeacher"></strong></p>
          <label class="form-label fw-semibold small">Feedback / Comment</label>
          <textarea name="comment" class="form-control" rows="4" required placeholder="Enter your feedback..."></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-outline-danger" onclick="setFeedbackDecision(this,'reject')">
            <i class="bi bi-x-circle me-2"></i>Reject
          </button>
          <button type="submit" class="btn btn-maroon" onclick="setFeedbackDecision(this,'approve')">
            <i class="bi bi-send me-2"></i>Submit Feedback
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extraScript = "<script>
const DOC_FILE_BASE = '" . base_url('documents/') . "';
function viewDoc(doc) {
    const fileLink = doc.file_path
        ? `<a href='\${DOC_FILE_BASE}\${doc.id}/file' target='_blank' rel='noopener' class='btn btn-sm btn-outline-secondary mt-2'><i class='bi bi-eye me-1'></i>View File</a>`
        : `<p class='text-muted small mb-0 mt-2'>No file attached.</p>`;
    document.getElementById('viewModalBody').innerHTML = `
      <table class='table table-sm'>
        <tr><th>Teacher</th><td>\${doc.teacher_name}</td></tr>
        <tr><th>Type</th><td>\${doc.type}</td></tr>
        <tr><th>Subject</th><td>\${doc.subject}</td></tr>
        <tr><th>Grade</th><td>\${doc.grade_level}</td></tr>
        <tr><th>Submitted</th><td>\${doc.date_submitted}</td></tr>
        <tr><th>Status</th><td><span class='badge bg-secondary'>\${doc.status}</span></td></tr>
      </table>
      \${fileLink}`;
    new bootstrap.Modal(document.getElementById('viewModal')).show();
}
function feedbackDoc(id, teacher) {
    document.getElementById('feedbackDocId').value = id;
    document.getElementById('feedbackTeacher').textContent = teacher;
    setFeedbackDecision(null, 'approve');
    new bootstrap.Modal(document.getElementById('feedbackModal')).show();
}
function setFeedbackDecision(btn, decision) {
    document.getElementById('feedbackDecision').value = decision;
    const form = document.getElementById('feedbackDecision').closest('form');
    if (decision === 'reject') {
        form.dataset.confirmTitle = 'Reject this document?';
        form.dataset.confirmText = 'The document will be marked as Returned and sent back to the teacher for revision.';
    } else {
        form.dataset.confirmTitle = 'Submit this feedback?';
        form.dataset.confirmText = 'The document status will be marked as Reviewed.';
    }
}
</script>";
include APPPATH . 'Views/layout/footer.php';
?>

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
                   value="<?= e($teacher['grade_level']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Add Document</label>
            <input type="file" name="document_file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png" required>
            <div class="form-text small">Upload the file you want to submit for review.</div>
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

<?php include APPPATH . 'Views/layout/footer.php'; ?>

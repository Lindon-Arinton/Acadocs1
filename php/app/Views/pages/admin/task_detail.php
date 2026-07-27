<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <a href="<?= base_url('tasks') ?>" class="text-white text-decoration-none small d-inline-flex align-items-center gap-1 mb-2" style="opacity:.85;">
        <i class="bi bi-arrow-left"></i>Back to Tasks
      </a>
      <h4><i class="bi bi-list-task me-2"></i><?= e($task['title']) ?></h4>
      <p>
        Assigned to <strong class="text-capitalize"><?= $task['assigned_role'] === 'specific' ? count($pendingUsers) + count($submissions) . ' specific ' . ((count($pendingUsers) + count($submissions)) === 1 ? 'person' : 'people') : e($task['assigned_role']) ?></strong>
        · Posted <?= date('M d, Y', strtotime($task['created_at'])) ?>
        · Deadline <?= date('M d, Y h:i A', strtotime($task['deadline'])) ?>
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
              <i class="bi bi-paperclip me-1"></i><?= count($s['files']) ?> file<?= count($s['files']) !== 1 ? 's' : '' ?>
              · Submitted <?= date('M d, Y h:i A', strtotime($s['submitted_at'])) ?>
            </span>
          </div>
          <span class="status-pill <?= $s['status'] === 'Reviewed' ? 'badge-reviewed' : 'badge-submitted' ?>"><?= e($s['status']) ?></span>
        </div>

        <?php if ($s['notes']): ?>
        <p class="small text-muted mb-3"><?= nl2br(e($s['notes'])) ?></p>
        <?php endif; ?>

        <button type="button" class="btn btn-sm btn-outline-maroon"
                onclick='viewSubmission(<?= json_encode([
                    'id'            => (int) $s['id'],
                    'submitterName' => $s['submitter_name'],
                    'files'         => array_map(static fn ($f) => [
                        'id'   => (int) $f['id'],
                        'name' => $f['file_name'],
                        'ext'  => strtolower(pathinfo($f['file_name'], PATHINFO_EXTENSION)),
                    ], $s['files']),
                    'feedback'      => array_map(static fn ($fb) => [
                        'comment' => $fb['comment'],
                        'date'    => date('M d, Y', strtotime($fb['date'])),
                    ], $s['feedback']),
                ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
          <i class="bi bi-eye me-1"></i>View File<?= count($s['files']) !== 1 ? 's' : '' ?>
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
          <div style="width:26px;height:26px;border-radius:50%;background:var(--surface-hover);color:var(--text-secondary);font-size:.65rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
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

<!-- View Submission Modal (files + preview + feedback) -->
<div class="modal fade" id="viewSubmissionModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--maroon);color:#fff;">
        <h6 class="modal-title fw-bold"><i class="bi bi-eye me-2"></i>Submission — <span id="viewSubmitterName"></span></h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small fw-semibold mb-2">Files</p>
        <div id="viewFilesList" class="mb-3"></div>

        <div id="viewFilePreviewWrap" class="mb-3" style="display:none;">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small fw-semibold" id="viewPreviewFileName"></span>
            <button type="button" class="btn-close" onclick="closeSubmissionPreview()"></button>
          </div>
          <div id="viewFilePreviewBody" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;"></div>
        </div>

        <div id="viewFeedbackThread"></div>

        <hr>
        <form method="POST" action="<?= base_url('tasks/' . $task['id']) ?>" class="ajax-form"
              data-confirm-action="update" data-confirm-title="Send this feedback?"
              data-confirm-text="Only the submitter will be able to see it.">
          <input type="hidden" name="submission_id" id="viewSubmissionId">
          <label class="form-label fw-semibold small">Private Comment</label>
          <textarea name="comment" class="form-control mb-2" rows="3" required placeholder="Only this person will see your feedback..."></textarea>
          <button type="submit" class="btn btn-maroon btn-sm"><i class="bi bi-send me-2"></i>Send Feedback</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
$extraScript = '<script>
const PREVIEWABLE_EXT = ["doc","docx","xls","xlsx","ppt","pptx","pdf","jpg","jpeg","png"];
const TASK_FILE_BASE = "' . base_url('task-submissions/') . '";

function escapeHtml(s) {
    const d = document.createElement("div");
    d.textContent = s;
    return d.innerHTML;
}

function viewSubmission(data) {
    document.getElementById("viewSubmitterName").textContent = data.submitterName;
    document.getElementById("viewSubmissionId").value = data.id;

    document.getElementById("viewFilesList").innerHTML = data.files.map(function (f) {
        const previewBtn = PREVIEWABLE_EXT.includes(f.ext)
            ? \'<button type="button" class="btn btn-sm btn-outline-secondary preview-file-btn" data-file-id="\' + f.id + \'" data-file-name="\' + escapeHtml(f.name) + \'"><i class="bi bi-eye"></i></button>\'
            : "";
        return \'<div class="d-flex justify-content-between align-items-center p-2 rounded-3 mb-2" style="background:#f8f9fa;">\'
            + \'<span class="small text-truncate me-2"><i class="bi bi-file-earmark me-1"></i>\' + escapeHtml(f.name) + "</span>"
            + \'<div class="d-flex gap-1 flex-shrink-0">\' + previewBtn
            + \'<a class="btn btn-sm btn-outline-secondary" href="\' + TASK_FILE_BASE + f.id + \'/download"><i class="bi bi-download"></i></a>\'
            + "</div></div>";
    }).join("");

    document.querySelectorAll("#viewFilesList .preview-file-btn").forEach(function (btn) {
        btn.addEventListener("click", function () {
            previewSubmissionFile(this.dataset.fileId, this.dataset.fileName);
        });
    });

    const thread = document.getElementById("viewFeedbackThread");
    thread.innerHTML = data.feedback.length
        ? \'<div class="p-3 rounded-3" style="background:#f8f9fa;border-left:3px solid var(--maroon);">\'
            + \'<p class="small fw-semibold mb-2 text-muted"><i class="bi bi-chat-dots me-1"></i>Your Private Feedback</p>\'
            + data.feedback.map(function (fb) {
                return \'<p class="small mb-1">\' + escapeHtml(fb.comment) + \' <span class="text-muted">— \' + escapeHtml(fb.date) + "</span></p>";
            }).join("")
            + "</div>"
        : "";

    closeSubmissionPreview();
    new bootstrap.Modal(document.getElementById("viewSubmissionModal")).show();
}

function previewSubmissionFile(fileId, name) {
    document.getElementById("viewPreviewFileName").textContent = name;
    document.getElementById("viewFilePreviewBody").innerHTML =
        \'<iframe src="\' + TASK_FILE_BASE + fileId + \'/preview" style="width:100%;height:400px;border:0;"></iframe>\';
    document.getElementById("viewFilePreviewWrap").style.display = "block";
}

function closeSubmissionPreview() {
    document.getElementById("viewFilePreviewWrap").style.display = "none";
    document.getElementById("viewFilePreviewBody").innerHTML = "";
}
</script>';
include APPPATH . 'Views/layout/footer.php';
?>

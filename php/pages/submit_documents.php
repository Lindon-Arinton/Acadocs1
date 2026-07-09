<?php
require_once __DIR__ . '/../config.php';
requireLogin();
if (!hasRole('teacher')) { header('Location: ' . BASE_URL . 'dashboard'); exit; }
define('PAGE_TITLE', 'Submit Documents');
$db = getDB();

$user    = currentUser();
$teacher = $db->prepare("SELECT * FROM teachers WHERE email=? LIMIT 1");
$teacher->execute([$user['email']]);
$teacher = $teacher->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $teacher) {
    $db->prepare("INSERT INTO documents (teacher_id,type,subject,grade_level,date_submitted,status) VALUES (?,?,?,?,?,?)")
       ->execute([$teacher['id'],$_POST['type'],$_POST['subject'],$_POST['grade_level'],date('Y-m-d H:i:s'),'Submitted']);
    header('Location: ' . BASE_URL . 'submit-documents?success=1'); exit;
}

// My submissions
$myDocs = [];
if ($teacher) {
    $stmt = $db->prepare("SELECT d.*, GROUP_CONCAT(df.comment ORDER BY df.date SEPARATOR '|||') AS feedback_comments
                          FROM documents d
                          LEFT JOIN document_feedback df ON df.document_id=d.id
                          WHERE d.teacher_id=?
                          GROUP BY d.id ORDER BY d.date_submitted DESC");
    $stmt->execute([$teacher['id']]);
    $myDocs = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

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
        <form method="POST">
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

<?php include __DIR__ . '/../includes/footer.php'; ?>

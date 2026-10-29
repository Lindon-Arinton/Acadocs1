<?php include APPPATH . 'Views/layout/header.php'; ?>
<?php
use App\Models\SubjectLoadRequestModel as SLR;

$loadLine = static fn (?array $r) => e(SLR::label($r));
// One line describing the request: "English · Grade 8 · Maagap → English · Grade 8 · Magalang".
$changeLine = static function (array $c) use ($loadLine): string {
    return match ($c['action']) {
        'change' => $loadLine($c['from']) . ' <i class="bi bi-arrow-right mx-1"></i> <strong>' . $loadLine($c['to']) . '</strong>',
        'add'    => '<strong>' . $loadLine($c['to']) . '</strong>',
        default  => '<strong>' . $loadLine($c['from']) . '</strong>',
    };
};
?>

<div class="page-header">
  <div>
    <h4><i class="bi bi-journal-check me-2"></i>Subject Requests</h4>
    <p>Teachers' requests to add, change or remove a subject in their load. A teacher's subjects change only when you approve.</p>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> mb-4"><?= e($flash['msg']) ?></div>
<?php endif; ?>

<h6 class="fw-bold text-muted small text-uppercase mb-2" style="letter-spacing:.04em;">
  <i class="bi bi-hourglass-split me-1"></i>Waiting for approval (<?= count($pending) ?>)
</h6>

<?php if (! $pending): ?>
<div class="card mb-4">
  <div class="card-body text-center text-muted py-5">
    <i class="bi bi-inbox fs-1 d-block mb-3"></i>No requests waiting. Teachers send them from My Profile → Subject Load.
  </div>
</div>
<?php endif; ?>

<?php foreach ($pending as $r): $c = $r['change']; $verb = SLR::ACTIONS[$c['action']]; ?>
<div class="card mb-3 subject-request-card">
  <div class="card-body">
    <div class="d-flex flex-wrap align-items-start gap-3">
      <span class="badge badge-change-<?= e($c['action']) ?> mt-1"><?= e($verb) ?></span>
      <div class="flex-grow-1 min-w-0">
        <div class="fw-semibold"><?= personLink((int) $r['user_id'], $r['teacher_name']) ?></div>
        <div class="small text-muted mb-2">Term <?= (int) $r['term'] ?>, SY <?= e($r['school_year']) ?> · sent <?= date('M d, Y h:i A', strtotime($r['created_at'])) ?></div>
        <div class="mb-1"><?= $changeLine($c) ?></div>
        <div class="small"><span class="text-muted">Reason:</span> <?= nl2br(e($r['reason'] ?? '')) ?></div>
        <?php if (! empty($r['conflict'])): ?>
        <div class="small text-danger mt-1"><i class="bi bi-exclamation-triangle me-1"></i><?= e($r['conflict']) ?> The load changed since this was sent, so reject it.</div>
        <?php endif; ?>
      </div>
      <div class="d-flex gap-2 flex-shrink-0">
        <?php if (empty($r['conflict'])): ?>
        <form method="POST" action="<?= base_url('subject-requests') ?>" class="ajax-form"
              data-confirm-title="Approve: <?= e($verb) ?> subject?"
              data-confirm-text="<?= e(strip_tags(html_entity_decode($changeLine($c)))) ?> — <?= e($r['teacher_name']) ?>, Term <?= (int) $r['term'] ?>, SY <?= e($r['school_year']) ?>.">
          <input type="hidden" name="request_id" value="<?= (int) $r['id'] ?>">
          <input type="hidden" name="decision" value="approve">
          <button class="btn btn-sm btn-success"><i class="bi bi-check-circle me-1"></i>Approve</button>
        </form>
        <?php endif; ?>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="rejectSubjectRequest(<?= (int) $r['id'] ?>, <?= e(json_encode($r['teacher_name'])) ?>)">
          <i class="bi bi-x-circle me-1"></i>Reject
        </button>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<?php if ($decided): ?>
<h6 class="fw-bold text-muted small text-uppercase mt-4 mb-2" style="letter-spacing:.04em;"><i class="bi bi-clock-history me-1"></i>Handled in the last 60 days</h6>
<div class="card mb-4">
  <div class="table-responsive">
    <table class="table mb-0 align-middle small">
      <thead><tr><th>Teacher</th><th>Request</th><th>Term</th><th>Status</th><th>By</th><th>Note</th></tr></thead>
      <tbody>
        <?php foreach ($decided as $r): $c = $r['change']; ?>
        <tr>
          <td class="fw-semibold"><?= e($r['teacher_name']) ?></td>
          <td><span class="badge badge-change-<?= e($c['action']) ?> me-1"><?= e(SLR::ACTIONS[$c['action']]) ?></span><?= $changeLine($c) ?></td>
          <td class="text-nowrap">Term <?= (int) $r['term'] ?>, SY <?= e($r['school_year']) ?></td>
          <td><span class="status-pill <?= $r['status'] === 'Approved' ? 'badge-reviewed' : 'badge-returned' ?>"><?= e($r['status']) ?></span></td>
          <td class="text-nowrap"><?= e($r['reviewer_name'] ?? '—') ?><div class="text-muted"><?= $r['reviewed_at'] ? date('M d, Y', strtotime($r['reviewed_at'])) : '' ?></div></td>
          <td><?= e($r['review_note'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Reject: the teacher sees this reason on their profile -->
<div class="modal fade" id="rejectSubjectModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= base_url('subject-requests') ?>" class="ajax-form">
        <div class="modal-header" style="background:var(--maroon);color:#fff;">
          <h6 class="modal-title fw-bold"><i class="bi bi-x-circle me-2"></i>Reject request — <span id="rejectSubjectTeacher"></span></h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="request_id" id="rejectSubjectId">
          <input type="hidden" name="decision" value="reject">
          <label class="form-label small fw-semibold" for="rejectSubjectNote">Why? The teacher will see this.</label>
          <textarea name="note" id="rejectSubjectNote" rows="3" maxlength="500" class="form-control" required
                    placeholder="e.g. Masikap is handled by another teacher this term."></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Reject</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extraScript = <<<'HTML'
<script>
window.rejectSubjectRequest = function (id, teacher) {
    document.getElementById('rejectSubjectId').value = id;
    document.getElementById('rejectSubjectTeacher').textContent = teacher;
    document.getElementById('rejectSubjectNote').value = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('rejectSubjectModal')).show();
};
</script>
HTML;
include APPPATH . 'Views/layout/footer.php';
?>

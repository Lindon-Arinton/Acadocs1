<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <h4><i class="bi bi-list-task me-2"></i>My Tasks</h4>
  <p>Document requirements assigned to you by the principal</p>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-check-circle-fill"></i><?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Search + Due Date Filter -->
<div class="card mb-3">
  <div class="card-body py-3">
    <div class="d-flex gap-2 flex-wrap align-items-center">
      <div class="input-group input-group-sm" style="flex:1;min-width:220px;max-width:340px;">
        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
        <input type="text" id="taskSearchInput" placeholder="Search tasks by title..."
               class="form-control border-start-0 ps-0" autocomplete="off">
      </div>
      <select id="taskDueFilter" class="form-select form-select-sm" style="width:auto;">
        <option value="all">All Due Dates</option>
        <option value="overdue">Overdue</option>
        <option value="today">Due Today</option>
        <option value="week">Due This Week</option>
        <option value="month">Due This Month</option>
      </select>
    </div>
  </div>
</div>

<?php
$today      = date('Y-m-d');
$weekStart  = date('Y-m-d', strtotime('monday this week'));
$weekEnd    = date('Y-m-d', strtotime('sunday this week'));
$monthStart = date('Y-m-01');
$monthEnd   = date('Y-m-t');
?>
<div id="taskListEmpty" class="card d-none"><div class="card-body text-center py-5 text-muted">
  <i class="bi bi-funnel fs-1 d-block mb-3"></i>No tasks match your filters.
</div></div>

<?php foreach ($tasks as $t):
  $submission   = $t['submission'];
  $overdue      = $t['status'] === 'Open' && ! $submission && strtotime($t['deadline']) < strtotime(date('Y-m-d'));
  $deadlineDate = date('Y-m-d', strtotime($t['deadline']));
  $dueToday     = $deadlineDate === $today;
  $dueThisWeek  = $deadlineDate >= $weekStart && $deadlineDate <= $weekEnd;
  $dueThisMonth = $deadlineDate >= $monthStart && $deadlineDate <= $monthEnd;
?>
<div class="card mb-3 task-card"
     data-title="<?= e(strtolower($t['title'])) ?>"
     data-overdue="<?= $overdue ? '1' : '0' ?>"
     data-due-today="<?= $dueToday ? '1' : '0' ?>"
     data-due-week="<?= $dueThisWeek ? '1' : '0' ?>"
     data-due-month="<?= $dueThisMonth ? '1' : '0' ?>">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
      <div>
        <h6 class="fw-bold mb-1"><?= e($t['title']) ?></h6>
        <span class="text-muted small <?= $overdue ? 'text-danger fw-semibold' : '' ?>">
          <i class="bi bi-calendar3 me-1"></i>Deadline <?= date('M d, Y', strtotime($t['deadline'])) ?>
          <?= $overdue ? ' · Overdue' : '' ?>
          <?= $t['status'] === 'Closed' ? ' · Closed' : '' ?>
        </span>
      </div>
      <?php if ($submission): ?>
      <span class="status-pill <?= $submission['status'] === 'Reviewed' ? 'badge-reviewed' : 'badge-submitted' ?>">
        <?= $submission['status'] === 'Reviewed' ? 'Reviewed' : 'Submitted' ?>
      </span>
      <?php elseif ($t['status'] === 'Closed'): ?>
      <span class="status-pill badge-returned">Missed</span>
      <?php else: ?>
      <span class="status-pill badge-pending">Not Submitted</span>
      <?php endif; ?>
    </div>

    <?php if ($t['description']): ?>
    <p class="small text-muted mb-3"><?= nl2br(e($t['description'])) ?></p>
    <?php endif; ?>

    <?php if ($submission): ?>
    <div class="p-3 rounded-3 mb-3" style="background:#f8f9fa;">
      <div class="d-flex justify-content-between align-items-center">
        <span class="small"><i class="bi bi-paperclip me-1"></i><?= e($submission['file_name']) ?></span>
        <a href="<?= base_url('task-submissions/' . $submission['id'] . '/download') ?>" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-download"></i>
        </a>
      </div>
      <div class="text-muted small mt-1">Submitted <?= date('M d, Y h:i A', strtotime($submission['submitted_at'])) ?></div>
    </div>
    <?php endif; ?>

    <?php if (! empty($t['feedback'])): ?>
    <div class="p-3 rounded-3 mb-3" style="background:#fff5f5;border-left:3px solid var(--maroon);">
      <p class="small fw-semibold mb-2 text-muted"><i class="bi bi-chat-dots me-1"></i>Principal Feedback (private)</p>
      <?php foreach ($t['feedback'] as $fb): ?>
      <p class="small mb-1"><?= e($fb['comment']) ?> <span class="text-muted">— <?= date('M d, Y', strtotime($fb['date'])) ?></span></p>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($t['status'] === 'Open'): ?>
    <button class="btn btn-sm btn-maroon" onclick="openSubmitModal(<?= $t['id'] ?>, '<?= e(addslashes($t['title'])) ?>')">
      <i class="bi bi-upload me-1"></i><?= $submission ? 'Replace Submission' : 'Submit Document' ?>
    </button>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<?php if (empty($tasks)): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
  <i class="bi bi-inbox fs-1 d-block mb-3"></i>No tasks assigned to you yet.
</div></div>
<?php endif; ?>

<!-- Submit Modal -->
<div class="modal fade" id="submitTaskModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-upload me-2"></i>Submit Document</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('my-tasks') ?>" class="ajax-form" enctype="multipart/form-data"
            data-confirm-action="add" data-confirm-title="Submit this document?"
            data-confirm-text="The principal will be notified and can review your file.">
        <input type="hidden" name="task_id" id="submitTaskId">
        <div class="modal-body">
          <p class="text-muted mb-3 small">Submitting for: <strong id="submitTaskTitle"></strong></p>
          <div class="mb-3">
            <label class="form-label">File</label>
            <input type="file" name="file" class="form-control" required
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png">
            <div class="form-text">PDF, Word, Excel, PowerPoint, or image. Max 10MB.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Notes (optional)</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Any notes for the principal"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Submit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extraScript = "<script>
function openSubmitModal(taskId, title) {
    document.getElementById('submitTaskId').value = taskId;
    document.getElementById('submitTaskTitle').textContent = title;
    new bootstrap.Modal(document.getElementById('submitTaskModal')).show();
}

const taskSearchInput = document.getElementById('taskSearchInput');
const taskDueFilter    = document.getElementById('taskDueFilter');
const taskCards        = document.querySelectorAll('.task-card');
const taskListEmpty    = document.getElementById('taskListEmpty');

function applyTaskFilters() {
    const query = taskSearchInput.value.trim().toLowerCase();
    const due   = taskDueFilter.value;
    let visibleCount = 0;

    taskCards.forEach(card => {
        const matchesSearch = !query || card.dataset.title.includes(query);
        const matchesDue    = due === 'all' || card.dataset[
            due === 'overdue' ? 'overdue' : due === 'today' ? 'dueToday' : due === 'week' ? 'dueWeek' : 'dueMonth'
        ] === '1';
        const visible = matchesSearch && matchesDue;
        card.classList.toggle('d-none', !visible);
        if (visible) visibleCount++;
    });

    taskListEmpty.classList.toggle('d-none', visibleCount > 0 || taskCards.length === 0);
}

taskSearchInput?.addEventListener('input', applyTaskFilters);
taskDueFilter?.addEventListener('change', applyTaskFilters);
</script>";
include APPPATH . 'Views/layout/footer.php';
?>

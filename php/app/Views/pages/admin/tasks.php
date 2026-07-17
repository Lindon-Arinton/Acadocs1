<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <h4><i class="bi bi-list-task me-2"></i>Tasks &amp; Assignments</h4>
      <p>Assign document requirements to a role, set a deadline, and review submissions</p>
    </div>
    <button class="btn btn-light" style="position:relative;z-index:1;" data-bs-toggle="modal" data-bs-target="#addTaskModal">
      <i class="bi bi-plus-lg me-1"></i>New Task
    </button>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-check-circle-fill"></i><?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <div class="card-title">Posted Tasks</div>
    <div class="card-description"><?= count($tasks) ?> task<?= count($tasks) !== 1 ? 's' : '' ?></div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Title</th><th>Assigned Role</th><th>Deadline</th>
            <th>Status</th><th>Submissions</th><th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tasks as $t):
            $overdue = $t['status'] === 'Open' && strtotime($t['deadline']) < strtotime(date('Y-m-d'));
            $pct     = $t['eligible_count'] > 0 ? round($t['submitted_count'] / $t['eligible_count'] * 100) : 0;
          ?>
          <tr>
            <td class="fw-semibold"><?= e($t['title']) ?></td>
            <td><span class="badge badge-outline text-capitalize"><?= e($t['assigned_role']) ?></span></td>
            <td class="small <?= $overdue ? 'text-danger fw-semibold' : 'text-muted' ?>">
              <?= date('M d, Y', strtotime($t['deadline'])) ?>
              <?php if ($overdue): ?><br><span class="small">Overdue</span><?php endif; ?>
            </td>
            <td>
              <span class="status-pill <?= $t['status'] === 'Open' ? 'badge-submitted' : 'badge-pending' ?>">
                <?= e($t['status']) ?>
              </span>
            </td>
            <td style="min-width:140px;">
              <div class="d-flex justify-content-between small mb-1">
                <span><?= $t['submitted_count'] ?> / <?= $t['eligible_count'] ?></span>
                <span class="text-muted"><?= $pct ?>%</span>
              </div>
              <div class="progress" style="height:6px;">
                <div class="progress-bar" style="width:<?= $pct ?>%;background:var(--maroon)!important;"></div>
              </div>
            </td>
            <td class="text-center">
              <a href="<?= base_url('tasks/' . $t['id']) ?>" class="btn btn-sm btn-outline-secondary" title="View submissions">
                <i class="bi bi-eye"></i>
              </a>
              <?php if ($t['status'] === 'Open'): ?>
              <form method="POST" action="<?= base_url('tasks') ?>" class="d-inline ajax-form"
                    data-confirm-title="Close this task?" data-confirm-text="Assignees will no longer be able to submit against it.">
                <input type="hidden" name="action" value="close">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <button class="btn btn-sm btn-outline-secondary" title="Close task"><i class="bi bi-lock"></i></button>
              </form>
              <?php else: ?>
              <form method="POST" action="<?= base_url('tasks') ?>" class="d-inline ajax-form"
                    data-confirm-title="Reopen this task?" data-confirm-text="Assignees will be able to submit again.">
                <input type="hidden" name="action" value="reopen">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <button class="btn btn-sm btn-outline-secondary" title="Reopen task"><i class="bi bi-unlock"></i></button>
              </form>
              <?php endif; ?>
              <form method="POST" action="<?= base_url('tasks') ?>" class="d-inline ajax-form"
                    data-confirm-title="Delete this task?" data-confirm-text="All submissions and feedback for this task will be permanently removed.">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <button class="btn btn-sm btn-outline-secondary text-danger" title="Delete task"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($tasks)): ?>
          <tr><td colspan="6" class="text-center py-5 text-muted">No tasks posted yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Post a New Task</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('tasks') ?>" class="ajax-form"
            data-confirm-text="Everyone with the selected role will see this task and can submit a document against it.">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Submit Q1 DLL" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description / Instructions</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Optional details for the assignees"></textarea>
          </div>
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label">Assign To</label>
              <select name="assigned_role" class="form-select" required>
                <option value="teacher">Teacher</option>
                <option value="secretary">Secretary</option>
                <option value="adas">ADAS</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">Deadline</label>
              <input type="date" name="deadline" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Post Task</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include APPPATH . 'Views/layout/footer.php'; ?>

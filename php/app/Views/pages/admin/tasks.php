<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <h4><i class="bi bi-list-task me-2"></i>Tasks &amp; Assignments</h4>
      <p>Assign document requirements to a role, set a deadline, and review submissions</p>
    </div>
    <button class="btn btn-light" style="position:relative;z-index:1;" onclick="openNewTaskModal()">
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
            $overdue = $t['status'] === 'Open' && strtotime($t['deadline']) < time();
            $pct     = $t['eligible_count'] > 0 ? round($t['submitted_count'] / $t['eligible_count'] * 100) : 0;
          ?>
          <tr>
            <td class="fw-semibold">
              <?= e($t['title']) ?>
              <div class="text-muted fw-normal" style="font-size:.7rem;">Posted <?= date('M d, Y', strtotime($t['created_at'])) ?></div>
            </td>
            <td>
              <span class="badge badge-outline text-capitalize">
                <?= $t['assigned_role'] === 'specific' ? 'Specific (' . $t['eligible_count'] . ')' : e($t['assigned_role']) ?>
              </span>
            </td>
            <td class="small <?= $overdue ? 'text-danger fw-semibold' : 'text-muted' ?>">
              <?= date('M d, Y h:i A', strtotime($t['deadline'])) ?>
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
              <div class="maroon-select" style="width:100%;">
                <select name="assigned_role" id="taskAssignedRole" class="maroon-select-native" required onchange="onAssignedRoleChange()">
                  <option value="teacher">Teacher</option>
                  <option value="adas">ADAS</option>
                  <option value="specific">Specific People</option>
                </select>
                <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
                <div class="maroon-select-panel"></div>
              </div>
            </div>
            <div class="col-6">
              <label class="form-label">Deadline Date</label>
              <div class="maroon-dp" id="taskDeadlineDp" data-min="<?= date('Y-m-d') ?>">
                <input type="text" class="form-control maroon-dp-display" placeholder="Select date" readonly required>
                <input type="hidden" name="deadline_date" value="<?= date('Y-m-d') ?>">
                <div class="maroon-dp-panel">
                  <div class="maroon-dp-header">
                    <button type="button" class="maroon-dp-nav" data-dir="-1"><i class="bi bi-chevron-left"></i></button>
                    <span class="maroon-dp-month-label"></span>
                    <button type="button" class="maroon-dp-nav" data-dir="1"><i class="bi bi-chevron-right"></i></button>
                  </div>
                  <div class="maroon-dp-dow"><span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span></div>
                  <div class="maroon-dp-grid"></div>
                </div>
              </div>
            </div>
            <div class="col-12 d-none" id="taskSpecificPickerWrap">
              <button type="button" class="btn btn-outline-maroon btn-sm" onclick="openPeoplePicker()">
                <i class="bi bi-people me-1"></i><span id="taskSpecificSummary">Choose People</span>
              </button>
              <div id="taskSpecificChips" class="d-flex flex-wrap gap-1 mt-2"></div>
              <div id="taskSpecificHiddenInputs"></div>
            </div>
            <div class="col-6">
              <label class="form-label">Deadline Time</label>
              <input type="time" name="deadline_time" class="form-control" value="00:00">
              <p class="text-muted mt-1 mb-0" style="font-size:.72rem;">Defaults to 12:00 AM.</p>
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

<!-- People Picker Modal (for "Specific People" assignment) -->
<div class="modal fade" id="taskPeoplePickerModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-people me-2"></i>Choose People</h6>
        <button type="button" class="btn-close btn-close-white" onclick="cancelPeoplePicker()"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <label class="form-label mb-0">Select recipients</label>
          <label class="d-flex align-items-center gap-1 mb-0" style="font-size:.75rem;cursor:pointer;">
            <input type="checkbox" id="taskPeopleSelectAll"> Select All
          </label>
        </div>
        <div class="input-group input-group-sm mb-2">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" id="taskPeopleSearchInput" class="form-control border-start-0 ps-0"
                 placeholder="Search people..." autocomplete="off">
        </div>
        <div class="d-flex gap-1 flex-wrap mb-2" id="taskPeopleRoleFilter">
          <button type="button" class="chat-filter-pill active" data-role="all">All</button>
          <button type="button" class="chat-filter-pill" data-role="teacher">Teacher</button>
          <button type="button" class="chat-filter-pill" data-role="adas">ADAS</button>
        </div>
        <div style="max-height:300px;overflow-y:auto;" id="taskPeopleList">
          <?php foreach ($assignableUsers as $u): ?>
          <label class="align-items-center gap-2 p-2 rounded-3 task-people-row" style="display:flex;cursor:pointer;"
                 data-role="<?= e($u['role']) ?>" data-search="<?= e(mb_strtolower($u['name'])) ?>"
                 data-id="<?= $u['id'] ?>" data-name="<?= e($u['name']) ?>">
            <input type="checkbox" class="task-people-checkbox">
            <div style="width:30px;height:30px;border-radius:50%;background:var(--surface-hover);color:var(--text-secondary);font-size:.68rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <?= e(strtoupper(substr($u['name'], 0, 1))) ?>
            </div>
            <div>
              <div class="fw-semibold" style="font-size:.82rem;"><?= e($u['name']) ?></div>
              <div class="text-muted" style="font-size:.7rem;"><?= e(ucfirst($u['role'])) ?></div>
            </div>
          </label>
          <?php endforeach; ?>
          <p class="text-muted text-center small p-3 mb-0 d-none" id="taskPeopleNoResults">No matches found.</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" onclick="cancelPeoplePicker()">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="confirmPeoplePicker()">Done</button>
      </div>
    </div>
  </div>
</div>

<?php
$extraScript = "<script>
let taskSelectedPeople = new Map();

function taskEscapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function openNewTaskModal() {
    taskSelectedPeople = new Map();
    const assignedRoleSelect = document.getElementById('taskAssignedRole');
    assignedRoleSelect.value = 'teacher';
    // Setting .value directly doesn't fire 'change', so tell the custom
    // dropdown widget to refresh its own displayed label to match.
    assignedRoleSelect.dispatchEvent(new Event('change'));
    document.getElementById('taskSpecificPickerWrap').classList.add('d-none');
    renderTaskSpecificSummary();
    const todayIso = new Date().toISOString().slice(0, 10);
    document.getElementById('taskDeadlineDp')?.maroonDpSetValue(todayIso);
    new bootstrap.Modal(document.getElementById('addTaskModal')).show();
}

function onAssignedRoleChange() {
    const val = document.getElementById('taskAssignedRole').value;
    document.getElementById('taskSpecificPickerWrap').classList.toggle('d-none', val !== 'specific');
}

function switchTaskModal(fromId, toId) {
    const fromEl = document.getElementById(fromId);
    const toEl = document.getElementById(toId);
    const fromModal = bootstrap.Modal.getInstance(fromEl);
    fromEl.addEventListener('hidden.bs.modal', function handler() {
        fromEl.removeEventListener('hidden.bs.modal', handler);
        new bootstrap.Modal(toEl).show();
    });
    fromModal?.hide();
}

function openPeoplePicker() {
    document.querySelectorAll('.task-people-row').forEach(row => {
        row.querySelector('.task-people-checkbox').checked = taskSelectedPeople.has(row.dataset.id);
    });
    syncTaskPeopleSelectAllState();
    switchTaskModal('addTaskModal', 'taskPeoplePickerModal');
}

function cancelPeoplePicker() {
    switchTaskModal('taskPeoplePickerModal', 'addTaskModal');
}

function confirmPeoplePicker() {
    taskSelectedPeople = new Map();
    document.querySelectorAll('.task-people-row').forEach(row => {
        if (row.querySelector('.task-people-checkbox').checked) {
            taskSelectedPeople.set(row.dataset.id, row.dataset.name);
        }
    });
    renderTaskSpecificSummary();
    switchTaskModal('taskPeoplePickerModal', 'addTaskModal');
}

function renderTaskSpecificSummary() {
    const count = taskSelectedPeople.size;
    document.getElementById('taskSpecificSummary').textContent = count > 0 ? ('Choose People (' + count + ' selected)') : 'Choose People';

    document.getElementById('taskSpecificChips').innerHTML = [...taskSelectedPeople.values()]
        .map(name => '<span class=\"badge bg-light text-dark border\">' + taskEscapeHtml(name) + '</span>')
        .join('');

    document.getElementById('taskSpecificHiddenInputs').innerHTML = [...taskSelectedPeople.keys()]
        .map(id => '<input type=\"hidden\" name=\"user_ids[]\" value=\"' + id + '\">')
        .join('');
}

function syncTaskPeopleSelectAllState() {
    const selectAll = document.getElementById('taskPeopleSelectAll');
    if (!selectAll) return;
    const visibleBoxes = [...document.querySelectorAll('.task-people-row')]
        .filter(row => row.style.display !== 'none')
        .map(row => row.querySelector('.task-people-checkbox'));
    selectAll.checked = visibleBoxes.length > 0 && visibleBoxes.every(cb => cb.checked);
}

function applyTaskPeopleFilter() {
    const q = (document.getElementById('taskPeopleSearchInput')?.value || '').trim().toLowerCase();
    const activeRoleBtn = document.querySelector('#taskPeopleRoleFilter .chat-filter-pill.active');
    const role = activeRoleBtn ? activeRoleBtn.dataset.role : 'all';
    let anyVisible = false;

    document.querySelectorAll('.task-people-row').forEach(row => {
        const matchSearch = !q || row.dataset.search.includes(q);
        const matchRole = role === 'all' || row.dataset.role === role;
        const match = matchSearch && matchRole;
        row.style.display = match ? 'flex' : 'none';
        if (match) anyVisible = true;
    });

    document.getElementById('taskPeopleNoResults')?.classList.toggle('d-none', anyVisible);
    syncTaskPeopleSelectAllState();
}

document.getElementById('taskPeopleSearchInput')?.addEventListener('input', applyTaskPeopleFilter);
document.querySelectorAll('#taskPeopleRoleFilter .chat-filter-pill').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#taskPeopleRoleFilter .chat-filter-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        applyTaskPeopleFilter();
    });
});
document.getElementById('taskPeopleSelectAll')?.addEventListener('change', function () {
    const checked = this.checked;
    document.querySelectorAll('.task-people-row').forEach(row => {
        if (row.style.display !== 'none') {
            row.querySelector('.task-people-checkbox').checked = checked;
        }
    });
});
</script>";
include APPPATH . 'Views/layout/footer.php';
?>

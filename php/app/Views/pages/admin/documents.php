<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3" style="position:relative;z-index:1">
    <div>
      <?php include APPPATH . 'Views/partials/doc_breadcrumb.php'; ?>
      <h4><i class="bi bi-<?= $current ? 'folder2-open' : 'file-earmark-check' ?> me-2"></i><?= e($current['name'] ?? 'Document Management') ?></h4>
      <p><?= $current ? 'Folder' : 'Task uploads, organized into folders' ?></p>
    </div>
    <?php if ($canManage): ?>
    <button class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);"
            data-bs-toggle="modal" data-bs-target="#newFolderModal">
      <i class="bi bi-folder-plus me-1"></i>New Folder
    </button>
    <?php endif; ?>
  </div>
</div>

<?php
$plainFolders = array_values(array_filter($folders, static fn ($f) => ! $f['is_task']));
$taskFolders  = array_values(array_filter($folders, static fn ($f) => $f['is_task']));

// Rename / Move to… / Delete menu, shared by folder cards and task sections.
$folderMenu = static function (array $folder, bool $floating) use ($canManage): void {
    if (! $canManage) {
        return;
    } ?>
      <div class="dropdown <?= $floating ? 'doc-folder-menu' : '' ?>">
        <button type="button" class="btn btn-ghost btn-sm" data-bs-toggle="dropdown" aria-label="Folder options"><i class="bi bi-three-dots-vertical"></i></button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><button type="button" class="dropdown-item" onclick="openRenameFolder(<?= (int) $folder['id'] ?>)"><i class="bi bi-pencil me-2"></i>Rename</button></li>
          <li><button type="button" class="dropdown-item" onclick="openMoveFolder(<?= (int) $folder['id'] ?>)"><i class="bi bi-folder-symlink me-2"></i>Move to…</button></li>
          <?php if (! $folder['is_task']): ?>
          <li><hr class="dropdown-divider"></li>
          <li>
            <form method="POST" action="<?= base_url('documents') ?>" class="ajax-form"
                  data-confirm-title="Delete &quot;<?= e($folder['name']) ?>&quot;?"
                  data-confirm-text="Only the folder is deleted. Everything inside it moves up one level."
                  data-confirm-action="delete">
              <input type="hidden" name="action" value="delete_folder">
              <input type="hidden" name="id" value="<?= (int) $folder['id'] ?>">
              <button class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>
            </form>
          </li>
          <?php endif; ?>
        </ul>
      </div>
<?php };
?>
<!-- Search + filters (Templates-style) -->
<div class="card mb-4">
  <div class="card-body py-3">
    <form method="GET" action="<?= base_url('documents') ?>" id="filterForm" class="d-flex flex-wrap gap-2 align-items-center">
      <?php if ($current): ?><input type="hidden" name="folder" value="<?= (int) $current['id'] ?>"><?php endif; ?>
      <div class="input-group input-group-sm" style="flex:1;min-width:220px;max-width:340px;">
        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
        <input type="text" name="q" id="docSearchInput" value="<?= e($search) ?>"
               class="form-control border-start-0 ps-0" placeholder="Search all folders by name...">
      </div>
      <?php if ($taskFolders): ?>
      <div class="maroon-select maroon-select-sm" style="width:auto;">
        <select id="docTypeFilter" class="maroon-select-native">
          <option value="all">All File Types</option>
          <?php foreach ($fileTypes as $ft): ?>
          <option value="<?= e($ft) ?>"><?= strtoupper(e($ft)) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
        <div class="maroon-select-panel"></div>
      </div>
      <div class="maroon-select maroon-select-sm" style="width:auto;">
        <select id="docStatusFilter" class="maroon-select-native">
          <option value="all">All Statuses</option>
          <option value="Pending">Pending</option>
          <option value="Reviewed">Reviewed</option>
          <option value="Returned">Returned</option>
        </select>
        <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
        <div class="maroon-select-panel"></div>
      </div>
      <button type="button" class="btn btn-outline-secondary btn-sm" id="docToggleAll"><i class="bi bi-arrows-expand me-1"></i>Expand all</button>
      <?php endif; ?>
      <?php if ($canManage && $search === '' && $folders): ?>
      <span class="text-muted small ms-auto"><i class="bi bi-arrows-move me-1"></i>Drag a folder onto another folder, or onto the path above, to move it.</span>
      <?php endif; ?>
    </form>
  </div>
</div>

<div id="docExplorer" data-folders="<?= e(json_encode($allFolders)) ?>">
<?php if (empty($folders)): ?>
<div class="card">
  <div class="card-body text-center py-5 text-muted small">
    <?php if ($search !== ''): ?>
      No folders match "<?= e($search) ?>".
    <?php elseif ($current): ?>
      <i class="bi bi-folder2-open fs-1 d-block mb-2"></i>This folder is empty.<?= $canManage ? ' Use "Move to…" on another folder to put it here, or create a new folder.' : '' ?>
    <?php else: ?>
      No folders yet. A task folder is created automatically when someone uploads to a task.
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php if ($plainFolders): ?>
<!-- Plain folders: open to browse, drop folders onto them to move -->
<h6 class="fw-bold text-muted small text-uppercase mb-2" style="letter-spacing:.04em;"><i class="bi bi-folder2 me-1"></i>Folders</h6>
<div class="row g-3 mb-4">
  <?php foreach ($plainFolders as $folder): ?>
  <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
    <div class="card doc-folder-card doc-folder-plain h-100 position-relative"
         <?php if ($canManage): ?>draggable="true"<?php endif; ?>
         data-folder-id="<?= (int) $folder['id'] ?>" data-folder-name="<?= e($folder['name']) ?>"
         data-drop-target="<?= (int) $folder['id'] ?>" data-drop-name="<?= e($folder['name']) ?>">
      <div class="card-body d-flex gap-3 align-items-start">
        <i class="bi bi-folder-fill doc-folder-icon doc-folder-icon-plain"></i>
        <div class="min-w-0 flex-grow-1 pe-3">
          <div class="fw-semibold text-truncate" style="color:var(--text);" title="<?= e($folder['name']) ?>"><?= e($folder['name']) ?></div>
          <?php if (! empty($folder['path'])): ?>
          <div class="text-muted text-truncate" style="font-size:.7rem;" title="<?= e($folder['path']) ?>"><i class="bi bi-folder2 me-1"></i><?= e($folder['path']) ?></div>
          <?php endif; ?>
          <div class="small text-muted">
            <?= $folder['child_count'] ?> folder<?= $folder['child_count'] === 1 ? '' : 's' ?>
            · <?= $folder['file_count'] ?> file<?= $folder['file_count'] === 1 ? '' : 's' ?>
          </div>
          <?php if ($folder['to_review_count'] > 0): ?>
          <span class="status-pill badge-pending mt-1 d-inline-block"><?= $folder['to_review_count'] ?> pending</span>
          <?php elseif ($folder['last_upload']): ?>
          <div class="small text-muted">Updated <?= date('M d, Y', strtotime($folder['last_upload'])) ?></div>
          <?php endif; ?>
        </div>
        <a href="<?= base_url('documents?folder=' . $folder['id']) ?>" class="stretched-link" draggable="false" aria-label="Open <?= e($folder['name']) ?>"></a>
      </div>
      <?php $folderMenu($folder, true); ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($taskFolders): ?>
<!-- Task folders, Templates-style: a collapsible section listing every uploaded file -->
<h6 class="fw-bold text-muted small text-uppercase mb-2" style="letter-spacing:.04em;"><i class="bi bi-list-task me-1"></i>Task Uploads</h6>
<?php foreach ($taskFolders as $folder):
  $files = $filesByTask[(int) $folder['task_id']] ?? [];
?>
<div class="card mb-3 doc-task-section" data-folder-id="<?= (int) $folder['id'] ?>" data-folder-name="<?= e($folder['name']) ?>"
     <?php if ($canManage): ?>draggable="true"<?php endif; ?>>
  <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between gap-2">
    <button type="button" class="btn section-toggle-btn collapsed flex-grow-1 d-flex align-items-center text-start p-0 border-0 bg-transparent" style="min-width:0;"
            data-bs-toggle="collapse" data-bs-target="#doc-section-<?= (int) $folder['id'] ?>" aria-expanded="false">
      <i class="bi bi-chevron-down section-toggle-arrow me-2 text-muted"></i>
      <i class="bi bi-folder-fill doc-folder-icon me-2" style="font-size:1.1rem;"></i>
      <span class="min-w-0">
        <span class="fw-semibold d-block text-truncate" title="<?= e($folder['name']) ?>"><?= e($folder['name']) ?></span>
        <?php if (! empty($folder['path'])): ?>
        <span class="text-muted d-block text-truncate" style="font-size:.7rem;"><i class="bi bi-folder2 me-1"></i><?= e($folder['path']) ?></span>
        <?php endif; ?>
      </span>
      <span class="badge badge-secondary ms-2 flex-shrink-0 doc-file-count"><?= count($files) ?></span>
      <?php if ($folder['to_review_count'] > 0): ?>
      <span class="status-pill badge-pending ms-2 flex-shrink-0"><?= $folder['to_review_count'] ?> pending</span>
      <?php endif; ?>
    </button>
    <div class="d-flex align-items-center gap-1 flex-shrink-0">
      <a href="<?= base_url('documents?folder=' . $folder['id']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-clipboard-check me-1"></i>Review
      </a>
      <?php $folderMenu($folder, false); ?>
    </div>
  </div>
  <div class="collapse" id="doc-section-<?= (int) $folder['id'] ?>">
    <div class="card-body">
      <?php if (empty($files)): ?>
      <p class="text-muted mb-0 text-center py-3"><i class="bi bi-inbox fs-4 d-block mb-2"></i>No files uploaded to this task yet.</p>
      <?php else: ?>
      <div class="list-group list-group-flush">
        <?php foreach ($files as $f): [$icon, $tc, $bg] = fileTypeStyle($f['ext']); ?>
        <div class="list-group-item d-flex align-items-center gap-3 flex-wrap py-3 doc-file-row"
             data-ext="<?= e($f['ext']) ?>" data-status="<?= e($f['status']) ?>">
          <div style="width:40px;height:40px;border-radius:10px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi <?= $icon ?>" style="color:<?= $tc ?>;font-size:1.1rem;"></i>
          </div>
          <div class="flex-grow-1" style="min-width:220px;">
            <div class="d-flex align-items-center gap-2 mb-1">
              <h6 class="fw-bold mb-0 text-truncate" style="font-size:.88rem" title="<?= e($f['file_name']) ?>"><?= e($f['file_name']) ?></h6>
              <?php if ($f['ext']): ?>
              <span class="badge flex-shrink-0" style="background:<?= $bg ?>;color:<?= $tc ?>;border:1px solid <?= $tc ?>22;font-size:.68rem;"><?= strtoupper(e($f['ext'])) ?></span>
              <?php endif; ?>
              <span class="status-pill <?= submissionBadge($f['status']) ?> flex-shrink-0"><?= e($f['status']) ?></span>
            </div>
            <p class="mb-0" style="font-size:.7rem;color:var(--muted);">
              <i class="bi bi-person me-1"></i><?= e($f['submitter_name']) ?> · <?= date('M d, Y h:i A', strtotime($f['submitted_at'])) ?>
            </p>
          </div>
          <div class="d-flex gap-2 flex-shrink-0">
            <a href="<?= base_url('task-submissions/' . (int) $f['id'] . '/preview') ?>" target="_blank" rel="noopener"
               class="btn btn-sm" style="border:1.5px solid <?= $tc ?>;color:var(--text);background:transparent;">
              <i class="bi bi-eye me-1"></i>Preview
            </a>
            <a href="<?= base_url('task-submissions/' . (int) $f['id'] . '/download') ?>" class="btn btn-sm" style="background:<?= $tc ?>;color:#fff;">
              <i class="bi bi-download me-1"></i>Download
            </a>
          </div>
        </div>
        <?php endforeach; ?>
        <p class="text-muted mb-0 text-center py-3 d-none doc-no-match"><i class="bi bi-funnel fs-5 d-block mb-1"></i>No files match the filters.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<?php if ($canManage): ?>
<!-- New Folder -->
<div class="modal fade" id="newFolderModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-folder-plus me-2"></i>New Folder</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('documents') ?>" class="ajax-form" data-confirm-title="Create this folder?">
        <input type="hidden" name="action" value="create_folder">
        <input type="hidden" name="parent_id" value="<?= (int) ($current['id'] ?? 0) ?>">
        <div class="modal-body">
          <label class="form-label">Folder name</label>
          <input type="text" name="name" class="form-control" maxlength="200" required placeholder="e.g. School Forms">
          <div class="form-text">Created in <strong><?= e($current['name'] ?? 'Documents (top level)') ?></strong>.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Folder</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Rename Folder -->
<div class="modal fade" id="renameFolderModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-pencil me-2"></i>Rename Folder</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('documents') ?>" class="ajax-form" data-confirm-action="update">
        <input type="hidden" name="action" value="rename_folder">
        <input type="hidden" name="id" id="renameFolderId">
        <div class="modal-body">
          <label class="form-label">Folder name</label>
          <input type="text" name="name" id="renameFolderName" class="form-control" maxlength="200" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Rename</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Move Folder -->
<div class="modal fade" id="moveFolderModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-folder-symlink me-2"></i>Move <span id="moveFolderTitle"></span></h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('documents') ?>" class="ajax-form" id="moveFolderForm" data-confirm-title="Move this folder?">
        <input type="hidden" name="action" value="move_folder">
        <input type="hidden" name="id" id="moveFolderId">
        <div class="modal-body">
          <label class="form-label">Move into</label>
          <div class="list-group doc-move-list" id="moveFolderList"></div>
          <input type="hidden" name="target_id" id="moveTargetId" required>
          <div class="form-text">Only plain folders are listed; task folders hold uploads, not other folders.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="moveFolderSubmit" disabled>Move Here</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$extraScript = <<<'HTML'
<script>
initLiveSearch('docSearchInput', 'filterForm');

(function () {
    const explorer = document.getElementById('docExplorer');
    const folders = explorer ? JSON.parse(explorer.dataset.folders || '[]') : [];
    const byId = {};
    folders.forEach(f => { byId[f.id] = f; });

    function descendantsOf(id) {
        const out = new Set();
        const queue = [id];
        while (queue.length) {
            const cur = queue.shift();
            folders.forEach(f => { if (f.parent === cur && !out.has(f.id)) { out.add(f.id); queue.push(f.id); } });
        }
        return out;
    }
    // Same rules the server enforces: plain folder or top level, not itself,
    // not inside itself, and not where it already is.
    function canMove(id, targetId) {
        const folder = byId[id];
        if (!folder) return false;
        if (targetId && (!byId[targetId] || byId[targetId].task)) return false;
        if (targetId === id || descendantsOf(id).has(targetId)) return false;
        return (folder.parent || 0) !== targetId;
    }
    function pathName(id) {
        const parts = [];
        for (let f = byId[id]; f; f = byId[f.parent]) parts.unshift(f.name);
        return parts.join(' / ');
    }
    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function submitMove(id, targetId) {
        const body = new FormData();
        body.append('action', 'move_folder');
        body.append('id', id);
        body.append('target_id', targetId);
        fetch(document.getElementById('filterForm').action, { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json().catch(() => ({ status: 'error', message: 'Unexpected server response.' })))
            .then(handleAjaxFormResult)
            .catch(() => Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server. Please try again.' }));
    }

    // Drag a folder card onto a plain folder card or a breadcrumb crumb.
    let dragId = null;
    document.querySelectorAll('[data-folder-id][draggable="true"]').forEach(card => {
        card.addEventListener('dragstart', e => {
            // Only the folder itself drags, not a file link or button inside it.
            if (e.target !== card) return;
            dragId = Number(card.dataset.folderId);
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', String(dragId));
            card.classList.add('dragging');
            document.body.classList.add('doc-dragging');
        });
        card.addEventListener('dragend', () => {
            dragId = null;
            card.classList.remove('dragging');
            document.body.classList.remove('doc-dragging');
            document.querySelectorAll('.drop-hover').forEach(el => el.classList.remove('drop-hover'));
        });
    });
    document.querySelectorAll('[data-drop-target]').forEach(target => {
        const targetId = Number(target.dataset.dropTarget);
        target.addEventListener('dragover', e => {
            if (dragId === null || !canMove(dragId, targetId)) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            target.classList.add('drop-hover');
        });
        target.addEventListener('dragleave', e => {
            if (!target.contains(e.relatedTarget)) target.classList.remove('drop-hover');
        });
        target.addEventListener('drop', e => {
            e.preventDefault();
            target.classList.remove('drop-hover');
            const id = dragId;
            if (id === null || !canMove(id, targetId)) return;
            Swal.fire({
                title: 'Move this folder?',
                html: 'Move <strong>' + escapeHtml(byId[id].name) + '</strong> into <strong>' + escapeHtml(target.dataset.dropName) + '</strong>?'
                    + '<div class="text-muted small mt-2">Everything inside it moves along with it.</div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, move it',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#800000',
                cancelButtonColor: '#6b7280',
                reverseButtons: true,
            }).then(result => { if (result.isConfirmed) submitMove(id, targetId); });
        });
    });

    // File type / status filters: hide non-matching files, open every
    // section that still has a match, and hide sections with none.
    const typeSel   = document.getElementById('docTypeFilter');
    const statusSel = document.getElementById('docStatusFilter');
    function applyFileFilters() {
        const type = typeSel ? typeSel.value : 'all';
        const status = statusSel ? statusSel.value : 'all';
        const filtering = type !== 'all' || status !== 'all';
        document.querySelectorAll('.doc-task-section').forEach(section => {
            const rows = [...section.querySelectorAll('.doc-file-row')];
            let shown = 0;
            rows.forEach(row => {
                const ok = (type === 'all' || row.dataset.ext === type) && (status === 'all' || row.dataset.status === status);
                row.classList.toggle('d-none', !ok);
                if (ok) shown++;
            });
            section.querySelector('.doc-no-match')?.classList.toggle('d-none', !(rows.length && shown === 0));
            section.querySelector('.doc-file-count').textContent = filtering ? shown + ' / ' + rows.length : rows.length;
            section.classList.toggle('d-none', filtering && shown === 0);
            if (filtering && shown) bootstrap.Collapse.getOrCreateInstance(section.querySelector('.collapse'), { toggle: false }).show();
        });
    }
    typeSel?.addEventListener('change', applyFileFilters);
    statusSel?.addEventListener('change', applyFileFilters);

    // Keep each section's chevron in step when it's opened from code
    // (filters / Expand all), not just by clicking its header.
    document.querySelectorAll('.doc-task-section .collapse').forEach(c => {
        const btn = c.closest('.doc-task-section').querySelector('.section-toggle-btn');
        c.addEventListener('show.bs.collapse', () => { btn.classList.remove('collapsed'); btn.setAttribute('aria-expanded', 'true'); });
        c.addEventListener('hide.bs.collapse', () => { btn.classList.add('collapsed'); btn.setAttribute('aria-expanded', 'false'); });
    });

    const toggleAll = document.getElementById('docToggleAll');
    toggleAll?.addEventListener('click', () => {
        const expand = toggleAll.dataset.state !== 'open';
        document.querySelectorAll('.doc-task-section:not(.d-none) .collapse').forEach(c => {
            const inst = bootstrap.Collapse.getOrCreateInstance(c, { toggle: false });
            expand ? inst.show() : inst.hide();
        });
        toggleAll.dataset.state = expand ? 'open' : '';
        toggleAll.innerHTML = expand ? '<i class="bi bi-arrows-collapse me-1"></i>Collapse all' : '<i class="bi bi-arrows-expand me-1"></i>Expand all';
    });

    window.openRenameFolder = function (id) {
        document.getElementById('renameFolderId').value = id;
        document.getElementById('renameFolderName').value = byId[id] ? byId[id].name : '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('renameFolderModal')).show();
    };

    // "Move to…": same move, picked from a list (works without drag & drop, e.g. on phones).
    window.openMoveFolder = function (id) {
        document.getElementById('moveFolderId').value = id;
        document.getElementById('moveFolderTitle').textContent = '"' + byId[id].name + '"';
        const targets = [{ id: 0, label: 'Documents (top level)' }].concat(
            folders.filter(f => !f.task).map(f => ({ id: f.id, label: pathName(f.id) }))
                .sort((a, b) => a.label.localeCompare(b.label))
        );
        const list = document.getElementById('moveFolderList');
        list.innerHTML = targets.map(t => {
            const ok = canMove(id, t.id);
            return '<button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-2"'
                + (ok ? '' : ' disabled') + ' data-target="' + t.id + '">'
                + '<i class="bi ' + (t.id ? 'bi-folder-fill' : 'bi-house-door') + ' text-muted"></i>'
                + '<span class="text-truncate">' + escapeHtml(t.label) + '</span>'
                + (ok ? '' : '<span class="ms-auto small text-muted">' + ((byId[id].parent || 0) === t.id ? 'current' : 'not allowed') + '</span>')
                + '</button>';
        }).join('');
        document.getElementById('moveTargetId').value = '';
        document.getElementById('moveFolderSubmit').disabled = true;
        list.querySelectorAll('[data-target]:not([disabled])').forEach(btn => btn.addEventListener('click', () => {
            list.querySelectorAll('.active').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('moveTargetId').value = btn.dataset.target;
            document.getElementById('moveFolderSubmit').disabled = false;
        }));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('moveFolderModal')).show();
    };
})();
</script>
HTML;
include APPPATH . 'Views/layout/footer.php';
?>

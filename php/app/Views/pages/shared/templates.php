<?php
if (! function_exists('tplFormatBytes')) {
    function tplFormatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }
}

$grouped = [];
foreach ($templates as $t) {
    $grouped[$t['category_id']][] = $t;
}
$previewableExt  = ['pdf', 'jpg', 'jpeg', 'png', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'odt', 'ods', 'odp'];
$hasActiveFilter = $search !== '' || $fileType !== 'all';
// No section picked and no search: show the sections as cards. Otherwise list the templates.
$gridMode    = $catId === 0 && ! $hasActiveFilter;
$openSection = null;
foreach ($categories as $c) {
    if ((int) $c['id'] === $catId) {
        $openSection = $c;
    }
}
// A paper folder (back, a sheet, front flap) — the flap opens when a file is dragged over it.
$folderIcon = static function (string $color, string $extra = ''): string {
    return '<div class="tpl-folder ' . $extra . '" style="--folder:' . $color . '" aria-hidden="true">'
        . '<span class="tpl-folder-back"></span><span class="tpl-folder-paper"></span><span class="tpl-folder-paper tpl-folder-paper-2"></span><span class="tpl-folder-front"></span></div>';
};
// Card colours, in section order (A–Z).
$sectionPalette = ['#7f1d1d', '#2f4a7a', '#2f7d4f', '#8e5ba8', '#ea7a1e', '#1f6f7a', '#d4a72c', '#be185d', '#475569', '#0e7490'];
$sectionColor   = [];
foreach (array_values($categories) as $i => $c) {
    $sectionColor[(int) $c['id']] = $sectionPalette[$i % count($sectionPalette)];
}
$canManage       = hasRole('adas');
$formatLabels    = ['pdf' => 'PDF', 'docx' => 'Word'];
// Optional folders inside sections: "Section / Folder / Subfolder" labels and folder counts per section.
$sectionNames  = array_column($categories, 'name', 'id');
$placeLabel    = static fn (int $sectionId, ?int $fid): string => ($sectionNames[$sectionId] ?? 'Section') . ($fid && isset($folders[$fid]) ? ' / ' . $folders[$fid]['path'] : '');
$sectionFolderCount = array_count_values(array_map(static fn ($f) => (int) $f['category_id'], $folders));
$currentFolder = $folderId ? ($folders[$folderId] ?? null) : null;
$countLabel    = static fn (int $n, string $one, string $many): string => $n . ' ' . ($n === 1 ? $one : $many);

include APPPATH . 'Views/layout/header.php';
?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3" style="position:relative;z-index:1">
    <div>
      <h4><i class="bi bi-folder2-open me-2"></i>Templates</h4>
      <p>Certificates, forms, checklists &amp; reference documents organized by section</p>
    </div>
    <?php if ($canManage): ?>
    <div class="d-flex gap-2">
      <button class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);"
              data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="bi bi-folder-plus me-1"></i>Add Section
      </button>
      <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#uploadModal">
        <i class="bi bi-cloud-upload me-1"></i>Upload Template
      </button>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-check-circle-fill"></i><?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Search + Filters -->
<div class="card mb-4">
  <div class="card-body py-3">
    <form method="GET" action="<?= base_url('templates') ?>" id="filterForm" class="d-flex gap-2 flex-wrap align-items-center">
      <div class="input-group input-group-sm" style="flex:1;min-width:220px;max-width:340px;">
        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search templates by title, file name or tag..."
               class="form-control border-start-0 ps-0" id="templateSearchInput" autocomplete="off">
      </div>

      <div class="maroon-select maroon-select-sm" style="width:auto;">
        <select name="category" class="maroon-select-native" onchange="this.form.requestSubmit()">
          <option value="0" <?= $catId === 0 ? 'selected' : '' ?>>All Sections</option>
          <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $catId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
        <div class="maroon-select-panel"></div>
      </div>

      <div class="maroon-select maroon-select-sm" style="width:auto;">
        <select name="type" class="maroon-select-native" onchange="this.form.requestSubmit()">
          <option value="all" <?= $fileType === 'all' ? 'selected' : '' ?>>All File Types</option>
          <?php foreach ($fileTypes as $ft): ?>
          <option value="<?= e($ft) ?>" <?= $fileType === $ft ? 'selected' : '' ?>><?= strtoupper(e($ft)) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
        <div class="maroon-select-panel"></div>
      </div>

      <div class="maroon-select maroon-select-sm" style="width:auto;">
        <select name="sort" class="maroon-select-native" onchange="this.form.requestSubmit()">
          <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Newest First</option>
          <option value="date_asc"  <?= $sort === 'date_asc'  ? 'selected' : '' ?>>Oldest First</option>
          <option value="section"   <?= $sort === 'section'   ? 'selected' : '' ?>>Sort by Section</option>
          <option value="type"      <?= $sort === 'type'      ? 'selected' : '' ?>>Sort by File Type</option>
        </select>
        <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
        <div class="maroon-select-panel"></div>
      </div>

      <?php if ($search !== '' || $catId !== 0 || $fileType !== 'all' || $sort !== 'date_desc'): ?>
      <a href="<?= base_url('templates') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php if ($gridMode): ?>
<!-- The folder animation stays inside data-folder-stage: below the header and filters here,
     and below the folder bar inside a folder. -->
<div data-folder-stage>
<!-- Sections as cards -->
<?php if (empty($categories)): ?>
<div class="card"><div class="card-body text-center py-5">
  <i class="bi bi-folder2 fs-1 d-block mb-3 text-muted"></i>
  <p class="text-muted mb-0"><?= $canManage ? 'No sections yet. Use "Add Section" to create one.' : 'No template sections yet.' ?></p>
</div></div>
<?php endif; ?>
<div class="row g-3 mb-4">
  <?php foreach ($categories as $cat): $count = (int) $cat['count']; ?>
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="tpl-section-card h-100" data-section-id="<?= (int) $cat['id'] ?>"<?php if ($canManage): ?> data-drop-section="<?= (int) $cat['id'] ?>" data-section-name="<?= e($cat['name']) ?>"<?php endif; ?>>
      <a href="<?= base_url('templates?category=' . (int) $cat['id']) ?>" class="tpl-section-link stretched-link"
         aria-label="Open <?= e($cat['name']) ?> (<?= $count ?> <?= $count === 1 ? 'template' : 'templates' ?>)">
        <?= $folderIcon($sectionColor[(int) $cat['id']]) ?>
        <div class="tpl-section-main">
          <div class="tpl-section-title">
            <span class="tpl-section-name<?= mb_strlen($cat['name']) > 20 ? ' is-long' : '' ?>"><?= e($cat['name']) ?></span>
            <span class="tpl-section-count"><?= $count ?></span>
          </div>
          <p class="tpl-section-desc"><?= e($cat['description'] ?: 'Forms and documents for ' . $cat['name'] . '.') ?></p>
        </div>
        <i class="bi bi-chevron-right tpl-section-chevron"></i>
      </a>
      <div class="tpl-section-foot">
        <?php if ($canManage): ?>
        <div class="tpl-section-actions">
          <button type="button" class="btn btn-ghost btn-sm" title="Upload to <?= e($cat['name']) ?>" onclick="openUploadModal(<?= (int) $cat['id'] ?>)">
            <i class="bi bi-cloud-upload"></i>
          </button>
          <button type="button" class="btn btn-ghost btn-sm" title="Edit section"
                  onclick='openEditSectionModal(<?= json_encode(['id' => (int) $cat['id'], 'name' => $cat['name'], 'description' => $cat['description'] ?? ''], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
            <i class="bi bi-pencil"></i>
          </button>
          <?php if ($count === 0): ?>
          <form method="POST" action="<?= base_url('templates') ?>" class="ajax-form d-inline"
                data-confirm-title="Delete this section?" data-confirm-text="This section has no templates and will be removed.">
            <input type="hidden" name="action" value="delete_category">
            <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
            <button class="btn btn-ghost btn-sm text-danger" title="Delete empty section"><i class="bi bi-trash"></i></button>
          </form>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        <span class="tpl-section-total"><i class="bi bi-file-earmark-text me-1"></i><?= $count ?> <?= $count === 1 ? 'template' : 'templates' ?><?php if (! empty($sectionFolderCount[(int) $cat['id']])): ?> · <i class="bi bi-folder2 me-1"></i><?= $countLabel($sectionFolderCount[(int) $cat['id']], 'folder', 'folders') ?><?php endif; ?></span>
      </div>
      <?php if ($canManage): ?><div class="tpl-drop-hint" aria-hidden="true"><i class="bi bi-box-arrow-in-down me-1"></i><span>Drop to add to <?= e($cat['name']) ?></span></div><?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
</div><!-- /data-folder-stage -->
<?php else: ?>

<?php if ($openSection): ?>
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
  <a href="<?= base_url('templates') ?>" class="btn btn-outline-secondary btn-sm tpl-back-link" data-section-id="<?= (int) $openSection['id'] ?>"
     data-section-name="<?= e($openSection['name']) ?>" data-folder-color="<?= $sectionColor[(int) $openSection['id']] ?? '#7f1d1d' ?>"><i class="bi bi-arrow-left me-1"></i>All sections</a>
  <?= $folderIcon($sectionColor[(int) $openSection['id']] ?? '#7f1d1d', 'tpl-folder-sm is-open') ?>
  <div style="min-width:0;">
    <div class="fw-bold"><?= e($openSection['name']) ?></div>
    <?php if (! empty($openSection['description'])): ?><div class="text-muted" style="font-size:.78rem;"><?= e($openSection['description']) ?></div><?php endif; ?>
  </div>
</div>
<?php if ($folderTrail): ?>
<!-- Where we are inside the section's folders; each step goes back up -->
<nav class="tpl-crumbs mb-3" aria-label="Folder path">
  <a href="<?= base_url('templates?category=' . (int) $openSection['id']) ?>"><i class="bi bi-folder2 me-1"></i><?= e($openSection['name']) ?></a>
  <?php foreach ($folderTrail as $i => $step): $isLast = $i === count($folderTrail) - 1; ?>
  <i class="bi bi-chevron-right tpl-crumb-sep" aria-hidden="true"></i>
  <?php if ($isLast): ?>
  <span aria-current="page"><i class="bi bi-folder2-open me-1"></i><?= e($step['name']) ?></span>
  <?php else: ?>
  <a href="<?= base_url('templates?category=' . (int) $openSection['id'] . '&folder=' . (int) $step['id']) ?>"><?= e($step['name']) ?></a>
  <?php endif; ?>
  <?php endforeach; ?>
</nav>
<?php endif; ?>
<?php elseif ($hasActiveFilter): ?>
<div class="mb-3"><a href="<?= base_url('templates') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>All sections</a></div>
<?php endif; ?>

<?php if (count($categories) > 0): ?>
<!-- Folder bar: switch folders (the open one is marked); the ADAS can also drag a template
     here to move it, or files from the computer to upload them -->
<div class="tpl-dock mb-3" id="tplDock">
  <div class="tpl-dock-label">
    <?php if ($canManage): ?>
    <i class="bi bi-arrows-move me-1"></i>Drag a template onto a folder to move it — or drop files from your computer to upload
    <?php else: ?>
    <i class="bi bi-folder2-open me-1"></i>Folders
    <?php endif; ?>
  </div>
  <div class="tpl-dock-folders" role="navigation" aria-label="Template folders">
    <?php foreach ($categories as $cat): $isOpen = $openSection && (int) $cat['id'] === (int) $openSection['id']; ?>
    <a href="<?= base_url('templates?category=' . (int) $cat['id']) ?>" class="tpl-dock-folder<?= $isOpen ? ' is-current' : '' ?>"
       data-section-id="<?= (int) $cat['id'] ?>" data-section-name="<?= e($cat['name']) ?>"<?= $isOpen && $folderId ? ' data-in-folder="1"' : '' ?>
       <?php if ($canManage): ?>data-drop-section="<?= (int) $cat['id'] ?>" data-drop-folder="0"<?php endif; ?>
       <?= $isOpen ? 'aria-current="page"' : '' ?> title="<?= e($cat['name']) . ($isOpen ? ' (open)' : '') ?>">
      <?= $folderIcon($sectionColor[(int) $cat['id']], 'tpl-folder-xs' . ($isOpen ? ' is-open' : '')) ?>
      <span class="tpl-dock-name"><?= e($cat['name']) ?></span>
      <?php if ($isOpen): ?><span class="tpl-dock-current"><i class="bi bi-eye-fill me-1"></i>Open</span><?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div data-folder-stage>
<!-- Sections -->
<?php $rendered = 0; foreach ($categories as $cat):
  if ($catId > 0 && $catId !== (int) $cat['id']) {
      continue;
  }
  $items = $grouped[$cat['id']] ?? [];
  if (empty($items) && $hasActiveFilter) {
      continue;
  }
  $rendered++;
  // Browsing a section: this card shows one folder level (the section's top level, or the open folder).
  $inFolder   = $browsing && $currentFolder;
  $hereLabel  = $inFolder ? $placeLabel((int) $cat['id'], $folderId) : $cat['name'];
  $shownFolders = $browsing ? $subfolders : [];
?>
<div class="card mb-4">
  <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
    <button type="button" class="btn section-toggle-btn flex-grow-1 d-flex align-items-center text-start p-0 border-0 bg-transparent"
            data-bs-toggle="collapse" data-bs-target="#section-body-<?= $cat['id'] ?>"
            aria-expanded="true" aria-controls="section-body-<?= $cat['id'] ?>">
      <i class="bi bi-chevron-down section-toggle-arrow me-2 text-muted"></i>
      <span class="fw-semibold"><i class="bi <?= $inFolder ? 'bi-folder2-open' : 'bi-folder2' ?> me-2 text-muted"></i><?= e($inFolder ? $currentFolder['name'] : $cat['name']) ?></span>
      <span class="badge badge-secondary ms-2"><?= count($items) ?></span>
    </button>
    <?php if ($canManage): ?>
    <div class="d-flex align-items-center gap-1">
      <?php if ($browsing): ?>
      <button type="button" class="btn btn-ghost btn-sm" title="<?= $inFolder ? 'New folder inside ' . e($currentFolder['name']) : 'New folder in this section' ?>"
              onclick='openAddFolder(<?= json_encode(['category' => (int) $cat['id'], 'parent' => $folderId, 'where' => $hereLabel], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
        <i class="bi bi-folder-plus"></i>
      </button>
      <?php endif; ?>
      <button type="button" class="btn btn-ghost btn-sm" title="Upload to <?= $inFolder ? 'this folder' : 'this section' ?>"
              onclick='openUploadModal(<?= (int) $cat['id'] ?>, <?= $inFolder ? json_encode(['id' => $folderId, 'label' => $hereLabel], JSON_HEX_APOS | JSON_HEX_QUOT) : 'null' ?>)'>
        <i class="bi bi-cloud-upload"></i>
      </button>
      <?php if ($inFolder): ?>
      <button type="button" class="btn btn-ghost btn-sm" title="Rename folder"
              onclick='openRenameFolder(<?= json_encode(['id' => $folderId, 'name' => $currentFolder['name']], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
        <i class="bi bi-pencil"></i>
      </button>
      <?php if (count($items) === 0 && $shownFolders === []): ?>
      <form method="POST" action="<?= base_url('templates') ?>" class="ajax-form"
            data-confirm-title="Delete this folder?" data-confirm-text="This folder is empty and will be removed.">
        <input type="hidden" name="action" value="delete_folder">
        <input type="hidden" name="id" value="<?= $folderId ?>">
        <button class="btn btn-ghost btn-sm text-danger" title="Delete empty folder"><i class="bi bi-trash"></i></button>
      </form>
      <?php endif; ?>
      <?php else: ?>
      <button type="button" class="btn btn-ghost btn-sm" title="Rename section"
              onclick='openEditSectionModal(<?= json_encode(['id' => (int) $cat['id'], 'name' => $cat['name'], 'description' => $cat['description'] ?? ''], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
        <i class="bi bi-pencil"></i>
      </button>
      <?php endif; ?>
      <?php if (! $inFolder && (int) $cat['count'] === 0): ?>
      <form method="POST" action="<?= base_url('templates') ?>" class="ajax-form"
            data-confirm-title="Delete this section?" data-confirm-text="This section has no templates and will be removed<?= empty($sectionFolderCount[(int) $cat['id']]) ? '' : ', along with its empty folders' ?>.">
        <input type="hidden" name="action" value="delete_category">
        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
        <button class="btn btn-ghost btn-sm text-danger" title="Delete empty section"><i class="bi bi-trash"></i></button>
      </form>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
  <div class="collapse show" id="section-body-<?= $cat['id'] ?>">
  <div class="card-body">
    <?php if ($shownFolders !== []): ?>
    <!-- Folders at this level: open one to go inside; the ADAS can drop templates or files on them -->
    <div class="tpl-subfolders<?= empty($items) ? '' : ' mb-3' ?>">
      <?php foreach ($shownFolders as $sf): ?>
      <div class="tpl-subfolder-wrap">
      <a href="<?= base_url('templates?category=' . (int) $cat['id'] . '&folder=' . (int) $sf['id']) ?>" class="tpl-subfolder"
         title="Open <?= e($sf['name']) ?>" data-section-name="<?= e($placeLabel((int) $cat['id'], (int) $sf['id'])) ?>"
         <?php if ($canManage): ?>data-drop-section="<?= (int) $cat['id'] ?>" data-drop-folder="<?= (int) $sf['id'] ?>"<?php endif; ?>>
        <?= $folderIcon($sectionColor[(int) $cat['id']] ?? '#7f1d1d', 'tpl-folder-xs') ?>
        <span class="tpl-subfolder-main">
          <span class="tpl-subfolder-name"><?= e($sf['name']) ?></span>
          <span class="tpl-subfolder-meta"><?= $countLabel($sf['templates'], 'template', 'templates') ?><?= $sf['folders'] ? ' · ' . $countLabel($sf['folders'], 'folder', 'folders') : '' ?></span>
        </span>
        <i class="bi bi-chevron-right text-muted"></i>
      </a>
      <?php if ($canManage): ?>
      <div class="tpl-subfolder-tools">
        <button type="button" class="btn btn-ghost btn-sm" title="Rename folder"
                onclick='openRenameFolder(<?= json_encode(['id' => (int) $sf['id'], 'name' => $sf['name']], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="bi bi-pencil"></i></button>
        <?php if (! $sf['templates'] && ! $sf['folders']): ?>
        <button type="button" class="btn btn-ghost btn-sm text-danger" title="Delete empty folder"
                onclick='confirmDeleteFolder(<?= json_encode(['id' => (int) $sf['id'], 'name' => $sf['name']], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="bi bi-trash"></i></button>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if (empty($items) && $shownFolders === []): ?>
    <p class="text-muted mb-0 text-center py-3"><i class="bi bi-inbox fs-4 d-block mb-2"></i><?= $inFolder ? 'This folder is empty.' : 'No templates uploaded in this section yet.' ?></p>
    <?php elseif (! empty($items)): ?>
    <div class="list-group list-group-flush">
      <?php foreach ($items as $t):
        [$icon, $tc, $bg] = fileTypeStyle($t['file_ext']);
        $conversions      = \App\Controllers\Shared\Templates::conversionTargets($t['file_ext']);
        // Only the Download button that actually has a dropdown attached
        // switches to the brand red — a plain Download (no conversions)
        // isn't a dropdown, so it keeps its file-type color.
        $downloadColor    = ! empty($conversions) ? 'var(--primary)' : $tc;
        $tFolderId        = $t['folder_id'] ? (int) $t['folder_id'] : null;
        $tPlace           = $placeLabel((int) $t['category_id'], $tFolderId);
      ?>
      <div class="list-group-item d-flex align-items-center gap-3 flex-wrap py-3<?= $canManage ? ' tpl-draggable' : '' ?>"<?php if ($canManage): ?> draggable="true" data-template-id="<?= (int) $t['id'] ?>" data-template-title="<?= e($t['title']) ?>" data-from-id="<?= (int) $t['category_id'] ?>" data-from-folder="<?= (int) $tFolderId ?>" data-from-name="<?= e($tPlace) ?>" title="Drag onto a folder to move it"<?php endif; ?>>
        <div style="width:40px;height:40px;border-radius:10px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i class="bi <?= $icon ?>" style="color:<?= $tc ?>;font-size:1.1rem;"></i>
        </div>
        <div class="flex-grow-1" style="min-width:220px;">
          <div class="d-flex align-items-center gap-2 mb-1">
            <h6 class="fw-bold mb-0 text-truncate" style="font-size:.88rem" title="<?= e($t['title']) ?>"><?= e($t['title']) ?></h6>
            <span class="badge flex-shrink-0" style="background:<?= $bg ?>;color:<?= $tc ?>;border:1px solid <?= $tc ?>22;font-size:.68rem;">
              <?= strtoupper(e($t['file_ext'])) ?>
            </span>
          </div>
          <p class="text-muted mb-1 text-truncate" style="font-size:.75rem;" title="<?= e($t['file_name']) ?>">
            <?= e($t['file_name']) ?> · <?= tplFormatBytes((int) $t['file_size']) ?>
          </p>
          <?php if (! $browsing && $tFolderId && isset($folders[$tFolderId])): ?>
          <a href="<?= base_url('templates?category=' . (int) $t['category_id'] . '&folder=' . $tFolderId) ?>" class="tpl-folder-path" title="Open this folder">
            <i class="bi bi-folder2 me-1"></i><?= e($folders[$tFolderId]['path']) ?>
          </a>
          <?php endif; ?>
          <?php if (! empty($t['description'])): ?>
          <p class="text-muted mb-1 text-truncate" style="font-size:.72rem;" title="<?= e($t['description']) ?>">
            <?= e($t['description']) ?>
          </p>
          <?php endif; ?>
          <?php if (! empty($t['tags'])): ?>
          <div class="d-flex flex-wrap gap-1 mb-1">
            <?php foreach (array_filter(array_map('trim', explode(',', $t['tags']))) as $tag): ?>
            <a href="<?= base_url('templates?q=' . rawurlencode($tag)) ?>" class="tpl-tag" title="Show templates tagged <?= e($tag) ?>"><?= e($tag) ?></a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <p class="mb-0" style="font-size:.7rem;color:var(--muted);">
            <i class="bi bi-person me-1"></i><?= e($t['uploaded_by']) ?> · <?= date('M d, Y', strtotime($t['date_added'])) ?>
          </p>
        </div>
        <div class="d-flex gap-2 flex-shrink-0 flex-wrap">
          <button type="button" class="btn btn-sm" style="border:1.5px solid <?= $tc ?>;color:var(--text);background:transparent;"
                  onclick='previewTemplate(<?= json_encode([
                      'id'          => (int) $t['id'],
                      'title'       => $t['title'],
                      'description' => $t['description'],
                      'ext'         => $t['file_ext'],
                      'category'    => $tPlace,
                      'uploader'    => $t['uploaded_by'],
                      'date'        => date('M d, Y', strtotime($t['date_added'])),
                      'fileName'    => $t['file_name'],
                      'fileBase'    => pathinfo($t['file_name'], PATHINFO_FILENAME),
                      'fileSize'    => tplFormatBytes((int) $t['file_size']),
                      'previewable' => in_array($t['file_ext'], $previewableExt, true),
                      'conversions' => $conversions,
                  ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
            <i class="bi bi-eye me-1"></i>Preview
          </button>
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm" style="background:<?= $downloadColor ?>;color:#fff;"
                    onclick="downloadTemplate('<?= base_url('templates/download/' . $t['id']) ?>', '<?= e(addslashes($t['file_name'])) ?>')">
              <i class="bi bi-download me-1"></i>Download
            </button>
            <?php if (! empty($conversions)): ?>
            <button type="button" class="btn btn-sm dropdown-toggle dropdown-toggle-split" style="background:<?= $downloadColor ?>;color:#fff;"
                    data-bs-toggle="dropdown" aria-expanded="false">
              <span class="visually-hidden">Toggle download options</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php foreach ($conversions as $c): $dlName = pathinfo($t['file_name'], PATHINFO_FILENAME) . '.' . $c; ?>
              <li>
                <a class="dropdown-item" href="#"
                   onclick="downloadTemplate('<?= base_url('templates/download/' . $t['id'] . '?as=' . $c) ?>', '<?= e(addslashes($dlName)) ?>'); return false;">
                  <i class="bi bi-file-earmark-<?= $c === 'pdf' ? 'pdf' : 'word' ?> me-2"></i>Convert to <?= e($formatLabels[$c] ?? strtoupper($c)) ?>
                </a>
              </li>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
          </div>
          <?php if ($canManage): ?>
          <button type="button" class="btn btn-sm btn-outline-secondary" title="Rename"
                  onclick='openRenameTemplate(<?= json_encode(['id' => (int) $t['id'], 'title' => $t['title'], 'fileBase' => pathinfo($t['file_name'], PATHINFO_FILENAME), 'ext' => $t['file_ext']], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="bi bi-pencil"></i></button>
          <button type="button" class="btn btn-sm btn-outline-secondary" title="Move to another section or folder"
                  onclick="openMoveTemplate(this.closest('[data-template-id]'))"><i class="bi bi-folder-symlink"></i></button>
          <form method="POST" action="<?= base_url('templates') ?>" class="ajax-form"
                data-confirm-title="Delete this template?">
            <input type="hidden" name="action" value="delete_template">
            <input type="hidden" name="id" value="<?= $t['id'] ?>">
            <button class="btn btn-sm btn-outline-secondary text-danger" title="Delete"><i class="bi bi-trash"></i></button>
          </form>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  </div>
</div>
<?php endforeach; ?>

<?php if ($rendered === 0): ?>
<div class="card"><div class="card-body text-center py-5">
  <i class="bi bi-inbox fs-1 d-block mb-3 text-muted"></i>
  <p class="text-muted mb-0">No templates found matching your search/filters.</p>
</div></div>
<?php endif; ?>
</div><!-- /data-folder-stage -->
<?php endif; /* gridMode */ ?>

<?php if ($canManage): ?>
<div id="tplSectionsData" class="d-none" data-url="<?= base_url('templates') ?>" data-sections="<?= e(json_encode(array_map(static fn ($c) => ['id' => (int) $c['id'], 'name' => $c['name']], array_values($categories)))) ?>"
     data-folders="<?= e(json_encode(array_map(static fn ($f) => ['id' => (int) $f['id'], 'section' => (int) $f['category_id'], 'path' => $f['path']], array_values($folders)))) ?>"></div>
<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-folder-plus me-2"></i>Add Section</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('templates') ?>" class="ajax-form">
        <input type="hidden" name="action" value="add_category">
        <div class="modal-body">
          <label class="form-label">Section Name</label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Certificate, BAC Forms, COA Forms" required>
          <p class="text-muted mt-2 mb-3" style="font-size:.78rem;">Only add a section if it doesn't already exist below.</p>
          <label class="form-label">Description <span class="text-muted">(optional)</span></label>
          <input type="text" name="description" class="form-control" maxlength="255" placeholder="e.g. Bids and Awards Committee forms and documents.">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Section</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Section Modal -->
<div class="modal fade" id="editSectionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Section</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('templates') ?>" class="ajax-form" data-confirm-action="update">
        <input type="hidden" name="action" value="edit_category">
        <input type="hidden" name="id" id="editSectionId">
        <div class="modal-body">
          <label class="form-label">Section Name</label>
          <input type="text" name="name" id="editSectionName" class="form-control mb-3" required>
          <label class="form-label">Description <span class="text-muted">(shown on the section card)</span></label>
          <input type="text" name="description" id="editSectionDesc" class="form-control" maxlength="255">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Upload Template Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-cloud-upload me-2"></i>Upload Template</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('templates') ?>" class="ajax-form" enctype="multipart/form-data"
            data-confirm-title="Upload this template?">
        <input type="hidden" name="action" value="upload">
        <input type="hidden" name="folder_id" id="uploadFolderId" value="">
        <div class="modal-body">
          <div id="uploadFolderNote" class="tpl-suggest mb-3 d-none"></div>
          <div class="mb-3">
            <label class="form-label">Section</label>
            <div class="maroon-select" style="width:100%;">
              <select name="category_id" id="uploadCategorySelect" class="maroon-select-native" required onchange="renderTemplateSuggestions()">
                <option value="auto" selected>Auto-classify from the file</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
              <div class="maroon-select-panel"></div>
            </div>
            <div class="text-muted mt-1" style="font-size:.75rem;" id="uploadAutoHint">
              <i class="bi bi-magic me-1"></i>The section is picked by reading the document's contents (the file name is used when there is little text). Each file can land in a different section.
              <?php if (! $ocrAvailable): ?><br><i class="bi bi-info-circle me-1"></i>Scanned PDFs and photos are matched by file name only until OCR (Tesseract) is installed on the server.<?php endif; ?>
            </div>
            <div id="uploadSuggestion" class="tpl-suggest d-none"></div>
          </div>
          <div class="mb-3">
            <label class="form-label">File(s)</label>
            <input type="file" name="file[]" id="uploadFileInput" class="form-control"
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.txt,.zip,.rar,.jpg,.jpeg,.png"
                   onchange="handleTemplateFilesChange(this)" data-dropzone-list="off" multiple required>
            <p class="text-muted mt-1 mb-0" style="font-size:.75rem;">PDF, Word, Excel, CSV, PowerPoint, images or archives · Max 10MB each. Select multiple files to upload them all at once.</p>
            <div id="uploadFilesPreview" class="mt-2 d-none"></div>
          </div>
          <div class="mb-3" id="uploadTitleGroup">
            <label class="form-label">Title</label>
            <input type="text" name="title" id="uploadTitleInput" class="form-control" placeholder="Select a file to auto-fill, or type your own" required
                   oninput="this.dataset.edited = '1'; renderTitleSuggestions()">
            <div id="uploadTitleSuggest" class="tpl-title-panel d-none" aria-live="polite"></div>
          </div>
          <div class="mb-3 d-none" id="uploadTagsGroup">
            <label class="form-label">Tags <span class="text-muted">(added automatically — edit if needed)</span></label>
            <input type="text" name="tags" id="uploadTagsInput" class="form-control" maxlength="400" disabled
                   placeholder="e.g. Certificate, Award" oninput="this.dataset.edited = '1'">
            <div class="form-text">Separate tags with commas. Tags are searchable.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Description <span class="text-muted">(optional)</span></label>
            <textarea name="description" class="form-control" rows="2" placeholder="What is this template for? (applied to all files if uploading more than one)"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--maroon);color:#fff;">
        <h6 class="modal-title fw-bold" id="previewModalTitle"><i class="bi bi-eye me-2"></i>Template</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="previewModalBody"></div>
      <div class="modal-footer">
        <?php if ($canManage): ?>
        <div class="d-flex gap-2 me-auto">
          <button type="button" class="btn btn-outline-secondary" id="previewModalRenameBtn"><i class="bi bi-pencil me-1"></i>Rename</button>
          <button type="button" class="btn btn-outline-danger" id="previewModalDeleteBtn"><i class="bi bi-trash me-1"></i>Delete</button>
        </div>
        <?php endif; ?>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <div class="btn-group">
          <button type="button" class="btn btn-primary" id="previewModalDownloadBtn">
            <i class="bi bi-download me-1"></i>Download
          </button>
          <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split d-none" id="previewModalDownloadCaret"
                  data-bs-toggle="dropdown" aria-expanded="false">
            <span class="visually-hidden">Toggle download options</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" id="previewModalDownloadMenu"></ul>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($canManage): ?>
<?php endif; ?>

<?php
$extraScript = "<script>
// var, not const: this script gets re-injected verbatim on every AJAX
// navigation to/within this page (see runPageScript() in footer.php), and a
// top-level const would collide with the one the first real load already
// declared in the shared global scope.
var TEMPLATES_PREVIEW_BASE = '" . base_url('templates/preview/') . "';

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

var FORMAT_LABELS = { pdf: 'PDF', docx: 'Word' };

// Renders the friendly \"nothing to show\" card in place of a broken or blank
// iframe/image whenever the server can't produce a preview at all (see
// previewUnavailable() in Templates::preview()) or the browser fails to load
// what it was given (e.g. a corrupted image).
function showPreviewUnavailable(box, downloadBase, fileName) {
    box.innerHTML = '<div class=\"text-center py-5\">'
        + '<i class=\"bi bi-file-earmark-fill fs-1 d-block mb-3 text-muted\"></i>'
        + '<div class=\"fw-semibold mb-1\">Preview Unavailable</div>'
        + '<p class=\"text-muted mx-auto mb-3\" style=\"font-size:.85rem;max-width:320px;\">This file cannot be previewed directly in the browser.</p>'
        + '<button type=\"button\" class=\"btn btn-primary btn-sm\" id=\"previewUnavailableDownloadBtn\"><i class=\"bi bi-download me-1\"></i>Download</button>'
        + '</div>';
    document.getElementById('previewUnavailableDownloadBtn').onclick = () => downloadTemplate(downloadBase, fileName);
}

// The template is listed but its file isn't on the server any more.
function showFileMissing(box) {
    box.innerHTML = '<div class=\"text-center py-5\">'
        + '<i class=\"bi bi-file-earmark-x fs-1 d-block mb-3 text-danger\"></i>'
        + '<div class=\"fw-semibold mb-1\">File not found on the server</div>'
        + '<p class=\"text-muted mx-auto mb-0\" style=\"font-size:.85rem;max-width:340px;\">The template is listed, but its file is missing from the uploads folder on the server. Upload the file again (or restore the uploads folder from a backup).</p>'
        + '</div>';
}

// Fetches the preview endpoint ourselves (rather than pointing an <iframe>
// straight at it) so a failed/unavailable preview can be caught before
// anything renders — the requirement is that a dead file type or a failed
// conversion always shows the \"Preview Unavailable\" card, never an empty or
// broken iframe. The endpoint's Content-Type decides how the successful
// response is displayed: application/pdf -> the browser's native PDF viewer,
// text/html -> the server-rendered spreadsheet table, text/plain -> raw text.
function loadTemplatePreview(t, previewUrl, downloadBase) {
    const box = document.getElementById('templatePreviewBox');
    if (!box) return;

    if (['jpg','jpeg','png'].includes(t.ext)) {
        const img = new Image();
        img.onload = () => {
            img.style.cssText = 'max-width:100%;max-height:65vh;border-radius:8px;';
            box.innerHTML = '';
            box.className = 'text-center';
            box.appendChild(img);
        };
        img.onerror = () => showPreviewUnavailable(box, downloadBase, t.fileName);
        img.src = previewUrl;
        return;
    }

    fetch(previewUrl)
        .then(res => {
            // No local rendering on this server: the endpoint hands back an
            // Office Online viewer URL (signed, expiring file link) to embed.
            const embedUrl = res.ok ? res.headers.get('X-Preview-Embed-Url') : null;
            if (embedUrl) {
                const frame = document.createElement('iframe');
                frame.style.cssText = 'width:100%;height:65vh;border:1px solid #e5e7eb;border-radius:8px;background:#fff;';
                frame.src = embedUrl;
                box.innerHTML = '';
                box.appendChild(frame);
                return null;
            }
            if (res.headers.get('X-Preview-Reason') === 'missing') {
                showFileMissing(box);
                return null;
            }
            if (!res.ok || res.headers.get('X-Preview-Available') === '0') {
                showPreviewUnavailable(box, downloadBase, t.fileName);
                return null;
            }
            const contentType = res.headers.get('Content-Type') || '';
            return res.blob().then(blob => ({ blob, contentType }));
        })
        .then(result => {
            if (!result) return;
            const objectUrl = URL.createObjectURL(result.blob);

            if (result.contentType.indexOf('application/pdf') !== -1) {
                box.innerHTML = '<iframe src=\"' + objectUrl + '\" style=\"width:100%;height:65vh;border:1px solid #e5e7eb;border-radius:8px;\"></iframe>';
            } else if (result.contentType.indexOf('text/html') !== -1) {
                const iframe = document.createElement('iframe');
                iframe.style.cssText = 'width:100%;height:65vh;border:1px solid #e5e7eb;border-radius:8px;background:#fff;';
                box.innerHTML = '';
                box.appendChild(iframe);
                iframe.src = objectUrl;
            } else if (result.contentType.indexOf('text/plain') !== -1) {
                result.blob.text().then(text => {
                    box.innerHTML = '<pre style=\"white-space:pre-wrap;max-height:65vh;overflow:auto;border:1px solid var(--border);border-radius:8px;padding:1rem;background:var(--card);margin:0;\">' + escapeHtml(text) + '</pre>';
                });
            } else {
                showPreviewUnavailable(box, downloadBase, t.fileName);
            }
        })
        .catch(() => showPreviewUnavailable(box, downloadBase, t.fileName));
}

function previewTemplate(t) {
    document.getElementById('previewModalTitle').innerHTML = '<i class=\"bi bi-eye me-2\"></i>' + escapeHtml(t.title);

    const previewUrl   = TEMPLATES_PREVIEW_BASE + t.id;
    const downloadBase = '" . base_url('templates/download/') . "' + t.id;

    document.getElementById('previewModalBody').innerHTML = `
      <div class=\"mb-3\">
        <table class=\"table table-sm mb-0\">
          <tr><th style=\"width:120px\">Section</th><td>\${escapeHtml(t.category)}</td></tr>
          <tr><th>File</th><td>\${escapeHtml(t.fileName)} · \${escapeHtml(t.fileSize)}</td></tr>
          <tr><th>Uploaded By</th><td>\${escapeHtml(t.uploader)}</td></tr>
          <tr><th>Date Added</th><td>\${escapeHtml(t.date)}</td></tr>
          \${t.description ? '<tr><th>Description</th><td>' + escapeHtml(t.description) + '</td></tr>' : ''}
        </table>
      </div>
      <div id=\"templatePreviewBox\">
        <div class=\"text-center text-muted py-5\"><span class=\"spinner-border spinner-border-sm me-2\"></span>Loading preview\\u2026</div>
      </div>
    `;

    document.getElementById('previewModalDownloadBtn').onclick = () => downloadTemplate(downloadBase, t.fileName);
    const renameBtn = document.getElementById('previewModalRenameBtn');
    if (renameBtn) renameBtn.onclick = () => tplFromPreview(() => openRenameTemplate({ id: t.id, title: t.title, fileBase: t.fileBase, ext: t.ext }));
    const deleteBtn = document.getElementById('previewModalDeleteBtn');
    if (deleteBtn) deleteBtn.onclick = () => tplFromPreview(() => confirmDeleteTemplate({ id: t.id, title: t.title }));

    const caret = document.getElementById('previewModalDownloadCaret');
    const menu  = document.getElementById('previewModalDownloadMenu');
    const conversions = t.conversions || [];

    menu.innerHTML = '';
    conversions.forEach(c => {
        const name = t.fileName.includes('.') ? t.fileName.substring(0, t.fileName.lastIndexOf('.')) : t.fileName;
        const li = document.createElement('li');
        const a  = document.createElement('a');
        a.className = 'dropdown-item';
        a.href = '#';
        a.innerHTML = '<i class=\"bi bi-file-earmark-' + (c === 'pdf' ? 'pdf' : 'word') + ' me-2\"></i>Convert to ' + (FORMAT_LABELS[c] || c.toUpperCase());
        a.onclick = (e) => { e.preventDefault(); downloadTemplate(downloadBase + '?as=' + c, name + '.' + c); };
        li.appendChild(a);
        menu.appendChild(li);
    });
    caret.classList.toggle('d-none', conversions.length === 0);

    bootstrap.Modal.getOrCreateInstance(document.getElementById('previewModal')).show();

    loadTemplatePreview(t, previewUrl, downloadBase);
}

function notifyDownloadSuccess(fileName) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Download successful',
        text: fileName,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });
}

async function downloadTemplate(url, suggestedName) {
    if (window.showSaveFilePicker) {
        try {
            const handle = await window.showSaveFilePicker({ suggestedName });
            const res = await fetch(url);
            const blob = await res.blob();
            const writable = await handle.createWritable();
            await writable.write(blob);
            await writable.close();
            notifyDownloadSuccess(suggestedName);
            return;
        } catch (err) {
            if (err && err.name === 'AbortError') return;
        }
    }
    // Pointing the throwaway <a> straight at the server URL still starts a
    // real top-level navigation under the hood until the attachment headers
    // arrive, so the browser's tab loading spinner keeps spinning for the
    // whole request — for a slow download (e.g. a PDF conversion) it can
    // look stuck well past the point the file is actually ready. Fetching
    // the bytes ourselves and handing the browser a same-document blob: URL
    // instead means the network transfer never touches page/tab navigation
    // state at all, so the spinner never engages in the first place.
    try {
        const res = await fetch(url);
        if (!res.ok) throw new Error('Download failed.');
        const blob      = await res.blob();
        const objectUrl = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = objectUrl;
        a.download = suggestedName || '';
        a.rel = 'noopener';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(objectUrl);
        notifyDownloadSuccess(suggestedName);
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Download failed', text: 'Something went wrong while downloading this file. Please try again.' });
    }
}

function openEditSectionModal(section) {
    document.getElementById('editSectionId').value   = section.id;
    document.getElementById('editSectionName').value = section.name || '';
    document.getElementById('editSectionDesc').value = section.description || '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editSectionModal')).show();
}

// folder: {id, label} when uploading from inside a folder, otherwise null.
function openUploadModal(categoryId, folder) {
    const folderInput = document.getElementById('uploadFolderId');
    if (folderInput) {
        folderInput.value = folder ? folder.id : '';
        folderInput.dataset.section = folder ? String(categoryId) : '';
        folderInput.dataset.label = folder ? folder.label : '';
    }
    const select = document.getElementById('uploadCategorySelect');
    if (select && categoryId) {
        select.value = categoryId;
        select.dispatchEvent(new Event('change'));
    }
    renderUploadFolderNote();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('uploadModal')).show();
}

// Says which folder the upload goes into; the folder only applies to files filed in its own section.
function renderUploadFolderNote() {
    const input  = document.getElementById('uploadFolderId');
    const note   = document.getElementById('uploadFolderNote');
    const select = document.getElementById('uploadCategorySelect');
    if (!input || !note || !select) return;
    if (!input.value) { note.classList.add('d-none'); note.innerHTML = ''; return; }
    const label = '<strong>' + escapeHtml(input.dataset.label) + '</strong>';
    let html;
    if (select.value === input.dataset.section) {
        html = '<i class=\"bi bi-folder2-open me-1\"></i>Saving into folder ' + label + '.';
    } else if (select.value === 'auto') {
        html = '<i class=\"bi bi-folder2-open me-1\"></i>Files filed in this folder’s section go into ' + label + '; files filed in other sections go to their top level.';
    } else {
        html = '<i class=\"bi bi-info-circle me-1\"></i>' + label + ' is not in the chosen section, so the files go to that section’s top level.';
    }
    note.innerHTML = html;
    note.classList.remove('d-none');
}

function handleTemplateFilesChange(fileInput) {
    if (typeof refreshDropzone === 'function') refreshDropzone(fileInput);
    const files = Array.from(fileInput.files || []);
    const titleGroup = document.getElementById('uploadTitleGroup');
    const titleInput = document.getElementById('uploadTitleInput');
    const preview = document.getElementById('uploadFilesPreview');

    const tagsGroup = document.getElementById('uploadTagsGroup');
    const tagsInput = document.getElementById('uploadTagsInput');
    // Tags are reviewed per file only for a single upload; a batch gets automatic tags.
    tagsGroup.classList.toggle('d-none', files.length !== 1);
    tagsInput.disabled = files.length !== 1;
    tagsInput.value = '';
    tagsInput.dataset.edited = '';
    titleInput.dataset.edited = '';
    const titleSuggest = document.getElementById('uploadTitleSuggest');
    titleSuggest.classList.add('d-none');
    titleSuggest.innerHTML = '';
    TEMPLATE_TITLE_OPTIONS = [];
    TEMPLATE_TITLE_NOTE = '';
    TEMPLATE_TITLE_STATE = 'idle';
    TEMPLATE_BATCH_OPTIONS = {};

    if (files.length === 0) {
        titleGroup.classList.remove('d-none');
        titleInput.required = true;
        titleInput.value = '';
        preview.classList.add('d-none');
        preview.innerHTML = '';
        TEMPLATE_SUGGESTIONS = [];
        renderTemplateSuggestions();
        return;
    }

    if (files.length > 1) {
        titleGroup.classList.add('d-none');
        titleInput.required = false;
        titleInput.value = '';
    } else {
        titleGroup.classList.remove('d-none');
        titleInput.required = true;
        const name = files[0].name;
        titleInput.value = baseFileTitle(name);
        showTitleLoading(name);
    }

    const summary = files.length > 1
        ? files.length + ' files selected — each becomes its own template. Check each title below.'
        : files.length + ' file selected.';

    preview.classList.remove('d-none');
    preview.innerHTML = '<div class=\"d-flex justify-content-between align-items-center mb-1\">'
        + '<p class=\"small text-muted mb-0\">' + summary + '</p>'
        + '<button type=\"button\" class=\"btn btn-sm btn-outline-secondary py-0 px-2\" style=\"font-size:.7rem;\" onclick=\"discardTemplateFiles()\">Discard All</button>'
        + '</div>'
        + files.map(function (f, i) {
            return '<div class=\"d-flex align-items-center gap-2 p-2 rounded-3 mb-1\" style=\"background:var(--surface-hover);\">'
                + '<i class=\"bi bi-file-earmark text-muted\"></i>'
                + '<div class=\"flex-grow-1\" style=\"min-width:0;\"><div class=\"small text-truncate\">' + escapeHtml(f.name) + '</div>'
                + '<div class=\"tpl-file-suggest\" id=\"tplSuggest' + i + '\"><span class=\"spinner-border spinner-border-sm me-1\" style=\"width:.7rem;height:.7rem;\"></span>Classifying…</div></div>'
                + '<button type=\"button\" class=\"btn btn-sm btn-outline-secondary py-0 px-2\" onclick=\"removeTemplateFile(' + i + ')\" title=\"Remove this file\"><i class=\"bi bi-x-lg\"></i></button>'
                + '</div>'
                + (files.length > 1 ? batchTitleRow(i, f.name) : '');
        }).join('');

    classifyTemplateFiles(fileInput);
}

/* ── Auto-classification (TemplateClassifier on the server) ── */
var TEMPLATE_SUGGESTIONS = [];
var TEMPLATE_CLASSIFY_TOKEN = 0;

function classifyTemplateFiles(fileInput) {
    const token = ++TEMPLATE_CLASSIFY_TOKEN;
    TEMPLATE_SUGGESTIONS = [];
    renderTemplateSuggestions();

    const fd = new FormData();
    fd.append('action', 'classify');
    Array.from(fileInput.files || []).forEach(function (f) { fd.append('file[]', f); });

    fetch(fileInput.form.getAttribute('action'), { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (token !== TEMPLATE_CLASSIFY_TOKEN) return; // files changed meanwhile
            TEMPLATE_SUGGESTIONS = data.status === 'success' ? data.results : [];
            const tagsInput = document.getElementById('uploadTagsInput');
            if (TEMPLATE_SUGGESTIONS.length === 1 && !tagsInput.dataset.edited) {
                tagsInput.value = (TEMPLATE_SUGGESTIONS[0].tags || []).join(', ');
            }
            if (TEMPLATE_SUGGESTIONS.length === 1) showTitleSuggestion(TEMPLATE_SUGGESTIONS[0]);
            if (TEMPLATE_SUGGESTIONS.length > 1) TEMPLATE_SUGGESTIONS.forEach(applyBatchTitleSuggestion);
            renderTemplateSuggestions();
        })
        .catch(function () {
            if (token !== TEMPLATE_CLASSIFY_TOKEN) return;
            document.querySelectorAll('.tpl-file-suggest').forEach(function (el) { el.textContent = 'Could not classify — pick a section.'; });
            if (TEMPLATE_TITLE_STATE === 'loading') {
                TEMPLATE_TITLE_STATE = 'ready';
                TEMPLATE_TITLE_NOTE = '<i class=\"bi bi-exclamation-circle me-1\"></i>Could not read the document to suggest titles. Check the title yourself.';
                renderTitleSuggestions();
            }
        });
}

function suggestionBadge(s) {
    if (!s || !s.category_id) {
        return '<span class=\"tpl-conf tpl-conf-none\"><i class=\"bi bi-question-circle me-1\"></i>No match — pick a section</span>';
    }
    const pct = Math.round(s.confidence * 100);
    return '<span class=\"tpl-conf ' + (s.confident ? 'tpl-conf-high' : 'tpl-conf-low') + '\"><i class=\"bi bi-magic me-1\"></i>'
        + escapeHtml(s.category_name) + ' · ' + pct + '%</span>';
}

// Shows each file's suggested section, and for a single file a summary box
// with one-click alternatives. Re-runs when the Section dropdown changes.
function renderTemplateSuggestions() {
    const select  = document.getElementById('uploadCategorySelect');
    const box     = document.getElementById('uploadSuggestion');
    const hint    = document.getElementById('uploadAutoHint');
    if (!select || !box) return;
    const isAuto  = select.value === 'auto';
    hint.classList.toggle('d-none', !isAuto);
    renderUploadFolderNote();

    TEMPLATE_SUGGESTIONS.forEach(function (s, i) {
        const el = document.getElementById('tplSuggest' + i);
        if (!el) return;
        const tags = (s.tags || []).map(function (t) { return '<span class=\"tpl-tag\">' + escapeHtml(t) + '</span>'; }).join('');
        const titled = false
            ? '<span class=\"tpl-basis\"><i class=\"bi bi-fonts me-1\"></i>Will be titled “' + escapeHtml(s.recommended_title) + '”</span> '
            : '';
        el.innerHTML = (isAuto ? suggestionBadge(s) + ' ' : '') + basisLabel(s) + ' ' + titled + tags;
    });

    const one = TEMPLATE_SUGGESTIONS.length === 1 ? TEMPLATE_SUGGESTIONS[0] : null;
    if (!one) { box.classList.add('d-none'); box.innerHTML = ''; return; }

    const options = [];
    if (one.category_id) options.push(one);
    (one.alternatives || []).forEach(function (a) { if (a.confidence >= 0.05) options.push(a); });

    let html;
    if (!one.category_id) {
        html = '<i class=\"bi bi-question-circle me-1\"></i>Could not tell which section this belongs to. Please pick one above.';
    } else if (isAuto) {
        html = '<div><i class=\"bi bi-magic me-1\"></i>Will be filed in <strong>' + escapeHtml(one.category_name) + '</strong> ('
            + Math.round(one.confidence * 100) + '% match' + (one.confident ? '' : ' — please double-check') + ').</div>'
            + basisSentence(one);
    } else if (String(one.category_id) !== select.value) {
        html = '<div><i class=\"bi bi-info-circle me-1\"></i>This looks like <strong>' + escapeHtml(one.category_name) + '</strong> ('
            + Math.round(one.confidence * 100) + '% match), not the section you picked.</div>';
    } else {
        html = '<div><i class=\"bi bi-check2-circle me-1\"></i>Matches the suggested section.</div>';
    }
    if (options.length > 1 || (!isAuto && one.category_id && String(one.category_id) !== select.value)) {
        html += '<div class=\"mt-1 d-flex flex-wrap gap-1 align-items-center\"><span>Use:</span>' + options.map(function (o) {
            return '<button type=\"button\" class=\"btn btn-sm btn-outline-secondary py-0 px-2\" style=\"font-size:.72rem;\" onclick=\"pickTemplateSection(' + o.category_id + ')\">'
                + escapeHtml(o.category_name) + ' · ' + Math.round(o.confidence * 100) + '%</button>';
        }).join('') + '</div>';
    }
    box.innerHTML = html;
    box.classList.remove('d-none');
}

// Title suggestions from the document's contents, in a highlighted panel
// under the Title field — always shown, even when the file name looks fine,
// so the ADAS can double-check it. When the name says nothing (final,
// revised copy, scan001, Document1) or a dated document lacks its year, the
// best suggestion is filled in right away. The file name stays one click away.
var TEMPLATE_TITLE_OPTIONS = [];
var TEMPLATE_TITLE_NOTE = '';
var TEMPLATE_TITLE_STATE = 'idle'; // idle | loading | ready
var TEMPLATE_FILE_NAME_TITLE = '';

// escapeHtml() does not escape quotes; this one is safe inside attributes.
function escapeAttr(str) {
    return escapeHtml(str).replace(/\"/g, '&quot;').replace(/'/g, '&#39;');
}

function baseFileTitle(name) {
    return name.includes('.') ? name.substring(0, name.lastIndexOf('.')) : name;
}

function uniqueTitles(list) {
    const seen = {};
    return list.filter(function (t) {
        if (!t) return false;
        const k = t.trim().toLowerCase();
        if (seen[k]) return false;
        seen[k] = true;
        return true;
    });
}

function showTitleLoading(fileName) {
    TEMPLATE_TITLE_STATE = 'loading';
    TEMPLATE_FILE_NAME_TITLE = baseFileTitle(fileName);
    TEMPLATE_TITLE_OPTIONS = [];
    TEMPLATE_TITLE_NOTE = '';
    renderTitleSuggestions();
}

function showTitleSuggestion(s) {
    const input = document.getElementById('uploadTitleInput');
    TEMPLATE_TITLE_STATE = 'ready';
    TEMPLATE_TITLE_OPTIONS = uniqueTitles([s.recommended_title].concat(s.suggested_titles || []));
    TEMPLATE_TITLE_NOTE = '';

    if (s.recommended_title && !input.dataset.edited) {
        const oldName = input.value;
        input.value = s.recommended_title;
        if (s.recommended_reason === 'period') {
            // Meaningful name, but the document is for a specific period and the name had no year.
            TEMPLATE_TITLE_NOTE = '<i class=\"bi bi-calendar-check me-1\"></i>Added <strong>' + escapeHtml(s.period) + '</strong> from the '
                + escapeHtml(s.period_source || 'document') + ' — this kind of document is for a specific period.';
        } else {
            TEMPLATE_TITLE_NOTE = '<i class=\"bi bi-magic me-1\"></i>“' + escapeHtml(oldName) + '” doesn’t say what the document is, so the title was taken from its '
                + (s.source === 'ocr' ? 'scanned text' : 'contents')
                + (s.period ? ' (period: <strong>' + escapeHtml(s.period) + '</strong>'
                    + (s.period_source === 'file name' ? ', kept from the file name' : '') + ')' : '') + '.';
        }
    } else if (s.generic_name && !input.dataset.edited) {
        TEMPLATE_TITLE_NOTE = '<span class=\"text-warning-emphasis\"><i class=\"bi bi-exclamation-triangle me-1\"></i>“' + escapeHtml(input.value)
            + '” doesn’t say what the document is, and no heading was found in it. Please type a descriptive title.</span>';
    } else if (TEMPLATE_TITLE_OPTIONS.length) {
        const same = TEMPLATE_TITLE_OPTIONS.some(function (t) { return t.toLowerCase() === input.value.trim().toLowerCase(); });
        TEMPLATE_TITLE_NOTE = same
            ? '<i class=\"bi bi-check2-circle me-1\"></i>The file name matches the document’s heading.'
            : '<i class=\"bi bi-info-circle me-1\"></i>The file name was kept. Compare it with what the document says:';
    }
    renderTitleSuggestions();
}

// One title choice button. best = mark it as the top suggestion.
function titleChip(text, active, onclick, kind) {
    const badge = kind === 'best' ? '<span class=\"tpl-chip-badge\">Best match</span>'
        : kind === 'file' ? '<span class=\"tpl-chip-badge tpl-chip-badge-muted\">File name</span>' : '';
    return '<button type=\"button\" class=\"tpl-title-chip' + (active ? ' active' : '') + (kind === 'file' ? ' is-file' : '') + '\" onclick=\"' + onclick + '\"'
        + ' title=\"' + escapeAttr(text) + '\">' + (active ? '<i class=\"bi bi-check-circle-fill me-1\"></i>' : '<i class=\"bi bi-circle me-1\"></i>')
        + '<span class=\"tpl-chip-text\">' + escapeHtml(text) + '</span>' + badge + '</button>';
}

function renderTitleSuggestions() {
    const input = document.getElementById('uploadTitleInput');
    const box   = document.getElementById('uploadTitleSuggest');
    if (!box) return;
    if (TEMPLATE_TITLE_STATE === 'idle') { box.classList.add('d-none'); box.innerHTML = ''; return; }

    const head = '<div class=\"tpl-title-panel-head\"><i class=\"bi bi-stars me-1\"></i>Suggested titles <span>from the document</span></div>';
    let body;
    if (TEMPLATE_TITLE_STATE === 'loading') {
        body = '<div class=\"tpl-title-panel-loading\"><span class=\"spinner-border spinner-border-sm me-2\"></span>Reading the document…</div>';
    } else {
        const value = input.value.trim().toLowerCase();
        const chips = TEMPLATE_TITLE_OPTIONS.map(function (t, i) {
            return titleChip(t, t.trim().toLowerCase() === value, 'useSuggestedTitle(' + i + ')', i === 0 ? 'best' : '');
        });
        const fileInList = TEMPLATE_TITLE_OPTIONS.some(function (t) { return t.trim().toLowerCase() === TEMPLATE_FILE_NAME_TITLE.trim().toLowerCase(); });
        if (TEMPLATE_FILE_NAME_TITLE && !fileInList) {
            chips.push(titleChip(TEMPLATE_FILE_NAME_TITLE, TEMPLATE_FILE_NAME_TITLE.trim().toLowerCase() === value, 'useFileNameTitle()', 'file'));
        }
        body = (TEMPLATE_TITLE_NOTE ? '<div class=\"tpl-title-panel-note\">' + TEMPLATE_TITLE_NOTE + '</div>' : '')
            + (TEMPLATE_TITLE_OPTIONS.length
                ? '<div class=\"tpl-title-chips\">' + chips.join('') + '</div>'
                : '<div class=\"tpl-title-panel-note text-muted\">No clear heading was found in the document, so the file name is used. Make sure it describes the document.</div>');
    }
    box.innerHTML = head + body;
    box.classList.remove('d-none');
}

function useSuggestedTitle(i) {
    const input = document.getElementById('uploadTitleInput');
    if (TEMPLATE_TITLE_OPTIONS[i] === undefined) return;
    input.value = TEMPLATE_TITLE_OPTIONS[i];
    input.dataset.edited = '1';
    renderTitleSuggestions();
}
function useFileNameTitle() {
    const input = document.getElementById('uploadTitleInput');
    input.value = TEMPLATE_FILE_NAME_TITLE;
    input.dataset.edited = '1';
    renderTitleSuggestions();
}

/* Batch upload: every file gets its own editable title with suggestions. */
var TEMPLATE_BATCH_OPTIONS = {};

function batchTitleRow(i, fileName) {
    return '<div class=\"tpl-batch-title\">'
        + '<div class=\"input-group input-group-sm\"><span class=\"input-group-text\"><i class=\"bi bi-fonts\"></i></span>'
        + '<input type=\"text\" name=\"batch_titles[]\" id=\"tplBatchTitle' + i + '\" class=\"form-control\" maxlength=\"255\" value=\"' + escapeAttr(baseFileTitle(fileName)) + '\"'
        + ' data-file-title=\"' + escapeAttr(baseFileTitle(fileName)) + '\" oninput=\"this.dataset.edited = \\'1\\'; renderBatchTitleChips(' + i + ')\" aria-label=\"Title for ' + escapeAttr(fileName) + '\"></div>'
        + '<div class=\"tpl-title-chips mt-1\" id=\"tplBatchChips' + i + '\"><span class=\"tpl-title-panel-loading\"><span class=\"spinner-border spinner-border-sm me-1\" style=\"width:.7rem;height:.7rem;\"></span>Reading the document for title suggestions…</span></div>'
        + '</div>';
}

function applyBatchTitleSuggestion(s) {
    const input = document.getElementById('tplBatchTitle' + s.index);
    if (!input) return;
    TEMPLATE_BATCH_OPTIONS[s.index] = uniqueTitles([s.recommended_title].concat(s.suggested_titles || []));
    if (s.recommended_title && !input.dataset.edited) input.value = s.recommended_title;
    renderBatchTitleChips(s.index);
}

function renderBatchTitleChips(i) {
    const box   = document.getElementById('tplBatchChips' + i);
    const input = document.getElementById('tplBatchTitle' + i);
    if (!box || !input) return;
    const opts  = TEMPLATE_BATCH_OPTIONS[i];
    if (!opts) return; // still loading
    const value = input.value.trim().toLowerCase();
    const file  = input.dataset.fileTitle || '';
    const chips = opts.slice(0, 3).map(function (t, k) {
        return titleChip(t, t.trim().toLowerCase() === value, 'useBatchTitle(' + i + ', ' + k + ')', k === 0 ? 'best' : '');
    });
    if (file && !opts.some(function (t) { return t.trim().toLowerCase() === file.trim().toLowerCase(); })) {
        chips.push(titleChip(file, file.trim().toLowerCase() === value, 'useBatchTitle(' + i + ', -1)', 'file'));
    }
    box.innerHTML = opts.length ? chips.join('') : '<span class=\"tpl-title-panel-loading\">No heading found — the file name will be used.</span>';
}

function useBatchTitle(i, k) {
    const input = document.getElementById('tplBatchTitle' + i);
    if (!input) return;
    input.value = k < 0 ? (input.dataset.fileTitle || '') : (TEMPLATE_BATCH_OPTIONS[i] || [])[k] || input.value;
    input.dataset.edited = '1';
    renderBatchTitleChips(i);
}

// What the suggestion was based on: the document's text, OCR of a scan, or only the file name.
function basisLabel(s) {
    if (s.source === 'ocr') return '<span class=\"tpl-basis\"><i class=\"bi bi-camera me-1\"></i>Scanned text</span>';
    if (s.basis === 'content') return '<span class=\"tpl-basis\"><i class=\"bi bi-file-text me-1\"></i>Read ' + s.words_read + ' words</span>';
    return '<span class=\"tpl-basis tpl-basis-name\"><i class=\"bi bi-type me-1\"></i>File name only</span>';
}
function basisSentence(s) {
    const words = (s.matched || []).map(function (w) { return '<em>' + escapeHtml(w) + '</em>'; }).join(', ');
    let why;
    if (s.source === 'ocr') why = 'Based on the scanned text of the document';
    else if (s.basis === 'content') why = 'Based on the document contents (' + s.words_read + ' words read)';
    else if (s.basis === 'name+content') why = 'The document has little text, so the file name counted most';
    else why = 'No readable text in this file, so only the file name was used';
    return '<div class=\"mt-1\" style=\"font-size:.72rem;\">' + why + (words ? ' — matched: ' + words : '') + '.</div>';
}

function pickTemplateSection(id) {
    const select = document.getElementById('uploadCategorySelect');
    select.value = String(id);
    const root = select.closest('.maroon-select');
    if (root && root.maroonSelectSync) root.maroonSelectSync();
    renderTemplateSuggestions();
}

// Opening the Upload button fresh goes back to auto-classify.
document.getElementById('uploadModal')?.addEventListener('hidden.bs.modal', function () {
    const folderInput = document.getElementById('uploadFolderId');
    if (folderInput) folderInput.value = '';
    const select = document.getElementById('uploadCategorySelect');
    if (select) { select.value = 'auto'; const root = select.closest('.maroon-select'); if (root && root.maroonSelectSync) root.maroonSelectSync(); }
    renderTemplateSuggestions();
});

function removeTemplateFile(index) {
    const input = document.getElementById('uploadFileInput');
    const dt = new DataTransfer();
    Array.from(input.files).forEach(function (file, i) {
        if (i !== index) dt.items.add(file);
    });
    input.files = dt.files;
    handleTemplateFilesChange(input);
}

function discardTemplateFiles() {
    const input = document.getElementById('uploadFileInput');
    input.value = '';
    handleTemplateFilesChange(input);
}

/* ── Drag and drop onto folders (ADAS) ──
   - A template row dragged onto another folder: asks, then moves it.
   - Files dragged from the computer onto a folder: opens Upload with that folder picked.
   The folder flap opens while something is held over it (see .tpl-folder in app.css). */
var TPL_DRAG_TEMPLATE = null; // the template being dragged: {id, title, fromId, fromFolder, fromName}

function tplDragKind(e) {
    if (TPL_DRAG_TEMPLATE) return 'template';
    const types = Array.from((e.dataTransfer && e.dataTransfer.types) || []);
    return types.indexOf('Files') !== -1 ? 'files' : null;
}

function initTemplateDragDrop() {
    if (!document.getElementById('tplSectionsData')) return; // only the ADAS manages templates

    document.querySelectorAll('.tpl-draggable').forEach(function (row) {
        row.addEventListener('dragstart', function (e) {
            TPL_DRAG_TEMPLATE = { id: row.dataset.templateId, title: row.dataset.templateTitle, fromId: row.dataset.fromId, fromFolder: row.dataset.fromFolder || '0', fromName: row.dataset.fromName };
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', row.dataset.templateTitle);
            row.classList.add('is-dragging');
            document.body.classList.add('tpl-dragging-doc');
        });
        row.addEventListener('dragend', function () {
            row.classList.remove('is-dragging');
            document.body.classList.remove('tpl-dragging-doc');
            setTimeout(function () { TPL_DRAG_TEMPLATE = null; }, 0);
        });
    });

    document.querySelectorAll('[data-drop-section]').forEach(function (target) {
        let depth = 0;
        const hint = target.querySelector('.tpl-drop-hint span');
        target.addEventListener('dragenter', function (e) {
            const kind = tplDragKind(e);
            if (!kind) return;
            e.preventDefault();
            depth++;
            // Section chips mean the section's top level (folder 0); folder tiles carry their folder id.
            const same = kind === 'template' && String(TPL_DRAG_TEMPLATE.fromId) === target.dataset.dropSection
                && String(TPL_DRAG_TEMPLATE.fromFolder) === (target.dataset.dropFolder || '0');
            target.classList.add(same ? 'is-drop-same' : 'is-dragover');
            if (hint) hint.textContent = same ? 'Already in this folder' : (kind === 'template' ? 'Drop to move here' : 'Drop to add to ' + target.dataset.sectionName);
        });
        target.addEventListener('dragover', function (e) {
            const kind = tplDragKind(e);
            if (!kind) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = target.classList.contains('is-drop-same') ? 'none' : (kind === 'template' ? 'move' : 'copy');
        });
        target.addEventListener('dragleave', function () {
            if (--depth <= 0) {
                depth = 0;
                target.classList.remove('is-dragover', 'is-drop-same');
            }
        });
        target.addEventListener('drop', function (e) {
            const kind = tplDragKind(e);
            if (!kind) return;
            e.preventDefault();
            depth = 0;
            const same = target.classList.contains('is-drop-same');
            target.classList.remove('is-dragover', 'is-drop-same');
            if (same) return;
            // The sheet drops into the folder and the flap closes.
            target.classList.add('is-dropped');
            setTimeout(function () { target.classList.remove('is-dropped'); }, 650);
            if (kind === 'template') {
                const t = TPL_DRAG_TEMPLATE;
                TPL_DRAG_TEMPLATE = null;
                setTimeout(function () { confirmMoveTemplate(t, target.dataset.dropSection, target.dataset.sectionName, target.dataset.dropFolder); }, 350);
            } else {
                const files = Array.from(e.dataTransfer.files || []);
                setTimeout(function () { uploadDroppedFiles(files, target.dataset.dropSection, target.dataset.sectionName, target.dataset.dropFolder); }, 350);
            }
        });
    });

    // Files dropped beside a folder must not make the browser open them instead.
    if (!window.__tplNoOpenDrop) {
        window.__tplNoOpenDrop = true;
        ['dragover', 'drop'].forEach(function (type) {
            window.addEventListener(type, function (e) {
                if (!document.getElementById('tplSectionsData')) return;
                const types = Array.from((e.dataTransfer && e.dataTransfer.types) || []);
                if (types.indexOf('Files') !== -1 && !e.defaultPrevented) e.preventDefault();
            });
        });
    }
}

// Posts one ADAS action to the Templates page, then shows the result and refreshes.
function postTemplateAction(fields) {
    const url = document.getElementById('tplSectionsData').dataset.url;
    const fd = new FormData();
    Object.keys(fields).forEach(function (k) { fd.append(k, fields[k]); });
    return fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json().catch(function () { return { status: 'error', message: 'Unexpected server response.' }; }); })
        .then(handleAjaxFormResult)
        .catch(function () { Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server. Please try again.' }); });
}

// New folder at the section's top level (parent 0) or inside the open folder.
function openAddFolder(o) {
    Swal.fire({
        title: 'New folder',
        html: '<div style=\"font-size:.85rem\">Inside <strong>' + escapeHtml(o.where) + '</strong></div>',
        input: 'text',
        inputPlaceholder: 'e.g. Leave Forms, 2026, Term 1',
        inputAttributes: { maxlength: 150 },
        showCancelButton: true,
        confirmButtonText: '<i class=\"bi bi-folder-plus me-1\"></i>Create folder',
        confirmButtonColor: '#800000',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        inputValidator: function (v) { return v && v.trim() ? null : 'Please enter a folder name.'; },
    }).then(function (r) {
        if (!r.isConfirmed) return;
        postTemplateAction({ action: 'add_folder', category_id: o.category, parent_id: o.parent || 0, name: r.value.trim() });
    });
}

function openRenameFolder(o) {
    Swal.fire({
        title: 'Rename folder',
        input: 'text',
        inputValue: o.name,
        inputAttributes: { maxlength: 150 },
        showCancelButton: true,
        confirmButtonText: 'Save',
        confirmButtonColor: '#800000',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        inputValidator: function (v) { return v && v.trim() ? null : 'Please enter a folder name.'; },
    }).then(function (r) {
        if (!r.isConfirmed || r.value.trim() === o.name) return;
        postTemplateAction({ action: 'rename_folder', id: o.id, name: r.value.trim() });
    });
}

// The preview window traps keyboard focus, so it closes before a dialog opens.
function tplFromPreview(open) {
    const modal = bootstrap.Modal.getInstance(document.getElementById('previewModal'));
    if (modal) { modal.hide(); setTimeout(open, 350); } else { open(); }
}

// New title and download name; the extension stays.
function openRenameTemplate(t) {
    const ext = t.ext ? '.' + escapeHtml(t.ext) : '';
    Swal.fire({
        title: 'Rename template',
        html: '<div class=\"text-start\" style=\"font-size:.85rem\">'
            + '<label class=\"form-label fw-semibold mb-1\" for=\"tplRenameTitle\">Title</label>'
            + '<input id=\"tplRenameTitle\" class=\"form-control mb-3\" maxlength=\"255\" value=\"' + escapeAttr(t.title) + '\">'
            + '<label class=\"form-label fw-semibold mb-1\" for=\"tplRenameFile\">File name <span class=\"text-muted fw-normal\">(used when downloading)</span></label>'
            + '<div class=\"input-group\"><input id=\"tplRenameFile\" class=\"form-control\" maxlength=\"200\" value=\"' + escapeAttr(t.fileBase || '') + '\">'
            + (ext ? '<span class=\"input-group-text\">' + ext + '</span>' : '') + '</div></div>',
        showCancelButton: true,
        confirmButtonText: 'Save',
        confirmButtonColor: '#800000',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        focusConfirm: false,
        didOpen: function () { const i = document.getElementById('tplRenameTitle'); i.focus(); i.select(); },
        preConfirm: function () {
            const title = document.getElementById('tplRenameTitle').value.trim();
            const file  = document.getElementById('tplRenameFile').value.trim();
            if (!title) { Swal.showValidationMessage('Please enter a title.'); return false; }
            if (!file) { Swal.showValidationMessage('Please enter a file name.'); return false; }
            return { title: title, file: file };
        },
    }).then(function (r) {
        if (!r.isConfirmed) return;
        postTemplateAction({ action: 'rename_template', id: t.id, title: r.value.title, file_base: r.value.file });
    });
}

function confirmDeleteTemplate(t) {
    Swal.fire({
        icon: 'warning',
        title: 'Delete this template?',
        html: '<strong>“' + escapeHtml(t.title) + '”</strong> and its file will be removed. This can’t be undone.',
        showCancelButton: true,
        confirmButtonText: '<i class=\"bi bi-trash me-1\"></i>Delete',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        focusCancel: true,
    }).then(function (r) {
        if (!r.isConfirmed) return;
        postTemplateAction({ action: 'delete_template', id: t.id });
    });
}

function confirmDeleteFolder(f) {
    Swal.fire({
        icon: 'warning',
        title: 'Delete this folder?',
        html: '<strong>“' + escapeHtml(f.name) + '”</strong> is empty and will be removed.',
        showCancelButton: true,
        confirmButtonText: '<i class=\"bi bi-trash me-1\"></i>Delete',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        focusCancel: true,
    }).then(function (r) {
        if (!r.isConfirmed) return;
        postTemplateAction({ action: 'delete_folder', id: f.id });
    });
}

// Do you want to transfer “doc” to “folder” from “folder”? toFolder 0/empty = the section's top level.
function confirmMoveTemplate(t, toId, toName, toFolder) {
    if (!t) return;
    Swal.fire({
        icon: 'question',
        title: 'Transfer document?',
        html: 'Do you want to transfer <strong>“' + escapeHtml(t.title) + '”</strong> to <strong>“' + escapeHtml(toName)
            + '”</strong> from <strong>“' + escapeHtml(t.fromName) + '”</strong>?',
        showCancelButton: true,
        confirmButtonText: '<i class=\"bi bi-folder-symlink me-1\"></i>Yes, transfer',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#800000',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        focusCancel: true,
    }).then(function (result) {
        if (!result.isConfirmed) return;
        Swal.fire({ title: 'Transferring…', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false, didOpen: function () { Swal.showLoading(); } });
        postTemplateAction({ action: 'move_template', id: t.id, category_id: toId, folder_id: toFolder || 0 });
    });
}

// Keyboard / touch alternative to dragging: pick the section or folder from a list
// (a section with folders becomes a group: its top level, then each folder by path).
function openMoveTemplate(row) {
    if (!row) return;
    const data     = document.getElementById('tplSectionsData').dataset;
    const sections = JSON.parse(data.sections || '[]');
    const folders  = JSON.parse(data.folders || '[]');
    const here     = row.dataset.fromId + ':' + (row.dataset.fromFolder || '0');
    const options  = {};
    const labels   = {};
    sections.forEach(function (s) {
        const inside = folders.filter(function (f) { return f.section === s.id; });
        if (!inside.length) {
            if (s.id + ':0' !== here) { options[s.id + ':0'] = s.name; labels[s.id + ':0'] = s.name; }
            return;
        }
        const group = {};
        if (s.id + ':0' !== here) { group[s.id + ':0'] = s.name + ' (top level)'; labels[s.id + ':0'] = s.name; }
        inside.forEach(function (f) {
            const key = s.id + ':' + f.id;
            if (key !== here) { group[key] = f.path; labels[key] = s.name + ' / ' + f.path; }
        });
        if (Object.keys(group).length) options[s.name] = group;
    });
    Swal.fire({
        title: 'Move to which folder?',
        html: '<div style=\"font-size:.85rem\">“' + escapeHtml(row.dataset.templateTitle) + '” is in <strong>' + escapeHtml(row.dataset.fromName) + '</strong>.</div>',
        input: 'select',
        inputOptions: options,
        inputPlaceholder: 'Choose a folder',
        showCancelButton: true,
        confirmButtonText: 'Next',
        confirmButtonColor: '#800000',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        inputValidator: function (v) { return v ? null : 'Please choose a folder.'; },
    }).then(function (r) {
        if (!r.isConfirmed) return;
        const parts = r.value.split(':');
        confirmMoveTemplate({ id: row.dataset.templateId, title: row.dataset.templateTitle, fromId: row.dataset.fromId, fromName: row.dataset.fromName }, parts[0], labels[r.value], parts[1]);
    });
}

// Files from the computer dropped on a section or folder: open Upload with it picked
// (titles, tags and the section check still run there).
function uploadDroppedFiles(files, sectionId, sectionName, folderId) {
    const input = document.getElementById('uploadFileInput');
    if (!input || !files.length) return;
    const accept = (input.getAttribute('accept') || '').split(',').map(function (a) { return a.trim().toLowerCase(); }).filter(Boolean);
    const ok = files.filter(function (f) { const n = f.name.toLowerCase(); return !accept.length || accept.some(function (a) { return n.endsWith(a); }); });
    if (ok.length < files.length) showToast((files.length - ok.length) + ' file(s) skipped — that file type isn’t allowed for templates.', 'danger');
    if (!ok.length) return;

    openUploadModal(sectionId, folderId && folderId !== '0' ? { id: folderId, label: sectionName } : null);
    const dt = new DataTransfer();
    ok.forEach(function (f) { dt.items.add(f); });
    input.files = dt.files;
    input.dispatchEvent(new Event('change', { bubbles: true }));
    showToast(ok.length + ' file' + (ok.length === 1 ? '' : 's') + ' ready to upload to ' + escapeHtml(sectionName) + '. Check the details, then upload.');
}

/* ── Opening a folder: instead of the loading screen, the folder opens and
   grows to fill the area below the page header and filters (those stay put)
   while the section loads; then its templates come out of it.
   Going back plays it in reverse: the section shrinks back into its folder
   card and the flap closes. ── */
function tplFolderMarkup(extra) {
    return '<div class=\"tpl-folder ' + (extra || '') + '\" aria-hidden=\"true\"><span class=\"tpl-folder-back\"></span>'
        + '<span class=\"tpl-folder-paper\"></span><span class=\"tpl-folder-paper tpl-folder-paper-2\"></span><span class=\"tpl-folder-front\"></span></div>';
}

function tplReduceMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

// The area below the header and filters, in page coordinates, as it will be
// once the page is scrolled to the top (navigation scrolls to the top).
function tplStageBox() {
    const stage = document.querySelector('[data-folder-stage]');
    const main  = document.getElementById('main-content');
    const sr = (stage || main).getBoundingClientRect();
    const mr = main.getBoundingClientRect();
    const top = sr.top + window.scrollY;
    return {
        left: mr.left + window.scrollX + 12,
        top: top,
        width: Math.max(200, mr.width - 24),
        height: Math.max(320, window.innerHeight - (top - window.scrollY > window.innerHeight ? 0 : top) - 16),
    };
}

function tplBoxOf(el) {
    const r = el.getBoundingClientRect();
    return { left: r.left + window.scrollX, top: r.top + window.scrollY, width: r.width, height: r.height };
}

function tplPlace(el, box) {
    el.style.left = box.left + 'px';
    el.style.top = box.top + 'px';
    el.style.width = box.width + 'px';
    el.style.height = box.height + 'px';
}

function tplMakeZoom(color, name, sub, box, open) {
    const zoom = document.createElement('div');
    zoom.className = 'tpl-folder-zoom' + (open ? ' is-expanded no-anim' : '');
    zoom.style.setProperty('--folder', color);
    zoom.setAttribute('role', 'status');
    zoom.innerHTML = '<div class=\"tpl-folder-zoom-inner\">' + tplFolderMarkup('tpl-folder-xl' + (open ? ' is-open' : ''))
        + '<div class=\"tpl-folder-zoom-name\">' + escapeHtml(name) + '</div><div class=\"tpl-folder-zoom-sub\">' + escapeHtml(sub) + '</div></div>';
    tplPlace(zoom, box);
    document.body.appendChild(zoom);
    if (open) { void zoom.offsetWidth; zoom.classList.remove('no-anim'); }
    return zoom;
}

function openSectionFolder(e) {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (typeof loadPage !== 'function') return;
    const link = e.currentTarget;
    // A section card, or a folder in the dock above a section's templates.
    const card = link.closest('.tpl-section-card, .tpl-dock-folder');
    e.preventDefault();
    if (card && card.classList.contains('is-current')) {
        // Already open — but from inside one of its folders, go back to its top level.
        if (card.dataset.inFolder) loadPage(link.href);
        return;
    }
    if (card && card.classList.contains('tpl-dock-folder')) {
        document.querySelectorAll('.tpl-dock-folder.is-current').forEach(function (c) {
            c.classList.remove('is-current');
            c.removeAttribute('aria-current');
            const f = c.querySelector('.tpl-folder');
            if (f) f.classList.remove('is-open');
            const mark = c.querySelector('.tpl-dock-current');
            if (mark) mark.remove();
        });
        card.classList.add('is-current');
        card.setAttribute('aria-current', 'page');
        card.insertAdjacentHTML('beforeend', '<span class=\"tpl-dock-current\"><i class=\"bi bi-eye-fill me-1\"></i>Open</span>');
    }

    if (!card || tplReduceMotion()) {
        loadPage(link.href);
        return;
    }

    const color = (card.querySelector('.tpl-folder') || card).style.getPropertyValue('--folder') || '#7f1d1d';
    const name  = card.dataset.sectionName || (card.querySelector('.tpl-section-name, .tpl-dock-name') || card).textContent.trim();
    const from  = tplBoxOf(card);
    const to    = tplStageBox();
    if (window.scrollY > 0) window.scrollTo({ top: 0, behavior: 'smooth' }); // bring the header back into view

    // 1) The card's own folder flips open.
    card.classList.add('is-opening');

    // 2) A copy grows from the card to fill the area below the filters; its big folder opens.
    const zoom = tplMakeZoom(color, name, 'Opening folder…', from, false);
    requestAnimationFrame(function () {
        requestAnimationFrame(function () {
            zoom.classList.add('is-expanded');
            tplPlace(zoom, to);
        });
    });
    setTimeout(function () { const f = zoom.querySelector('.tpl-folder'); if (f) f.classList.add('is-open'); }, 220);

    // 3) Loaded and fully open: the folder lifts away and the templates rise out of it.
    let loaded = false, opened = false;
    function reveal() {
        if (!loaded || !opened) return;
        zoom.classList.add('is-done');
        setTimeout(function () { zoom.remove(); }, 380);
    }
    setTimeout(function () { opened = true; reveal(); }, 700);
    setTimeout(function () { if (document.body.contains(zoom)) zoom.remove(); }, 10000); // never leave it stuck
    loadPage(link.href, { overlay: false, entry: 'folder', scroll: false, afterSwap: function () { loaded = true; reveal(); } });
}

document.querySelectorAll('.tpl-section-link, .tpl-dock-folder').forEach(function (link) {
    link.addEventListener('click', openSectionFolder);
});

// The All sections button inside a folder: the section folds back into its folder right
// away (no loading screen), then the cards page shrinks it onto its card.
function closeSectionFolder(e) {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (typeof loadPage !== 'function') return;
    const link = e.currentTarget;
    e.preventDefault();
    if (tplReduceMotion()) {
        loadPage(link.href);
        return;
    }
    if (window.scrollY > 0) window.scrollTo({ top: 0, behavior: 'smooth' });
    const zoom = tplMakeZoom(link.dataset.folderColor || '#7f1d1d', link.dataset.sectionName || '', 'Closing folder…', tplStageBox(), true);
    zoom.classList.add('is-folding-in', 'is-pending-close');
    setTimeout(function () { if (document.body.contains(zoom)) zoom.remove(); }, 10000);
    loadPage(link.href, { overlay: false, entry: 'none', scroll: false });
}

document.querySelectorAll('.tpl-back-link').forEach(function (link) {
    link.addEventListener('click', closeSectionFolder);
});

// Back on the cards (from All sections, the sidebar or the browser's Back):
// if we came from a section, it shrinks back onto its card and the flap closes.
(function () {
    const grid = document.querySelector('.tpl-section-card');
    const pending = document.querySelector('.tpl-folder-zoom.is-pending-close');
    let fromId = null;
    try {
        const from = new URL(window.lastAjaxFromUrl || '', window.location.href);
        if (/\/templates\/?$/.test(from.pathname) && from.searchParams.get('category')) fromId = from.searchParams.get('category');
    } catch (err) { /* no previous page */ }
    window.lastAjaxFromUrl = null;

    const card = grid && fromId ? document.querySelector('.tpl-section-card[data-section-id=\"' + fromId + '\"]') : null;
    if (!card) {
        if (pending) pending.remove();
        return;
    }
    if (tplReduceMotion()) {
        if (pending) pending.remove();
        return;
    }

    const color = (card.querySelector('.tpl-folder') || card).style.getPropertyValue('--folder') || '#7f1d1d';
    const name  = card.querySelector('.tpl-section-name').textContent;
    // Sidebar / Back button: no folder on screen yet — start from the open, full-size one.
    const zoom = pending || tplMakeZoom(color, name, 'Closing folder…', tplStageBox(), true);
    zoom.classList.remove('is-pending-close', 'is-folding-in');
    card.classList.add('is-opening'); // the card's folder waits open for its contents

    requestAnimationFrame(function () {
        requestAnimationFrame(function () {
            zoom.classList.add('is-shrinking');
            tplPlace(zoom, tplBoxOf(card));
            const f = zoom.querySelector('.tpl-folder');
            setTimeout(function () { if (f) f.classList.remove('is-open'); }, 160);
        });
    });
    setTimeout(function () {
        zoom.classList.add('is-done');
        card.classList.remove('is-opening');
        card.classList.add('is-closing');
        setTimeout(function () { zoom.remove(); }, 300);
        setTimeout(function () { card.classList.remove('is-closing'); }, 800);
    }, 560);
})();

initTemplateDragDrop();
initLiveSearch('templateSearchInput', 'filterForm');
</script>";
include APPPATH . 'Views/layout/footer.php'; ?>

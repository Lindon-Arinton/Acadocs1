<?php
$condCfg = [
    'Excellent' => ['cond-excellent','bi-star-fill'],
    'Good'      => ['cond-good',     'bi-check-circle-fill'],
    'Fair'      => ['cond-fair',     'bi-dash-circle-fill'],
    'Poor'      => ['cond-poor',     'bi-exclamation-triangle-fill'],
];
include APPPATH . 'Views/layout/header.php';
?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3" style="position:relative;z-index:1">
    <div>
      <h4><i class="bi bi-building me-2"></i>Property Management</h4>
      <p>Grade &amp; section asset inventory and condition tracking</p>
    </div>
    <?php if (hasRole('teacher')): ?>
    <button class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);"
            data-bs-toggle="modal" data-bs-target="#addItemModal">
      <i class="bi bi-plus-lg me-1"></i>Add Item
    </button>
    <?php endif; ?>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-check-circle-fill"></i><?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Condition summary -->
<div class="row g-3 mb-4">
  <?php foreach (['Excellent'=>['#d1fae5','#065f46'],'Good'=>['#dbeafe','#1e40af'],'Fair'=>['#fef9c3','#713f12'],'Poor'=>['#fee2e2','#991b1b']] as $cond=>[$bg,$tc]): ?>
  <div class="col-6 col-xl-3">
    <div class="card">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="kpi-icon" style="background:<?= $bg ?>">
          <i class="bi <?= $condCfg[$cond][1] ?>" style="color:<?= $tc ?>;font-size:1.1rem;"></i>
        </div>
        <div>
          <div style="font-size:1.6rem;font-weight:700;color:<?= $tc ?>"><?= $condStats[$cond] ?? 0 ?></div>
          <div class="text-muted" style="font-size:.78rem"><?= $cond ?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card mb-4">
  <div class="card-body py-3">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <form method="GET" action="<?= base_url('property-management') ?>" id="filterForm" class="d-flex align-items-center gap-3 flex-wrap">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-mortarboard text-muted"></i>
          <div class="maroon-select maroon-select-sm" style="width:auto;">
            <select name="grade" class="maroon-select-native" onchange="this.form.requestSubmit()">
              <option value="all" <?= $grade==='all'?'selected':'' ?>>All Grades</option>
              <?php foreach ($grades as $g): ?>
              <option value="<?= e($g) ?>" <?= $grade===$g?'selected':'' ?>><?= e($g) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
            <div class="maroon-select-panel"></div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-funnel text-muted"></i>
          <div class="maroon-select maroon-select-sm" style="width:auto;">
            <select name="condition" class="maroon-select-native" onchange="this.form.requestSubmit()">
              <option value="all" <?= $condition==='all'?'selected':'' ?>>All Conditions</option>
              <?php foreach ($conditions as $c): ?>
              <option value="<?= $c ?>" <?= $condition===$c?'selected':'' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
            <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
            <div class="maroon-select-panel"></div>
          </div>
        </div>
        <div class="input-group input-group-sm" style="max-width:220px;">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="q" id="propSearchInput" value="<?= e($search) ?>"
                 class="form-control border-start-0 ps-0" placeholder="Search section, item...">
        </div>
        <div class="maroon-select maroon-select-sm" style="width:auto;">
          <select name="sort" class="maroon-select-native" onchange="this.form.requestSubmit()">
            <option value="grade_az" <?= $sort==='grade_az' ? 'selected' : '' ?>>Grade A-Z</option>
            <option value="item_az"  <?= $sort==='item_az'  ? 'selected' : '' ?>>Item Name A-Z</option>
            <option value="condition" <?= $sort==='condition' ? 'selected' : '' ?>>Condition</option>
            <option value="newest"   <?= $sort==='newest'   ? 'selected' : '' ?>>Recently Added</option>
          </select>
          <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
          <div class="maroon-select-panel"></div>
        </div>
      </form>
      <div class="ms-auto d-flex gap-2 align-items-center">
        <span class="text-muted" style="font-size:.78rem;"><?= count($items) ?> items</span>
        <button class="btn btn-outline-secondary btn-sm" onclick="exportTable('prop-table','properties')">
          <i class="bi bi-download me-1"></i>Export
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Table -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table mb-0" id="prop-table">
        <thead>
          <tr>
            <th>Grade</th><th>Section</th><th>Item</th>
            <th class="text-center">Condition</th>
            <th>Date Added</th>
            <?php if (hasRole('teacher')): ?><th></th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item):
            [$cls,$ico] = $condCfg[$item['condition_status']] ?? ['cond-good','bi-circle'];
          ?>
          <tr>
            <td class="text-muted" style="font-size:.78rem"><?= e($item['grade']) ?></td>
            <td><?= e($item['section']) ?></td>
            <td class="fw-semibold"><?= e($item['item_name']) ?></td>
            <td class="text-center">
              <span class="badge <?= $cls ?>">
                <i class="bi <?= $ico ?> me-1"></i><?= e($item['condition_status']) ?>
              </span>
            </td>
            <td class="text-muted" style="font-size:.78rem"><?= date('M d, Y', strtotime($item['created_at'])) ?></td>
            <?php if (hasRole('teacher')): ?>
            <td>
              <form method="POST" action="<?= base_url('property-management') ?>" class="ajax-form"
                    data-confirm-title="Delete this item?">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                <button class="btn btn-ghost btn-sm text-danger"><i class="bi bi-trash"></i></button>
              </form>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($items)): ?>
          <tr><td colspan="6" class="text-center py-5">
            <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
            <span class="text-muted">No items found.</span>
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Property Item</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('property-management') ?>" class="ajax-form">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label">Grade</label>
              <input type="text" name="grade" class="form-control" list="gradeList" required>
              <datalist id="gradeList">
                <?php foreach ($grades as $g): ?><option value="<?= e($g) ?>"><?php endforeach; ?>
              </datalist>
            </div>
            <div class="col-6">
              <label class="form-label">Section</label>
              <input type="text" name="section" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label">Item Name</label>
              <input type="text" name="item_name" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label">Condition</label>
              <div class="maroon-select" style="width:100%;">
                <select name="condition_status" class="maroon-select-native">
                  <option>Excellent</option><option>Good</option><option>Fair</option><option>Poor</option>
                </select>
                <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
                <div class="maroon-select-panel"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Item</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extraScript = <<<'HTML'
<script>
function exportTable(tableId, filename) {
    const rows = [...document.getElementById(tableId).querySelectorAll('tr')].map(r =>
        [...r.querySelectorAll('th,td')].map(c => JSON.stringify(c.innerText.trim())).join(',')
    );
    const a = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(new Blob([rows.join('\\n')],{type:'text/csv'})),
        download: filename+'.csv'
    });
    a.click();
}
initLiveSearch('propSearchInput', 'filterForm');
</script>
HTML;
include APPPATH . 'Views/layout/footer.php';
?>

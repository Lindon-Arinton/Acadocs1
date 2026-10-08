<?php
$condCfg = [
    'Serviceable'     => ['cond-serviceable',     'bi-check-circle-fill'],
    'Non-serviceable' => ['cond-non-serviceable', 'bi-exclamation-triangle-fill'],
];
$parCfg = [
    'Pending'  => ['badge-pending',  'bi-hourglass-split'],
    'Approved' => ['badge-reviewed', 'bi-check2-circle'],
    'Returned' => ['badge-returned', 'bi-arrow-return-left'],
];
$isAdas    = hasRole('adas');
$isTeacher = hasRole('teacher');
$me        = currentUser();

// ADAS can change any item; a teacher only the items issued to them (or
// unassigned items they added themselves) — mirrors Properties::findManageable().
$canChange = static function (array $item) use ($isAdas, $isTeacher, $me): bool {
    if ($isAdas) {
        return true;
    }
    if (! $isTeacher) {
        return false;
    }
    return (int) $item['issued_to'] === (int) $me['id']
        || ($item['issued_to'] === null && $item['uploaded_by'] === $me['name']);
};

// Grade / section / item / quantity / acquisition / notes / issued-to fields,
// shared by the Add and Edit modals. $p prefixes the element ids.
$itemFields = static function (string $p) use ($sectionsByGrade, $acquisitions, $teachers, $isAdas): void { ?>
            <div class="col-6">
              <label class="form-label">Grade</label>
              <div class="maroon-select" style="width:100%;">
                <select name="grade" id="<?= $p ?>Grade" class="maroon-select-native item-grade" required
                        data-section-target="<?= $p ?>Section">
                  <?php foreach (array_keys($sectionsByGrade) as $g): ?>
                  <option value="<?= e($g) ?>" <?= $g === 'Grade 7' ? 'selected' : '' ?>><?= e($g) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
                <div class="maroon-select-panel"></div>
              </div>
            </div>
            <div class="col-6">
              <label class="form-label">Section</label>
              <div class="maroon-select" style="width:100%;">
                <select name="section" id="<?= $p ?>Section" class="maroon-select-native" required disabled>
                  <option value="" disabled selected>Select grade first</option>
                </select>
                <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
                <div class="maroon-select-panel"></div>
              </div>
            </div>
            <div class="col-8">
              <label class="form-label">Item Name</label>
              <input type="text" name="item_name" id="<?= $p ?>Name" class="form-control" maxlength="150" required>
            </div>
            <div class="col-4">
              <label class="form-label">Quantity</label>
              <input type="number" name="quantity" id="<?= $p ?>Qty" class="form-control" min="1" value="1" required>
            </div>
            <div class="col-6">
              <label class="form-label">Acquired As</label>
              <div class="maroon-select" style="width:100%;">
                <select name="acquisition_type" id="<?= $p ?>Acq" class="maroon-select-native item-acq" data-other-target="<?= $p ?>AcqOtherWrap">
                  <?php foreach ($acquisitions as $a): ?>
                  <option value="<?= e($a) ?>"><?= e($a === 'Other' ? 'Other (please specify)' : $a) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
                <div class="maroon-select-panel"></div>
              </div>
            </div>
            <div class="col-6 d-none" id="<?= $p ?>AcqOtherWrap">
              <label class="form-label">Please specify</label>
              <input type="text" name="acquisition_other" id="<?= $p ?>AcqOther" class="form-control" maxlength="150" placeholder="e.g. Transferred from Division">
            </div>
            <?php if ($isAdas): ?>
            <div class="col-12">
              <label class="form-label">Issued To <span class="text-muted fw-normal">(accountable teacher)</span></label>
              <div class="maroon-select" style="width:100%;">
                <select name="issued_to" id="<?= $p ?>IssuedTo" class="maroon-select-native">
                  <option value="">— Not yet issued —</option>
                  <?php foreach ($teachers as $t): ?>
                  <option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
                <div class="maroon-select-panel"></div>
              </div>
            </div>
            <?php endif; ?>
            <div class="col-12">
              <label class="form-label">Notes <span class="text-muted fw-normal">(optional)</span></label>
              <textarea name="notes" id="<?= $p ?>Notes" class="form-control" rows="2" placeholder="Brand, serial no., location in the room, etc."></textarea>
            </div>
<?php };

include APPPATH . 'Views/layout/header.php';
?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3" style="position:relative;z-index:1">
    <div>
      <h4><i class="bi bi-building me-2"></i>Property Management</h4>
      <p>Grade &amp; section inventory, accountability, and condition tracking</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <?php if ($isAdas): ?>
      <button class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);"
              data-bs-toggle="modal" data-bs-target="#issueParModal">
        <i class="bi bi-file-earmark-check me-1"></i>Issue PAR
      </button>
      <?php endif; ?>
      <?php if ($canManage): ?>
      <button class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);"
              data-bs-toggle="modal" data-bs-target="#addItemModal">
        <i class="bi bi-plus-lg me-1"></i>Add Item
      </button>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-check-circle-fill"></i><?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Summary -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
      ['Serviceable',     $condStats['Serviceable'] ?? 0,     'bi-check-circle-fill',         '#d1fae5', '#065f46', 'in working condition'],
      ['Non-serviceable', $condStats['Non-serviceable'] ?? 0, 'bi-exclamation-triangle-fill', '#fee2e2', '#991b1b', 'damaged or not working'],
      [$isTeacher ? 'PARs to Approve' : 'Pending PARs', $pendingParCount, 'bi-hourglass-split', '#fef9c3', '#713f12', $isTeacher ? 'waiting for your approval' : 'waiting for teacher approval'],
      ['Not Yet Issued',  $unassignedCount,                   'bi-person-dash',               '#e0e7ff', '#3730a3', 'no accountable teacher'],
  ];
  foreach ($cards as [$label, $count, $icon, $bg, $tc, $sub]): ?>
  <div class="col-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="kpi-icon" style="background:<?= $bg ?>">
          <i class="bi <?= $icon ?>" style="color:<?= $tc ?>;font-size:1.1rem;"></i>
        </div>
        <div>
          <div class="text-muted text-uppercase fw-semibold" style="font-size:.68rem;letter-spacing:.04em"><?= e($label) ?></div>
          <div style="font-size:1.6rem;font-weight:700;color:<?= $tc ?>;line-height:1.2"><?= (int) $count ?></div>
          <div class="text-muted" style="font-size:.75rem"><?= e($sub) ?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'items' ? 'active' : '' ?>" href="<?= base_url('property-management') ?>">
      <i class="bi bi-box-seam me-1"></i>Inventory
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'par' ? 'active' : '' ?>" href="<?= base_url('property-management?tab=par') ?>">
      <i class="bi bi-file-earmark-check me-1"></i>Acknowledgment Receipts (PAR)
      <?php if ($pendingParCount): ?><span class="badge badge-pending ms-1"><?= $pendingParCount ?></span><?php endif; ?>
    </a>
  </li>
</ul>

<?php if ($tab === 'items'): ?>
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
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-person-badge text-muted"></i>
          <div class="maroon-select maroon-select-sm" style="width:auto;">
            <select name="issued" class="maroon-select-native" onchange="this.form.requestSubmit()">
              <option value="all" <?= $issued==='all'?'selected':'' ?>>Issued to anyone</option>
              <?php if ($isTeacher): ?>
              <option value="me" <?= $issued==='me'?'selected':'' ?>>Issued to me</option>
              <?php endif; ?>
              <option value="none" <?= $issued==='none'?'selected':'' ?>>Not yet issued</option>
              <?php foreach ($teachers as $t): ?>
              <option value="<?= (int) $t['id'] ?>" <?= $issued===(string) $t['id']?'selected':'' ?>><?= e($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
            <div class="maroon-select-panel"></div>
          </div>
        </div>
        <div class="input-group input-group-sm" style="max-width:240px;">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="q" id="propSearchInput" value="<?= e($search) ?>"
                 class="form-control border-start-0 ps-0" placeholder="Search item, section, teacher...">
        </div>
        <div class="maroon-select maroon-select-sm" style="width:auto;">
          <select name="sort" class="maroon-select-native" onchange="this.form.requestSubmit()">
            <option value="grade_az"  <?= $sort==='grade_az'  ? 'selected' : '' ?>>Grade A-Z</option>
            <option value="item_az"   <?= $sort==='item_az'   ? 'selected' : '' ?>>Item Name A-Z</option>
            <option value="condition" <?= $sort==='condition' ? 'selected' : '' ?>>Condition</option>
            <option value="issued_to" <?= $sort==='issued_to' ? 'selected' : '' ?>>Issued To</option>
            <option value="newest"    <?= $sort==='newest'    ? 'selected' : '' ?>>Recently Added</option>
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

<!-- Inventory table -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table mb-0 align-middle" id="prop-table">
        <thead>
          <tr>
            <th>Grade</th><th>Section</th><th>Item</th>
            <th class="text-center">Qty</th>
            <th class="text-center">Condition</th>
            <th>Acquired As</th>
            <th>Issued To</th>
            <th>Date Added</th>
            <th class="text-end no-export">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item):
            [$cls, $ico] = $condCfg[$item['condition_status']] ?? ['cond-serviceable', 'bi-circle'];
            $editable    = $canChange($item);
            $itemJson    = e(json_encode([
                'id'                => (int) $item['id'],
                'grade'             => $item['grade'],
                'section'           => $item['section'],
                'item_name'         => $item['item_name'],
                'quantity'          => (int) $item['quantity'],
                'condition_status'  => $item['condition_status'],
                'acquisition_type'  => $item['acquisition_type'],
                'acquisition_other' => $item['acquisition_other'],
                'notes'             => $item['notes'],
                'issued_to'         => $item['issued_to'] !== null ? (int) $item['issued_to'] : '',
            ]));
          ?>
          <tr>
            <td class="text-muted" style="font-size:.78rem"><?= e($item['grade']) ?></td>
            <td><?= e($item['section']) ?></td>
            <td>
              <div class="fw-semibold"><?= e($item['item_name']) ?></div>
              <?php if ($item['notes']): ?>
              <div class="text-muted" style="font-size:.72rem;max-width:260px;white-space:normal;"><i class="bi bi-sticky me-1"></i><?= e($item['notes']) ?></div>
              <?php endif; ?>
            </td>
            <td class="text-center"><?= (int) $item['quantity'] ?></td>
            <td class="text-center">
              <span class="badge <?= $cls ?>"><i class="bi <?= $ico ?> me-1"></i><?= e($item['condition_status']) ?></span>
            </td>
            <td style="font-size:.8rem"><?= e(\App\Models\RoomPropertyModel::acquisitionLabel($item)) ?></td>
            <td style="font-size:.8rem">
              <?php if ($item['issued_to_name']): ?>
                <div><?= e($item['issued_to_name']) ?></div>
                <?php if ($item['par_no']): [$pc] = $parCfg[$item['par_status']] ?? ['badge-secondary']; ?>
                <span class="badge <?= $pc ?>" style="font-size:.62rem"><?= e($item['par_no']) ?> · <?= e($item['par_status']) ?></span>
                <?php else: ?>
                <span class="badge badge-secondary" style="font-size:.62rem">No PAR yet</span>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="text-muted" style="font-size:.78rem"><?= date('M d, Y', strtotime($item['created_at'])) ?></td>
            <td class="text-end no-export" style="white-space:nowrap">
              <button type="button" class="btn btn-ghost btn-sm" title="Condition history"
                      onclick="showConditionHistory(<?= (int) $item['id'] ?>)"><i class="bi bi-clock-history"></i></button>
              <?php if ($editable): ?>
              <button type="button" class="btn btn-ghost btn-sm" title="Update condition"
                      data-item="<?= $itemJson ?>" onclick="openConditionModal(this)"><i class="bi bi-arrow-repeat"></i></button>
              <button type="button" class="btn btn-ghost btn-sm" title="Edit item"
                      data-item="<?= $itemJson ?>" onclick="openEditModal(this)"><i class="bi bi-pencil"></i></button>
              <form method="POST" action="<?= base_url('property-management') ?>" class="ajax-form d-inline"
                    data-confirm-title="Delete this item?" data-confirm-text="Its condition history will be deleted too.">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <button class="btn btn-ghost btn-sm text-danger" title="Delete"><i class="bi bi-trash"></i></button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($items)): ?>
          <tr><td colspan="9" class="text-center py-5">
            <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
            <span class="text-muted">No items found.</span>
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php else: ?>
<!-- PAR list -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table mb-0 align-middle">
        <thead>
          <tr>
            <th>PAR No.</th>
            <?php if (! $isTeacher): ?><th>Issued To</th><?php endif; ?>
            <th class="text-center">Items</th>
            <th>Issued By</th>
            <th>Date Issued</th>
            <th class="text-center">Status</th>
            <th>Remarks</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pars as $par):
            [$pc, $pi] = $parCfg[$par['status']] ?? ['badge-secondary', 'bi-circle'];
            $mineToAnswer = $isTeacher && $par['status'] === 'Pending' && (int) $par['issued_to'] === $userId;
          ?>
          <tr>
            <td class="fw-semibold"><?= e($par['par_no']) ?></td>
            <?php if (! $isTeacher): ?><td><?= e($par['issued_to_name'] ?? '—') ?></td><?php endif; ?>
            <td class="text-center"><?= (int) $par['item_count'] ?> <span class="text-muted" style="font-size:.72rem">(<?= (int) $par['total_qty'] ?> pcs)</span></td>
            <td style="font-size:.82rem"><?= e($par['issued_by_name'] ?? '—') ?></td>
            <td class="text-muted" style="font-size:.78rem"><?= date('M d, Y', strtotime($par['created_at'])) ?></td>
            <td class="text-center">
              <span class="badge <?= $pc ?>"><i class="bi <?= $pi ?> me-1"></i><?= e($par['status']) ?></span>
              <?php if ($par['responded_at']): ?>
              <div class="text-muted" style="font-size:.66rem"><?= date('M d, Y', strtotime($par['responded_at'])) ?></div>
              <?php endif; ?>
            </td>
            <td class="text-muted" style="font-size:.78rem;max-width:220px;white-space:normal;"><?= e($par['remarks'] ?? '') ?></td>
            <td class="text-end" style="white-space:nowrap">
              <a href="<?= base_url('property-management/par/' . (int) $par['id']) ?>" target="_blank" rel="noopener"
                 class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>View</a>
              <?php if ($mineToAnswer): ?>
              <form method="POST" action="<?= base_url('property-management') ?>" class="ajax-form d-inline"
                    data-confirm-title="Approve <?= e($par['par_no']) ?>?"
                    data-confirm-text="You confirm that you received these items and are accountable for them.">
                <input type="hidden" name="action" value="par_respond">
                <input type="hidden" name="decision" value="approve">
                <input type="hidden" name="par_id" value="<?= (int) $par['id'] ?>">
                <button class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i>Approve</button>
              </form>
              <button type="button" class="btn btn-outline-danger btn-sm"
                      onclick="openReturnParModal(<?= (int) $par['id'] ?>, <?= e(json_encode($par['par_no'])) ?>)">
                <i class="bi bi-arrow-return-left me-1"></i>Return
              </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($pars)): ?>
          <tr><td colspan="8" class="text-center py-5">
            <i class="bi bi-file-earmark-check fs-1 d-block mb-2 text-muted"></i>
            <span class="text-muted"><?= $isAdas ? 'No PARs yet. Use "Issue PAR" to send a teacher their items for approval.' : 'No acknowledgment receipts yet.' ?></span>
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($canManage): ?>
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
            <?php $itemFields('add'); ?>
            <div class="col-12">
              <label class="form-label">Condition</label>
              <div class="cond-choice">
                <input type="radio" name="condition_status" id="addCondS" value="Serviceable" checked>
                <label for="addCondS" class="is-serviceable"><i class="bi bi-check-circle-fill"></i>Serviceable</label>
                <input type="radio" name="condition_status" id="addCondN" value="Non-serviceable">
                <label for="addCondN" class="is-non-serviceable"><i class="bi bi-exclamation-triangle-fill"></i>Non-serviceable</label>
              </div>
            </div>
            <?php if (! $isAdas): ?>
            <div class="col-12 text-muted" style="font-size:.78rem"><i class="bi bi-info-circle me-1"></i>This item will be issued to you.</div>
            <?php endif; ?>
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

<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Property Item</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('property-management') ?>" class="ajax-form" data-confirm-action="update">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="editId">
        <div class="modal-body">
          <div class="row g-3">
            <?php $itemFields('edit'); ?>
            <?php if ($isAdas): ?>
            <div class="col-12 text-muted" style="font-size:.75rem"><i class="bi bi-info-circle me-1"></i>Changing the teacher removes the item from its current PAR, so you'll need to issue a new one.</div>
            <?php endif; ?>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Update Condition Modal -->
<div class="modal fade" id="conditionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i>Update Condition</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('property-management') ?>" class="ajax-form" data-confirm-action="update">
        <input type="hidden" name="action" value="update_condition">
        <input type="hidden" name="id" id="condId">
        <div class="modal-body">
          <div class="mb-3">
            <div class="fw-semibold" id="condItemName"></div>
            <div class="text-muted" style="font-size:.78rem" id="condItemMeta"></div>
          </div>
          <label class="form-label">New condition</label>
          <div class="cond-choice mb-3">
            <input type="radio" name="condition_status" id="condS" value="Serviceable">
            <label for="condS" class="is-serviceable"><i class="bi bi-check-circle-fill"></i>Serviceable</label>
            <input type="radio" name="condition_status" id="condN" value="Non-serviceable">
            <label for="condN" class="is-non-serviceable"><i class="bi bi-exclamation-triangle-fill"></i>Non-serviceable</label>
          </div>
          <label class="form-label">Remarks <span class="text-muted fw-normal" id="condRemarksHint">(required when non-serviceable)</span></label>
          <textarea name="remarks" class="form-control" rows="2" maxlength="255" placeholder="e.g. Broken leg, screen not turning on, repaired by maintenance"></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Condition</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($isAdas): ?>
<!-- Issue PAR Modal -->
<div class="modal fade" id="issueParModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-file-earmark-check me-2"></i>Issue Property Acknowledgment Receipt</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('property-management') ?>" class="ajax-form"
            data-confirm-title="Issue this PAR?" data-confirm-text="The teacher will be notified to approve it.">
        <input type="hidden" name="action" value="generate_par">
        <div class="modal-body">
          <p class="text-muted" style="font-size:.82rem">
            The PAR lists every item issued to the teacher that isn't on a PAR yet. The teacher approves it to acknowledge receipt, or returns it with remarks.
          </p>
          <label class="form-label">Teacher</label>
          <div class="maroon-select" style="width:100%;">
            <select name="teacher_id" class="maroon-select-native" required>
              <option value="" disabled selected>Select a teacher</option>
              <?php foreach ($teachers as $t): $n = $unacknowledged[(int) $t['id']] ?? 0; ?>
              <option value="<?= (int) $t['id'] ?>" <?= $n ? '' : 'disabled' ?>>
                <?= e($t['name']) ?> — <?= $n ? $n . ' ' . ($n === 1 ? 'item' : 'items') . ' waiting' : 'nothing to issue' ?>
              </option>
              <?php endforeach; ?>
            </select>
            <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
            <div class="maroon-select-panel"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Issue PAR</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($isTeacher): ?>
<!-- Return PAR Modal -->
<div class="modal fade" id="returnParModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-arrow-return-left me-2"></i>Return <span id="returnParNo"></span></h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('property-management') ?>" class="ajax-form"
            data-confirm-title="Return this PAR?" data-confirm-text="The ADAS will be notified to correct it.">
        <input type="hidden" name="action" value="par_respond">
        <input type="hidden" name="decision" value="return">
        <input type="hidden" name="par_id" id="returnParId">
        <div class="modal-body">
          <label class="form-label">What needs to be corrected?</label>
          <textarea name="remarks" class="form-control" rows="3" maxlength="255" required
                    placeholder="e.g. Only 38 chairs were received, not 40."></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Return PAR</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Condition History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1"
     data-url="<?= base_url('property-management') ?>" data-sections="<?= e(json_encode($sectionsByGrade)) ?>">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-clock-history me-2"></i>Condition History</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="historyBody"></div>
    </div>
  </div>
</div>

<?php
$extraScript = <<<'HTML'
<script>
function exportTable(tableId, filename) {
    const rows = [...document.getElementById(tableId).querySelectorAll('tr')].map(r =>
        [...r.querySelectorAll('th:not(.no-export),td:not(.no-export)')].map(c => JSON.stringify(c.innerText.trim())).join(',')
    );
    const a = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(new Blob([rows.join('\\n')],{type:'text/csv'})),
        download: filename+'.csv'
    });
    a.click();
}
if (document.getElementById('propSearchInput')) initLiveSearch('propSearchInput', 'filterForm');

function syncMaroon(sel) {
    const root = sel && sel.closest('.maroon-select');
    if (root && root.maroonSelectSync) root.maroonSelectSync();
}
function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// Grade → Section: the Section dropdown only lists the chosen grade's sections.
const sectionsByGrade = JSON.parse(document.getElementById('historyModal').dataset.sections || '{}');

function fillSections(gradeSel, selected) {
    const sectionSel = document.getElementById(gradeSel.dataset.sectionTarget);
    const sections = sectionsByGrade[gradeSel.value] || [];
    sectionSel.innerHTML = '';
    sectionSel.add(new Option(sections.length ? 'Select section' : 'No sections for this grade', '', !selected, !selected));
    sectionSel.options[0].disabled = true;
    sections.forEach(s => sectionSel.add(new Option(s, s, s === selected, s === selected)));
    sectionSel.disabled = sections.length === 0;
    syncMaroon(sectionSel);
}
// "Other" acquisition shows the "please specify" box.
function toggleAcqOther(acqSel) {
    const wrap = document.getElementById(acqSel.dataset.otherTarget);
    const isOther = acqSel.value === 'Other';
    wrap.classList.toggle('d-none', !isOther);
    wrap.querySelector('input').required = isOther;
}
document.querySelectorAll('.item-grade').forEach(sel => {
    sel.addEventListener('change', () => fillSections(sel));
    fillSections(sel);
});
document.querySelectorAll('.item-acq').forEach(sel => {
    sel.addEventListener('change', () => toggleAcqOther(sel));
    toggleAcqOther(sel);
});

function openEditModal(btn) {
    const item = JSON.parse(btn.dataset.item);
    const set = (id, v) => { const el = document.getElementById(id); if (!el) return; el.value = v ?? ''; syncMaroon(el); };
    document.getElementById('editId').value = item.id;
    set('editGrade', item.grade);
    fillSections(document.getElementById('editGrade'), item.section);
    set('editName', item.item_name);
    set('editQty', item.quantity);
    set('editAcq', item.acquisition_type);
    toggleAcqOther(document.getElementById('editAcq'));
    set('editAcqOther', item.acquisition_other);
    set('editIssuedTo', item.issued_to);
    set('editNotes', item.notes);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editItemModal')).show();
}

function openConditionModal(btn) {
    const item = JSON.parse(btn.dataset.item);
    const modal = document.getElementById('conditionModal');
    document.getElementById('condId').value = item.id;
    document.getElementById('condItemName').textContent = item.item_name + ' (' + item.quantity + ')';
    document.getElementById('condItemMeta').textContent = item.grade + ' · ' + item.section + ' · currently ' + item.condition_status;
    // Pre-select the opposite condition; the current one can't be picked again.
    ['condS', 'condN'].forEach(id => {
        const r = document.getElementById(id);
        r.disabled = r.value === item.condition_status;
        r.checked  = r.value !== item.condition_status;
    });
    modal.querySelector('textarea[name="remarks"]').value = '';
    bootstrap.Modal.getOrCreateInstance(modal).show();
}

function openReturnParModal(id, parNo) {
    document.getElementById('returnParId').value = id;
    document.getElementById('returnParNo').textContent = parNo;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('returnParModal')).show();
}

function showConditionHistory(id) {
    const body = document.getElementById('historyBody');
    body.innerHTML = '<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('historyModal')).show();
    fetch(document.getElementById('historyModal').dataset.url + '/' + id + '/history', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (data.status !== 'success') throw new Error(data.message);
            const badge = s => '<span class="badge ' + (s === 'Non-serviceable' ? 'cond-non-serviceable' : 'cond-serviceable') + '">' + escapeHtml(s) + '</span>';
            const head = '<div class="mb-3"><div class="fw-semibold">' + escapeHtml(data.item.name) + '</div>'
                + '<div class="text-muted" style="font-size:.78rem">' + escapeHtml(data.item.grade + ' · ' + data.item.section) + '</div></div>';
            if (!data.logs.length) {
                body.innerHTML = head + '<p class="text-muted mb-0">No condition changes recorded yet.</p>';
                return;
            }
            body.innerHTML = head + '<ul class="cond-timeline">' + data.logs.map(l =>
                '<li><div>' + (l.from ? badge(l.from) + ' <i class="bi bi-arrow-right mx-1 text-muted"></i> ' : '') + badge(l.to) + '</div>'
                + (l.remarks ? '<div style="font-size:.82rem" class="mt-1">' + escapeHtml(l.remarks) + '</div>' : '')
                + '<div class="text-muted" style="font-size:.72rem">' + escapeHtml(l.by) + ' · ' + escapeHtml(l.at) + '</div></li>'
            ).join('') + '</ul>';
        })
        .catch(() => { body.innerHTML = '<p class="text-danger mb-0">Could not load the history. Please try again.</p>'; });
}
</script>
HTML;
include APPPATH . 'Views/layout/footer.php';
?>

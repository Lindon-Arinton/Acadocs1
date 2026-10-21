<?php
$condCfg = [
    'Working'     => ['cond-serviceable',     'bi-check-circle-fill'],
    'Not Working' => ['cond-non-serviceable', 'bi-exclamation-triangle-fill'],
];
// PAR statuses as shown on screen: an approved PAR means the adviser
// confirmed they received the items.
$parCfg = [
    'Pending'  => ['badge-pending',  'bi-hourglass-split',   'Awaiting confirmation'],
    'Approved' => ['badge-reviewed', 'bi-check2-circle',     'Received'],
    'Returned' => ['badge-returned', 'bi-arrow-return-left', 'Returned'],
];
$concernCfg = [
    'Open'     => ['badge-pending',  'bi-flag-fill'],
    'Resolved' => ['badge-reviewed', 'bi-check2-circle'],
];
$isAdas    = hasRole('adas');
$isTeacher = hasRole('teacher');

// Grade / section / item / quantity / acquisition / adviser / notes fields,
// shared by the ADAS's Add and Edit modals. $p prefixes the element ids.
$itemFields = static function (string $p) use ($sectionsByGrade, $acquisitions, $teachers): void { ?>
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
              <label class="form-label">Section (room)</label>
              <div class="maroon-select" style="width:100%;">
                <select name="section" id="<?= $p ?>Section" class="maroon-select-native item-section" required disabled
                        data-adviser-target="<?= $p ?>IssuedTo">
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
            <div class="col-12">
              <label class="form-label">Accountable Adviser</label>
              <div class="maroon-select" style="width:100%;">
                <select name="issued_to" id="<?= $p ?>IssuedTo" class="maroon-select-native">
                  <option value="">— Section's adviser (automatic) —</option>
                  <?php foreach ($teachers as $t): ?>
                  <option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
                <div class="maroon-select-panel"></div>
              </div>
              <div class="text-muted mt-1" style="font-size:.75rem" id="<?= $p ?>IssuedToHint"></div>
            </div>
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
      <h4><i class="bi bi-building me-2"></i><?= $isTeacher ? 'My Room Property' : 'Property Management' ?></h4>
      <p><?= $isTeacher
          ? 'Property issued to you by the ADAS — confirm what you received and report any concern'
          : 'Room property audit, distribution to advisers, and condition checks' ?></p>
    </div>
    <?php if ($isAdas): ?>
    <div class="d-flex gap-2 flex-wrap">
      <button class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);"
              data-bs-toggle="modal" data-bs-target="#issueParModal">
        <i class="bi bi-file-earmark-check me-1"></i>Issue PAR
      </button>
      <button class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);"
              data-bs-toggle="modal" data-bs-target="#addItemModal">
        <i class="bi bi-plus-lg me-1"></i>Add Item
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

<?php if ($isTeacher && $pendingParCount && $tab !== 'par'): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-hourglass-split"></i>
  <div class="flex-grow-1">
    The ADAS issued you <?= $pendingParCount === 1 ? 'a receipt' : $pendingParCount . ' receipts' ?> for room property. Please check the items and confirm that you received them.
  </div>
  <a href="<?= base_url('property-management?tab=par') ?>" class="btn btn-sm btn-primary">Review</a>
</div>
<?php endif; ?>

<!-- Summary -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
      ['Working',       $condStats['Working'] ?? 0,     'bi-check-circle-fill',         '#d1fae5', '#065f46', 'in working condition'],
      ['Not Working',   $condStats['Not Working'] ?? 0, 'bi-exclamation-triangle-fill', '#fee2e2', '#991b1b', 'damaged or not working'],
      ['Open Concerns', $openConcernCount,              'bi-flag-fill',                 '#fef9c3', '#713f12', $isTeacher ? 'you reported, not yet resolved' : 'reported by advisers'],
      $isTeacher
          ? ['To Confirm', $pendingParCount, 'bi-hourglass-split', '#e0e7ff', '#3730a3', 'receipts waiting for you']
          : ['No Adviser', $unassignedCount, 'bi-person-dash',     '#e0e7ff', '#3730a3', 'items not yet distributed'],
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
      <i class="bi bi-box-seam me-1"></i><?= $isTeacher ? 'My Property' : 'Inventory' ?>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'par' ? 'active' : '' ?>" href="<?= base_url('property-management?tab=par') ?>">
      <i class="bi bi-file-earmark-check me-1"></i>Acknowledgment Receipts (PAR)
      <?php if ($pendingParCount): ?><span class="badge badge-pending ms-1"><?= $pendingParCount ?></span><?php endif; ?>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'concerns' ? 'active' : '' ?>" href="<?= base_url('property-management?tab=concerns') ?>">
      <i class="bi bi-flag me-1"></i><?= $isTeacher ? 'My Concerns' : 'Concerns' ?>
      <?php if ($openConcernCount): ?><span class="badge badge-pending ms-1"><?= $openConcernCount ?></span><?php endif; ?>
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
            <select name="grade" class="maroon-select-native" onchange="this.form.section.value = 'all'; this.form.requestSubmit()">
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
          <i class="bi bi-door-open text-muted"></i>
          <div class="maroon-select maroon-select-sm" style="width:auto;">
            <select name="section" class="maroon-select-native" onchange="this.form.requestSubmit()">
              <option value="all" <?= $section === 'all' ? 'selected' : '' ?>>All Sections</option>
              <?php foreach ($sectionOptions as $o): ?>
              <option value="<?= e($o['section']) ?>" <?= $section === $o['section'] ? 'selected' : '' ?>>
                <?= e($o['section']) ?><?= $grade === 'all' ? ' (' . e($o['grade']) . ')' : '' ?>
              </option>
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
        <?php if (! $isTeacher): ?>
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-person-badge text-muted"></i>
          <div class="maroon-select maroon-select-sm" style="width:auto;">
            <select name="issued" class="maroon-select-native" onchange="this.form.requestSubmit()">
              <option value="all" <?= $issued==='all'?'selected':'' ?>>All advisers</option>
              <option value="none" <?= $issued==='none'?'selected':'' ?>>No adviser yet</option>
              <?php foreach ($teachers as $t): ?>
              <option value="<?= (int) $t['id'] ?>" <?= $issued===(string) $t['id']?'selected':'' ?>><?= e($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
            <div class="maroon-select-panel"></div>
          </div>
        </div>
        <?php endif; ?>
        <div class="input-group input-group-sm" style="max-width:240px;">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="q" id="propSearchInput" value="<?= e($search) ?>"
                 class="form-control border-start-0 ps-0" placeholder="<?= $isTeacher ? 'Search item, section...' : 'Search item, section, adviser...' ?>">
        </div>
        <div class="maroon-select maroon-select-sm" style="width:auto;">
          <select name="sort" class="maroon-select-native" onchange="this.form.requestSubmit()">
            <option value="grade_az"  <?= $sort==='grade_az'  ? 'selected' : '' ?>>Grade A-Z</option>
            <option value="item_az"   <?= $sort==='item_az'   ? 'selected' : '' ?>>Item Name A-Z</option>
            <option value="condition" <?= $sort==='condition' ? 'selected' : '' ?>>Condition</option>
            <?php if (! $isTeacher): ?>
            <option value="issued_to" <?= $sort==='issued_to' ? 'selected' : '' ?>>Adviser</option>
            <?php endif; ?>
            <option value="newest"    <?= $sort==='newest'    ? 'selected' : '' ?>>Recently Added</option>
          </select>
          <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
          <div class="maroon-select-panel"></div>
        </div>
      </form>
      <div class="ms-auto d-flex gap-2 align-items-center">
        <span class="text-muted" style="font-size:.78rem;"><?= count($items) ?> items</span>
        <?php $exportQuery = ['grade' => $grade, 'section' => $section, 'condition' => $condition, 'issued' => $issued, 'q' => $search]; ?>
        <div class="dropdown">
          <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-download me-1"></i>Export
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" data-no-ajax href="<?= base_url('property-management/export') . '?' . http_build_query($exportQuery + ['format' => 'xlsx']) ?>">
              <i class="bi bi-file-earmark-excel me-2 text-success"></i>Excel (.xlsx)</a></li>
            <li><a class="dropdown-item" data-no-ajax href="<?= base_url('property-management/export') . '?' . http_build_query($exportQuery + ['format' => 'docx']) ?>">
              <i class="bi bi-file-earmark-word me-2 text-primary"></i>Word (.docx)</a></li>
          </ul>
        </div>
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
            <th><?= $isTeacher ? 'Receipt' : 'Adviser' ?></th>
            <th>Last Checked</th>
            <th class="text-end no-export">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item):
            [$cls, $ico] = $condCfg[$item['condition_status']] ?? ['cond-serviceable', 'bi-circle'];
            $openConcerns = $openByProperty[(int) $item['id']] ?? 0;
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
            [$pc, , $pLabel] = $item['par_no'] ? ($parCfg[$item['par_status']] ?? ['badge-secondary', '', $item['par_status']]) : ['badge-secondary', '', ''];
          ?>
          <tr>
            <td class="text-muted" style="font-size:.78rem"><?= e($item['grade']) ?></td>
            <td><?= e($item['section']) ?></td>
            <td>
              <div class="fw-semibold"><?= e($item['item_name']) ?></div>
              <?php if ($item['notes']): ?>
              <div class="text-muted" style="font-size:.72rem;max-width:260px;white-space:normal;"><i class="bi bi-sticky me-1"></i><?= e($item['notes']) ?></div>
              <?php endif; ?>
              <?php if ($openConcerns): ?>
              <a href="<?= base_url('property-management?tab=concerns') ?>" class="badge badge-pending text-decoration-none" style="font-size:.62rem">
                <i class="bi bi-flag-fill me-1"></i><?= $openConcerns ?> open <?= $openConcerns === 1 ? 'concern' : 'concerns' ?>
              </a>
              <?php endif; ?>
            </td>
            <td class="text-center"><?= (int) $item['quantity'] ?></td>
            <td class="text-center">
              <span class="badge <?= $cls ?>"><i class="bi <?= $ico ?> me-1"></i><?= e($item['condition_status']) ?></span>
            </td>
            <td style="font-size:.8rem"><?= e(\App\Models\RoomPropertyModel::acquisitionLabel($item)) ?></td>
            <td style="font-size:.8rem">
              <?php if ($isTeacher): ?>
                <?php if ($item['par_no']): ?>
                <span class="badge <?= $pc ?>" style="font-size:.66rem"><?= e($pLabel) ?></span>
                <div class="text-muted" style="font-size:.66rem"><?= e($item['par_no']) ?></div>
                <?php else: ?>
                <span class="badge badge-secondary" style="font-size:.66rem">Waiting for PAR</span>
                <?php endif; ?>
              <?php elseif ($item['issued_to_name']): ?>
                <div><?= e($item['issued_to_name']) ?></div>
                <?php if ($item['par_no']): ?>
                <span class="badge <?= $pc ?>" style="font-size:.62rem"><?= e($item['par_no']) ?> · <?= e($pLabel) ?></span>
                <?php else: ?>
                <span class="badge badge-secondary" style="font-size:.62rem">No PAR yet</span>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="text-muted" style="font-size:.78rem">
              <?php if ($item['last_checked_at']): ?>
              <?= date('M d, Y', strtotime($item['last_checked_at'])) ?>
              <?php if ($item['last_checked_by']): ?><div style="font-size:.68rem"><?= e($item['last_checked_by']) ?></div><?php endif; ?>
              <?php else: ?>
              Not yet audited
              <?php endif; ?>
            </td>
            <td class="text-end no-export" style="white-space:nowrap">
              <button type="button" class="btn btn-ghost btn-sm" title="Condition history"
                      onclick="showConditionHistory(<?= (int) $item['id'] ?>)"><i class="bi bi-clock-history"></i></button>
              <?php if ($isTeacher): ?>
              <button type="button" class="btn btn-outline-secondary btn-sm" title="Report a concern"
                      data-item="<?= $itemJson ?>" onclick="openConcernModal(this)"><i class="bi bi-flag me-1"></i>Report</button>
              <?php elseif ($isAdas): ?>
              <button type="button" class="btn btn-ghost btn-sm" title="Audit condition"
                      data-item="<?= $itemJson ?>" onclick="openConditionModal(this)"><i class="bi bi-clipboard-check"></i></button>
              <button type="button" class="btn btn-ghost btn-sm" title="Edit item"
                      data-item="<?= $itemJson ?>" onclick="openEditModal(this)"><i class="bi bi-pencil"></i></button>
              <form method="POST" action="<?= base_url('property-management') ?>" class="ajax-form d-inline"
                    data-confirm-title="Delete this item?" data-confirm-text="Its condition history and concerns will be deleted too.">
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
            <span class="text-muted"><?= $isTeacher ? 'No property has been issued to you yet.' : 'No items found.' ?></span>
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif ($tab === 'par'): ?>
<!-- PAR list -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table mb-0 align-middle">
        <thead>
          <tr>
            <th>PAR No.</th>
            <?php if (! $isTeacher): ?><th>Adviser</th><?php endif; ?>
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
            [$pc, $pi, $pLabel] = $parCfg[$par['status']] ?? ['badge-secondary', 'bi-circle', $par['status']];
            $mineToAnswer = $isTeacher && $par['status'] === 'Pending' && (int) $par['issued_to'] === $userId;
          ?>
          <tr>
            <td class="fw-semibold"><?= e($par['par_no']) ?></td>
            <?php if (! $isTeacher): ?><td><?= e($par['issued_to_name'] ?? '—') ?></td><?php endif; ?>
            <td class="text-center"><?= (int) $par['item_count'] ?> <span class="text-muted" style="font-size:.72rem">(<?= (int) $par['total_qty'] ?> pcs)</span></td>
            <td style="font-size:.82rem"><?= e($par['issued_by_name'] ?? '—') ?></td>
            <td class="text-muted" style="font-size:.78rem"><?= date('M d, Y', strtotime($par['created_at'])) ?></td>
            <td class="text-center">
              <span class="badge <?= $pc ?>"><i class="bi <?= $pi ?> me-1"></i><?= e($pLabel) ?></span>
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
                    data-confirm-title="Confirm receipt of <?= e($par['par_no']) ?>?"
                    data-confirm-text="You confirm that you received these items and are accountable for them.">
                <input type="hidden" name="action" value="par_respond">
                <input type="hidden" name="decision" value="approve">
                <input type="hidden" name="par_id" value="<?= (int) $par['id'] ?>">
                <button class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i>Confirm Received</button>
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
            <span class="text-muted"><?= $isAdas ? 'No PARs yet. Use "Issue PAR" to send an adviser their room property to confirm.' : 'No acknowledgment receipts yet.' ?></span>
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php else: ?>
<!-- Concerns -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table mb-0 align-middle">
        <thead>
          <tr>
            <th>Date</th>
            <th>Item</th>
            <?php if (! $isTeacher): ?><th>Reported By</th><?php endif; ?>
            <th>Concern</th>
            <th class="text-center">Status</th>
            <th>ADAS Response</th>
            <?php if ($isAdas): ?><th class="text-end">Actions</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($concerns as $c): [$cc, $ci] = $concernCfg[$c['status']] ?? ['badge-secondary', 'bi-circle']; ?>
          <tr>
            <td class="text-muted" style="font-size:.78rem;white-space:nowrap"><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
            <td>
              <div class="fw-semibold"><?= e($c['item_name']) ?> <span class="text-muted fw-normal">(<?= (int) $c['quantity'] ?>)</span></div>
              <div class="text-muted" style="font-size:.72rem"><?= e($c['grade']) ?> · <?= e($c['section']) ?> · <?= e($c['condition_status']) ?></div>
            </td>
            <?php if (! $isTeacher): ?><td style="font-size:.82rem"><?= e($c['reported_by_name'] ?? '—') ?></td><?php endif; ?>
            <td style="max-width:280px;white-space:normal;">
              <div class="fw-semibold" style="font-size:.8rem"><?= e($c['category']) ?></div>
              <div style="font-size:.8rem"><?= e($c['details']) ?></div>
            </td>
            <td class="text-center">
              <span class="badge <?= $cc ?>"><i class="bi <?= $ci ?> me-1"></i><?= e($c['status']) ?></span>
            </td>
            <td class="text-muted" style="font-size:.78rem;max-width:240px;white-space:normal;">
              <?php if ($c['response']): ?>
              <?= e($c['response']) ?>
              <div style="font-size:.68rem"><?= e($c['resolved_by_name'] ?? '') ?><?= $c['resolved_at'] ? ' · ' . date('M d, Y', strtotime($c['resolved_at'])) : '' ?></div>
              <?php endif; ?>
            </td>
            <?php if ($isAdas): ?>
            <td class="text-end" style="white-space:nowrap">
              <?php if ($c['status'] === 'Open'): ?>
              <button type="button" class="btn btn-primary btn-sm"
                      onclick="openResolveModal(<?= (int) $c['id'] ?>, <?= e(json_encode($c['item_name'] . ' — ' . $c['section'])) ?>)">
                <i class="bi bi-check2 me-1"></i>Resolve
              </button>
              <?php endif; ?>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($concerns)): ?>
          <tr><td colspan="7" class="text-center py-5">
            <i class="bi bi-flag fs-1 d-block mb-2 text-muted"></i>
            <span class="text-muted"><?= $isTeacher ? 'No concerns reported. Use "Report" on an item in My Property if something is wrong.' : 'No concerns reported by advisers.' ?></span>
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($isAdas): ?>
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
                <input type="radio" name="condition_status" id="addCondS" value="Working" checked>
                <label for="addCondS" class="is-serviceable"><i class="bi bi-check-circle-fill"></i>Working</label>
                <input type="radio" name="condition_status" id="addCondN" value="Not Working">
                <label for="addCondN" class="is-non-serviceable"><i class="bi bi-exclamation-triangle-fill"></i>Not Working</label>
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
            <div class="col-12 text-muted" style="font-size:.75rem"><i class="bi bi-info-circle me-1"></i>Changing the adviser removes the item from its current PAR, so you'll need to issue a new one.</div>
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

<!-- Audit Condition Modal -->
<div class="modal fade" id="conditionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-clipboard-check me-2"></i>Audit Condition</h6>
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
          <label class="form-label">Condition found</label>
          <div class="cond-choice mb-3">
            <input type="radio" name="condition_status" id="condS" value="Working">
            <label for="condS" class="is-serviceable"><i class="bi bi-check-circle-fill"></i>Working</label>
            <input type="radio" name="condition_status" id="condN" value="Not Working">
            <label for="condN" class="is-non-serviceable"><i class="bi bi-exclamation-triangle-fill"></i>Not Working</label>
          </div>
          <label class="form-label">Remarks <span class="text-muted fw-normal">(required when changing to Not Working)</span></label>
          <textarea name="remarks" class="form-control" rows="2" maxlength="200" placeholder="e.g. Broken leg, screen not turning on, repaired by maintenance"></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Audit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Issue PAR Modal -->
<div class="modal fade" id="issueParModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-file-earmark-check me-2"></i>Issue Property Acknowledgment Receipt</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('property-management') ?>" class="ajax-form"
            data-confirm-title="Issue this PAR?" data-confirm-text="The adviser will be notified to confirm they received the items.">
        <input type="hidden" name="action" value="generate_par">
        <div class="modal-body">
          <p class="text-muted" style="font-size:.82rem">
            The PAR lists every item distributed to the adviser that isn't on a PAR yet. The adviser confirms they received it, or returns it with remarks.
          </p>
          <label class="form-label">Adviser</label>
          <div class="maroon-select" style="width:100%;">
            <select name="teacher_id" class="maroon-select-native" required>
              <option value="" disabled selected>Select an adviser</option>
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

<!-- Resolve Concern Modal -->
<div class="modal fade" id="resolveModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-check2-circle me-2"></i>Resolve Concern</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('property-management') ?>" class="ajax-form"
            data-confirm-title="Mark as resolved?" data-confirm-text="The adviser will be notified with your response.">
        <input type="hidden" name="action" value="resolve_concern">
        <input type="hidden" name="concern_id" id="resolveConcernId">
        <div class="modal-body">
          <div class="fw-semibold mb-3" id="resolveItemName"></div>
          <label class="form-label">Response to the adviser</label>
          <textarea name="response" class="form-control" rows="3" maxlength="255" required
                    placeholder="e.g. Checked on Oct 22 — fan repaired. Audit updated to Working."></textarea>
          <div class="text-muted mt-2" style="font-size:.75rem"><i class="bi bi-info-circle me-1"></i>If the item's condition changed, also record it with <i class="bi bi-clipboard-check"></i> Audit in the inventory.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Mark Resolved</button>
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
          <label class="form-label">What doesn't match what you received?</label>
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

<!-- Report Concern Modal -->
<div class="modal fade" id="concernModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-flag me-2"></i>Report a Concern</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('property-management') ?>" class="ajax-form"
            data-confirm-title="Send this concern?" data-confirm-text="The ADAS will be notified.">
        <input type="hidden" name="action" value="report_concern">
        <input type="hidden" name="id" id="concernItemId">
        <div class="modal-body">
          <div class="mb-3">
            <div class="fw-semibold" id="concernItemName"></div>
            <div class="text-muted" style="font-size:.78rem" id="concernItemMeta"></div>
          </div>
          <label class="form-label">What is the concern about?</label>
          <div class="maroon-select mb-3" style="width:100%;">
            <select name="category" id="concernCategory" class="maroon-select-native" required>
              <?php foreach ($concernCategories as $cat): ?>
              <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
            <div class="maroon-select-panel"></div>
          </div>
          <label class="form-label">Details</label>
          <textarea name="details" class="form-control" rows="3" maxlength="1000" required
                    placeholder="e.g. The electric fan stopped working on Oct 20."></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Send to ADAS</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Condition History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1"
     data-url="<?= base_url('property-management') ?>"
     data-sections="<?= e(json_encode($sectionsByGrade)) ?>"
     data-advisers="<?= e(json_encode((object) $advisers)) ?>">
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
if (document.getElementById('propSearchInput')) initLiveSearch('propSearchInput', 'filterForm');

function syncMaroon(sel) {
    const root = sel && sel.closest('.maroon-select');
    if (root && root.maroonSelectSync) root.maroonSelectSync();
}
function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

const historyModalEl  = document.getElementById('historyModal');
const sectionsByGrade = JSON.parse(historyModalEl.dataset.sections || '{}');
const advisers        = JSON.parse(historyModalEl.dataset.advisers || '{}');

// Grade → Section: the Section dropdown only lists the chosen grade's sections.
function fillSections(gradeSel, selected) {
    const sectionSel = document.getElementById(gradeSel.dataset.sectionTarget);
    const sections = sectionsByGrade[gradeSel.value] || [];
    sectionSel.innerHTML = '';
    sectionSel.add(new Option(sections.length ? 'Select section' : 'No sections for this grade', '', !selected, !selected));
    sectionSel.options[0].disabled = true;
    sections.forEach(s => sectionSel.add(new Option(s, s, s === selected, s === selected)));
    sectionSel.disabled = sections.length === 0;
    syncMaroon(sectionSel);
    showAdviserHint(sectionSel);
}
// Section → Adviser: pick the section's adviser, and say who it is.
function showAdviserHint(sectionSel) {
    const hint = document.getElementById(sectionSel.dataset.adviserTarget + 'Hint');
    if (!hint) return;
    const adv = advisers[(sectionSel.value || '').trim().toLowerCase()];
    hint.textContent = !sectionSel.value ? ''
        : adv ? 'Adviser of ' + sectionSel.value + ': ' + adv.name
        : sectionSel.value + ' has no adviser on record — pick who is accountable.';
}
function applyAdviser(sectionSel) {
    const issued = document.getElementById(sectionSel.dataset.adviserTarget);
    const adv = advisers[(sectionSel.value || '').trim().toLowerCase()];
    if (issued) { issued.value = adv ? String(adv.id) : ''; syncMaroon(issued); }
    showAdviserHint(sectionSel);
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
document.querySelectorAll('.item-section').forEach(sel => {
    sel.addEventListener('change', () => applyAdviser(sel));
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
    ['condS', 'condN'].forEach(id => {
        const r = document.getElementById(id);
        r.checked = r.value === item.condition_status;
    });
    modal.querySelector('textarea[name="remarks"]').value = '';
    bootstrap.Modal.getOrCreateInstance(modal).show();
}

function openConcernModal(btn) {
    const item = JSON.parse(btn.dataset.item);
    const modal = document.getElementById('concernModal');
    document.getElementById('concernItemId').value = item.id;
    document.getElementById('concernItemName').textContent = item.item_name + ' (' + item.quantity + ')';
    document.getElementById('concernItemMeta').textContent = item.grade + ' · ' + item.section + ' · ' + item.condition_status;
    modal.querySelector('textarea[name="details"]').value = '';
    bootstrap.Modal.getOrCreateInstance(modal).show();
}

function openResolveModal(id, label) {
    const modal = document.getElementById('resolveModal');
    document.getElementById('resolveConcernId').value = id;
    document.getElementById('resolveItemName').textContent = label;
    modal.querySelector('textarea[name="response"]').value = '';
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
    bootstrap.Modal.getOrCreateInstance(historyModalEl).show();
    fetch(historyModalEl.dataset.url + '/' + id + '/history', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (data.status !== 'success') throw new Error(data.message);
            const badge = s => '<span class="badge ' + (s === 'Not Working' ? 'cond-non-serviceable' : 'cond-serviceable') + '">' + escapeHtml(s) + '</span>';
            const head = '<div class="mb-3"><div class="fw-semibold">' + escapeHtml(data.item.name) + '</div>'
                + '<div class="text-muted" style="font-size:.78rem">' + escapeHtml(data.item.grade + ' · ' + data.item.section) + '</div></div>';
            if (!data.logs.length) {
                body.innerHTML = head + '<p class="text-muted mb-0">No condition checks recorded yet.</p>';
                return;
            }
            body.innerHTML = head + '<ul class="cond-timeline">' + data.logs.map(l =>
                '<li><div>' + (l.from && l.from !== l.to ? badge(l.from) + ' <i class="bi bi-arrow-right mx-1 text-muted"></i> ' : '') + badge(l.to) + '</div>'
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

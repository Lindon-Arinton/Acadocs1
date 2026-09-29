<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h4><i class="bi bi-pencil-square me-2"></i>Enter MPS Scores</h4>
      <p>Summative Test 1, Summative Test 2 &amp; Term Examination — per grade level, subject &amp; section</p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= base_url('performance/mps/template?year=' . urlencode($year) . '&term=' . (int) $term) ?>" class="btn btn-sm btn-outline-light">
        <i class="bi bi-download me-1"></i>Download Template
      </a>
      <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#importMpsModal">
        <i class="bi bi-upload me-1"></i>Import from Excel
      </button>
      <a href="<?= base_url('teacher-dashboard') ?>" class="btn btn-sm btn-outline-light">
        <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
      </a>
    </div>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-check-circle-fill"></i><?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Year / Term filter -->
<div class="card mb-4">
  <div class="card-body py-3">
    <form method="GET" action="<?= base_url('performance/mps') ?>" class="d-flex align-items-center gap-3 flex-wrap">
      <div class="d-flex align-items-center gap-2">
        <label class="small fw-semibold text-muted mb-0">School Year:</label>
        <input type="text" name="year" value="<?= e($year) ?>" list="mpsYearOptions"
               pattern="\d{4}-\d{4}" placeholder="e.g. 2026-2027" required
               class="form-control form-control-sm" style="width:130px;"
               onblur="if(this.checkValidity() && this.value!==this.defaultValue) this.form.submit()"
               onkeydown="if(event.key==='Enter'){event.preventDefault(); if(this.checkValidity()) this.form.submit();}">
        <datalist id="mpsYearOptions">
          <?php foreach ($years as $y): ?>
          <option value="<?= e($y) ?>">
          <?php endforeach; ?>
        </datalist>
      </div>
      <div class="d-flex align-items-center gap-2">
        <label class="small fw-semibold text-muted mb-0">Term:</label>
        <div class="maroon-select maroon-select-sm" style="width:auto;">
          <select name="term" class="maroon-select-native" onchange="this.form.submit()">
            <?php foreach ($terms as $t): ?>
            <option value="<?= (int) $t ?>" <?= $t === $term ? 'selected' : '' ?>>Term <?= (int) $t ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
          <div class="maroon-select-panel"></div>
        </div>
      </div>
    </form>
  </div>
</div>

<?php if (empty($handledCells)): ?>
<div class="card mb-4">
  <div class="card-body text-center py-5 text-muted">
    <i class="bi bi-clipboard-x fs-1 d-block mb-3"></i>
    You have no subjects for Term <?= (int) $term ?>, SY <?= e($year) ?> yet —
    <a href="<?= base_url('profile?sy=' . urlencode($year) . '&term=' . (int) $term) ?>#subject-load">add your subject load</a>
    before entering MPS scores.
  </div>
</div>
<?php else: ?>
<form method="POST" action="<?= base_url('performance/mps') ?>">
  <input type="hidden" name="school_year" value="<?= e($year) ?>">
  <input type="hidden" name="term" value="<?= (int) $term ?>">

  <?php foreach ($periods as $shortKey => $label): ?>
  <div class="card mb-4">
    <div class="card-header py-3 text-white fw-bold" style="background:#800000;">
      <i class="bi bi-clipboard-data me-2"></i><?= e($label) ?>
    </div>
    <div class="card-body">
      <?php foreach ($gradeLevels as $grade): ?>
      <?php if (empty($sectionTree[$grade])) continue; ?>
      <h6 class="fw-bold text-muted mb-2 mt-2"><?= e($grade) ?></h6>
      <div class="row g-3 mb-3">
        <?php foreach ($subjects as $subject): ?>
        <?php if (empty($sectionTree[$grade][$subject])) continue; ?>
        <div class="col-sm-6 col-lg-4">
          <div class="border rounded-3 p-2 h-100">
            <div class="small fw-semibold text-muted mb-2"><?= e($subject) ?></div>
            <table class="table table-sm mb-0">
              <thead>
                <tr>
                  <th class="small text-muted fw-normal p-1">Section</th>
                  <th class="small text-muted fw-normal p-1 text-end" style="width:96px;">Score</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($sectionTree[$grade][$subject] as $cell): ?>
                <tr>
                  <td class="small p-1 align-middle text-truncate" style="max-width:0;" title="<?= e($cell['label']) ?>"><?= e($cell['label']) ?></td>
                  <td class="p-1">
                    <input type="number" step="0.01" min="0" max="100"
                           name="scores[<?= e($shortKey) ?>][<?= e($grade) ?>][<?= e($subject) ?>][<?= e($cell['section']) ?>]"
                           value="<?= e($existing[$shortKey][$grade][$subject][$cell['section']] ?? '') ?>"
                           class="form-control form-control-sm text-center" placeholder="—">
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="d-flex justify-content-end mb-4">
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-save me-1"></i>Save All Scores
    </button>
  </div>
</form>
<?php endif; ?>


<!-- Import from Excel Modal -->
<div class="modal fade" id="importMpsModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-upload me-2"></i>Import MPS Scores</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('performance/mps/import') ?>" class="ajax-form" enctype="multipart/form-data" data-confirm-title="Import this file?" data-confirm-text="Matching scores for the same school year, term, test period, grade level, and subject will be overwritten.">
        <div class="modal-body">
          <p class="text-muted" style="font-size:.82rem;">
            Upload the school's MPS workbook — the sheet with a grade-level &times; subject grid for each test period
            (Summative Test 1/2, Term Examination), same layout as the printed MPS report.
            Not sure of the format? <a href="<?= base_url('performance/mps/template?year=' . urlencode($year) . '&term=' . (int) $term) ?>">Download the template</a>.
          </p>
          <div class="row g-2 mb-2">
            <div class="col-6">
              <label class="form-label">School Year</label>
              <input type="text" name="school_year" value="<?= e($year) ?>" list="mpsYearOptions"
                     pattern="\d{4}-\d{4}" placeholder="e.g. 2026-2027" required
                     class="form-control form-control-sm">
            </div>
            <div class="col-6">
              <label class="form-label">Term</label>
              <div class="maroon-select maroon-select-sm" style="width:100%;">
                <select name="term" class="maroon-select-native" required>
                  <?php foreach ($terms as $t): ?>
                  <option value="<?= (int) $t ?>" <?= $t === $term ? 'selected' : '' ?>>Term <?= (int) $t ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
                <div class="maroon-select-panel"></div>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Excel file (.xlsx, .xls)</label>
            <input type="file" name="import_file" class="form-control" accept=".xlsx,.xls" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Import</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include APPPATH . 'Views/layout/footer.php'; ?>

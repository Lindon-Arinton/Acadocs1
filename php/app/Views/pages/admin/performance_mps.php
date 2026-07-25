<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h4><i class="bi bi-pencil-square me-2"></i>Enter MPS Scores</h4>
      <p>Summative Test 1, Summative Test 2 &amp; Term Examination — per grade level &amp; subject</p>
    </div>
    <a href="<?= base_url('performance') ?>" class="btn btn-sm btn-outline-light">
      <i class="bi bi-arrow-left me-1"></i>Back to Performance Analytics
    </a>
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
        <select name="year" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
          <?php foreach ($years as $y): ?>
          <option value="<?= e($y) ?>" <?= $y === $year ? 'selected' : '' ?>><?= e(str_replace('-', '–', $y)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="d-flex align-items-center gap-2">
        <label class="small fw-semibold text-muted mb-0">Term:</label>
        <select name="term" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
          <?php foreach ($terms as $t): ?>
          <option value="<?= (int) $t ?>" <?= $t === $term ? 'selected' : '' ?>>Term <?= (int) $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
  </div>
</div>

<form method="POST" action="<?= base_url('performance/mps') ?>">
  <input type="hidden" name="school_year" value="<?= e($year) ?>">
  <input type="hidden" name="term" value="<?= (int) $term ?>">

  <?php foreach ($periods as $shortKey => $label): ?>
  <div class="card mb-4">
    <div class="card-header py-3 text-white fw-bold" style="background:linear-gradient(135deg,#9d174d,#800000,#c2410c);">
      <i class="bi bi-clipboard-data me-2"></i><?= e($label) ?>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0 align-middle">
          <thead>
            <tr>
              <th style="min-width:90px;">Grade</th>
              <?php foreach ($subjects as $subject): ?>
              <th class="text-center" style="min-width:90px;"><?= e($subject) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($gradeLevels as $grade): ?>
            <tr>
              <td class="fw-semibold text-muted"><?= e($grade) ?></td>
              <?php foreach ($subjects as $subject): ?>
              <td>
                <input type="number" step="0.01" min="0" max="100"
                       name="scores[<?= e($shortKey) ?>][<?= e($grade) ?>][<?= e($subject) ?>]"
                       value="<?= e($existing[$shortKey][$grade][$subject] ?? '') ?>"
                       class="form-control form-control-sm text-center" placeholder="—">
              </td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="d-flex justify-content-end mb-4">
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-save me-1"></i>Save All Scores
    </button>
  </div>
</form>

<?php include APPPATH . 'Views/layout/footer.php'; ?>

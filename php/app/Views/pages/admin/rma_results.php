<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h4><i class="bi bi-calculator me-2"></i>Rapid Mathematics Assessment (RMA)</h4>
      <p>Grades 7 to 10 — results entered by each section's Math teacher</p>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body py-3">
    <form method="GET" action="<?= base_url('rma-results') ?>" class="d-flex align-items-center gap-3 flex-wrap">
      <input type="hidden" name="rma_period" value="<?= e($rma['period']) ?>">
      <div class="d-flex align-items-center gap-2">
        <label class="small fw-semibold text-muted mb-0">School Year:</label>
        <div class="maroon-select maroon-select-sm" style="width:auto;">
          <select name="year" class="maroon-select-native" onchange="this.form.rma_period.value = ''; this.form.requestSubmit()">
            <?php foreach ($years as $y): ?>
            <option value="<?= e($y) ?>" <?= $y === $rma['year'] ? 'selected' : '' ?>><?= e($y) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
          <div class="maroon-select-panel"></div>
        </div>
      </div>
      <span class="small text-muted ms-auto">Status is <strong>Data Validated</strong> when the counts add up and the mean score is in.</span>
    </form>
  </div>
</div>

<?php include APPPATH . 'Views/partials/rma_school_table.php'; ?>

<?php include APPPATH . 'Views/layout/footer.php'; ?>

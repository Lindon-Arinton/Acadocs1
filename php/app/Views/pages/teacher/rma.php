<?php include APPPATH . 'Views/layout/header.php'; ?>
<?php
use App\Models\RmaResultModel;

$val = static fn (?array $r, string $f) => $r === null || $r[$f] === null ? '' : (in_array($f, RmaResultModel::SCORE_FIELDS, true) ? rtrim(rtrim(number_format((float) $r[$f], 2, '.', ''), '0'), '.') : (string) $r[$f]);
$num = static fn (?float $v) => $v === null ? '' : rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h4><i class="bi bi-calculator me-2"></i>Rapid Mathematics Assessment (RMA)</h4>
      <p>Grades 7 to 10 — enter your Mathematics sections' results. Copy the values from your class scoresheet.</p>
    </div>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> mb-4"><?= e($flash['msg']) ?></div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-body py-3">
    <form method="GET" action="<?= base_url('rma') ?>" class="d-flex align-items-center gap-3 flex-wrap">
      <div class="d-flex align-items-center gap-2">
        <label class="small fw-semibold text-muted mb-0">School Year:</label>
        <div class="maroon-select maroon-select-sm" style="width:auto;">
          <select name="year" class="maroon-select-native" onchange="this.form.requestSubmit()">
            <?php foreach ($years as $y): ?>
            <option value="<?= e($y) ?>" <?= $y === $year ? 'selected' : '' ?>><?= e($y) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
          <div class="maroon-select-panel"></div>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <label class="small fw-semibold text-muted mb-0">Assessment:</label>
        <div class="maroon-select maroon-select-sm" style="width:auto;">
          <select name="period" class="maroon-select-native" onchange="this.form.requestSubmit()">
            <?php foreach (RmaResultModel::PERIODS as $key => $label): ?>
            <option value="<?= $key ?>" <?= $key === $period ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
          <div class="maroon-select-panel"></div>
        </div>
      </div>
    </form>
  </div>
</div>

<?php if (! $sections): ?>
<div class="card">
  <div class="card-body text-center text-muted py-5">
    <i class="bi bi-calculator fs-1 d-block mb-3"></i>
    You have no Mathematics sections in your subject load for SY <?= e($year) ?>.
    The RMA is entered by each section's Math teacher.
  </div>
</div>
<?php else: ?>
<form method="POST" action="<?= base_url('rma') ?>" class="ajax-form" id="rmaForm"
      data-confirm-title="Save RMA results?"
      data-confirm-text="<?= e(RmaResultModel::PERIODS[$period] . ', SY ' . $year . '. The principal sees them on the dashboard.') ?>">
  <input type="hidden" name="school_year" value="<?= e($year) ?>">
  <input type="hidden" name="period" value="<?= e($period) ?>">

  <div class="card mb-3">
    <div class="table-responsive">
      <table class="table table-bordered mb-0 align-middle rma-table rma-entry">
        <?php include APPPATH . 'Views/partials/rma_table_head.php'; ?>
        <tbody>
          <?php foreach ($sections as $s): foreach (RmaResultModel::SEXES as $i => $sex):
            $key = $s['grade'] . '|' . $s['section'] . '|' . $sex;
            $r   = $rows[$key] ?? null;
            $name = static fn (string $f) => 'rows[' . e($key) . '][' . $f . ']';
            [$statusLabel, $tone] = RmaResultModel::status($r);
          ?>
          <tr class="rma-row<?= $i === 0 ? ' rma-section-start' : '' ?>">
            <td class="text-nowrap"><?= e($s['grade']) ?></td>
            <td class="fw-semibold text-nowrap"><?= e(mb_strtoupper($s['section'])) ?></td>
            <td><?= e($sex) ?></td>
            <?php foreach (RmaResultModel::COUNT_FIELDS as $f): ?>
            <td><input type="number" min="0" step="1" inputmode="numeric" class="form-control form-control-sm rma-input" data-f="<?= $f ?>" name="<?= $name($f) ?>" value="<?= e($val($r, $f)) ?>"></td>
            <?php endforeach; ?>
            <?php foreach (array_keys(RmaResultModel::CONTENT) as $f): ?>
            <td><input type="number" min="0" step="0.01" inputmode="decimal" class="form-control form-control-sm rma-input" data-f="<?= $f ?>" name="<?= $name($f) ?>" value="<?= e($val($r, $f)) ?>"></td>
            <?php endforeach; ?>
            <td class="text-center fw-semibold rma-total" data-total="content"><?= e($num(RmaResultModel::contentTotal($r))) ?></td>
            <?php foreach (array_keys(RmaResultModel::COGNITIVE) as $f): ?>
            <td><input type="number" min="0" step="0.01" inputmode="decimal" class="form-control form-control-sm rma-input" data-f="<?= $f ?>" name="<?= $name($f) ?>" value="<?= e($val($r, $f)) ?>"></td>
            <?php endforeach; ?>
            <td class="text-center fw-semibold rma-total" data-total="cognitive"><?= e($num(RmaResultModel::cognitiveTotal($r))) ?></td>
            <td><input type="number" min="0" step="0.01" inputmode="decimal" class="form-control form-control-sm rma-input" data-f="mean_score" name="<?= $name('mean_score') ?>" value="<?= e($val($r, 'mean_score')) ?>"></td>
            <td class="text-nowrap"><span class="rma-status rma-status-<?= $tone ?>"><?= e($statusLabel) ?></span></td>
          </tr>
          <?php endforeach; endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
    <span class="small text-muted">Status shows <strong>Data Validated</strong> when learners assessed ≤ enrolment, the five proficiency levels add up to the learners assessed, and the mean score is in. Leave a row empty if it doesn't apply.</span>
    <button type="submit" class="btn btn-primary ms-auto"><i class="bi bi-check-lg me-1"></i>Save RMA Results</button>
  </div>
</form>
<?php endif; ?>

<?php
$extraScript = <<<'HTML'
<script>
// Live totals and Status while typing (same rules as RmaResultModel::status()).
(function () {
    const LEVELS = ['lvl_not_proficient', 'lvl_low_proficient', 'lvl_nearly_proficient', 'lvl_proficient', 'lvl_highly_proficient'];
    const CONTENT = ['cd_number_algebra', 'cd_measurement_geometry', 'cd_data_probability'];
    const COGNITIVE = ['cg_knowing', 'cg_interpreting', 'cg_applying', 'cg_reasoning'];
    const fmt = n => (Math.round(n * 100) / 100).toString();

    function refresh(row) {
        const v = f => row.querySelector('[data-f="' + f + '"]').value.trim();
        const sum = fs => { const vals = fs.map(v).filter(x => x !== ''); return vals.length ? vals.reduce((a, x) => a + parseFloat(x), 0) : null; };
        const c = sum(CONTENT), g = sum(COGNITIVE);
        row.querySelector('[data-total="content"]').textContent = c === null ? '' : fmt(c);
        row.querySelector('[data-total="cognitive"]').textContent = g === null ? '' : fmt(g);

        const all = row.querySelectorAll('.rma-input');
        const empty = [...all].every(i => i.value.trim() === '');
        let label = 'Data Validated', tone = 'ok';
        if (empty) { label = 'No data'; tone = 'none'; }
        else if (v('enrolment') === '' || v('assessed') === '' || v('mean_score') === '') { label = 'Incomplete'; tone = 'warn'; }
        else if (parseInt(v('assessed'), 10) > parseInt(v('enrolment'), 10)) { label = 'Assessed > enrolment'; tone = 'warn'; }
        else if (LEVELS.reduce((a, f) => a + (parseInt(v(f), 10) || 0), 0) !== parseInt(v('assessed'), 10)) { label = 'Levels ≠ assessed'; tone = 'warn'; }
        const badge = row.querySelector('.rma-status');
        badge.textContent = label;
        badge.className = 'rma-status rma-status-' + tone;
    }

    document.querySelectorAll('.rma-entry .rma-row').forEach(row => {
        row.addEventListener('input', () => refresh(row));
    });
})();
</script>
HTML;
include APPPATH . 'Views/layout/footer.php';
?>

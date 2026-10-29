<?php include APPPATH . 'Views/layout/header.php'; ?>
<?php
$fmt  = static fn (?float $v) => $v === null ? '' : number_format($v, 2);
$pill = static function (?float $v) use ($fmt): string {
    if ($v === null) {
        return '';
    }
    return '<span class="mps-pill ' . e(mpsDescriptor($v)['class'] ?? '') . '">' . $fmt($v) . '</span>';
};
$subjectsWithData = array_values(array_filter($subjects, static fn ($s) => ! empty($breakdown[$s])));
$hasData          = $subjectsWithData !== [];
?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h4><i class="bi bi-table me-2"></i>MPS Summary</h4>
      <p>Mean Percentage Score per grade level and subject — Term <?= (int) $term ?>, SY <?= e($year) ?></p>
    </div>
    <div class="d-flex gap-2 print-hide">
      <?php if ($hasData): ?>
      <button type="button" class="btn btn-sm btn-outline-light" onclick="mpsSummaryExport()"><i class="bi bi-download me-1"></i>Export</button>
      <button type="button" class="btn btn-sm btn-outline-light" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
      <?php endif; ?>
      <a href="<?= base_url('dashboard') ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
    </div>
  </div>
</div>

<!-- School year / term -->
<div class="card mb-4 print-hide">
  <div class="card-body py-3">
    <form method="GET" action="<?= base_url('performance/mps-summary') ?>" class="d-flex align-items-center gap-3 flex-wrap">
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
        <label class="small fw-semibold text-muted mb-0">Term:</label>
        <div class="maroon-select maroon-select-sm" style="width:auto;">
          <select name="term" class="maroon-select-native" onchange="this.form.requestSubmit()">
            <?php foreach ($terms as $t): ?>
            <option value="<?= (int) $t ?>" <?= $t === $term ? 'selected' : '' ?>>Term <?= (int) $t ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
          <div class="maroon-select-panel"></div>
        </div>
      </div>
      <span class="small text-muted ms-auto">Each value is the average of the grade level's sections. Click a value to see its sections.</span>
    </form>
  </div>
</div>

<?php if (! $hasData): ?>
<div class="card mb-4">
  <div class="card-body text-center py-5 text-muted">
    <i class="bi bi-table fs-1 d-block mb-3"></i>
    No MPS scores yet for Term <?= (int) $term ?>, SY <?= e($year) ?>. They appear here as teachers enter them on <strong>Enter MPS Scores</strong>.
  </div>
</div>
<?php endif; ?>

<!-- One table per test period, laid out like the school's summary sheet -->
<?php foreach ($periods as $key => $label): ?>
<div class="card mb-4 mps-summary-card">
  <div class="card-header bg-transparent fw-bold"><?= e(mb_strtoupper($label)) ?></div>
  <div class="table-responsive">
    <table class="table table-bordered mb-0 align-middle mps-summary-table" data-export-title="<?= e(mb_strtoupper($label)) ?>">
      <thead>
        <tr>
          <th class="mps-summary-grade"></th>
          <?php foreach ($subjects as $s): ?>
          <th class="text-center"><?= e($s) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($grades as $g): ?>
        <tr>
          <th class="mps-summary-grade"><?= e(mb_strtoupper($g)) ?></th>
          <?php foreach ($subjects as $s): $cell = $grid[$key][$g][$s] ?? null; ?>
          <?php if ($cell): ?>
          <td class="text-center mps-summary-cell" role="button" tabindex="0"
              data-subject="<?= e($s) ?>" data-grade="<?= e($g) ?>"
              title="<?= e($s . ' · ' . $g . ' — average of ' . $cell['sections'] . ' section' . ($cell['sections'] === 1 ? '' : 's') . '. Click for the breakdown.') ?>"><?= $pill($cell['mps']) ?></td>
          <?php else: ?>
          <td></td>
          <?php endif; ?>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>

<?php if ($hasData): ?>
<!-- Breakdown: raw MPS of each section, per subject -->
<div class="card mb-4" id="mpsBreakdown">
  <div class="card-header bg-transparent">
    <div class="fw-bold mb-2"><i class="bi bi-list-columns-reverse me-2"></i>Breakdown by Section</div>
    <div class="d-flex flex-wrap gap-2 print-hide" id="mpsSubjectPills">
      <?php foreach ($subjectsWithData as $i => $s): ?>
      <button type="button" class="chat-filter-pill<?= $i === 0 ? ' active' : '' ?>" data-subject="<?= e($s) ?>"><?= e($s) ?></button>
      <?php endforeach; ?>
    </div>
  </div>
  <?php foreach ($subjectsWithData as $i => $s): ?>
  <div class="mps-breakdown-pane<?= $i === 0 ? '' : ' d-none' ?>" data-subject="<?= e($s) ?>">
    <div class="table-responsive">
      <table class="table mb-0 align-middle mps-breakdown-table" data-export-title="<?= e('BREAKDOWN — ' . mb_strtoupper($s)) ?>">
        <thead>
          <tr>
            <th>Section</th><th>Teacher</th>
            <?php foreach ($periods as $label): ?><th class="text-center"><?= e($label) ?></th><?php endforeach; ?>
            <th class="text-center">Average</th><th>Descriptor</th>
          </tr>
        </thead>
        <tbody>
          <?php $lastGrade = null; foreach ($breakdown[$s] as $r): ?>
          <?php if ($r['grade'] !== $lastGrade): $lastGrade = $r['grade']; ?>
          <tr class="mps-breakdown-grade" data-grade="<?= e($r['grade']) ?>"><th colspan="<?= count($periods) + 4 ?>"><?= e(mb_strtoupper($r['grade'])) ?> · <?= e($s) ?></th></tr>
          <?php endif; ?>
          <tr data-grade="<?= e($r['grade']) ?>">
            <td class="fw-semibold"><?= e($r['section'] ?? '(no section on record)') ?></td>
            <td class="text-muted small"><?= e($r['teacher'] ?: '—') ?></td>
            <?php foreach (array_keys($periods) as $key): ?>
            <td class="text-center"><?= $r[$key] === null ? '<span class="text-muted">—</span>' : $fmt($r[$key]) ?></td>
            <?php endforeach; ?>
            <td class="text-center"><?= $pill($r['avg']) ?: '<span class="text-muted">—</span>' ?></td>
            <td class="small"><?= e(mpsDescriptor($r['avg'])['label'] ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>
  <div class="desc-legend px-3 py-2 border-top">
    <?php foreach (MPS_DESCRIPTORS as [$min, $label, $range]): ?>
    <span class="mps-pill desc-<?= strtolower($label) ?>"><?= $range ?> <?= $label ?></span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php
$exportName = json_encode('MPS_Summary_SY' . $year . '_Term' . $term);
$extraScript = <<<HTML
<script>
// Show one subject's section breakdown (from a pill, or a clicked summary value).
function mpsShowSubject(subject, grade) {
    document.querySelectorAll('#mpsSubjectPills .chat-filter-pill').forEach(function (b) {
        b.classList.toggle('active', b.dataset.subject === subject);
    });
    document.querySelectorAll('.mps-breakdown-pane').forEach(function (p) {
        p.classList.toggle('d-none', p.dataset.subject !== subject);
    });
    document.querySelectorAll('.mps-breakdown-pane tr.is-picked').forEach(function (r) { r.classList.remove('is-picked'); });
    if (grade) {
        var pane = document.querySelector('.mps-breakdown-pane[data-subject="' + CSS.escape(subject) + '"]');
        if (pane) pane.querySelectorAll('tr[data-grade="' + CSS.escape(grade) + '"]').forEach(function (r) { r.classList.add('is-picked'); });
    }
}

document.getElementById('mpsSubjectPills')?.addEventListener('click', function (e) {
    var btn = e.target.closest('.chat-filter-pill');
    if (btn) mpsShowSubject(btn.dataset.subject, null);
});

document.querySelectorAll('.mps-summary-cell').forEach(function (td) {
    var open = function () {
        mpsShowSubject(td.dataset.subject, td.dataset.grade);
        document.getElementById('mpsBreakdown').scrollIntoView({ behavior: 'smooth', block: 'start' });
    };
    td.addEventListener('click', open);
    td.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); } });
});

// CSV of the three summary tables and every subject's breakdown.
window.mpsSummaryExport = function () {
    var lines = [];
    document.querySelectorAll('.mps-summary-table, .mps-breakdown-table').forEach(function (t) {
        lines.push(JSON.stringify(t.dataset.exportTitle));
        t.querySelectorAll('tr').forEach(function (r) {
            lines.push([].map.call(r.querySelectorAll('th,td'), function (c) { return JSON.stringify(c.innerText.trim()); }).join(','));
        });
        lines.push('');
    });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob(['\\ufeff' + lines.join('\\r\\n')], { type: 'text/csv' }));
    a.download = {$exportName} + '.csv';
    a.click();
};
</script>
HTML;
include APPPATH . 'Views/layout/footer.php';
?>

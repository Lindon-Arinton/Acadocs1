<?php include APPPATH . 'Views/layout/header.php'; ?>
<?php
use App\Models\ReadingResultModel;

$tables  = ReadingResultModel::tables($language);
$pct     = static fn (?float $v) => $v === null ? '—' : rtrim(rtrim(number_format($v, 1), '0'), '.') . '%';
$query   = ['year' => $year, 'period' => $period, 'language' => $language];
$school  = $summary['school'];
$tested  = $school['tested_m'] + $school['tested_f'];
$enroled = $school['enrolled_m'] + $school['enrolled_f'];
$lvTotal = static fn (array $b, string $t, string $lv) => $b['tables'][$t][$lv]['m'] + $b['tables'][$t][$lv]['f'];

// Encoding progress: every section in the school, by status.
$progress = [];
$totals   = ['none' => 0, 'draft' => 0, 'submitted' => 0, 'approved' => 0];
foreach ($sections as $g => $list) {
    $progress[$g] = ['none' => 0, 'draft' => 0, 'submitted' => 0, 'approved' => 0];
    foreach ($list as $s) {
        $st  = $rows[$g . '|' . $s]['status'] ?? null;
        $key = match ($st) { 'Draft', 'Returned' => 'draft', 'Submitted' => 'submitted', 'Approved' => 'approved', default => 'none' };
        $progress[$g][$key]++;
        $totals[$key]++;
    }
}
$allSections = array_sum($totals);

// Which way is better for each level (the Beginning → Year End change column).
$goodUp   = ['independent', 'instructional', 'spcp'];
$goodDown = ['frustration', 'nonreader', 'down3', 'down2', 'smcm'];
?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h4><i class="bi bi-book-half me-2"></i>Reading Program (Phil-IRI)</h4>
      <p>School-wide reading assessment, Grades 7 to 10 — assign the encoders, follow the progress and review each section</p>
    </div>
    <a href="<?= base_url('reading/report?' . http_build_query($query)) ?>" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm"><i class="bi bi-printer me-1"></i>Print Report</a>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body py-3">
    <form method="GET" action="<?= base_url('reading-program') ?>" class="d-flex align-items-center gap-3 flex-wrap">
      <?php foreach (['year' => ['School Year', array_combine($years, $years), $year], 'period' => ['Period', ReadingResultModel::PERIODS, $period], 'language' => ['Language', array_combine(ReadingResultModel::LANGUAGES, ReadingResultModel::LANGUAGES), $language]] as $name => [$caption, $options, $current]): ?>
      <div class="d-flex align-items-center gap-2">
        <label class="small fw-semibold text-muted mb-0"><?= e($caption) ?>:</label>
        <div class="maroon-select maroon-select-sm" style="width:auto;">
          <select name="<?= $name ?>" class="maroon-select-native" onchange="this.form.requestSubmit()">
            <?php foreach ($options as $value => $text): ?>
            <option value="<?= e($value) ?>" <?= (string) $value === (string) $current ? 'selected' : '' ?>><?= e($text) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
          <div class="maroon-select-panel"></div>
        </div>
      </div>
      <?php endforeach; ?>
      <span class="small text-muted ms-auto">Charts count <strong>submitted</strong> and <strong>approved</strong> sections only.</span>
    </form>
  </div>
</div>

<!-- Who encodes -->
<div class="card mb-4">
  <div class="card-body">
    <div class="fw-bold mb-1"><i class="bi bi-person-check me-1"></i>Encoders</div>
    <div class="small text-muted mb-3">The assigned teacher encodes every section for that language. They get a "Phil-IRI" link in their sidebar.</div>
    <div class="row g-3">
      <?php foreach (ReadingResultModel::LANGUAGES as $lang): ?>
      <div class="col-md-6">
        <div class="reading-assign">
          <div class="fw-semibold mb-2">Phil-IRI <?= e($lang) ?></div>
          <?php if (! $assignments[$lang]): ?>
          <div class="small text-muted mb-2">No one assigned yet.</div>
          <?php endif; ?>
          <?php foreach ($assignments[$lang] as $a): ?>
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-person-circle text-muted"></i>
            <span class="flex-grow-1"><?= personLink((int) $a['user_id'], $a['name']) ?></span>
            <form method="POST" action="<?= base_url('reading-program') ?>" class="ajax-form" data-confirm-title="Remove <?= e($a['name']) ?>?" data-confirm-text="They stop encoding Phil-IRI <?= e($lang) ?>. What they already encoded stays.">
              <input type="hidden" name="action" value="unassign"><input type="hidden" name="assignment_id" value="<?= (int) $a['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove"><i class="bi bi-x-lg"></i></button>
            </form>
          </div>
          <?php endforeach; ?>
          <form method="POST" action="<?= base_url('reading-program') ?>" class="ajax-form d-flex gap-2 mt-2" data-confirm-title="Assign Phil-IRI <?= e($lang) ?>?" data-confirm-text="The teacher will be notified and can start encoding.">
            <input type="hidden" name="action" value="assign"><input type="hidden" name="language" value="<?= e($lang) ?>">
            <div class="maroon-select maroon-select-sm flex-grow-1">
              <select name="user_id" class="maroon-select-native" required>
                <option value="">Choose a teacher…</option>
                <?php $taken = array_column($assignments[$lang], 'user_id'); foreach ($teachers as $t): if (in_array($t['id'], $taken)) continue; ?>
                <option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
              <div class="maroon-select-panel"></div>
            </div>
            <button type="submit" class="btn btn-sm btn-primary text-nowrap"><i class="bi bi-plus-lg me-1"></i>Assign</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Headline figures -->
<div class="rma-stats mb-4">
  <div><div class="rma-stat-label">Sections approved</div><div class="rma-stat-value"><?= $totals['approved'] ?><span class="rma-stat-of"> / <?= $allSections ?></span></div><div class="rma-stat-sub"><?= $totals['submitted'] ?> waiting for your review</div></div>
  <div><div class="rma-stat-label">Not started</div><div class="rma-stat-value"><?= $totals['none'] ?></div><div class="rma-stat-sub"><?= $totals['draft'] ?> in draft or returned</div></div>
  <div><div class="rma-stat-label">Learners tested</div><div class="rma-stat-value"><?= number_format($tested) ?></div><div class="rma-stat-sub">of <?= number_format($enroled) ?> enrolled<?= $enroled ? ' (' . $pct(ReadingResultModel::pct($tested, $enroled)) . ')' : '' ?></div></div>
  <div><div class="rma-stat-label"><?= $language === 'Filipino' ? 'Malaya (Independent)' : 'Independent readers' ?></div><div class="rma-stat-value"><?= $pct(ReadingResultModel::pct($lvTotal($school, 'rp', 'independent'), $tested)) ?></div><div class="rma-stat-sub">Frustration + Non-reader: <?= $pct(ReadingResultModel::pct($lvTotal($school, 'rp', 'frustration') + $lvTotal($school, 'rp', 'nonreader'), $tested)) ?></div></div>
</div>

<?php
$levelSet = static function (string $t) use ($tables, $summary, $lvTotal) {
    // Worst level first so the bars read left → right towards the best level
    // (the GST bands are already listed worst first).
    $out = [];
    foreach ($t === 'gst' ? $tables[$t]['levels'] : array_reverse($tables[$t]['levels'], true) as $lv => $lvLabel) {
        $out[] = [
            'label'  => $lvLabel,
            'pct'    => array_values(array_map(static fn ($b) => ReadingResultModel::pct($lvTotal($b, $t, $lv), $b['tested_m'] + $b['tested_f']) ?? 0, $summary['grades'] + ['School' => $summary['school']])),
            'counts' => array_values(array_map(static fn ($b) => $lvTotal($b, $t, $lv), $summary['grades'] + ['School' => $summary['school']])),
        ];
    }

    return $out;
};
$compareSet = static function (string $t) use ($tables, $compare, $lvTotal) {
    $out = [];
    foreach (['Beginning', 'End'] as $p) {
        $b     = $compare[$p]['school'];
        $out[] = ['label' => ReadingResultModel::PERIODS[$p], 'data' => array_values(array_map(static fn ($lv) => ReadingResultModel::pct($lvTotal($b, $t, $lv), $b['tested_m'] + $b['tested_f']), array_keys($tables[$t]['levels'])))];
    }

    return $out;
};
$src = ' — ' . ReadingResultModel::PERIODS[$period] . ', SY ' . $year . ' (submitted and approved sections).';
?>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card h-100"><div class="card-body">
      <div class="fw-bold">Encoding progress by grade</div>
      <div class="small text-muted mb-2">Sections per status</div>
      <div class="dash-chart" style="height:230px"><canvas id="rdProgressChart" data-label-header="Grade Level" data-source="<?= e('Phil-IRI ' . $language . ' sections by encoding status — ' . ReadingResultModel::PERIODS[$period] . ', SY ' . $year . '.') ?>"></canvas></div>
    </div></div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100"><div class="card-body">
      <div class="fw-bold"><?= e($tables['rp']['title']) ?></div>
      <div class="small text-muted mb-2">% of learners tested, by grade</div>
      <div class="dash-chart" style="height:230px"><canvas id="rdProfileChart" data-label-header="Grade Level" data-unit="%" data-source="<?= e('Phil-IRI ' . $language . ' reading profile' . $src) ?>"></canvas></div>
    </div></div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100"><div class="card-body">
      <div class="fw-bold"><?= e($tables['gst']['title']) ?></div>
      <div class="small text-muted mb-2">% of learners tested, by grade</div>
      <div class="dash-chart" style="height:230px"><canvas id="rdGstChart" data-label-header="Grade Level" data-unit="%" data-source="<?= e('Phil-IRI ' . $language . ' Group Screening Test bands' . $src) ?>"></canvas></div>
    </div></div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100"><div class="card-body">
      <div class="fw-bold">Beginning vs Year End — <?= e($tables['rp']['title']) ?></div>
      <div class="small text-muted mb-2">School-wide % of learners tested, SY <?= e($year) ?></div>
      <div class="dash-chart" style="height:230px"><canvas id="rdCompareChart" data-label-header="Level" data-unit="%" data-source="<?= e('Phil-IRI ' . $language . ' reading profile, Beginning vs Year End, SY ' . $year . ' (submitted and approved sections).') ?>"></canvas></div>
    </div></div>
  </div>
  <?php if (isset($tables['tala'])): ?>
  <div class="col-lg-6">
    <div class="card h-100"><div class="card-body">
      <div class="fw-bold">Project TALA — Beginning vs Year End</div>
      <div class="small text-muted mb-2">S = speed, C = comprehension · school-wide %</div>
      <div class="dash-chart" style="height:230px"><canvas id="rdTalaChart" data-label-header="Group" data-unit="%" data-source="<?= e('Project TALA groups, Beginning vs Year End, SY ' . $year . ' (submitted and approved sections).') ?>"></canvas></div>
    </div></div>
  </div>
  <?php endif; ?>
  <div class="col-lg-<?= isset($tables['tala']) ? '6' : '12' ?>">
    <div class="card h-100"><div class="card-body">
      <div class="fw-bold">Gain / Loss, Beginning → Year End</div>
      <div class="small text-muted mb-2">School-wide, percentage points · green is an improvement</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle reading-gain">
          <thead><tr><th>Level</th><th class="text-end">Beginning</th><th class="text-end">Year End</th><th class="text-end">Change</th></tr></thead>
          <tbody>
            <?php foreach ($tables as $t => $def): if ($t === 'wr' || $t === 'rc') continue; ?>
            <tr class="reading-gain-group"><td colspan="4"><?= e($def['title']) ?></td></tr>
            <?php foreach ($def['levels'] as $lv => $lvLabel):
              $b = ReadingResultModel::pct($lvTotal($compare['Beginning']['school'], $t, $lv), $compare['Beginning']['school']['tested_m'] + $compare['Beginning']['school']['tested_f']);
              $y = ReadingResultModel::pct($lvTotal($compare['End']['school'], $t, $lv), $compare['End']['school']['tested_m'] + $compare['End']['school']['tested_f']);
              $d = $b !== null && $y !== null ? round($y - $b, 1) : null;
              $dir  = in_array($lv, $goodUp, true) ? 1 : (in_array($lv, $goodDown, true) ? -1 : 0);
              $tone = $d === null || $d == 0 || $dir === 0 ? '' : ($d * $dir > 0 ? 'text-success' : 'text-danger');
            ?>
            <tr>
              <td><?= e($lvLabel) ?></td>
              <td class="text-end"><?= $pct($b) ?></td>
              <td class="text-end"><?= $pct($y) ?></td>
              <td class="text-end fw-semibold <?= $tone ?>"><?= $d === null ? '—' : ($d > 0 ? '+' : '') . rtrim(rtrim(number_format($d, 1), '0'), '.') . ' pp' ?></td>
            </tr>
            <?php endforeach; endforeach; ?>
          </tbody>
        </table>
      </div>
    </div></div>
  </div>
</div>

<!-- Sections -->
<div class="card mb-4" id="reading-sections">
  <div class="card-body">
    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
      <div>
        <div class="fw-bold">Sections</div>
        <div class="small text-muted">Open a section to see what was encoded, then approve it or return it with a note.</div>
      </div>
      <?php if ($totals['submitted'] > 0): ?>
      <form method="POST" action="<?= base_url('reading-program') ?>" class="ajax-form ms-auto" data-confirm-title="Approve all <?= $totals['submitted'] ?> submitted sections?" data-confirm-text="Phil-IRI <?= e($language) ?>, <?= e(ReadingResultModel::PERIODS[$period]) ?>, SY <?= e($year) ?>.">
        <input type="hidden" name="action" value="approve_all">
        <?php foreach ($query as $k => $v): ?><input type="hidden" name="<?= $k ?>" value="<?= e($v) ?>"><?php endforeach; ?>
        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2-all me-1"></i>Approve All Submitted (<?= $totals['submitted'] ?>)</button>
      </form>
      <?php endif; ?>
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0 align-middle">
        <thead><tr><th>Grade</th><th>Section</th><th>Status</th><th class="text-end">Tested / Enrolled</th><th>Encoded by</th><th>Updated</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
          <?php foreach ($sections as $g => $list): foreach ($list as $s):
            $r = $rows[$g . '|' . $s] ?? null;
            [$stLabel, $tone] = ReadingResultModel::statusOf($r);
            $view = base_url('reading?' . http_build_query($query + ['grade' => $g, 'section' => $s]));
          ?>
          <tr>
            <td class="text-nowrap"><?= e($g) ?></td>
            <td class="fw-semibold"><?= e($s) ?></td>
            <td><span class="rd-status rd-status-<?= $tone ?>"><?= e($stLabel) ?></span><?php if (($r['status'] ?? '') === 'Returned' && $r['review_note']): ?><div class="small text-muted text-truncate" style="max-width:220px" title="<?= e($r['review_note']) ?>"><?= e($r['review_note']) ?></div><?php endif; ?></td>
            <td class="text-end text-nowrap"><?= $r && $r['tested_m'] !== null ? (int) $r['tested_m'] + (int) $r['tested_f'] . ' / ' . ((int) $r['enrolled_m'] + (int) $r['enrolled_f']) : '—' ?></td>
            <td class="small"><?= $r && $r['updated_by'] ? e($names[$r['updated_by']] ?? '—') : '—' ?></td>
            <td class="small text-muted text-nowrap"><?= $r && $r['updated_at'] ? e(date('M j, g:i A', strtotime($r['updated_at']))) : '' ?></td>
            <td class="text-end text-nowrap">
              <?php if ($r): ?>
              <a href="<?= $view ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
              <?php endif; ?>
              <?php if (($r['status'] ?? '') === 'Submitted'): ?>
              <form method="POST" action="<?= base_url('reading-program') ?>" class="ajax-form d-inline" data-confirm-title="Approve <?= e($g . ' ' . $s) ?>?">
                <input type="hidden" name="action" value="approve"><input type="hidden" name="result_id" value="<?= (int) $r['id'] ?>">
                <button type="submit" class="btn btn-sm btn-success" title="Approve"><i class="bi bi-check-lg"></i></button>
              </form>
              <?php endif; ?>
              <?php if (in_array($r['status'] ?? '', ['Submitted', 'Approved'], true)): ?>
              <button type="button" class="btn btn-sm btn-outline-danger" title="Return" onclick="readingReturn(<?= (int) $r['id'] ?>, <?= e(json_encode($g . ' ' . $s)) ?>)"><i class="bi bi-arrow-return-left"></i></button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Summary sheets -->
<?php foreach ($tables as $t => $def): $sheetId = 'readingSheet-' . $t; ?>
<div class="card mb-4">
  <div class="rma-sheet-head">
    <div>
      <div class="rma-sheet-title"><?= e($def['title']) ?></div>
      <div class="small text-muted">Phil-IRI <?= e($language) ?> · <?= e(ReadingResultModel::PERIODS[$period]) ?>, SY <?= e($year) ?> · submitted and approved sections</div>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="rmaExport('<?= $sheetId ?>')"><i class="bi bi-download me-1"></i>Export</button>
  </div>
  <div class="table-responsive">
    <table class="table table-bordered table-sm mb-0 align-middle rma-table" id="<?= $sheetId ?>"
           data-export-title="<?= e($def['title'] . ' — Phil-IRI ' . $language . ', ' . ReadingResultModel::PERIODS[$period] . ', SY ' . $year) ?>"
           data-export-file="<?= e('Phil-IRI ' . $language . ' ' . $def['title'] . ' ' . $period . ' ' . $year) ?>">
      <thead>
        <tr>
          <th rowspan="2">Grade</th><th rowspan="2" class="text-center">Sections</th><th rowspan="2" class="text-center">Tested</th>
          <?php foreach ($def['levels'] as $lvLabel): ?><th colspan="4" class="text-center"><?= e($lvLabel) ?></th><?php endforeach; ?>
        </tr>
        <tr><?php foreach ($def['levels'] as $_): ?><th class="text-center">M</th><th class="text-center">F</th><th class="text-center">Total</th><th class="text-center">%</th><?php endforeach; ?></tr>
      </thead>
      <tbody>
        <?php foreach ($summary['grades'] + ['School' => $summary['school']] as $g => $b): $bt = $b['tested_m'] + $b['tested_f']; ?>
        <tr class="<?= $g === 'School' ? 'rma-total-row fw-bold' : '' ?>">
          <td class="text-nowrap"><?= $g === 'School' ? 'Whole school' : e($g) ?></td>
          <td class="text-center"><?= (int) $b['sections'] ?></td>
          <td class="text-center"><?= $bt ?></td>
          <?php foreach (array_keys($def['levels']) as $lv): $c = $b['tables'][$t][$lv]; ?>
          <td class="text-center"><?= $c['m'] ?></td><td class="text-center"><?= $c['f'] ?></td><td class="text-center fw-semibold"><?= $c['m'] + $c['f'] ?></td><td class="text-center"><?= $pct(ReadingResultModel::pct($c['m'] + $c['f'], $bt)) ?></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>

<?php include APPPATH . 'Views/partials/reading_return_modal.php'; ?>

<?php
$progressSets = [
    ['label' => 'Not started',        'alpha' => 0.14, 'data' => array_values(array_column($progress, 'none'))],
    ['label' => 'Draft / Returned',   'alpha' => 0.4,  'data' => array_values(array_column($progress, 'draft'))],
    ['label' => 'Submitted',          'alpha' => 0.7,  'data' => array_values(array_column($progress, 'submitted'))],
    ['label' => 'Approved',           'alpha' => 1,    'data' => array_values(array_column($progress, 'approved'))],
];
$extraScript = '<script>
(function () {
  const surface = getComputedStyle(document.documentElement).getPropertyValue("--card").trim() || "#fff";
  const grades = ' . json_encode(array_keys($progress)) . ';
  const legend = { position: "bottom", labels: { boxWidth: 10, boxHeight: 10, font: { size: 10 } } };
  // Worst → best level, lightest → darkest (same ordinal ramp as the RMA charts).
  const ramp = n => n === 3 ? [0.36, 0.68, 1] : [0.36, 0.57, 0.79, 1];

  new Chart(document.getElementById("rdProgressChart"), {
    type: "bar",
    data: { labels: grades, datasets: ' . json_encode($progressSets) . '.map(d => ({ label: d.label, data: d.data, backgroundColor: chartColor(d.alpha), borderColor: surface, borderWidth: { right: 2 }, borderSkipped: false, maxBarThickness: 22 })) },
    options: { indexAxis: "y", responsive: true, maintainAspectRatio: false,
      scales: { x: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, grid: { color: chartGridColor() } }, y: { stacked: true, grid: { display: false } } },
      plugins: { legend, tooltip: { callbacks: { label: c => c.dataset.label + ": " + c.parsed.x + " section" + (c.parsed.x === 1 ? "" : "s") } } } }
  });

  function stacked(id, sets) {
    const alphas = ramp(sets.length);
    new Chart(document.getElementById(id), {
      type: "bar",
      data: { labels: ' . json_encode(array_merge(array_keys($summary['grades']), ['School'])) . ', datasets: sets.map((s, i) => ({ label: s.label, data: s.pct, counts: s.counts, backgroundColor: chartColor(alphas[i]), borderColor: surface, borderWidth: { right: 2 }, borderSkipped: false, maxBarThickness: 22 })) },
      options: { indexAxis: "y", responsive: true, maintainAspectRatio: false,
        scales: { x: { stacked: true, min: 0, max: 100, ticks: { callback: v => v + "%" }, grid: { color: chartGridColor() } }, y: { stacked: true, grid: { display: false } } },
        plugins: { legend, tooltip: { callbacks: { label: c => c.dataset.label + ": " + c.parsed.x + "% (" + c.dataset.counts[c.dataIndex] + " learners)" } } } }
    });
  }
  stacked("rdProfileChart", ' . json_encode($levelSet('rp')) . ');
  stacked("rdGstChart", ' . json_encode($levelSet('gst')) . ');

  function compare(id, labels, sets) {
    const el = document.getElementById(id);
    if (!el) return;
    new Chart(el, {
      type: "bar",
      data: { labels, datasets: sets.map((s, i) => ({ label: s.label, data: s.data, backgroundColor: chartColor(i === 0 ? 0.42 : 1), borderRadius: 4, maxBarThickness: 26 })) },
      options: { responsive: true, maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + "%" }, grid: { color: chartGridColor() } }, x: { grid: { display: false } } },
        plugins: { legend, tooltip: { callbacks: { label: c => c.dataset.label + ": " + (c.parsed.y === null ? "no data" : c.parsed.y + "%") } } } }
    });
  }
  compare("rdCompareChart", ' . json_encode(array_values($tables['rp']['levels'])) . ', ' . json_encode($compareSet('rp')) . ');
  ' . (isset($tables['tala']) ? 'compare("rdTalaChart", ' . json_encode(array_values($tables['tala']['levels'])) . ', ' . json_encode($compareSet('tala')) . ');' : '') . '
})();
</script>';
include APPPATH . 'Views/layout/footer.php';
?>

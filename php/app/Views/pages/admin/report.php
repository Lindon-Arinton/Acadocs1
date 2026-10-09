<?php
/**
 * Printable school report (Dashboard::report()). Same figures as the
 * dashboard for the chosen school year / range; only the ticked sections are
 * rendered. Always light-themed — it's meant for paper / PDF.
 *
 * @var string[] $sections
 */
$has = static fn (string $key): bool => in_array($key, $sections, true);

$scope = $range !== null
    ? 'SY ' . $range['start'] . '–' . ($range['start'] + 1) . ' to SY ' . ($range['end'] - 1) . '–' . $range['end']
    : 'SY ' . str_replace('-', '–', $currentYear);

$roleLabel = ['admin' => 'School Principal', 'adas' => 'Administrative Assistant'][$generatedRole] ?? ucfirst($generatedRole);

// Key figures for the focus year (the selected year, or the newest in a range).
$enrolleesNow = null;
foreach ($enrollmentTotals as $r) {
    if ($r['school_year'] === $currentYear) {
        $enrolleesNow = (int) $r['total'];
    }
}
if ($enrolleesNow === null && $enrollment !== []) {
    $enrolleesNow = array_sum(array_map(static fn ($e) => (int) $e['students'], $enrollment));
}

$dropoutRow = null;
foreach ($depedKpis as $k) {
    if ($k['school_year'] === $currentYear) {
        $dropoutRow = $k;
    }
}
$dropoutNote = '';
if ($dropoutRow === null && $depedKpis !== []) {
    $dropoutRow  = end($depedKpis);
    $dropoutNote = 'latest report: SY ' . str_replace('-', '–', $dropoutRow['school_year']);
}

$deltaText = static function (?array $delta, bool $upIsGood, string $suffix): string {
    if ($delta === null) {
        return '<span class="muted">No earlier year to compare</span>';
    }
    $up   = $delta['delta'] >= 0;
    $good = $up === $upIsGood;

    return '<span class="' . ($good ? 'good' : 'bad') . '">' . ($up ? '▲' : '▼') . ' ' . number_format(abs($delta['delta']), 2) . $suffix . '</span>'
        . ' <span class="muted">vs ' . e($delta['vsLabel']) . '</span>';
};

$pct = static fn ($v): string => $v !== null ? number_format((float) $v, 2) . '%' : '—';

// Grade levels in natural order (Grade 7 … Grade 10, not string order).
usort($perfLevel, static fn ($a, $b) => strnatcmp($a['grade_level'], $b['grade_level']));
usort($enrollment, static fn ($a, $b) => strnatcmp($a['grade_level'], $b['grade_level']));

$enrollTotal = array_sum(array_map(static fn ($e) => (int) $e['students'], $enrollment));
$toneColors  = ['danger' => '#dc2626', 'warning' => '#d97706', 'success' => '#059669', 'info' => '#800000'];

$sectionNo = 0;
$heading = static function (string $title, string $icon) use (&$sectionNo): string {
    $sectionNo++;

    return '<h2 class="sec-title"><span class="sec-no">' . $sectionNo . '</span><i class="bi ' . $icon . '"></i>' . e($title) . '</h2>';
};
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>School Report — <?= e($scope) ?> — ACADOCS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="icon" type="image/png" href="<?= base_url('assets/img/logo-icon.png') ?>">
<style>
  :root { --maroon: #800000; --maroon-soft: #fdf2f2; --text: #1f2937; --muted: #6b7280; --border: #e5e7eb; }
  * { box-sizing: border-box; }
  body { margin: 0; background: #e9e5e6; color: var(--text); font-family: 'Inter', system-ui, sans-serif; font-size: 12.5px; line-height: 1.5; }

  .toolbar { position: sticky; top: 0; z-index: 5; display: flex; gap: .5rem; justify-content: center; padding: .7rem; background: #2b2b30; }
  .toolbar button { font: inherit; font-weight: 600; border: 0; border-radius: 8px; padding: .5rem 1rem; cursor: pointer; }
  .toolbar .primary { background: var(--maroon); color: #fff; }
  .toolbar .ghost { background: rgba(255,255,255,.12); color: #fff; }

  .page { width: 210mm; min-height: 297mm; margin: 18px auto; padding: 16mm 15mm; background: #fff; box-shadow: 0 6px 24px rgba(0,0,0,.18); }

  .letterhead { display: grid; grid-template-columns: 80px 1fr 80px; align-items: center; gap: 10px; padding-bottom: 10px; border-bottom: 3px double var(--maroon); }
  .letterhead img { width: 76px; height: auto; }
  .letterhead .lh-text { text-align: center; line-height: 1.25; }
  .lh-text .rp { font-family: 'Old English Text MT', 'UnifrakturMaguntia', serif; font-size: 13px; }
  .lh-text .deped { font-family: 'Old English Text MT', 'UnifrakturMaguntia', serif; font-size: 20px; }
  .lh-text .line { font-weight: 700; font-size: 10.5px; letter-spacing: .02em; }

  .report-title { text-align: center; margin: 16px 0 4px; }
  .report-title h1 { margin: 0; font-size: 18px; letter-spacing: .06em; text-transform: uppercase; color: var(--maroon); }
  .report-title .scope { font-weight: 700; font-size: 13px; margin-top: 2px; }
  .meta { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 4px; margin: 10px 0 6px; padding: 7px 10px; background: var(--maroon-soft); border-radius: 6px; font-size: 11px; color: var(--muted); }

  .sec { margin-top: 18px; break-inside: avoid; page-break-inside: avoid; }
  .sec-title { display: flex; align-items: center; gap: 8px; margin: 0 0 8px; padding-bottom: 5px; font-size: 13.5px; border-bottom: 1px solid var(--border); }
  .sec-title i { color: var(--maroon); }
  .sec-no { display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 50%; background: var(--maroon); color: #fff; font-size: 10.5px; }
  .note { font-size: 11px; color: var(--muted); margin: 4px 0 0; }

  .tiles { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
  .tile { border: 1px solid var(--border); border-top: 3px solid var(--maroon); border-radius: 8px; padding: 9px 10px; }
  .tile .label { font-size: 10.5px; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; font-weight: 600; }
  .tile .value { font-size: 20px; font-weight: 700; margin: 2px 0; }
  .tile .delta { font-size: 10.5px; }
  .good { color: #059669; font-weight: 600; } .bad { color: #dc2626; font-weight: 600; } .muted { color: var(--muted); }

  .insight { display: flex; gap: 8px; align-items: flex-start; padding: 7px 10px; margin-bottom: 6px; border-radius: 6px; background: #fafafa; border-left: 4px solid; }
  .insight i { margin-top: 1px; }

  .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; align-items: start; }
  .chart-box { position: relative; height: 210px; border: 1px solid var(--border); border-radius: 8px; padding: 8px; }

  table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
  th, td { padding: 5px 7px; border-bottom: 1px solid var(--border); text-align: left; }
  thead th { background: var(--maroon); color: #fff; font-weight: 600; font-size: 10.5px; text-transform: uppercase; letter-spacing: .03em; }
  tbody tr:nth-child(even) td { background: #fbf8f8; }
  tfoot td { font-weight: 700; border-top: 2px solid var(--maroon); }
  .num { text-align: right; font-variant-numeric: tabular-nums; }
  /* Wide tables (DepEd KPIs: 11 columns) must fit the A4 width. */
  table.compact { table-layout: fixed; font-size: 10px; }
  table.compact th, table.compact td { padding: 4px 3px; }
  table.compact thead th { font-size: 8.5px; letter-spacing: 0; line-height: 1.2; white-space: normal; }
  table.compact th:first-child, table.compact td:first-child { width: 62px; }
  .empty { padding: 12px; text-align: center; color: var(--muted); border: 1px dashed var(--border); border-radius: 8px; }

  .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 34px; break-inside: avoid; }
  .sig .lbl { font-size: 11px; color: var(--muted); margin-bottom: 30px; }
  .sig .name { border-top: 1px solid var(--text); padding-top: 3px; font-weight: 700; text-align: center; }
  .sig .role { text-align: center; font-size: 11px; color: var(--muted); }
  .footer { margin-top: 18px; font-size: 10px; color: var(--muted); text-align: center; }

  @page { size: A4; margin: 12mm; }
  @media print {
    body { background: #fff; }
    .toolbar { display: none; }
    .page { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
    thead th, .sec-no, .tile, .insight, .meta, tbody tr:nth-child(even) td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
  @media (max-width: 840px) {
    .page { width: auto; margin: 0; padding: 18px 14px; }
    .tiles { grid-template-columns: repeat(2, 1fr); }
    .two-col { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <button type="button" class="primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
  <button type="button" class="primary" onclick="saveReportPdf()"><i class="bi bi-file-earmark-pdf me-1"></i> Save as PDF</button>
  <button type="button" class="ghost" onclick="window.close()"><i class="bi bi-x-lg"></i> Close</button>
</div>

<div class="page">
  <div class="letterhead">
    <img src="<?= base_url('assets/img/deped-seal.png') ?>" alt="DepEd seal">
    <div class="lh-text">
      <div class="rp">Republic of the Philippines</div>
      <div class="deped">Department of Education</div>
      <div class="line">REGION IV-A, CALABARZON</div>
      <div class="line">SCHOOLS DIVISION OF BATANGAS PROVINCE</div>
      <div class="line">MATABUNGKAY NATIONAL HIGH SCHOOL</div>
      <div class="line">MATABUNGKAY, LIAN, BATANGAS</div>
    </div>
    <img src="<?= base_url('assets/img/logo-icon.png') ?>" alt="School logo">
  </div>

  <div class="report-title">
    <h1>School Performance Report</h1>
    <div class="scope"><?= e($scope) ?></div>
  </div>
  <div class="meta">
    <span><i class="bi bi-calendar3"></i> Generated <?= date('F d, Y · h:i A') ?></span>
    <span><i class="bi bi-person"></i> by <?= e($generatedBy) ?> (<?= e($roleLabel) ?>)</span>
    <?php if ($range !== null): ?>
    <span><i class="bi bi-info-circle"></i> Single-year figures: SY <?= e(str_replace('-', '–', $currentYear)) ?></span>
    <?php endif; ?>
  </div>

  <?php if ($has('summary')): ?>
  <section class="sec">
    <?= $heading('Key Figures', 'bi-speedometer2') ?>
    <div class="tiles">
      <div class="tile">
        <div class="label">Total Enrollees</div>
        <div class="value"><?= $enrolleesNow !== null ? number_format($enrolleesNow) : '—' ?></div>
        <div class="delta"><?= $deltaText($enrolleesDelta, true, '%') ?></div>
      </div>
      <div class="tile">
        <div class="label">Drop-Out Rate</div>
        <div class="value"><?= $dropoutRow ? $pct($dropoutRow['dropout_rate']) : '—' ?></div>
        <div class="delta"><?= $dropoutNote !== '' ? '<span class="muted">' . e($dropoutNote) . '</span>' : $deltaText($dropoutDelta, false, ' pts') ?></div>
      </div>
      <div class="tile">
        <div class="label">Average MPS (Term <?= (int) $currentTerm ?>)</div>
        <div class="value"><?= $pct($avgMps) ?></div>
        <div class="delta"><?= $deltaText($mpsDelta, true, ' pts') ?></div>
      </div>
      <div class="tile">
        <div class="label">Submission Compliance</div>
        <div class="value"><?= $pct($complianceRate) ?></div>
        <div class="delta muted">Target: 85%</div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($has('insights')): ?>
  <section class="sec">
    <?= $heading('Insights', 'bi-lightbulb-fill') ?>
    <?php if ($insights === []): ?>
    <div class="empty">No notable findings for this period.</div>
    <?php else: ?>
    <?php foreach ($insights as $ins): $c = $toneColors[$ins['tone']] ?? '#800000'; ?>
    <div class="insight" style="border-left-color:<?= $c ?>;">
      <i class="bi <?= e($ins['icon']) ?>" style="color:<?= $c ?>;"></i>
      <div><?= strip_tags($ins['text'], '<strong><em>') ?></div>
    </div>
    <?php endforeach; ?>
    <p class="note">Insights are generated automatically from the figures in this report using set rules (thresholds and year-over-year comparisons).</p>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($has('enrollment')): ?>
  <section class="sec">
    <?= $heading('Enrollment by Grade Level', 'bi-people-fill') ?>
    <?php if ($enrollment === []): ?>
    <div class="empty">No enrollment data for SY <?= e(str_replace('-', '–', $currentYear)) ?>.</div>
    <?php else: ?>
    <div class="two-col">
      <table>
        <thead><tr><th>Grade Level</th><th class="num">Sections</th><th class="num">Male</th><th class="num">Female</th><th class="num">Total</th><th class="num">Share</th></tr></thead>
        <tbody>
          <?php foreach ($enrollment as $e): ?>
          <tr>
            <td><?= e($e['grade_level']) ?></td>
            <td class="num"><?= (int) $e['sections'] ?></td>
            <td class="num"><?= isset($e['male']) ? number_format((int) $e['male']) : '—' ?></td>
            <td class="num"><?= isset($e['female']) ? number_format((int) $e['female']) : '—' ?></td>
            <td class="num"><?= number_format((int) $e['students']) ?></td>
            <td class="num"><?= $enrollTotal ? number_format((int) $e['students'] / $enrollTotal * 100, 1) . '%' : '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td>Total</td><td class="num"><?= array_sum(array_map(static fn ($e) => (int) $e['sections'], $enrollment)) ?></td><td></td><td></td><td class="num"><?= number_format($enrollTotal) ?></td><td class="num">100%</td></tr></tfoot>
      </table>
      <div class="chart-box"><canvas id="chartEnrollment"></canvas></div>
    </div>
    <p class="note">SY <?= e(str_replace('-', '–', $currentYear)) ?><?= ! empty($enrollmentMonth) ? ' · count as of ' . e(date('F Y', strtotime($enrollmentMonth . '-01'))) : '' ?>.</p>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($has('enrollees')): ?>
  <section class="sec">
    <?= $heading('Total Enrollees by School Year', 'bi-graph-up') ?>
    <?php if ($enrollmentTotals === []): ?>
    <div class="empty">No enrollment totals for this period.</div>
    <?php else: ?>
    <div class="two-col">
      <div class="chart-box"><canvas id="chartEnrollees"></canvas></div>
      <table>
        <thead><tr><th>School Year</th><th class="num">Enrollees</th><th class="num">Change</th></tr></thead>
        <tbody>
          <?php $prev = null; foreach ($enrollmentTotals as $r): ?>
          <tr>
            <td>SY <?= e(str_replace('-', '–', $r['school_year'])) ?></td>
            <td class="num"><?= number_format((int) $r['total']) ?></td>
            <td class="num"><?php if ($prev): $chg = ($r['total'] - $prev) / $prev * 100; ?><span class="<?= $chg >= 0 ? 'good' : 'bad' ?>"><?= ($chg >= 0 ? '+' : '') . number_format($chg, 1) ?>%</span><?php else: ?>—<?php endif; ?></td>
          </tr>
          <?php $prev = (int) $r['total']; endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($has('dropout')): ?>
  <section class="sec">
    <?= $heading('Drop-Out Rate by School Year', 'bi-graph-down-arrow') ?>
    <?php $dropRows = array_values(array_filter($depedKpis, static fn ($k) => $k['dropout_rate'] !== null)); ?>
    <?php if ($dropRows === []): ?>
    <div class="empty">No DepEd drop-out figures for this period.</div>
    <?php else: ?>
    <div class="two-col">
      <div class="chart-box"><canvas id="chartDropout"></canvas></div>
      <table>
        <thead><tr><th>School Year</th><th class="num">Drop-Out Rate</th><th class="num">Change</th></tr></thead>
        <tbody>
          <?php $prev = null; foreach ($dropRows as $k): ?>
          <tr>
            <td>SY <?= e(str_replace('-', '–', $k['school_year'])) ?></td>
            <td class="num"><?= $pct($k['dropout_rate']) ?></td>
            <td class="num"><?php if ($prev !== null): $chg = (float) $k['dropout_rate'] - $prev; ?><span class="<?= $chg <= 0 ? 'good' : 'bad' ?>"><?= ($chg >= 0 ? '+' : '') . number_format($chg, 2) ?> pts</span><?php else: ?>—<?php endif; ?></td>
          </tr>
          <?php $prev = (float) $k['dropout_rate']; endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="note">Source: DepEd Key Performance Indicator reports (Guidance Office). A falling rate is an improvement.</p>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($has('mps')): ?>
  <section class="sec">
    <?= $heading('Average MPS by Term and Grade Level', 'bi-bar-chart-line-fill') ?>
    <?php if ($mpsTrend === [] && $perfLevel === []): ?>
    <div class="empty">No MPS scores for this period.</div>
    <?php else: ?>
    <div class="two-col">
      <div>
        <div class="chart-box"><canvas id="chartMpsTerm"></canvas></div>
        <p class="note">School-wide average per term, SY <?= e(str_replace('-', '–', $mpsSourceYear)) ?><?= $mpsOverallAvg !== null ? ' · overall ' . $pct($mpsOverallAvg) : '' ?>.</p>
      </div>
      <div>
        <table>
          <thead><tr><th>Grade Level</th><th class="num">MPS (Term <?= (int) $currentTerm ?>)</th><th>Descriptor</th></tr></thead>
          <tbody>
            <?php foreach ($perfLevel as $p): $m = (float) $p['mps']; ?>
            <tr>
              <td><?= e($p['grade_level']) ?></td>
              <td class="num"><?= $pct($m) ?></td>
              <td><span class="<?= $m >= 75 ? 'good' : 'bad' ?>"><?= mpsDescriptor($m)['label'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if ($perfLevel === []): ?><tr><td colspan="3" class="muted">No grade-level MPS for SY <?= e(str_replace('-', '–', $currentYear)) ?>.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($has('subjects')): ?>
  <section class="sec">
    <?= $heading('Performance by Learning Area', 'bi-mortarboard-fill') ?>
    <?php if ($avgPerf === []): ?>
    <div class="empty">No learning-area scores for SY <?= e(str_replace('-', '–', $currentYear)) ?>, Term <?= (int) $currentTerm ?>.</div>
    <?php else: ?>
    <div class="two-col">
      <div class="chart-box" style="height:<?= max(210, count($avgPerf) * 22 + 40) ?>px;"><canvas id="chartSubjects"></canvas></div>
      <table>
        <thead><tr><th>#</th><th>Learning Area</th><th class="num">Average MPS</th><th>Descriptor</th></tr></thead>
        <tbody>
          <?php foreach ($avgPerf as $i => $p): ?>
          <tr>
            <td class="muted"><?= $i + 1 ?></td>
            <td><?= e($p['subject']) ?></td>
            <td class="num"><span class="<?= $p['mps'] >= 75 ? '' : 'bad' ?>"><?= $pct($p['mps']) ?></span></td>
            <td class="muted"><?= mpsDescriptor((float) $p['mps'])['label'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="note">Average across grade levels, SY <?= e(str_replace('-', '–', $currentYear)) ?>, Term <?= (int) $currentTerm ?>. Scores below 75% (Developing or Emerging) are shown in red. Descriptors: 90–100 Advancing, 80–89 Benchmarking, 75–79 Connecting, 65–74 Developing, below 65 Emerging.</p>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($has('deped')): ?>
  <section class="sec">
    <?= $heading('DepEd Historical KPIs', 'bi-clipboard-data-fill') ?>
    <?php if ($depedKpis === []): ?>
    <div class="empty">No DepEd KPI reports for this period.</div>
    <?php else: ?>
    <table class="compact">
      <thead><tr><th>SY</th><th class="num">GER</th><th class="num">NER</th><th class="num">Cohort Surv.</th><th class="num">Repetition</th><th class="num">Promotion</th><th class="num">Retention</th><th class="num">Graduation</th><th class="num">Completion</th><th class="num">Transition</th><th class="num">Drop-Out</th></tr></thead>
      <tbody>
        <?php foreach ($depedKpis as $k): ?>
        <tr>
          <td><?= e(str_replace('-', '–', $k['school_year'])) ?></td>
          <?php foreach (['gross_enrolment_rate', 'net_enrolment_rate', 'cohort_survival_rate', 'repetition_rate', 'promotion_rate', 'retention_rate', 'graduation_rate', 'completion_rate', 'transition_rate', 'dropout_rate'] as $col): ?>
          <td class="num"><?= $pct($k[$col]) ?></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p class="note">GER = Gross Enrolment Rate · NER = Net Enrolment Rate.</p>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php // The principal noting their own report is redundant — only ask for it when ADAS prepared it. ?>
  <div class="signatures">
    <div class="sig">
      <div class="lbl">Prepared by:</div>
      <div class="name"><?= e($generatedBy) ?></div>
      <div class="role"><?= e($roleLabel) ?></div>
    </div>
    <?php if ($generatedRole !== 'admin'): ?>
    <div class="sig">
      <div class="lbl">Noted by:</div>
      <div class="name"><?= ($principalName ?? '') !== '' ? e($principalName) : '&nbsp;' ?></div>
      <div class="role">School Principal</div>
    </div>
    <?php endif; ?>
  </div>

  <div class="footer">ACADOCS: Integrated Academic Monitoring and Document Management System · Matabungkay National High School</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const MAROON = '#800000', MAROON_SOFT = 'rgba(128,0,0,.18)', GOLD = '#c9a24a';
Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
Chart.defaults.font.size = 11;
Chart.defaults.color = '#4b5563';
Chart.defaults.animation = false;      // render instantly so printing never catches a half-drawn chart
Chart.defaults.devicePixelRatio = 2;   // crisp on paper
Chart.defaults.maintainAspectRatio = false;
Chart.defaults.plugins.legend.labels.boxWidth = 12;

function chart(id, config) {
  const el = document.getElementById(id);
  if (el) new Chart(el, config);
}
const pctAxis = { ticks: { callback: v => v + '%' } };

chart('chartEnrollment', {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($enrollment, 'grade_level')) ?>,
    datasets: [
      { label: 'Male',   data: <?= json_encode(array_map(static fn ($e) => (int) ($e['male'] ?? 0), $enrollment)) ?>,   backgroundColor: MAROON, borderRadius: 3 },
      { label: 'Female', data: <?= json_encode(array_map(static fn ($e) => (int) ($e['female'] ?? 0), $enrollment)) ?>, backgroundColor: GOLD,   borderRadius: 3 },
    ],
  },
  options: { plugins: { title: { display: true, text: 'Learners per grade level' } }, scales: { y: { beginAtZero: true } } },
});

chart('chartEnrollees', {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_map(static fn ($r) => str_replace('-', '–', $r['school_year']), $enrollmentTotals)) ?>,
    datasets: [{ label: 'Enrollees', data: <?= json_encode(array_map(static fn ($r) => (int) $r['total'], $enrollmentTotals)) ?>, backgroundColor: MAROON, borderRadius: 3 }],
  },
  options: { plugins: { legend: { display: false }, title: { display: true, text: 'Total enrollees' } }, scales: { y: { beginAtZero: true } } },
});

<?php $dropRows = array_values(array_filter($depedKpis, static fn ($k) => $k['dropout_rate'] !== null)); ?>
chart('chartDropout', {
  type: 'line',
  data: {
    labels: <?= json_encode(array_map(static fn ($k) => str_replace('-', '–', $k['school_year']), $dropRows)) ?>,
    datasets: [{ label: 'Drop-out rate', data: <?= json_encode(array_map(static fn ($k) => (float) $k['dropout_rate'], $dropRows)) ?>, borderColor: MAROON, backgroundColor: MAROON_SOFT, fill: true, tension: .3, pointRadius: 4, pointBackgroundColor: MAROON }],
  },
  options: { plugins: { legend: { display: false }, title: { display: true, text: 'Drop-out rate (%)' } }, scales: { y: Object.assign({ beginAtZero: true }, pctAxis) } },
});

chart('chartMpsTerm', {
  type: <?= count($mpsTrend) < 2 ? "'bar'" : "'line'" ?>, // a lone point reads better as a bar
  data: {
    labels: <?= json_encode(array_map(static fn ($t) => 'Term ' . $t['term'], $mpsTrend)) ?>,
    datasets: [{ label: 'Average MPS', data: <?= json_encode(array_map(static fn ($t) => (float) $t['avg_mps'], $mpsTrend)) ?>, borderColor: MAROON, backgroundColor: <?= count($mpsTrend) < 2 ? 'MAROON' : 'MAROON_SOFT' ?>, fill: true, tension: .3, pointRadius: 4, pointBackgroundColor: MAROON }],
  },
  options: { plugins: { legend: { display: false }, title: { display: true, text: 'Average MPS per term' } }, scales: { y: Object.assign({ suggestedMin: 0, suggestedMax: 100 }, pctAxis) } },
});

chart('chartSubjects', {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($avgPerf, 'subject')) ?>,
    datasets: [{
      label: 'Average MPS',
      data: <?= json_encode(array_map(static fn ($p) => (float) $p['mps'], $avgPerf)) ?>,
      backgroundColor: <?= json_encode(array_map(static fn ($p) => (float) $p['mps'] >= 75 ? '#800000' : '#dc2626', $avgPerf)) ?>,
      borderRadius: 3,
    }],
  },
  options: { indexAxis: 'y', plugins: { legend: { display: false }, title: { display: true, text: 'Average MPS by learning area' } }, scales: { x: Object.assign({ suggestedMin: 0, suggestedMax: 100 }, pctAxis) } },
});

/* Save as PDF: downloads the report straight to a file, no print dialog. */
function saveReportPdf() {
  Swal.fire({
    title: 'Saving as PDF...',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => Swal.showLoading(),
  });

  // The on-screen sheet is already A4 wide (210mm, with 15mm side padding), so
  // it's captured as-is; only the hidden copy loses its shadow and top/bottom
  // padding — the PDF's own 12mm top/bottom margins replace those.
  html2pdf().set({
    margin: [12, 0, 12, 0],
    filename: <?= json_encode('School Report - ' . str_replace('–', '-', $scope) . '.pdf') ?>,
    image: { type: 'jpeg', quality: 0.95 },
    html2canvas: {
      scale: 2, useCORS: true, backgroundColor: '#ffffff',
      onclone: doc => {
        const page = doc.querySelector('.page');
        Object.assign(page.style, { margin: '0', minHeight: '0', paddingTop: '0', paddingBottom: '0', boxShadow: 'none' });
      },
    },
    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
    pagebreak: { mode: ['css', 'legacy'], avoid: ['.sec', '.signatures', 'tr', '.tile', '.insight'] },
  }).from(document.querySelector('.page')).save()
    .then(() => Swal.fire({ icon: 'success', title: 'PDF saved', timer: 1500, showConfirmButton: false }))
    .catch(() => Swal.fire({ icon: 'error', title: 'Could not create the PDF', text: 'Try Print and choose "Save as PDF" instead.' }));
}
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>

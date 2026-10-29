<?php
/**
 * Printable Phil-IRI report (Teacher\Reading::report()): DepEd letterhead,
 * school-wide figures by grade for every table, Beginning vs Year End, the
 * reading profile per section, then signatures. Submitted and approved
 * sections only. Always light — it's meant for paper / PDF.
 */
use App\Models\ReadingResultModel;

$fil     = $language === 'Filipino';
$tables  = ReadingResultModel::tables($language);
$L       = $fil ? ['m' => 'L', 'f' => 'B', 'total' => 'Blg.', 'grade' => 'Baitang', 'tested' => 'Kumuha'] : ['m' => 'M', 'f' => 'F', 'total' => 'Total', 'grade' => 'Grade', 'tested' => 'Tested'];
$pct     = static fn (?float $v) => $v === null ? '—' : rtrim(rtrim(number_format($v, 1), '0'), '.') . '%';
$lvTotal = static fn (array $b, string $t, string $lv) => $b['tables'][$t][$lv]['m'] + $b['tables'][$t][$lv]['f'];
$tested  = static fn (array $b) => $b['tested_m'] + $b['tested_f'];
$school  = $summary['school'];
$rowsBy  = $summary['grades'] + ['School' => $school];
$scope   = ReadingResultModel::PERIODS[$period] . ' · SY ' . str_replace('-', '–', $year);
$hasBoth = $tested($compare['Beginning']['school']) > 0 && $tested($compare['End']['school']) > 0;
$goodUp   = ['independent', 'instructional', 'spcp'];
$goodDown = ['frustration', 'nonreader', 'down3', 'down2', 'smcm'];

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
<title>Phil-IRI <?= e($language) ?> Report — <?= e($scope) ?> — ACADOCS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="icon" type="image/png" href="<?= asset_url('assets/img/logo-icon.png') ?>">
<link rel="stylesheet" href="<?= asset_url('assets/css/report-print.css') ?>">
<style>
  /* Level tables: grade rows × (M, F, Total, %) per level — kept inside the A4 width. */
  table.levels { table-layout: fixed; font-size: 9.5px; }
  table.levels th, table.levels td { padding: 3px 2px; text-align: center; }
  table.levels th:first-child, table.levels td:first-child { width: 58px; text-align: left; }
  table.levels thead th { font-size: 8.5px; letter-spacing: 0; line-height: 1.2; white-space: normal; }
  table.levels thead tr + tr th { background: #9a2a2a; }
  table.levels td.lv-pct { font-weight: 600; }
  table.levels tr.school td { font-weight: 700; border-top: 2px solid var(--maroon); background: #fdf2f2; }
  @media print { table.levels thead tr + tr th, table.levels tr.school td { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
</style>
</head>
<body>

<div class="toolbar">
  <button type="button" class="primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
  <button type="button" class="primary" onclick="saveReadingPdf()"><i class="bi bi-file-earmark-pdf me-1"></i> Save as PDF</button>
  <button type="button" class="ghost" onclick="window.close()">Close</button>
</div>

<div class="page">
  <?php include APPPATH . 'Views/partials/report_letterhead.php'; ?>

  <div class="report-title">
    <h1>Phil-IRI <?= e($language) ?> Report</h1>
    <div class="scope">Philippine Informal Reading Inventory · Grades 7–10 · <?= e($scope) ?></div>
  </div>
  <div class="meta">
    <span>School ID: 301125</span>
    <span>Sections reported: <?= count($reported) ?> of <?= (int) $sectionsTotal ?></span>
    <span>Generated <?= date('F j, Y g:i A') ?> by <?= e($generatedBy) ?></span>
  </div>

  <?php if ($reported === []): ?>
  <div class="empty" style="margin-top:18px">No section has been submitted for <?= e($scope) ?> yet. The report fills in as sections are submitted and approved.</div>
  <?php else: ?>

  <section class="sec">
    <?= $heading('Summary', 'bi-clipboard-data') ?>
    <div class="tiles">
      <div class="tile"><div class="label">Learners tested</div><div class="value"><?= number_format($tested($school)) ?></div><div class="delta muted">of <?= number_format($school['enrolled_m'] + $school['enrolled_f']) ?> enrolled (<?= $pct(ReadingResultModel::pct($tested($school), $school['enrolled_m'] + $school['enrolled_f'])) ?>)</div></div>
      <div class="tile"><div class="label"><?= e($tables['rp']['levels']['independent']) ?></div><div class="value"><?= $pct(ReadingResultModel::pct($lvTotal($school, 'rp', 'independent'), $tested($school))) ?></div><div class="delta muted"><?= number_format($lvTotal($school, 'rp', 'independent')) ?> learners</div></div>
      <div class="tile"><div class="label"><?= e($tables['rp']['levels']['instructional']) ?></div><div class="value"><?= $pct(ReadingResultModel::pct($lvTotal($school, 'rp', 'instructional'), $tested($school))) ?></div><div class="delta muted"><?= number_format($lvTotal($school, 'rp', 'instructional')) ?> learners</div></div>
      <div class="tile"><div class="label"><?= e($tables['rp']['levels']['frustration'] . ' + ' . $tables['rp']['levels']['nonreader']) ?></div><div class="value"><?= $pct(ReadingResultModel::pct($lvTotal($school, 'rp', 'frustration') + $lvTotal($school, 'rp', 'nonreader'), $tested($school))) ?></div><div class="delta muted"><?= number_format($lvTotal($school, 'rp', 'frustration') + $lvTotal($school, 'rp', 'nonreader')) ?> learners need support</div></div>
    </div>
  </section>

  <section class="sec">
    <?= $heading($fil ? 'Bilang ng Naka-enroll at Kumuha ng Pagtataya' : 'Learners Enrolled and Tested', 'bi-people') ?>
    <table>
      <thead><tr><th><?= e($L['grade']) ?></th><th class="num">Sections</th><th class="num">Enrolled <?= $L['m'] ?></th><th class="num">Enrolled <?= $L['f'] ?></th><th class="num">Enrolled</th><th class="num"><?= e($L['tested']) ?> <?= $L['m'] ?></th><th class="num"><?= e($L['tested']) ?> <?= $L['f'] ?></th><th class="num"><?= e($L['tested']) ?></th><th class="num">% Tested</th></tr></thead>
      <tbody>
        <?php foreach ($summary['grades'] as $g => $b): ?>
        <tr><td><?= e($g) ?></td><td class="num"><?= (int) $b['sections'] ?></td><td class="num"><?= $b['enrolled_m'] ?></td><td class="num"><?= $b['enrolled_f'] ?></td><td class="num"><?= $b['enrolled_m'] + $b['enrolled_f'] ?></td><td class="num"><?= $b['tested_m'] ?></td><td class="num"><?= $b['tested_f'] ?></td><td class="num"><?= $tested($b) ?></td><td class="num"><?= $pct(ReadingResultModel::pct($tested($b), $b['enrolled_m'] + $b['enrolled_f'])) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot><tr><td>Whole school</td><td class="num"><?= (int) $school['sections'] ?></td><td class="num"><?= $school['enrolled_m'] ?></td><td class="num"><?= $school['enrolled_f'] ?></td><td class="num"><?= $school['enrolled_m'] + $school['enrolled_f'] ?></td><td class="num"><?= $school['tested_m'] ?></td><td class="num"><?= $school['tested_f'] ?></td><td class="num"><?= $tested($school) ?></td><td class="num"><?= $pct(ReadingResultModel::pct($tested($school), $school['enrolled_m'] + $school['enrolled_f'])) ?></td></tr></tfoot>
    </table>
  </section>

  <?php foreach ($tables as $t => $def): ?>
  <section class="sec">
    <?= $heading($def['title'], ['wr' => 'bi-fonts', 'rc' => 'bi-chat-square-text', 'rp' => 'bi-bar-chart-steps', 'gst' => 'bi-list-check', 'tala' => 'bi-speedometer2'][$t]) ?>
    <table class="levels">
      <thead>
        <tr><th rowspan="2"><?= e($L['grade']) ?></th><th rowspan="2"><?= e($L['tested']) ?></th><?php foreach ($def['levels'] as $lvLabel): ?><th colspan="4"><?= e($lvLabel) ?></th><?php endforeach; ?></tr>
        <tr><?php foreach ($def['levels'] as $_): ?><th><?= $L['m'] ?></th><th><?= $L['f'] ?></th><th><?= e($L['total']) ?></th><th>%</th><?php endforeach; ?></tr>
      </thead>
      <tbody>
        <?php foreach ($rowsBy as $g => $b): ?>
        <tr class="<?= $g === 'School' ? 'school' : '' ?>">
          <td><?= $g === 'School' ? 'School' : e(str_replace('Grade ', $fil ? 'Baitang ' : 'Grade ', $g)) ?></td>
          <td><?= $tested($b) ?></td>
          <?php foreach (array_keys($def['levels']) as $lv): $c = $b['tables'][$t][$lv]; ?>
          <td><?= $c['m'] ?></td><td><?= $c['f'] ?></td><td><?= $c['m'] + $c['f'] ?></td><td class="lv-pct"><?= $pct(ReadingResultModel::pct($c['m'] + $c['f'], $tested($b))) ?></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if ($t === 'gst'): ?><p class="note">GST score bands: <?php foreach (ReadingResultModel::GST_BANDS as $i => $band): ?><?= $i === 'down3' ? '' : ' · ' ?><?= e($band[$language]) ?> <?= e($band['range']) ?><?php endforeach; ?>.</p><?php endif; ?>
    <?php if ($t === 'tala'): ?><p class="note">S = reading speed, C = comprehension (+ meets / − below the expected level).</p><?php endif; ?>
  </section>
  <?php endforeach; ?>

  <?php if ($hasBoth): ?>
  <section class="sec">
    <?= $heading('Beginning vs Year End (Gain / Loss)', 'bi-graph-up-arrow') ?>
    <table>
      <thead><tr><th>Level</th><th class="num">Beginning</th><th class="num">Year End</th><th class="num">Change</th></tr></thead>
      <tbody>
        <?php foreach ($tables as $t => $def): if ($t === 'wr' || $t === 'rc') continue; ?>
        <tr><td colspan="4" style="font-weight:700;color:var(--maroon)"><?= e($def['title']) ?></td></tr>
        <?php foreach ($def['levels'] as $lv => $lvLabel):
          $b = ReadingResultModel::pct($lvTotal($compare['Beginning']['school'], $t, $lv), $tested($compare['Beginning']['school']));
          $y = ReadingResultModel::pct($lvTotal($compare['End']['school'], $t, $lv), $tested($compare['End']['school']));
          $d = $b !== null && $y !== null ? round($y - $b, 1) : null;
          $dir  = in_array($lv, $goodUp, true) ? 1 : (in_array($lv, $goodDown, true) ? -1 : 0);
          $tone = $d === null || $d == 0 || $dir === 0 ? '' : ($d * $dir > 0 ? 'good' : 'bad');
        ?>
        <tr><td><?= e($lvLabel) ?></td><td class="num"><?= $pct($b) ?></td><td class="num"><?= $pct($y) ?></td><td class="num <?= $tone ?>"><?= $d === null ? '—' : ($d > 0 ? '+' : '') . rtrim(rtrim(number_format($d, 1), '0'), '.') . ' pp' ?></td></tr>
        <?php endforeach; endforeach; ?>
      </tbody>
    </table>
    <p class="note">Percentage points of the learners tested, whole school. Green is an improvement.</p>
  </section>
  <?php endif; ?>

  <section class="sec" style="break-inside:auto;page-break-inside:auto">
    <?= $heading($fil ? 'Antas ng Kakayahan sa Pagbasa bawat Seksyon' : 'Reading Profile per Section', 'bi-grid-3x3-gap') ?>
    <table class="compact">
      <thead><tr><th><?= e($L['grade']) ?></th><th>Section</th><th class="num"><?= e($L['tested']) ?></th><?php foreach ($tables['rp']['levels'] as $lvLabel): ?><th class="num"><?= e($lvLabel) ?></th><?php endforeach; ?><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($reported as $r): $c = ReadingResultModel::decodeCounts($r['counts']); $rt = (int) $r['tested_m'] + (int) $r['tested_f']; ?>
        <tr>
          <td><?= e($r['grade_level']) ?></td>
          <td><?= e($r['section']) ?></td>
          <td class="num"><?= $rt ?></td>
          <?php foreach (array_keys($tables['rp']['levels']) as $lv): $n = (int) ($c['rp'][$lv]['m'] ?? 0) + (int) ($c['rp'][$lv]['f'] ?? 0); ?>
          <td class="num"><?= $n ?> <span class="muted">(<?= $pct(ReadingResultModel::pct($n, $rt)) ?>)</span></td>
          <?php endforeach; ?>
          <td><?= e($r['status']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
  <?php endif; ?>

  <div class="signatures">
    <div class="sig">
      <div class="lbl">Prepared by:</div>
      <div class="name"><?= e($preparedBy) ?></div>
      <div class="role"><?= e($preparedRole) ?></div>
    </div>
    <div class="sig">
      <div class="lbl">Noted by:</div>
      <div class="name"><?= e($principalName) ?></div>
      <div class="role">School Principal</div>
    </div>
  </div>
  <div class="footer">ACADOCS · Matabungkay National High School · Phil-IRI <?= e($language) ?>, <?= e($scope) ?> · submitted and approved sections</div>
</div>

<script>
/* Save as PDF: downloads straight to a file, no print dialog (same approach as the school report). */
function saveReadingPdf() {
  Swal.fire({ title: 'Saving as PDF...', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
  html2pdf().set({
    margin: [12, 0, 12, 0],
    filename: <?= json_encode('Phil-IRI ' . $language . ' Report - ' . ReadingResultModel::PERIODS[$period] . ' SY ' . $year . '.pdf') ?>,
    image: { type: 'jpeg', quality: 0.95 },
    html2canvas: {
      scale: 2, useCORS: true, backgroundColor: '#ffffff',
      onclone: doc => {
        const page = doc.querySelector('.page');
        Object.assign(page.style, { margin: '0', minHeight: '0', paddingTop: '0', paddingBottom: '0', boxShadow: 'none' });
      },
    },
    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
    pagebreak: { mode: ['css', 'legacy'], avoid: ['.sec', '.signatures', 'tr', '.tile'] },
  }).from(document.querySelector('.page')).save()
    .then(() => Swal.fire({ icon: 'success', title: 'PDF saved', timer: 1500, showConfirmButton: false }))
    .catch(() => Swal.fire({ icon: 'error', title: 'Could not create the PDF', text: 'Try Print and choose "Save as PDF" instead.' }));
}
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>

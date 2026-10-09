<?php
/**
 * End-of-term / end-of-year report pack (Admin\TermPack, data from
 * App\Libraries\TermPack). Printable A4 page; "Save as PDF" downloads it.
 *
 * @var array<string,mixed> $pack
 */
$scope   = 'SY ' . str_replace('-', '–', $pack['year']) . ($pack['term'] === 'year' ? '' : ' · Term ' . $pack['term']);
$period  = date('M d, Y', strtotime($pack['start'])) . ' – ' . date('M d, Y', strtotime($pack['end']));
$pct     = static fn ($v) => $v === null ? '—' : number_format((float) $v, 1) . '%';
$mps     = static fn ($v) => $v === null ? '—' : number_format((float) $v, 2) . '%';
$desc    = static fn ($v) => $v === null ? '' : mpsDescriptor((float) $v)['label'];
$sectionNo = 0;
$heading = static function (string $title, string $icon) use (&$sectionNo): string {
    $sectionNo++;

    return '<h2 class="sec-title"><span class="sec-no">' . $sectionNo . '</span><i class="bi ' . $icon . '"></i>' . e($title) . '</h2>';
};
$att   = $pack['attendance'];
$tasks = $pack['tasks'];
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pack['label']) ?> — <?= e($scope) ?> — ACADOCS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="icon" type="image/png" href="<?= base_url('assets/img/logo-icon.png') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/report-print.css') ?>">
<style>
  .toolbar select { font: inherit; border: 0; border-radius: 8px; padding: .45rem .6rem; }
  .partial { margin: 8px 0 0; padding: 7px 10px; border-radius: 6px; background: #fff7ed; color: #9a3412; font-size: 11px; }
</style>
</head>
<body>

<div class="toolbar">
  <form method="GET" action="<?= base_url('reports/term-pack') ?>" style="display:flex;gap:.5rem;">
    <select name="year" onchange="this.form.submit()" aria-label="School year">
      <?php foreach ($years as $y): ?>
      <option value="<?= e($y) ?>" <?= $y === $pack['year'] ? 'selected' : '' ?>>SY <?= e(str_replace('-', '–', $y)) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="term" onchange="this.form.submit()" aria-label="Period">
      <?php foreach ($terms as $t): ?>
      <option value="<?= $t ?>" <?= (string) $t === $pack['term'] ? 'selected' : '' ?>>Term <?= $t ?></option>
      <?php endforeach; ?>
      <option value="year" <?= $pack['term'] === 'year' ? 'selected' : '' ?>>Whole school year</option>
    </select>
  </form>
  <button type="button" class="primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
  <button type="button" class="primary" onclick="savePackPdf()"><i class="bi bi-file-earmark-pdf me-1"></i> Save as PDF</button>
  <button type="button" class="ghost" onclick="window.close()"><i class="bi bi-x-lg"></i> Close</button>
</div>

<div class="page">
  <?php include APPPATH . 'Views/partials/report_letterhead.php'; ?>

  <div class="report-title">
    <h1><?= e($pack['label']) ?></h1>
    <div class="scope"><?= e($scope) ?> · <?= e($period) ?></div>
  </div>
  <div class="meta">
    <span><i class="bi bi-calendar3"></i> Generated <?= date('F d, Y · h:i A') ?></span>
    <span><i class="bi bi-person"></i> by <?= e($generatedBy) ?></span>
  </div>
  <?php if (! $pack['complete']): ?>
  <div class="partial"><i class="bi bi-hourglass-split"></i> This period isn't over yet: figures cover up to <?= date('F d, Y', strtotime($pack['asOf'])) ?>.</div>
  <?php endif; ?>

  <section class="sec">
    <?= $heading('Key Figures', 'bi-speedometer2') ?>
    <div class="tiles">
      <div class="tile">
        <div class="label">Enrolled</div>
        <div class="value"><?= number_format($pack['enrollment']['total']) ?></div>
        <div class="delta muted"><?= $pack['enrollment']['month'] ? 'as of ' . date('F Y', strtotime($pack['enrollment']['month'] . '-01')) : 'latest count' ?></div>
      </div>
      <div class="tile">
        <div class="label">Staff Attendance</div>
        <div class="value"><?= $pct($att['rate']) ?></div>
        <div class="delta muted"><?= $att['schoolDays'] ?> school days</div>
      </div>
      <div class="tile">
        <div class="label">Average MPS</div>
        <div class="value"><?= $mps($pack['mps']['overall']) ?></div>
        <div class="delta <?= $pack['mps']['overall'] !== null && $pack['mps']['overall'] < 75 ? 'bad' : 'good' ?>"><?= e($desc($pack['mps']['overall'])) ?></div>
      </div>
      <div class="tile">
        <div class="label">Task Compliance</div>
        <div class="value"><?= $pct($tasks['rate']) ?></div>
        <div class="delta muted"><?= $tasks['totals']['submitted'] ?> of <?= $tasks['totals']['assigned'] ?> submitted</div>
      </div>
    </div>
  </section>

  <section class="sec">
    <?= $heading('Enrollment', 'bi-people-fill') ?>
    <?php if ($pack['enrollment']['rows'] === []): ?>
    <div class="empty">No enrollment figures for SY <?= e(str_replace('-', '–', $pack['year'])) ?>.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Grade Level</th><th class="num">Sections</th><th class="num">Male</th><th class="num">Female</th><th class="num">Students</th></tr></thead>
      <tbody>
        <?php foreach ($pack['enrollment']['rows'] as $e): ?>
        <tr>
          <td><?= e($e['grade_level']) ?></td>
          <td class="num"><?= (int) $e['sections'] ?></td>
          <td class="num"><?= $e['male'] !== null ? number_format((int) $e['male']) : '—' ?></td>
          <td class="num"><?= $e['female'] !== null ? number_format((int) $e['female']) : '—' ?></td>
          <td class="num"><?= number_format((int) $e['students']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot><tr><td>Total</td><td class="num"><?= array_sum(array_map(static fn ($e) => (int) $e['sections'], $pack['enrollment']['rows'])) ?></td><td></td><td></td><td class="num"><?= number_format($pack['enrollment']['total']) ?></td></tr></tfoot>
    </table>
    <?php endif; ?>
  </section>

  <section class="sec">
    <?= $heading('Staff Attendance', 'bi-clock-history') ?>
    <?php if ($att['people'] === []): ?>
    <div class="empty">No time records for this period.</div>
    <?php else: ?>
    <p class="note" style="margin:0 0 6px;">
      <?= $att['schoolDays'] ?> school days (weekdays, excluding holidays and academic breaks) ·
      <?= number_format($att['totals']['Present']) ?> present, <?= number_format($att['totals']['Late']) ?> late,
      <?= number_format($att['totals']['Absent']) ?> absent, <?= number_format($att['totals']['On Leave']) ?> on leave.
      Rate = days attended ÷ (attended + absent).
    </p>
    <table>
      <thead><tr><th>Employee</th><th class="num">Present</th><th class="num">Late</th><th class="num">Absent</th><th class="num">On Leave</th><th class="num">Rate</th></tr></thead>
      <tbody>
        <?php foreach ($att['people'] as $p): ?>
        <tr>
          <td><?= e($p['name']) ?></td>
          <td class="num"><?= $p['counts']['Present'] ?></td>
          <td class="num"><?= $p['counts']['Late'] ?></td>
          <td class="num <?= $p['counts']['Absent'] > 0 ? 'bad' : '' ?>"><?= $p['counts']['Absent'] ?></td>
          <td class="num"><?= $p['counts']['On Leave'] ?></td>
          <td class="num"><?= $pct($p['rate']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>

  <section class="sec">
    <?= $heading('MPS by Learning Area', 'bi-mortarboard-fill') ?>
    <?php if ($pack['mps']['subjects'] === []): ?>
    <div class="empty">No MPS recorded for this period.</div>
    <?php else: ?>
    <table>
      <thead>
        <tr><th>Learning Area</th><?php foreach ($pack['mps']['columns'] as $c): ?><th class="num"><?= e($c) ?></th><?php endforeach; ?><th class="num">Average</th><th>Descriptor</th></tr>
      </thead>
      <tbody>
        <?php foreach ($pack['mps']['subjects'] as $s): ?>
        <tr>
          <td><?= e($s['subject']) ?></td>
          <?php foreach ($pack['mps']['columns'] as $c): ?>
          <td class="num"><?= isset($s['values'][$c]) ? $mps($s['values'][$c]) : '—' ?></td>
          <?php endforeach; ?>
          <td class="num <?= $s['avg'] < 75 ? 'bad' : '' ?>"><strong><?= $mps($s['avg']) ?></strong></td>
          <td><?= e($desc($s['avg'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p class="note">Descriptors: 90–100 Advancing, 80–89 Benchmarking, 75–79 Connecting, 65–74 Developing, below 65 Emerging. Below 75% shown in red.</p>

    <?php if ($pack['classes'] !== []): ?>
    <h3 style="font-size:12px;margin:12px 0 6px;">Classes below 75% (Developing or Emerging)</h3>
    <table>
      <thead><tr><?= $pack['term'] === 'year' ? '<th>Term</th>' : '' ?><th>Grade Level</th><th>Learning Area</th><th>Section</th><th class="num">MPS</th><th>Descriptor</th></tr></thead>
      <tbody>
        <?php foreach ($pack['classes'] as $c): ?>
        <tr>
          <?= $pack['term'] === 'year' ? '<td>' . (int) $c['term'] . '</td>' : '' ?>
          <td><?= e($c['grade']) ?></td>
          <td><?= e($c['subject']) ?></td>
          <td><?= e($c['section'] ?? '—') ?></td>
          <td class="num bad"><?= $mps($c['avg']) ?></td>
          <td><?= e($desc($c['avg'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
    <?php endif; ?>
  </section>

  <section class="sec">
    <?= $heading('Task Compliance', 'bi-list-check') ?>
    <?php if ($tasks['rows'] === []): ?>
    <div class="empty">No tasks were due in this period.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Task</th><th>Due</th><th class="num">Assigned</th><th class="num">On time</th><th class="num">Late</th><th class="num">Missing</th><th class="num">Compliance</th></tr></thead>
      <tbody>
        <?php foreach ($tasks['rows'] as $t): ?>
        <tr>
          <td><?= e($t['title']) ?></td>
          <td><?= date('M d', strtotime($t['deadline'])) ?></td>
          <td class="num"><?= $t['assigned'] ?></td>
          <td class="num"><?= $t['onTime'] ?></td>
          <td class="num"><?= $t['late'] ?></td>
          <td class="num <?= $t['missing'] > 0 ? 'bad' : '' ?>"><?= $t['missing'] ?></td>
          <td class="num"><?= $pct($t['rate']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="2">Total</td>
          <td class="num"><?= $tasks['totals']['assigned'] ?></td>
          <td class="num"><?= $tasks['totals']['onTime'] ?></td>
          <td class="num"><?= $tasks['totals']['late'] ?></td>
          <td class="num"><?= $tasks['totals']['missing'] ?></td>
          <td class="num"><?= $pct($tasks['rate']) ?></td>
        </tr>
      </tfoot>
    </table>
    <?php if ($tasks['missingPeople'] !== []): ?>
    <p class="note">Most missing submissions: <?= e(implode(', ', array_map(static fn ($p) => $p['name'] . ' (' . $p['missing'] . ')', $tasks['missingPeople']))) ?>.</p>
    <?php endif; ?>
    <?php endif; ?>
  </section>

  <div class="signatures">
    <div class="sig">
      <div class="lbl">Prepared by:</div>
      <div class="name"><?= e($generatedBy) ?></div>
      <div class="role"><?= e(['admin' => 'School Principal', 'adas' => 'Administrative Assistant'][currentUser()['role'] ?? ''] ?? '') ?></div>
    </div>
    <div class="sig">
      <div class="lbl">Noted by:</div>
      <div class="name"><?= e($principalName) ?></div>
      <div class="role">School Principal</div>
    </div>
  </div>
  <div class="footer">ACADOCS · Matabungkay National High School · <?= e($pack['label']) ?>, <?= e($scope) ?></div>
</div>

<script>
/* Save as PDF: downloads the pack straight to a file, no print dialog (same approach as the school report). */
function savePackPdf() {
  Swal.fire({ title: 'Saving as PDF...', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
  html2pdf().set({
    margin: [12, 0, 12, 0],
    filename: <?= json_encode($pack['label'] . ' - ' . str_replace('–', '-', $scope) . '.pdf') ?>,
    image: { type: 'jpeg', quality: 0.95 },
    html2canvas: {
      scale: 2, useCORS: true, backgroundColor: '#ffffff',
      onclone: doc => {
        const page = doc.querySelector('.page');
        Object.assign(page.style, { margin: '0', minHeight: '0', paddingTop: '0', paddingBottom: '0', boxShadow: 'none' });
      },
    },
    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
    pagebreak: { mode: ['css', 'legacy'], avoid: ['.signatures', 'tr', '.tile'] },
  }).from(document.querySelector('.page')).save()
    .then(() => Swal.fire({ icon: 'success', title: 'PDF saved', timer: 1500, showConfirmButton: false }))
    .catch(() => Swal.fire({ icon: 'error', title: 'Could not create the PDF', text: 'Try Print and choose "Save as PDF" instead.' }));
}
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>

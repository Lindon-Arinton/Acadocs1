<?php include APPPATH . 'Views/layout/header.php'; ?>

<?php
$personLabel = static fn (array $p) => $p['name'] . ' · AC-' . $p['ac_no'];
$reportUrl   = static fn (string $f, string $t) => base_url('time-records/report') . '?' . http_build_query(array_filter([
    'person' => $acNo, 'from' => $f, 'to' => $t,
]));

$quickRanges = [
    'This week'  => [date('Y-m-d', strtotime('monday this week')), date('Y-m-d')],
    'This month' => [date('Y-m-01'), date('Y-m-d')],
    'Last month' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
    'This year'  => [date('Y-01-01'), date('Y-m-d')],
];

$datePicker = static fn (string $name, string $value) => '
              <div class="maroon-dp">
                <input type="text" class="form-control form-control-sm maroon-dp-display" value="' . date('M j, Y', strtotime($value)) . '" readonly required>
                <input type="hidden" name="' . $name . '" value="' . e($value) . '">
                <div class="maroon-dp-panel">
                  <div class="maroon-dp-header">
                    <button type="button" class="maroon-dp-nav" data-dir="-1"><i class="bi bi-chevron-left"></i></button>
                    <span class="maroon-dp-month-label"></span>
                    <button type="button" class="maroon-dp-nav" data-dir="1"><i class="bi bi-chevron-right"></i></button>
                  </div>
                  <div class="maroon-dp-dow"><span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span></div>
                  <div class="maroon-dp-grid"></div>
                </div>
              </div>';

$icons = ['Present' => 'bi-check-circle-fill', 'Late' => 'bi-alarm-fill', 'Absent' => 'bi-x-circle-fill', 'On Leave' => 'bi-calendar-check', 'Academic Break' => 'bi-calendar-range-fill'];
?>

<div class="page-header print-hide">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h4><i class="bi bi-file-earmark-bar-graph me-2"></i>Attendance Report</h4>
      <p>One person's attendance over the dates you choose</p>
    </div>
    <a href="<?= base_url('time-records') ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-arrow-left me-1"></i>Back to Time Records</a>
  </div>
</div>

<!-- Filters -->
<div class="card mb-4 print-hide">
  <div class="card-body py-3">
    <form method="GET" action="<?= base_url('time-records/report') ?>" class="row g-2 align-items-end">
      <?php if ($canPickAnyone): ?>
      <div class="col-md-5">
        <label class="form-label">Person</label>
        <input type="search" name="person" class="form-control form-control-sm" list="reportPeople" autocomplete="off" required
               placeholder="Type a name or AC-No…" value="<?= $person ? e($personLabel($person)) : '' ?>">
        <datalist id="reportPeople">
          <?php foreach ($people as $p): ?>
          <option value="<?= e($personLabel($p)) ?>">
          <?php endforeach; ?>
        </datalist>
      </div>
      <?php endif; ?>
      <div class="col-6 col-md-2">
        <label class="form-label">From</label><?= $datePicker('from', $from) ?>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label">To</label><?= $datePicker('to', $to) ?>
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Show Attendance</button>
      </div>
    </form>
    <div class="d-flex gap-2 flex-wrap mt-2">
      <?php foreach ($quickRanges as $label => [$qf, $qt]): ?>
      <a href="<?= $reportUrl($qf, $qt) ?>" class="btn btn-outline-secondary btn-sm py-0 <?= $from === $qf && $to === $qt ? 'active' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php if ($acNo === '' || $person === null): ?>
<div class="card">
  <div class="card-body text-center py-5 text-muted">
    <i class="bi bi-person-lines-fill fs-1 d-block mb-3"></i>
    <?php if (! $canPickAnyone): ?>
    Your account has no biometric number yet, so there's no attendance to show. Ask the administrator to set it.
    <?php elseif ($acNo !== ''): ?>
    No one with AC-<?= e($acNo) ?> was found. Pick a person from the list.
    <?php else: ?>
    Choose a person and a date range to see their attendance.
    <?php endif; ?>
  </div>
</div>
<?php else: ?>

<!-- Report -->
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
      <div>
        <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.04em;">Attendance Report</div>
        <h5 class="mb-1"><?= personLink($person['user_id'], $person['name']) ?></h5>
        <div class="text-muted small">
          AC-<?= e($person['ac_no']) ?> · <?= e($person['role']) ?> ·
          <?= date('M j, Y', strtotime($from)) ?> – <?= date('M j, Y', strtotime($to)) ?>
        </div>
      </div>
      <div class="d-flex gap-2 print-hide">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="exportReportCsv()"><i class="bi bi-download me-1"></i>Export CSV</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
      </div>
    </div>

    <div class="row row-cols-2 row-cols-sm-4 row-cols-lg-7 g-2">
      <div class="col">
        <div class="report-stat"><div class="k">Attendance rate</div><div class="v"><?= $rate === null ? '—' : $rate . '%' ?></div></div>
      </div>
      <?php foreach ($totals as $status => $n): ?>
      <div class="col">
        <div class="report-stat">
          <div class="k"><span class="badge badge-<?= strtolower(str_replace(' ', '-', $status)) ?>"><i class="bi <?= $icons[$status] ?>"></i> <?= e($status) ?></span></div>
          <div class="v"><?= (int) $n ?></div>
        </div>
      </div>
      <?php endforeach; ?>
      <div class="col">
        <div class="report-stat"><div class="k">Total tardiness</div><div class="v"><?= intdiv($lateMinutes, 60) ? intdiv($lateMinutes, 60) . 'h ' : '' ?><?= $lateMinutes % 60 ?>m</div></div>
      </div>
    </div>
    <div class="text-muted mt-2" style="font-size:.75rem;">
      Attendance rate = (Present + Late) ÷ (Present + Late + Absent). Tardiness counts minutes after <?= date('g:i A', strtotime(\App\Libraries\TimeRecordImporter::LATE_THRESHOLD)) ?> on Late days.
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table mb-0" id="report-table">
        <thead>
          <tr>
            <th>Date</th><th>Day</th>
            <th class="text-center">Time In</th>
            <th class="text-center">Time Out</th>
            <th class="text-center">Hours</th>
            <th class="text-center">Status</th>
            <th>Remarks</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $r): ?>
          <?php
            $hours = $r['time_in'] && $r['time_out'] ? (strtotime($r['time_out']) - strtotime($r['time_in'])) / 3600 : null;
          ?>
          <tr>
            <td class="text-nowrap"><a href="<?= base_url('time-records?date=' . $r['date']) ?>" class="text-reset"><?= date('M j, Y', strtotime($r['date'])) ?></a></td>
            <td class="text-muted"><?= date('D', strtotime($r['date'])) ?></td>
            <td class="text-center"><?= $r['time_in'] ? date('h:i A', strtotime($r['time_in'])) : '<span class="text-muted">—</span>' ?></td>
            <td class="text-center"><?= $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : '<span class="text-muted">—</span>' ?></td>
            <td class="text-center"><?= $hours !== null ? number_format($hours, 1) : '<span class="text-muted">—</span>' ?></td>
            <td class="text-center">
              <span class="badge badge-<?= strtolower(str_replace(' ', '-', $r['status'])) ?>"><i class="bi <?= $icons[$r['status']] ?? 'bi-circle' ?>"></i> <?= e($r['status']) ?></span>
            </td>
            <td class="text-muted" style="font-size:.78rem;"><?= e($r['remarks'] ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($records)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No attendance records in this date range.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$csvName = $person ? json_encode('attendance_' . preg_replace('/[^A-Za-z0-9]+/', '_', $person['name']) . "_{$from}_{$to}") : '""';
$extraScript = <<<HTML
<script>
function exportReportCsv() {
    var table = document.getElementById('report-table');
    if (!table) return;
    var rows = [...table.querySelectorAll('tr')].map(function (r) {
        return [...r.querySelectorAll('th,td')].map(function (c) { return JSON.stringify(c.innerText.trim()); }).join(',');
    });
    var a = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(new Blob([rows.join('\\n')], { type: 'text/csv' })),
        download: {$csvName} + '.csv'
    });
    a.click();
}
</script>
HTML;
include APPPATH . 'Views/layout/footer.php';
?>

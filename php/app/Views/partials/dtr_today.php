<?php
/**
 * "Daily Time Records — as of today" panel for the dashboards.
 * Expects $dtrToday from TimeRecordModel::daySummary().
 */
// label => [--s-* color token, icon]
$dtrStatusCfg = [
    'Present'  => ['present', 'bi-check-circle-fill'],
    'Late'     => ['late',    'bi-alarm-fill'],
    'Absent'   => ['absent',  'bi-x-circle-fill'],
    'On Leave' => ['leave',   'bi-calendar-check'],
];
// Only on days that have them (break days), so normal days keep four tiles.
if (($dtrToday['counts']['Academic Break'] ?? 0) > 0) {
    $dtrStatusCfg['Academic Break'] = ['break', 'bi-calendar-range-fill'];
}
$dtrShowMax = 5;
$dtrNames = static function (array $rows, bool $withTime) use ($dtrShowMax): string {
    $out = [];
    foreach (array_slice($rows, 0, $dtrShowMax) as $r) {
        $out[] = e($r['employee_name']) . ($withTime && $r['time_in'] ? ' <span class="text-muted">' . e(date('g:i A', strtotime($r['time_in']))) . '</span>' : '');
    }
    $more = count($rows) - $dtrShowMax;

    return implode('<br>', $out) . ($more > 0 ? '<br><span class="text-muted">+' . $more . ' more</span>' : '');
};
?>
<div class="card">
  <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
    <div>
      <span class="fw-semibold small"><i class="bi bi-clock me-2 text-muted"></i>Daily Time Records</span>
      <div class="text-muted" style="font-size:.7rem;">As of <?= date('g:i A') ?> today · <?= date('D, M j, Y', strtotime($dtrToday['date'])) ?></div>
    </div>
    <a href="<?= base_url('time-records') ?>" class="btn btn-sm btn-outline-primary rounded-pill py-0 px-3 text-nowrap flex-shrink-0" style="font-size:.7rem;">View All</a>
  </div>
  <div class="card-body card-body-tight">
    <div class="row g-2 text-center mb-2">
      <?php foreach ($dtrStatusCfg as $label => [$tok, $icon]): ?>
      <div class="col">
        <a href="<?= base_url('time-records?status=' . urlencode($label)) ?>" class="d-block text-decoration-none rounded-3 py-2" style="background:var(--s-<?= $tok ?>-bg);">
          <div class="fw-bold" style="font-size:1.25rem;line-height:1.1;color:var(--s-<?= $tok ?>-tx);"><?= (int) $dtrToday['counts'][$label] ?></div>
          <div style="font-size:.64rem;color:var(--s-<?= $tok ?>-tx);"><i class="bi <?= $icon ?> me-1"></i><?= $label ?></div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($dtrToday['total'] === 0): ?>
    <p class="text-muted small mb-0 text-center">
      No time records for today yet.
      <?php if ($dtrToday['lastDate']): ?>
      <br>Last import: <a href="<?= base_url('time-records?date=' . e($dtrToday['lastDate'])) ?>"><?= date('M j, Y', strtotime($dtrToday['lastDate'])) ?></a>
      <?php endif; ?>
    </p>
    <?php else: ?>
    <div class="row g-2 small">
      <div class="col-6">
        <div class="fw-semibold mb-1" style="color:var(--s-late-tx);font-size:.72rem;">LATE (<?= count($dtrToday['late']) ?>)</div>
        <div style="font-size:.74rem;line-height:1.45;"><?= $dtrToday['late'] ? $dtrNames($dtrToday['late'], true) : '<span class="text-muted">No one</span>' ?></div>
      </div>
      <div class="col-6">
        <div class="fw-semibold mb-1 text-danger" style="font-size:.72rem;">ABSENT (<?= count($dtrToday['absent']) ?>)</div>
        <div style="font-size:.74rem;line-height:1.45;"><?= $dtrToday['absent'] ? $dtrNames($dtrToday['absent'], false) : '<span class="text-muted">No one</span>' ?></div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

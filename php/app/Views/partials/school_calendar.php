<?php
/**
 * "School Calendar" card for the dashboards: where today falls in the DepEd
 * three-term calendar (Config\SchoolCalendar) — term, block, days left, and
 * any academic break. Set $calendarCardClass before including to match the
 * surrounding cards. Renders nothing outside the configured school years.
 */
$cal = (new \App\Libraries\SchoolCalendar())->on();
if ($cal === null) {
    return;
}
$fmtRange = static fn (string $a, string $b) => date('M j', strtotime($a)) . ' – ' . date('M j, Y', strtotime($b));
?>
<div class="<?= $calendarCardClass ?? 'card' ?> cal-card">
  <div class="cal-head">
    <span class="cal-title"><i class="bi bi-calendar3-week"></i>School Calendar</span>
    <span class="cal-sy">SY <?= e(str_replace('-', '–', $cal['schoolYear'])) ?></span>
  </div>

  <?php if ($cal['break']): ?>
  <div class="cal-now">
    <span class="cal-term"><?= e($cal['break']['label']) ?></span>
    <span class="badge badge-academic-break">Academic Break</span>
  </div>
  <div class="cal-range"><?= $fmtRange($cal['break']['start_date'], $cal['break']['end_date']) ?></div>
  <?php if ($cal['nextTerm']): ?>
  <div class="cal-meta">Term <?= $cal['nextTerm']['term'] ?> starts <?= date('M j, Y', strtotime($cal['nextTerm']['start'])) ?></div>
  <?php endif; ?>

  <?php elseif ($cal['term'] !== null): ?>
  <div class="cal-now">
    <span class="cal-term">Term <?= $cal['term'] ?></span>
    <?php if ($cal['block']): ?><span class="cal-block"><?= e($cal['block']) ?></span><?php endif; ?>
  </div>
  <div class="cal-range"><?= $fmtRange($cal['termStart'], $cal['termEnd']) ?></div>
  <div class="cal-progress" title="<?= $cal['termProgress'] ?>% of the term"><div style="width:<?= $cal['termProgress'] ?>%"></div></div>
  <div class="cal-meta">
    <span><strong><?= $cal['daysLeft'] ?></strong> day<?= $cal['daysLeft'] === 1 ? '' : 's' ?> left in the term</span>
    <?php if ($cal['classDaysThisMonth'] !== null): ?>
    <span><strong><?= $cal['classDaysThisMonth'] ?></strong> class days in <?= date('F') ?></span>
    <?php endif; ?>
  </div>

  <?php else: ?>
  <div class="cal-now"><span class="cal-term">Between terms</span></div>
  <?php if ($cal['nextTerm']): ?>
  <div class="cal-meta">Term <?= $cal['nextTerm']['term'] ?> starts <?= date('M j, Y', strtotime($cal['nextTerm']['start'])) ?></div>
  <?php endif; ?>
  <?php endif; ?>
</div>

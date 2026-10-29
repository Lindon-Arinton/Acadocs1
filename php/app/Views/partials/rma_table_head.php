<?php
/**
 * Column headers of the RMA table, in the layout of DepEd's "School Rapid
 * Mathematics Assessment Results" sheet. Shared by the Math teacher's entry
 * page (pages/teacher/rma.php) and the principal's dashboard.
 */
use App\Models\RmaResultModel;
?>
<thead>
  <tr>
    <th rowspan="2">Grade</th>
    <th rowspan="2">Section</th>
    <th rowspan="2">Sex</th>
    <th rowspan="2" class="text-center">Total Enrolment</th>
    <th rowspan="2" class="text-center">No. of Learners Assessed</th>
    <th colspan="<?= count(RmaResultModel::LEVELS) ?>" class="text-center rma-group">Number of Learners Assessed</th>
    <th colspan="<?= count(RmaResultModel::CONTENT) + 1 ?>" class="text-center rma-group">Content Domain</th>
    <th colspan="<?= count(RmaResultModel::COGNITIVE) + 1 ?>" class="text-center rma-group">Cognitive Domain</th>
    <th rowspan="2" class="text-center">Mean Score</th>
    <th rowspan="2">Status</th>
  </tr>
  <tr>
    <?php foreach (RmaResultModel::LEVELS as [$band, $level]): ?>
    <th class="text-center rma-sub"><?= e($band) ?><br><span class="fw-normal">(<?= e($level) ?>)</span></th>
    <?php endforeach; ?>
    <?php foreach (RmaResultModel::CONTENT as $label): ?>
    <th class="text-center rma-sub"><?= e($label) ?></th>
    <?php endforeach; ?>
    <th class="text-center rma-sub">Total</th>
    <?php foreach (RmaResultModel::COGNITIVE as $label): ?>
    <th class="text-center rma-sub"><?= e($label) ?></th>
    <?php endforeach; ?>
    <th class="text-center rma-sub">Total</th>
  </tr>
</thead>

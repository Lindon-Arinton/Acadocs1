<?php
/**
 * The school-wide RMA table (DepEd "School Rapid Mathematics Assessment
 * Results" layout) — principal's RMA page and dashboard. Expects $rma from
 * RmaResultModel::schoolTable(). Period buttons keep the page's other
 * query parameters; Export is window.rmaExport() in footer.php.
 */
?>
<?php
$rmaNum   = static fn ($v) => $v === null || $v === '' ? '' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
$rmaLink  = static fn (string $p) => current_url() . '?' . http_build_query(array_merge($_GET, ['rma_period' => $p])) . '#rma';
$rmaTotal = array_fill_keys(\App\Models\RmaResultModel::COUNT_FIELDS, 0);
?>
<!-- Rapid Mathematics Assessment (RMA): every Math section, entered by its Math teacher (Teacher\Rma) -->
<div class="card dash-card mb-4 rma-card" id="rma">
  <div class="rma-sheet-head">
    <div>
      <div class="rma-sheet-title">School Rapid Mathematics Assessment Results</div>
      <div class="small text-muted">School ID: <strong>301125</strong> · School Name: <strong>Matabungkay National High School</strong></div>
    </div>
    <div class="text-end">
      <div class="fw-bold">RAPID MATHEMATICS ASSESSMENT (RMA)</div>
      <div class="small text-muted">Grades 7 to 10 · SY <?= e(str_replace('-', '–', $rma['year'])) ?></div>
    </div>
  </div>
  <div class="d-flex flex-wrap align-items-center gap-2 px-3 pb-3">
    <?php foreach (\App\Models\RmaResultModel::PERIODS as $p => $label): ?>
    <a href="<?= e($rmaLink($p)) ?>" class="chat-filter-pill text-decoration-none<?= $p === $rma['period'] ? ' active' : '' ?>">
      <?= e($label) ?><?php if (! empty($rma['counts'][$p])): ?> <span class="opacity-75">· <?= (int) $rma['counts'][$p] ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
    <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" onclick="rmaExport()"><i class="bi bi-download me-1"></i>Export</button>
  </div>
  <?php if (! $rma['sections']): ?>
  <div class="text-center text-muted py-4 small">No Mathematics sections in the teachers' subject loads yet.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-bordered mb-0 align-middle rma-table" id="rmaTable"
           data-export-title="<?= e('School Rapid Mathematics Assessment Results — SY ' . $rma['year'] . ', ' . \App\Models\RmaResultModel::PERIODS[$rma['period']]) ?>"
           data-export-file="<?= e('RMA_SY' . $rma['year'] . '_' . $rma['period']) ?>">
      <?php include APPPATH . 'Views/partials/rma_table_head.php'; ?>
      <tbody>
        <?php foreach ($rma['sections'] as $s): foreach (\App\Models\RmaResultModel::SEXES as $i => $sex):
          $r = $rma['rows'][$s['grade'] . '|' . $s['section'] . '|' . $sex] ?? null;
          [$statusLabel, $tone] = \App\Models\RmaResultModel::status($r);
          foreach (\App\Models\RmaResultModel::COUNT_FIELDS as $f) { $rmaTotal[$f] += (int) ($r[$f] ?? 0); }
        ?>
        <tr class="<?= $i === 0 ? 'rma-section-start' : '' ?>">
          <td class="text-nowrap"><?= e($s['grade']) ?></td>
          <td class="fw-semibold text-nowrap"><?= e(mb_strtoupper($s['section'])) ?></td>
          <td><?= e($sex) ?></td>
          <?php foreach (\App\Models\RmaResultModel::COUNT_FIELDS as $f): ?><td class="text-center"><?= e($rmaNum($r[$f] ?? null)) ?></td><?php endforeach; ?>
          <?php foreach (array_keys(\App\Models\RmaResultModel::CONTENT) as $f): ?><td class="text-center"><?= e($rmaNum($r[$f] ?? null)) ?></td><?php endforeach; ?>
          <td class="text-center fw-semibold"><?= e($rmaNum(\App\Models\RmaResultModel::contentTotal($r))) ?></td>
          <?php foreach (array_keys(\App\Models\RmaResultModel::COGNITIVE) as $f): ?><td class="text-center"><?= e($rmaNum($r[$f] ?? null)) ?></td><?php endforeach; ?>
          <td class="text-center fw-semibold"><?= e($rmaNum(\App\Models\RmaResultModel::cognitiveTotal($r))) ?></td>
          <td class="text-center fw-semibold"><?= e($rmaNum($r['mean_score'] ?? null)) ?></td>
          <td class="text-nowrap"><span class="rma-status rma-status-<?= $tone ?>"><?= e($statusLabel) ?></span></td>
        </tr>
        <?php endforeach; endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="rma-total-row">
          <td colspan="3" class="fw-bold">TOTAL</td>
          <?php foreach (\App\Models\RmaResultModel::COUNT_FIELDS as $f): ?><td class="text-center fw-bold"><?= (int) $rmaTotal[$f] ?></td><?php endforeach; ?>
          <td colspan="<?= count(\App\Models\RmaResultModel::CONTENT) + count(\App\Models\RmaResultModel::COGNITIVE) + 4 ?>"></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <div class="small text-muted px-3 py-2">Entered by each section's Math teacher (Teacher → RMA). Status is <strong>Data Validated</strong> when learners assessed ≤ enrolment, the five levels add up to the learners assessed, and the mean score is in.</div>
  <?php endif; ?>
</div>

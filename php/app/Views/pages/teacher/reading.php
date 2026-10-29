<?php include APPPATH . 'Views/layout/header.php'; ?>
<?php
use App\Models\ReadingResultModel;

$fil    = $language === 'Filipino';
$L      = $fil
    ? ['level' => 'Antas', 'm' => 'Lalaki', 'f' => 'Babae', 'total' => 'Blg.', 'sum' => 'Kabuuan', 'enrolled' => 'Naka-enroll', 'tested' => 'Kumuha ng pagtataya', 'learners' => 'Bilang ng mag-aaral']
    : ['level' => 'Level', 'm' => 'Male', 'f' => 'Female', 'total' => 'Total', 'sum' => 'Total', 'enrolled' => 'Enrolled', 'tested' => 'Tested', 'learners' => 'Learners'];
$tables = ReadingResultModel::tables($language);
$counts = ReadingResultModel::decodeCounts($row['counts'] ?? null);
$cell   = static fn (string $t, string $lv, string $sex) => isset($counts[$t][$lv][$sex]) ? (string) (int) $counts[$t][$lv][$sex] : '';
$num    = static fn (?array $r, string $f) => $r === null || $r[$f] === null ? '' : (string) (int) $r[$f];
$link   = static fn (array $q) => base_url('reading?' . http_build_query($q + ['year' => $year, 'period' => $period, 'language' => $language, 'grade' => $grade]));
$dis    = $canEdit ? '' : ' disabled';
$label  = 'Phil-IRI ' . $language . ' · ' . $grade . ' ' . $section;
[$statusLabel, $tone] = ReadingResultModel::statusOf($row);
?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h4><i class="bi bi-book-half me-2"></i>Phil-IRI <?= e($language) ?></h4>
      <p><?= $fil ? 'Philippine Informal Reading Inventory · lahat ng seksyon ng Baitang 7–10' : 'Philippine Informal Reading Inventory · every Grade 7–10 section' ?> · <?= e(ReadingResultModel::PERIODS[$period]) ?>, SY <?= e($year) ?></p>
    </div>
    <div class="d-flex gap-2">
      <?php if ($isPrincipal): ?>
      <a href="<?= base_url('reading-program?' . http_build_query(['year' => $year, 'period' => $period, 'language' => $language])) ?>" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Reading Program</a>
      <?php endif; ?>
      <a href="<?= base_url('reading/report?' . http_build_query(['year' => $year, 'period' => $period, 'language' => $language])) ?>" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm"><i class="bi bi-printer me-1"></i>Print Report</a>
    </div>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body py-3">
    <form method="GET" action="<?= base_url('reading') ?>" class="d-flex align-items-center gap-3 flex-wrap">
      <?php
      $selects = ['year' => ['School Year', array_combine($years, $years), $year], 'period' => ['Period', ReadingResultModel::PERIODS, $period]];
      if (count($languages) > 1) {
          $selects['language'] = ['Language', array_combine($languages, $languages), $language];
      }
      $selects['grade'] = ['Grade', array_combine(array_keys($sections), array_keys($sections)), $grade];
      foreach ($selects as $name => [$caption, $options, $current]): ?>
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
      <?php if (count($languages) === 1): ?><input type="hidden" name="language" value="<?= e($language) ?>"><?php endif; ?>
      <span class="small text-muted ms-auto"><strong><?= (int) $done[$grade] ?></strong> of <?= count($sections[$grade]) ?> <?= e($grade) ?> sections submitted</span>
    </form>
  </div>
</div>

<?php if (! $sections[$grade]): ?>
<div class="card">
  <div class="card-body text-center text-muted py-5">
    <i class="bi bi-book fs-1 d-block mb-3"></i>
    No <?= e($grade) ?> sections yet. Sections come from the teachers' subject loads.
  </div>
</div>
<?php else: ?>

<div class="reading-tabs mb-3" role="tablist">
  <?php foreach ($sections[$grade] as $s): [, $t] = ReadingResultModel::statusOf($rows[$grade . '|' . $s] ?? null); ?>
  <a href="<?= $link(['section' => $s]) ?>" class="reading-tab<?= $s === $section ? ' active' : '' ?>" title="<?= e(ReadingResultModel::statusOf($rows[$grade . '|' . $s] ?? null)[0]) ?>">
    <span class="rd-dot rd-dot-<?= $t ?>"></span><?= e($s) ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if (($row['status'] ?? '') === 'Returned'): ?>
<div class="alert alert-danger d-flex gap-2 mb-3"><i class="bi bi-arrow-return-left"></i><div><strong>Returned by the principal:</strong> <?= e($row['review_note']) ?><br><span class="small">Fix it and submit again.</span></div></div>
<?php elseif (($row['status'] ?? '') === 'Submitted'): ?>
<div class="alert alert-info d-flex gap-2 mb-3"><i class="bi bi-hourglass-split"></i><div>Submitted <?= e(date('M j, Y g:i A', strtotime($row['submitted_at']))) ?><?= $updatedBy ? ' by ' . e($updatedBy) : '' ?> — waiting for the principal's review.</div></div>
<?php elseif (($row['status'] ?? '') === 'Approved'): ?>
<div class="alert alert-success d-flex gap-2 mb-3"><i class="bi bi-check-circle"></i><div>Approved by the principal<?= $row['reviewed_at'] ? ' on ' . e(date('M j, Y', strtotime($row['reviewed_at']))) : '' ?>.</div></div>
<?php endif; ?>

<form method="POST" action="<?= base_url('reading') ?>" class="ajax-form" id="readingForm" data-language="<?= e($language) ?>" data-can-edit="<?= $canEdit ? '1' : '0' ?>">
  <?php foreach (['year' => $year, 'period' => $period, 'language' => $language, 'grade' => $grade, 'section' => $section] as $k => $v): ?>
  <input type="hidden" name="<?= $k ?>" value="<?= e($v) ?>">
  <?php endforeach; ?>
  <input type="hidden" name="intent" value="draft">

  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
          <div class="fw-bold fs-6"><?= e($grade) ?> — <?= e(mb_strtoupper($section)) ?></div>
          <div class="small text-muted"><?= e($L['learners']) ?></div>
        </div>
        <span class="rd-status rd-status-<?= $tone ?>"><?= e($statusLabel) ?></span>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0 align-middle reading-table reading-enrol">
          <thead><tr><th></th><th><?= e($L['m']) ?></th><th><?= e($L['f']) ?></th><th><?= e($L['total']) ?></th></tr></thead>
          <tbody>
            <?php foreach (['enrolled' => $L['enrolled'], 'tested' => $L['tested']] as $k => $caption): ?>
            <tr data-enrol="<?= $k ?>">
              <td class="fw-semibold"><?= e($caption) ?></td>
              <?php foreach (['m', 'f'] as $sex): ?>
              <td><input type="number" min="0" step="1" inputmode="numeric" class="form-control form-control-sm rd-input" name="<?= $k . '_' . $sex ?>" data-sex="<?= $sex ?>" value="<?= e($num($row, $k . '_' . $sex)) ?>"<?= $dis ?>></td>
              <?php endforeach; ?>
              <td class="text-center fw-bold rd-row-total"></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <?php foreach ($tables as $key => $t): ?>
    <div class="col-xl-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="fw-bold"><?= e($t['title']) ?></div>
          <div class="small text-muted mb-2"><?= e($t['sub']) ?></div>
          <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 align-middle reading-table" data-table="<?= $key ?>" data-title="<?= e($t['title']) ?>">
              <thead><tr><th><?= e($L['level']) ?></th><th><?= e($L['m']) ?></th><th><?= e($L['f']) ?></th><th><?= e($L['total']) ?></th><th>%</th></tr></thead>
              <tbody>
                <?php foreach ($t['levels'] as $lv => $lvLabel): ?>
                <tr data-level="<?= $lv ?>">
                  <td class="fw-semibold<?= mb_strlen($lvLabel) <= 14 ? ' text-nowrap' : '' ?>"><?= e($lvLabel) ?></td>
                  <?php foreach (['m', 'f'] as $sex): ?>
                  <td><input type="number" min="0" step="1" inputmode="numeric" class="form-control form-control-sm rd-input" name="counts[<?= $key ?>][<?= $lv ?>][<?= $sex ?>]" data-sex="<?= $sex ?>" value="<?= e($cell($key, $lv, $sex)) ?>"<?= $dis ?>></td>
                  <?php endforeach; ?>
                  <td class="text-center fw-semibold rd-row-total"></td>
                  <td class="text-center text-muted rd-row-pct"></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr class="rd-sum-row">
                  <th><?= e($L['sum']) ?></th><th class="text-center" data-sum="m"></th><th class="text-center" data-sum="f"></th><th class="text-center" data-sum="total"></th><th class="text-center" data-sum="check"></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="card mb-4">
    <div class="card-body d-flex flex-wrap align-items-start gap-3">
      <div class="flex-grow-1">
        <div class="fw-semibold small mb-1"><i class="bi bi-clipboard-check me-1"></i><?= $fil ? 'Pagsusuri bago ipasa' : 'Checks before submitting' ?></div>
        <ul class="small mb-0 ps-3 rd-problems" id="readingProblems"></ul>
        <div class="small text-success d-none" id="readingReady"><i class="bi bi-check-circle-fill me-1"></i><?= $fil ? 'Tugma ang lahat ng talahanayan. Maaari nang ipasa.' : 'Every table adds up. Ready to submit.' ?></div>
      </div>
      <?php if ($canEdit): ?>
      <div class="d-flex gap-2 ms-auto">
        <button type="submit" class="btn btn-outline-primary" onclick="readingIntent(this.form, 'draft')"><i class="bi bi-save me-1"></i>Save Draft</button>
        <button type="submit" class="btn btn-primary" id="readingSubmitBtn" onclick="readingIntent(this.form, 'submit')"><i class="bi bi-send me-1"></i>Submit for Review</button>
      </div>
      <?php endif; ?>
    </div>
  </div>
</form>

<?php if ($isPrincipal && $row && in_array($row['status'], ['Submitted', 'Approved'], true)): ?>
<div class="d-flex justify-content-end gap-2 mb-4">
  <?php if ($row['status'] === 'Submitted'): ?>
  <form method="POST" action="<?= base_url('reading-program') ?>" class="ajax-form" data-confirm-title="Approve <?= e($grade . ' ' . $section) ?>?" data-confirm-text="Phil-IRI <?= e($language) ?>, <?= e(ReadingResultModel::PERIODS[$period]) ?>, SY <?= e($year) ?>.">
    <input type="hidden" name="action" value="approve"><input type="hidden" name="result_id" value="<?= (int) $row['id'] ?>">
    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Approve</button>
  </form>
  <?php endif; ?>
  <button type="button" class="btn btn-outline-danger" onclick="readingReturn(<?= (int) $row['id'] ?>, <?= e(json_encode($label)) ?>)"><i class="bi bi-arrow-return-left me-1"></i>Return</button>
</div>
<?php include APPPATH . 'Views/partials/reading_return_modal.php'; ?>
<?php endif; ?>
<?php endif; ?>

<?php
$extraScript = <<<'HTML'
<script>
// Live totals, % and the submit checks (same rules as ReadingResultModel::problems()).
(function () {
    const form = document.getElementById('readingForm');
    if (!form) return;
    const fil = form.dataset.language === 'Filipino';
    const canEdit = form.dataset.canEdit === '1';
    const n = el => { const v = el ? el.value.trim() : ''; return v === '' ? null : parseInt(v, 10); };

    window.readingIntent = function (f, intent) {
        f.intent.value = intent;
        const where = f.grade.value + ' ' + f.section.value;
        f.dataset.confirmTitle = intent === 'submit' ? 'Submit ' + where + ' for review?' : 'Save draft for ' + where + '?';
        f.dataset.confirmText = intent === 'submit' ? 'The principal will review it. You can\'t edit it again unless it\'s returned.' : 'You can keep editing it until you submit.';
    };

    function refresh() {
        const problems = [];
        const enrol = {};
        form.querySelectorAll('[data-enrol]').forEach(tr => {
            const m = n(tr.querySelector('[data-sex="m"]')), f = n(tr.querySelector('[data-sex="f"]'));
            enrol[tr.dataset.enrol] = { m, f };
            tr.querySelector('.rd-row-total').textContent = m === null && f === null ? '' : (m || 0) + (f || 0);
        });
        const e = enrol.enrolled, t = enrol.tested;
        const tested = (t.m || 0) + (t.f || 0);
        if ([e.m, e.f, t.m, t.f].includes(null)) {
            problems.push(fil ? 'Kumpletuhin ang bilang ng naka-enroll at kumuha.' : 'Fill in the enrolled and tested counts.');
        } else {
            if (t.m > e.m || t.f > e.f) problems.push(fil ? 'Ang kumuha ay hindi maaaring higit sa naka-enroll.' : 'Tested cannot be more than enrolled.');
            if (tested === 0) problems.push(fil ? 'Walang kumuha ng pagtataya.' : 'No learners were tested.');
        }

        form.querySelectorAll('table[data-table]').forEach(table => {
            let sm = 0, sf = 0;
            table.querySelectorAll('tr[data-level]').forEach(tr => {
                const m = n(tr.querySelector('[data-sex="m"]')), f = n(tr.querySelector('[data-sex="f"]'));
                const total = (m || 0) + (f || 0);
                sm += m || 0; sf += f || 0;
                tr.querySelector('.rd-row-total').textContent = m === null && f === null ? '' : total;
                tr.querySelector('.rd-row-pct').textContent = m === null && f === null || !tested ? '' : (Math.round(total / tested * 1000) / 10) + '%';
            });
            const ok = t.m !== null && t.f !== null && sm === t.m && sf === t.f;
            table.querySelector('[data-sum="m"]').textContent = sm;
            table.querySelector('[data-sum="f"]').textContent = sf;
            table.querySelector('[data-sum="total"]').textContent = sm + sf;
            const check = table.querySelector('[data-sum="check"]');
            check.innerHTML = ok ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-exclamation-circle-fill text-warning"></i>';
            check.title = ok ? 'Adds up to the learners tested' : 'Should add up to the learners tested (' + (t.m || 0) + ' M, ' + (t.f || 0) + ' F)';
            table.classList.toggle('rd-mismatch', !ok && sm + sf > 0);
            if (!ok) problems.push(fil ? 'Ang ' + table.dataset.title + ' ay hindi tugma sa bilang ng kumuha.' : table.dataset.title + ' does not add up to the number tested.');
        });

        const list = document.getElementById('readingProblems');
        list.innerHTML = '';
        problems.forEach(p => { const li = document.createElement('li'); li.textContent = p; list.appendChild(li); });
        document.getElementById('readingReady').classList.toggle('d-none', problems.length > 0);
        const btn = document.getElementById('readingSubmitBtn');
        if (btn) btn.disabled = !canEdit || problems.length > 0;
    }

    form.addEventListener('input', refresh);
    refresh();
})();
</script>
HTML;
include APPPATH . 'Views/layout/footer.php';
?>

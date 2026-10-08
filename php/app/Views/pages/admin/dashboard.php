<?php include APPPATH . 'Views/layout/header.php'; ?>

<?php
// Sparkline SVG for the KPI cards: line + soft area wash in the accent
// color, with the latest point as a solid dot.
$sparklineSvg = static function (?array $spark, string $accentColor): string {
    if ($spark === null) {
        return '';
    }

    return '<svg class="stat-sparkline dash-sparkline" viewBox="0 0 64 22" preserveAspectRatio="none">'
        . '<polygon points="' . e($spark['areaPoints']) . '" fill="' . $accentColor . '"></polygon>'
        . '<polyline points="' . e($spark['points']) . '" stroke="' . $accentColor . '"></polyline>'
        . '<circle cx="' . $spark['lastX'] . '" cy="' . $spark['lastY'] . '" r="2.25" fill="' . $accentColor . '"></circle>'
        . '</svg>';
};

// Delta: arrow + signed change, green when the direction is GOOD for that
// metric (down is good for drop-out, up for enrollees / MPS), with a
// "vs. SY …" caption naming what it's compared against.
$deltaRow = static function (?array $delta, bool $upIsGood, string $suffix = '%'): string {
    if ($delta === null) {
        return '';
    }
    $isUp   = $delta['delta'] >= 0;
    $isGood = $isUp === $upIsGood;

    return '<div class="dash-delta ' . ($isGood ? 'is-good' : 'is-bad') . '">'
        . '<i class="bi ' . ($isUp ? 'bi-arrow-up' : 'bi-arrow-down') . '"></i>'
        . ($isUp ? '+' : '−') . number_format(abs($delta['delta']), 2) . $suffix
        . '</div><div class="dash-vs">vs. ' . e(str_replace('-', '–', $delta['vsLabel'])) . '</div>';
};

$docOnTrack = ($docSummary['Submitted'] ?? 0) + ($docSummary['Reviewed'] ?? 0);
$docTotal   = array_sum($docSummary);
$docPct     = static fn (string $s) => $docTotal > 0 ? round(($docSummary[$s] ?? 0) / $docTotal * 100) : 0;
$enrollTotal = array_sum(array_column($enrollment, 'students'));
$insightTone = [
    'danger'  => ['#fee2e2', '#b91c1c'],
    'warning' => ['#fef3c7', '#b45309'],
    'success' => ['#d1fae5', '#059669'],
    'info'    => ['#f3e8e8', '#800000'],
];
?>

<!-- Welcome header (no banner, per the redesign) -->
<div class="dash-head">
  <div>
    <div class="dash-eyebrow">Welcome back,</div>
    <h1 class="dash-name"><?= e($user['name'] ?? '') ?></h1>
    <p class="dash-sub">Here's an overview of your school's performance.</p>
    <div class="dash-rule"></div>
  </div>
  <div class="dash-actions">
    <label class="dash-year" for="dashboard-year-filter">
      <i class="bi bi-calendar3"></i>
      <span>
        <small>School Year</small>
        <select id="dashboard-year-filter"
                onchange="loadPage('<?= base_url('dashboard') ?>?year=' + encodeURIComponent(this.value))">
          <?php foreach ($years as $y): ?>
          <option value="<?= e($y) ?>" <?= $y === $currentYear ? 'selected' : '' ?>><?= e(str_replace('-', '–', $y)) ?></option>
          <?php endforeach; ?>
        </select>
      </span>
    </label>
    <?php if (hasRole('admin')): ?>
    <button type="button" class="btn dash-btn dash-btn-primary" data-bs-toggle="modal" data-bs-target="#addEnrollmentModal">
      <i class="bi bi-person-plus me-2"></i>Add Enrollment
    </button>
    <button type="button" class="btn dash-btn" data-bs-toggle="modal" data-bs-target="#importKpiModal">
      <i class="bi bi-upload me-2"></i>Import KPI Report
    </button>
    <?php endif; ?>
    <button type="button" class="btn dash-btn" onclick="document.getElementById('dash-breakdown').scrollIntoView({ behavior: 'smooth', block: 'start' })">
      <i class="bi bi-bar-chart-line me-2"></i>View Data
    </button>
  </div>
</div>

<!-- KPI cards (the first three also switch the trend chart below) -->
<div class="dash-kpis mb-3">
  <div class="dash-kpi stat-tile-clickable active" data-metric="enrollees" onclick="selectKpiMetric('enrollees')">
    <div class="dash-kpi-top"><span class="dash-kpi-icon"><i class="bi bi-people-fill"></i></span>Total Enrollees</div>
    <div class="dash-kpi-body">
      <div>
        <div class="dash-kpi-value" id="kpiCardValue-enrollees">—</div>
        <?= $deltaRow($enrolleesDelta, true) ?: '<div class="dash-vs">SY ' . e($currentYear) . '</div>' ?>
      </div>
      <?= $sparklineSvg($enrolleesSparkline, '#800000') ?>
    </div>
  </div>

  <div class="dash-kpi stat-tile-clickable" data-metric="dropout" onclick="selectKpiMetric('dropout')">
    <div class="dash-kpi-top"><span class="dash-kpi-icon"><i class="bi bi-exclamation-triangle-fill"></i></span>Drop-out Rate</div>
    <div class="dash-kpi-body">
      <div>
        <div class="dash-kpi-value" id="kpiCardValue-dropout">—</div>
        <div class="dash-vs" id="kpiCardYearNote-dropout"></div>
        <?= $deltaRow($dropoutDelta, false, ' pts') ?>
      </div>
      <?= $sparklineSvg($dropoutSparkline, '#800000') ?>
    </div>
  </div>

  <div class="dash-kpi stat-tile-clickable" data-metric="mps" onclick="selectKpiMetric('mps')">
    <div class="dash-kpi-top"><span class="dash-kpi-icon"><i class="bi bi-graph-up-arrow"></i></span>Average MPS</div>
    <div class="dash-kpi-body">
      <div>
        <div class="dash-kpi-value"><?= $mpsOverallAvg !== null ? number_format($mpsOverallAvg, 2) . '%' : 'No data' ?></div>
        <?= $deltaRow($mpsDelta, true, ' pts') ?: '<div class="dash-vs">SY ' . e($mpsSourceYear) . '</div>' ?>
      </div>
      <?= $sparklineSvg($mpsSparkline, '#800000') ?>
    </div>
  </div>

  <a href="<?= base_url('documents') ?>" class="dash-kpi dash-kpi-link">
    <div class="dash-kpi-top"><span class="dash-kpi-icon"><i class="bi bi-shield-fill-check"></i></span>Submission Compliance</div>
    <div class="dash-kpi-body">
      <div>
        <?php if ($complianceRate !== null): ?>
        <div class="dash-kpi-value"><?= $docOnTrack ?> <span class="dash-kpi-of">of <?= $docTotal ?></span></div>
        <div class="dash-vs">Documents submitted or reviewed</div>
        <?php else: ?>
        <div class="dash-kpi-value">No data</div>
        <div class="dash-vs">Documents</div>
        <?php endif; ?>
      </div>
      <i class="bi bi-file-earmark-text dash-kpi-ghost"></i>
    </div>
  </a>
</div>

<!-- Charts + side rail -->
<div class="dash-grid mb-3">
  <!-- Trend (enrollees / drop-out / MPS) -->
  <div class="card dash-card dash-area-trend">
    <div class="dash-card-head">
      <span><i class="bi bi-graph-up"></i><span id="kpiChartTitle">Total Enrollees</span> Trend</span>
      <select class="dash-mini-select" id="kpiMetricSelect" onchange="selectKpiMetric(this.value)" aria-label="Trend metric">
        <option value="enrollees">Total Enrollees</option>
        <option value="dropout">Drop-out Rate</option>
        <option value="mps">Average MPS</option>
      </select>
    </div>
    <div class="dash-card-body">
      <div class="dash-chart"><canvas id="kpiChart" data-source=""></canvas></div>
      <p id="kpiChartEmpty" class="text-muted text-center py-4 mb-0 d-none small">
        <i class="bi bi-bar-chart fs-4 d-block mb-2"></i><span id="kpiChartEmptyText">No data available.</span>
      </p>
    </div>
  </div>

  <!-- Gender & grade enrollment -->
  <div class="card dash-card dash-area-gender">
    <div class="dash-card-head">
      <span><i class="bi bi-gender-ambiguous"></i>Gender &amp; Grade Enrollment</span>
      <?php if (! empty($enrollmentMonths)): ?>
      <select class="dash-mini-select" id="enrollment-month-filter" aria-label="Enrollment month"
              onchange="loadPage('<?= base_url('dashboard') ?>?year=<?= urlencode($currentYear) ?>&month=' + encodeURIComponent(this.value), { scroll: false })">
        <?php foreach (array_reverse($enrollmentMonths) as $m): ?>
        <option value="<?= e($m) ?>" <?= $m === $enrollmentMonth ? 'selected' : '' ?>><?= e(date('M Y', strtotime($m . '-01'))) ?></option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>
    </div>
    <div class="dash-card-body">
      <?php if (empty($enrollment)): ?>
      <p class="text-muted text-center py-4 mb-0 small"><i class="bi bi-bar-chart fs-4 d-block mb-2"></i>No enrolment data for SY <?= e($currentYear) ?>.</p>
      <?php else: ?>
      <div class="dash-legend"><span><i style="background:#6e1020"></i>Male</span><span><i style="background:#e3b6bd"></i>Female</span></div>
      <div class="dash-chart"><canvas id="enrollChart" data-label-header="Grade Level" data-table-total="1" data-source="<?= e('Enrollment sheet uploaded with Add Enrollment — the ' . ($enrollmentMonth ? date('F Y', strtotime($enrollmentMonth . '-01')) . ' ' : '') . 'count for SY ' . $currentYear . ', with each grade level’s sections added up.') ?>"></canvas></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Document status -->
  <div class="card dash-card dash-area-docs">
    <div class="dash-card-head">
      <span><i class="bi bi-file-earmark-text"></i>Document Status</span>
    </div>
    <div class="dash-card-body d-flex align-items-center gap-3 flex-wrap justify-content-center">
      <div class="donut-wrap" style="width:130px;height:130px;">
        <canvas id="docChart" width="130" height="130" data-label-header="Status"
                data-source="Documents submitted through the ACADOCS mobile app. Task uploads reviewed in Manage Documents are not counted here."></canvas>
        <div class="donut-center-label">
          <div class="donut-center-value"><?= $docTotal ? $docPct('Submitted') . '%' : '0' ?></div>
          <div class="donut-center-caption"><?= $docTotal ? 'Submitted' : 'Documents' ?></div>
        </div>
      </div>
      <div class="flex-grow-1" style="min-width:120px;">
        <?php foreach (['Submitted' => 1, 'Reviewed' => .7, 'Pending' => .45, 'Returned' => .25] as $status => $alpha): ?>
        <div class="d-flex justify-content-between align-items-center small mb-2">
          <span><span class="d-inline-block rounded-circle me-2" style="width:9px;height:9px;background:rgba(110,16,32,<?= $alpha ?>);"></span><?= $status ?></span>
          <strong><?= $docPct($status) ?>%</strong>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Side rail: insights + today's time records -->
  <div class="dash-area-side d-flex flex-column gap-3">
    <div class="card dash-card">
      <div class="dash-card-head">
        <span><i class="bi bi-lightbulb"></i>Insights</span>
      </div>
      <div class="dash-card-body pt-1">
        <?php if (empty($insights)): ?>
        <p class="text-muted small text-center py-3 mb-0">No insights yet — they appear as data comes in.</p>
        <?php endif; ?>
        <?php foreach ($insights as $insight): [$ibg, $ifg] = $insightTone[$insight['tone']]; ?>
        <div class="dash-insight">
          <span class="dash-insight-icon" style="background:<?= $ibg ?>;color:<?= $ifg ?>;"><i class="bi <?= $insight['icon'] ?>"></i></span>
          <div class="min-w-0">
            <div class="dash-insight-title"><?= $insight['title'] ?? '' ?></div>
            <div class="dash-insight-text"><?= $insight['text'] ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php include APPPATH . 'Views/partials/dtr_today.php'; ?>
  </div>

  <!-- Performance by level -->
  <div class="card dash-card dash-area-perf">
    <div class="dash-card-head">
      <span><i class="bi bi-graph-up"></i>Performance by Level</span>
      <div class="dash-legend m-0"><span><i style="background:#6e1020"></i>Average MPS</span></div>
    </div>
    <div class="dash-card-body">
      <?php if (empty($perfLevel)): ?>
      <p class="text-muted text-center py-4 mb-0 small"><i class="bi bi-graph-up fs-4 d-block mb-2"></i>No performance data for SY <?= e($currentYear) ?>.</p>
      <?php else: ?>
      <div class="dash-chart"><canvas id="perfChart" data-label-header="Grade Level" data-unit="%" data-source="<?= e('Average of the MPS scores teachers entered on Enter MPS Scores (typed in or imported from Excel) for SY ' . $currentYear . ', Term ' . $currentTerm . ', per grade level.') ?>"></canvas></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Enrollment breakdown / learning area / historical KPIs -->
  <div class="card dash-card dash-area-breakdown" id="dash-breakdown">
    <div class="dash-card-head flex-wrap gap-2">
      <span><i class="bi bi-building"></i>Enrollment Breakdown<?php if (! empty($enrollmentMonth)): ?> <small class="text-muted fw-normal">(<?= e(date('F Y', strtotime($enrollmentMonth . '-01'))) ?>)</small><?php endif; ?></span>
      <div class="dash-tabs" data-tab-group="data">
        <button type="button" class="active" data-tab-key="breakdown" onclick="switchTab('data','breakdown')">By Grade &amp; Section</button>
        <button type="button" data-tab-key="subject" onclick="switchTab('data','subject')">Learning Area</button>
        <?php if (! empty($depedKpis)): ?>
        <button type="button" data-tab-key="deped" onclick="switchTab('data','deped')">Historical KPIs</button>
        <?php endif; ?>
      </div>
    </div>

    <div class="dash-card-body p-0" data-tab-panel="data:breakdown">
      <div class="table-responsive">
        <table class="table dash-table mb-0">
          <thead><tr><th>Grade</th><th class="text-center">Sections</th><th class="text-end">Students</th><th>Share</th></tr></thead>
          <tbody>
            <?php if (empty($enrollment)): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">No enrolment data available for <?= e(str_replace('-', '–', $currentYear)) ?>.</td></tr>
            <?php endif; ?>
            <?php foreach ($enrollment as $row): $pct = $enrollTotal > 0 ? round($row['students'] / $enrollTotal * 100, 1) : 0; ?>
            <tr>
              <td><?= e($row['grade_level']) ?></td>
              <td class="text-center"><?= (int) $row['sections'] ?></td>
              <td class="text-end"><?= number_format($row['students']) ?></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <span class="small text-muted" style="width:44px;"><?= $pct ?>%</span>
                  <div class="dash-bar"><div style="width:<?= $pct ?>%"></div></div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <?php if (! empty($enrollment)): ?>
          <tfoot>
            <tr>
              <td>Total</td>
              <td class="text-center"><?= array_sum(array_column($enrollment, 'sections')) ?></td>
              <td class="text-end"><?= number_format($enrollTotal) ?></td>
              <td><div class="d-flex align-items-center gap-2"><span class="small" style="width:44px;">100%</span><div class="dash-bar"><div style="width:100%"></div></div></div></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <div class="dash-card-body p-0 d-none" data-tab-panel="data:subject">
      <div class="d-flex justify-content-end px-3 pt-2">
        <button type="button" id="perfViewAllBtn" class="btn btn-sm btn-outline-secondary" onclick="togglePerfBreakdown()">
          <i class="bi bi-list-ul me-1"></i>View All
        </button>
      </div>
      <div class="table-responsive">
        <table class="table dash-table mb-0">
          <thead><tr><th>Subject</th><th>Grade Level</th><th>Teacher</th><th class="text-end">MPS</th><th class="text-center">Status</th></tr></thead>
          <tbody id="perfSummaryBody">
            <?php foreach ($avgPerf as $p):
              $badge = $p['mps'] >= 85 ? ['Excellent', 'badge-submitted'] : ($p['mps'] >= 75 ? ['Satisfactory', 'badge-reviewed'] : ['Needs Improvement', 'badge-returned']);
            ?>
            <tr>
              <td class="fw-semibold"><?= e($p['subject']) ?></td><td class="text-muted">All Grades</td><td class="text-muted">—</td>
              <td class="text-end fw-bold"><?= $p['mps'] ?>%</td>
              <td class="text-center"><span class="status-pill <?= $badge[1] ?>"><?= $badge[0] ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tbody id="perfFullBody" class="d-none">
            <?php foreach ($allPerf as $p):
              $badge = $p['mps'] >= 85 ? ['Excellent', 'badge-submitted'] : ($p['mps'] >= 75 ? ['Satisfactory', 'badge-reviewed'] : ['Needs Improvement', 'badge-returned']);
            ?>
            <tr>
              <td class="fw-semibold"><?= e($p['subject']) ?></td><td class="text-muted"><?= e($p['grade_level']) ?></td><td class="text-muted"><?= e($p['instructor']) ?></td>
              <td class="text-end fw-bold"><?= $p['mps'] ?>%</td>
              <td class="text-center"><span class="status-pill <?= $badge[1] ?>"><?= $badge[0] ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if (! empty($depedKpis)): ?>
    <div class="dash-card-body p-0 d-none" data-tab-panel="data:deped">
      <div class="table-responsive">
        <table class="table dash-table mb-0">
          <thead>
            <tr>
              <th>School Year</th><th class="text-end">Gross Enrol.</th><th class="text-end">Net Enrol.</th><th class="text-end">Cohort Surv.</th>
              <th class="text-end">Repetition</th><th class="text-end">Promotion</th><th class="text-end">Retention</th><th class="text-end">Graduation</th>
              <th class="text-end">Completion</th><th class="text-end">Transition</th><th class="text-end">Drop Out</th>
            </tr>
          </thead>
          <tbody>
            <?php $fmt = static fn ($v) => $v !== null ? number_format((float) $v, 2) . '%' : '—'; ?>
            <?php foreach ($depedKpis as $k): ?>
            <tr>
              <td class="fw-semibold"><?= e(str_replace('-', '–', $k['school_year'])) ?></td>
              <td class="text-end"><?= $fmt($k['gross_enrolment_rate']) ?></td>
              <td class="text-end"><?= $fmt($k['net_enrolment_rate']) ?></td>
              <td class="text-end"><?= $fmt($k['cohort_survival_rate']) ?></td>
              <td class="text-end"><?= $fmt($k['repetition_rate']) ?></td>
              <td class="text-end"><?= $fmt($k['promotion_rate']) ?></td>
              <td class="text-end"><?= $fmt($k['retention_rate']) ?></td>
              <td class="text-end"><?= $fmt($k['graduation_rate']) ?></td>
              <td class="text-end"><?= $fmt($k['completion_rate']) ?></td>
              <td class="text-end"><?= $fmt($k['transition_rate']) ?></td>
              <td class="text-end <?= (float) $k['dropout_rate'] > 1.5 ? 'text-danger fw-semibold' : '' ?>"><?= $fmt($k['dropout_rate']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="text-muted small px-3 py-2">DepEd source: Guidance Office official reports</div>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="dash-footer">
  <span><span class="dash-footer-rule"></span><strong>ACADOCS</strong> · School Documents Management System</span>
  <em>Better Records. Brighter Futures.</em>
</div>

<?php if (hasRole('admin')): ?>
<!-- Add Enrollment Modal -->
<div class="modal fade" id="addEnrollmentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-people me-2"></i>Add Enrollment</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('enrollment/import') ?>" class="ajax-form" enctype="multipart/form-data" data-confirm-title="Import this enrollment?" data-confirm-text="Enrollment for the months in this file will be replaced; other months are kept.">
        <div class="modal-body">
          <p class="text-muted" style="font-size:.82rem;">
            Upload the enrollment sheet: a <strong>GRADE 7–10</strong> block each, one row per section with
            <strong>MALE / FEMALE / TOTAL</strong>, then the grade's TOTAL row and a GRAND TOTAL.
            Section rows are added up per grade level. Every sheet is read — name each sheet by its
            count date (e.g. <em>June 10</em>, <em>AUG 11</em>); each month keeps its latest-dated sheet.
          </p>
          <a href="<?= base_url('enrollment/template') ?>?year=<?= urlencode($currentYear) ?>" id="enrollmentTemplateLink"
             class="btn btn-sm btn-outline-primary w-100 mb-3" data-no-ajax>
            <i class="bi bi-download me-1"></i>Download Enrollment Template (.xlsx)
          </a>
          <div class="mb-3">
            <label class="form-label">School Year</label>
            <input type="text" name="school_year" class="form-control form-control-sm sy-input" list="kpiYearOptions"
                   inputmode="numeric" maxlength="9" autocomplete="off" title="YYYY-YYYY, e.g. 2026-2027"
                   value="<?= e($currentYear) ?>" placeholder="e.g. 2026-2027" pattern="\d{4}-\d{4}" required
                   oninput="updateEnrollmentTemplateLink(this.form)">
            <div class="form-text">Uploading a month again replaces it; other months are kept.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Excel file (.xlsx, .xls, .csv)</label>
            <input type="file" name="import_file" class="form-control" accept=".xlsx,.xls,.csv" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Import</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Import KPI Report Modal -->
<div class="modal fade" id="importKpiModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-upload me-2"></i>Import KPI Report</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('enrollment-kpis/import') ?>" class="ajax-form" enctype="multipart/form-data" data-confirm-title="Import this file?" data-confirm-text="Existing KPI data for the selected school year will be overwritten.">
        <div class="modal-body">
          <p class="text-muted" style="font-size:.82rem;">
            Upload the DepEd "Key Performance Indicator" Word report (Indicator table plus the
            "Enrolment per Grade Level" table). The document doesn't state its own school year, so
            enter it below. Not sure of the format?
            <a href="<?= base_url('enrollment-kpis/template') ?>?year=<?= urlencode($currentYear) ?>" id="kpiTemplateLink">Download the template</a>.
          </p>
          <div class="mb-3">
            <label class="form-label">School Year</label>
            <input type="text" name="school_year" class="form-control form-control-sm sy-input" list="kpiYearOptions"
                   inputmode="numeric" maxlength="9" autocomplete="off" title="YYYY-YYYY, e.g. 2025-2026"
                   value="<?= e($currentYear) ?>" placeholder="e.g. 2025-2026" pattern="\d{4}-\d{4}" required
                   oninput="document.getElementById('kpiTemplateLink').href = '<?= base_url('enrollment-kpis/template') ?>?year=' + encodeURIComponent(this.value)">
            <datalist id="kpiYearOptions">
              <?php foreach ($years as $y): ?>
              <option value="<?= e($y) ?>"></option>
              <?php endforeach; ?>
            </datalist>
            <div class="form-text">Type a new school year (YYYY-YYYY) or pick an existing one. The template above will use this year.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Word file (.docx)</label>
            <input type="file" name="import_file" class="form-control" accept=".docx" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Import</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$extraScript = '<script>
const maroon = chartColor(), maroonLight = chartColorAlt(), maroonDark = "#560000", crimson = "#dc143c";

function togglePerfBreakdown() {
  const summary = document.getElementById("perfSummaryBody");
  const full = document.getElementById("perfFullBody");
  const btn = document.getElementById("perfViewAllBtn");
  const showingFull = !full.classList.contains("d-none");
  full.classList.toggle("d-none", showingFull);
  summary.classList.toggle("d-none", !showingFull);
  btn.innerHTML = showingFull
    ? "<i class=\\"bi bi-list-ul me-1\\"></i>View All"
    : "<i class=\\"bi bi-collection me-1\\"></i>Collapse";
}

// Generic tab-pill switcher: shared by the "charttype" pills (bar/line,
// handled via selectChartKind) and the "data" table pills below.
function switchTab(group, key) {
  document.querySelectorAll(\'[data-tab-group="\' + group + \'"] [data-tab-key]\').forEach(el => {
    el.classList.toggle("active", el.dataset.tabKey === key);
  });
  document.querySelectorAll(\'[data-tab-panel^="\' + group + \':"]\').forEach(el => {
    el.classList.toggle("d-none", el.dataset.tabPanel !== group + ":" + key);
  });
}

// Enrollment Chart — a paler tint (context/volume metric); thin, capped
// bars with air between them, not a wall-to-wall saturated block. Performance
// below stays full-saturation since it is the more actionable metric.
// Canvas only exists in the DOM when there\'s data (see the PHP empty-state
// check around it) — guard the lookup so a data-less year can\'t throw here
// and silently skip every chart built after it in this script.
// Keeps the Add Enrollment template link in step with the chosen school year.
function updateEnrollmentTemplateLink(form) {
  const link = document.getElementById("enrollmentTemplateLink");
  if (!link || !form) return;
  link.href = "' . base_url('enrollment/template') . '?year=" + encodeURIComponent(form.school_year.value);
}

const enrollChartEl = document.getElementById("enrollChart");
if (enrollChartEl) {
  // Male vs female side by side. Years saved without a split (e.g. from a
  // DepEd KPI report) fall back to a single "Students" bar.
  const enrollTotals = ' . json_encode(array_map('intval', array_column($enrollment, 'students'))) . ';
  const enrollHasSplit = ' . json_encode(array_filter($enrollment, static fn ($r) => $r['male'] !== null || $r['female'] !== null) !== []) . ';
  new Chart(enrollChartEl, {
    type: "bar",
    data: {
      labels: ' . json_encode(array_column($enrollment, 'grade_level')) . ',
      datasets: enrollHasSplit ? [
        { label: "Male",   data: ' . json_encode(array_map(static fn ($r) => (int) $r['male'], $enrollment)) . ',   backgroundColor: "#6e1020", borderRadius: 4, maxBarThickness: 22 },
        { label: "Female", data: ' . json_encode(array_map(static fn ($r) => (int) $r['female'], $enrollment)) . ', backgroundColor: "#e3b6bd", borderRadius: 4, maxBarThickness: 22 }
      ] : [
        { label: "Students", data: enrollTotals, backgroundColor: chartColor(.25), borderColor: maroon, borderWidth: 1, borderRadius: 4, maxBarThickness: 28 }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { footer: (items) => enrollHasSplit ? "Total: " + enrollTotals[items[0].dataIndex] : "" } }
      },
      scales: { y: { beginAtZero: true, grid: { color: chartGridColor() } }, x: { grid: { display: false } } }
    }
  });
}

// Performance Chart
const perfChartEl = document.getElementById("perfChart");
if (perfChartEl) {
  new Chart(perfChartEl, {
    type: "bar",
    data: {
      labels: ' . json_encode(array_column($perfLevel, 'grade_level')) . ',
      datasets: [
        { label:"Average MPS", data:' . json_encode(array_column($perfLevel, 'mps')) . ', backgroundColor:"#6e1020", borderRadius:4, maxBarThickness:26 }
      ]
    },
    // Axis starts 10 points below the lowest grade level (rounded down to a
    // multiple of 10), so no bar is ever cut off.
    options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:false,min:Math.max(0, Math.floor((Math.min(...' . json_encode(array_map('floatval', array_column($perfLevel, 'mps'))) . ') - 10) / 10) * 10),max:100,grid:{color:chartGridColor()}},x:{grid:{display:false}}} }
  });
}

// Document Pie — with no documents yet, a plain grey ring stands in so the
// card doesn\'t look broken.
const docCounts = ' . json_encode([
      $docSummary['Submitted'] ?? 0,
      $docSummary['Reviewed']  ?? 0,
      $docSummary['Pending']   ?? 0,
      $docSummary['Returned']  ?? 0,
    ]) . ';
const docHasAny = docCounts.some(n => n > 0);
new Chart(document.getElementById("docChart"), {
  type: "doughnut",
  data: {
    labels: docHasAny ? ["Submitted","Reviewed","Pending","Returned"] : ["No documents"],
    datasets: [{
      data: docHasAny ? docCounts : [1],
      backgroundColor: docHasAny
        ? ["rgba(110,16,32,1)","rgba(110,16,32,.7)","rgba(110,16,32,.45)","rgba(110,16,32,.25)"]
        : [getComputedStyle(document.documentElement).getPropertyValue("--border").trim() || "#e5e7eb"],
      borderWidth: 0,
    }]
  },
  options: { responsive:true, maintainAspectRatio:false, cutout:"72%", plugins:{ legend:{display:false}, tooltip:{enabled:docHasAny} } }
});

// ── Interactive Enrollees / Drop-Out / MPS KPI chart (single canvas, tab-switched) ──
const DEPED_KPI_DATA = ' . json_encode($depedKpis) . ';
const ENROLLMENT_TOTALS_DATA = ' . json_encode($enrollmentTotals) . ';
const MPS_TREND_DATA = ' . json_encode($mpsTrend) . ';
const MPS_OVERALL_AVG = ' . json_encode($mpsOverallAvg) . ';
const MPS_SOURCE_YEAR = ' . json_encode($mpsSourceYear) . ';
let kpiChart = null;
let currentMetric = "enrollees";
let currentChartKind = "line";

// "enrollees" is sourced from the actual per-grade-level headcounts (Enrolment per
// Grade Level table), not gross_enrolment_rate — that\'s a computed rate the KPI
// report often leaves blank, while the headcount table is what schools reliably fill in.
// All three share the brand maroon, matching the stat tiles\' unified accent.
// Where each trend metric’s numbers come from — shown in the expanded chart.
const KPI_METRIC_SOURCES = {
  enrollees: "Enrollment sheets uploaded with Add Enrollment (each school year’s latest monthly count). Years without a sheet use the enrolment total from that year’s imported DepEd KPI report.",
  dropout: "DepEd KPI reports uploaded with Import KPI Report — the Drop Out Rate indicator, one report per school year.",
  mps: "MPS scores teachers entered on Enter MPS Scores (typed in or imported from Excel), averaged across grade levels for each term of SY " + ' . json_encode($mpsSourceYear) . ' + ".",
};
const KPI_METRIC_CONFIG = {
  enrollees: { name: "Enrollees",  label: "Total Enrollees",  field: "total",         unit: "",  color: "#800000", axisNoun: "school years" },
  dropout:   { name: "Drop-Out",   label: "Drop-Out Rate",    field: "dropout_rate",  unit: "%", color: "#800000", axisNoun: "school years" },
  mps:       { name: "Average MPS", label: "Average MPS",     field: "avg_mps",       unit: "%", color: "#800000", axisNoun: "grading periods" },
};

function findDepedKpiForYear(year) {
  return DEPED_KPI_DATA.find(r => r.school_year === year) || null;
}

// Not every year in the School Year filter has a DepEd KPI report imported yet
// (the filter also includes years that only have enrollment headcounts) — the
// chart below already plots every year that DOES have KPI data, which is why
// it shows bars even when the selected year has none. Falling back to the
// latest reported year, with a note, keeps the tile from reading "No data"
// while the chart clearly is not empty.
function latestDepedKpiRow() {
  const sorted = [...DEPED_KPI_DATA].sort((a, b) => b.school_year.localeCompare(a.school_year));

  return sorted[0] || null;
}

function findEnrollmentTotalForYear(year) {
  return ENROLLMENT_TOTALS_DATA.find(r => r.school_year === year) || null;
}

function formatKpiValue(row, field, unit) {
  if (!row || row[field] === null || row[field] === undefined) return "No data";
  const num = parseFloat(row[field]);
  return unit === "%" ? num.toFixed(2) + "%" : num.toLocaleString();
}

function renderKpiCards(year) {
  document.getElementById("kpiCardValue-enrollees").textContent = formatKpiValue(findEnrollmentTotalForYear(year), "total", "");

  const yearNoteEl  = document.getElementById("kpiCardYearNote-dropout");
  let dropoutRow    = findDepedKpiForYear(year);
  if (dropoutRow) {
    yearNoteEl.textContent = "";
  } else {
    dropoutRow = latestDepedKpiRow();
    yearNoteEl.textContent = dropoutRow ? "(SY " + dropoutRow.school_year + ")" : "";
  }
  document.getElementById("kpiCardValue-dropout").textContent = formatKpiValue(dropoutRow, "dropout_rate", "%");
}

function buildKpiChart() {
  const cfg = Object.assign({}, KPI_METRIC_CONFIG[currentMetric]);
  cfg.color = chartColor();
  document.getElementById("kpiChartTitle").textContent = cfg.label;
  const kpiCanvas = document.getElementById("kpiChart");
  kpiCanvas.dataset.source      = KPI_METRIC_SOURCES[currentMetric] || "";
  kpiCanvas.dataset.unit        = cfg.unit;
  kpiCanvas.dataset.labelHeader = currentMetric === "mps" ? "Term" : "School Year";

  let labels, values;
  if (currentMetric === "mps") {
    const terms = [...MPS_TREND_DATA].sort((a, b) => a.term - b.term);
    labels = terms.map(t => "Term " + t.term + " (SY " + MPS_SOURCE_YEAR + ")");
    values = terms.map(t => t.avg_mps);
  } else if (currentMetric === "enrollees") {
    const years = [...ENROLLMENT_TOTALS_DATA].sort((a, b) => a.school_year.localeCompare(b.school_year));
    labels = years.map(r => r.school_year);
    values = years.map(r => r.total);
  } else {
    const years = [...DEPED_KPI_DATA].sort((a, b) => a.school_year.localeCompare(b.school_year));
    labels = years.map(r => r.school_year);
    values = years.map(r => (r[cfg.field] !== null ? parseFloat(r[cfg.field]) : null));
  }

  const canvas = document.getElementById("kpiChart");
  const empty  = document.getElementById("kpiChartEmpty");
  const hasAny = values.length > 0 && values.some(v => v !== null);

  if (kpiChart) { kpiChart.destroy(); kpiChart = null; }

  if (!hasAny) {
    canvas.classList.add("d-none");
    empty.classList.remove("d-none");
    document.getElementById("kpiChartEmptyText").textContent = "No " + cfg.label + " data available for these " + cfg.axisNoun + ".";
    return;
  }

  canvas.classList.remove("d-none");
  empty.classList.add("d-none");

  const type = currentChartKind === "line" ? "line" : "bar";

  kpiChart = new Chart(canvas, {
    type: type,
    data: {
      labels: labels,
      datasets: [{
        label: cfg.label,
        data: values,
        // Line: a ~10% wash under a 2px line, never a saturated block.
        // Bar: a solid fill, but thin and capped (maxBarThickness) so it
        // reads as a mark, not a wall of color.
        backgroundColor: type === "line" ? (ctx) => {
          const area = ctx.chart.chartArea;
          if (!area) return chartColor(.1);
          const g = ctx.chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
          g.addColorStop(0, chartColor(.22));
          g.addColorStop(1, chartColor(0));
          return g;
        } : cfg.color,
        borderColor: cfg.color,
        borderRadius: type === "bar" ? 6 : undefined,
        maxBarThickness: type === "bar" ? 40 : undefined,
        fill: type === "line",
        tension: .3,
        cubicInterpolationMode: "monotone", // smooth, but never dips or peaks past a real data point
        borderWidth: type === "line" ? 2 : undefined,
        pointRadius: type === "line" ? 4 : undefined,
        pointBackgroundColor: type === "line" ? cfg.color : undefined,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      // Single series — no legend box, the card title already names it.
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, grid: { color: chartGridColor() } }, x: { grid: { display: false } } },
    },
  });
}

function selectKpiMetric(metric) {
  currentMetric = metric;
  document.querySelectorAll(".stat-tile-clickable").forEach(el => el.classList.toggle("active", el.dataset.metric === metric));
  const metricSelect = document.getElementById("kpiMetricSelect");
  if (metricSelect) metricSelect.value = metric;
  buildKpiChart();
}

function selectChartKind(kind) {
  currentChartKind = kind;
  switchTab("charttype", kind);
  buildKpiChart();
}

renderKpiCards(' . json_encode($currentYear) . ');
buildKpiChart();
</script>';
include APPPATH . 'Views/layout/footer.php';

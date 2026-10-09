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
    'info'    => ['rgba(var(--chart-rgb), .1)', 'rgb(var(--chart-rgb))'], // maroon in light, gold in dark
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
        <select id="dashboard-year-filter" onchange="onDashboardYearChange(this)">
          <option value="__other">Custom…</option>
          <?php if ($range !== null): ?>
          <option value="__range" selected><?= e($range['label']) ?> (<?= (int) $range['count'] ?> yrs)</option>
          <?php endif; ?>
          <?php foreach ($years as $y): ?>
          <option value="<?= e($y) ?>" <?= $range === null && $y === $currentYear ? 'selected' : '' ?>><?= e(str_replace('-', '–', $y)) ?></option>
          <?php endforeach; ?>
        </select>
      </span>
    </label>
    <?php if (hasRole('admin', 'adas')): ?>
    <button type="button" class="btn dash-btn dash-btn-primary" data-bs-toggle="modal" data-bs-target="#addEnrollmentModal">
      <i class="bi bi-person-plus me-2"></i>Add Enrollment
    </button>
    <button type="button" class="btn dash-btn" data-bs-toggle="modal" data-bs-target="#importKpiModal">
      <i class="bi bi-upload me-2"></i>Import KPI Report
    </button>
    <?php endif; ?>
    <button type="button" class="btn dash-btn" onclick="openReportModal()">
      <i class="bi bi-file-earmark-bar-graph me-2"></i>Generate Report
    </button>
    <button type="button" class="btn dash-btn" onclick="document.getElementById('dash-breakdown').scrollIntoView({ behavior: 'smooth', block: 'start' })">
      <i class="bi bi-bar-chart-line me-2"></i>View Data
    </button>
  </div>
</div>

<?php if ($range !== null): ?>
<div class="alert alert-info d-flex flex-wrap align-items-center gap-2 py-2 mb-3 small">
  <i class="bi bi-calendar-range"></i>
  <span>
    Showing trends for <strong>SY <?= $range['start'] ?>–<?= $range['start'] + 1 ?></strong> to
    <strong>SY <?= $range['end'] - 1 ?>–<?= $range['end'] ?></strong> (<?= (int) $range['count'] ?> school years).
    Single-year cards show <strong>SY <?= e(str_replace('-', '–', $currentYear)) ?></strong>, the latest in this range.
  </span>
  <a href="#" class="ms-auto fw-semibold text-decoration-none" onclick="openYearRangeModal(); return false;"><i class="bi bi-sliders me-1"></i>Change</a>
  <a href="<?= base_url('dashboard') ?>" class="fw-semibold text-decoration-none"><i class="bi bi-x-circle me-1"></i>Clear</a>
</div>
<?php endif; ?>

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
              onchange="loadPage('<?= base_url('dashboard') ?>?<?= $range !== null ? 'range=' . $range['start'] . '-' . $range['end'] : 'year=' . urlencode($currentYear) ?>&month=' + encodeURIComponent(this.value), { scroll: false })">
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
      <div class="dash-legend"><span><i style="background:rgb(var(--chart-rgb))"></i>Male</span><span><i style="background:var(--chart-soft)"></i>Female</span></div>
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
          <span><span class="d-inline-block rounded-circle me-2" style="width:9px;height:9px;background:rgba(var(--chart-rgb),<?= $alpha ?>);"></span><?= $status ?></span>
          <strong><?= $docPct($status) ?>%</strong>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Side rail: insights + today's time records -->
  <div class="dash-area-side d-flex flex-column gap-3">
    <?php $calendarCardClass = 'card dash-card'; include APPPATH . 'Views/partials/school_calendar.php'; ?>
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
      <div class="dash-legend m-0"><span><i style="background:rgb(var(--chart-rgb))"></i>Average MPS</span></div>
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
      <?php /* One title per tab — switchTab() shows the one matching the active tab like it does the panels. */ ?>
      <span data-tab-panel="data:breakdown"><i class="bi bi-building"></i>Enrollment Breakdown<?php if (! empty($enrollmentMonth)): ?> <small class="text-muted fw-normal">(<?= e(date('F Y', strtotime($enrollmentMonth . '-01'))) ?>)</small><?php endif; ?></span>
      <span data-tab-panel="data:subject" class="d-none"><i class="bi bi-mortarboard"></i>Performance by Learning Area <small class="text-muted fw-normal">(SY <?= e(str_replace('-', '–', $currentYear)) ?>, Term <?= e($currentTerm) ?>)</small></span>
      <?php if (! empty($depedKpis)): ?>
      <span data-tab-panel="data:deped" class="d-none"><i class="bi bi-clipboard-data"></i>DepEd Historical KPIs</span>
      <?php endif; ?>
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
    <?php
      // DepEd descriptor (Advancing … Emerging) as [label, pill class].
      $mpsBadge = static function ($mps): array {
          $d = mpsDescriptor((float) $mps);

          return [$d['label'], $d['class']];
      };
      // "A, B" => linked names (person card) for teachers with an account; placeholders => "Not assigned".
      $teacherCell = static function (string $instructor) use ($perfTeachers): string {
          $instructor = trim($instructor);
          if (in_array($instructor, \App\Libraries\MpsCalculator::UNKNOWN_INSTRUCTORS, true)) {
              return '<span class="text-muted fst-italic">Not assigned</span>';
          }
          $names = array_filter(array_map('trim', explode(',', $instructor)));

          return implode(', ', array_map(static fn ($n) => personLink($perfTeachers[$n] ?? null, $n), $names));
      };
      $perfBySubject = [];
      foreach ($allPerf as $p) {
          $perfBySubject[$p['subject']][] = $p;
      }
    ?>
    <div class="table-responsive" id="perfSummaryTable">
      <table class="table dash-table mb-0">
        <thead>
          <tr>
            <th>Subject</th><th class="text-center">Grade Levels</th>
            <th class="text-end">Average MPS</th><th class="text-center">Status</th><th style="width:1%;"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($avgPerf as $p): $badge = $mpsBadge($p['mps']); ?>
          <tr class="perf-subject-row" role="button" tabindex="0" title="View <?= e($p['subject']) ?> by grade level and teacher"
              data-subject="<?= e($p['subject']) ?>" onclick="openSubjectDetail(this)" onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openSubjectDetail(this); }">
            <td class="fw-semibold"><?= e($p['subject']) ?></td>
            <td class="text-center text-muted"><?= count($perfBySubject[$p['subject']] ?? []) ?></td>
            <td class="text-end"><span class="badge bg-light text-dark border fw-bold"><?= $p['mps'] ?>%</span></td>
            <td class="text-center"><span class="status-pill <?= $badge[1] ?>"><?= $badge[0] ?></span></td>
            <td class="text-muted"><i class="bi bi-chevron-right"></i></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="table-responsive d-none" id="perfFullTable">
      <table class="table dash-table mb-0">
        <thead>
          <tr>
            <th>Subject</th><th>Grade Level</th><th>Teacher</th>
            <th class="text-end">MPS</th><th class="text-center">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allPerf as $p): $badge = $mpsBadge($p['mps']); ?>
          <tr>
            <td class="fw-semibold"><?= e($p['subject']) ?></td>
            <td class="text-muted"><?= e($p['grade_level']) ?></td>
            <td><?= $teacherCell((string) $p['instructor']) ?></td>
            <td class="text-end"><span class="badge bg-light text-dark border fw-bold"><?= $p['mps'] ?>%</span></td>
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

<?php if (hasRole('admin', 'adas')): ?>
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
            <datalist id="kpiYearOptions">
              <?php foreach ($years as $y): ?>
              <option value="<?= e($y) ?>"></option>
              <?php endforeach; ?>
            </datalist>
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
      <form method="POST" action="<?= base_url('enrollment-kpis/import') ?>" class="ajax-form" enctype="multipart/form-data" data-sy-prompt="school_year" data-confirm-text="Existing KPI data for this school year will be overwritten.">
        <input type="hidden" name="school_year" value="">
        <div class="modal-body">
          <p class="text-muted" style="font-size:.82rem;">
            Upload the DepEd "Key Performance Indicator" Word report (the Indicator table). You'll be asked
            which school year the data is for after you click Import. Not sure of the format?
            <a href="<?= base_url('enrollment-kpis/template') ?>">Download the template</a>.
          </p>
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

<!-- Generate Report -->
<div class="modal fade" id="reportModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-file-earmark-bar-graph me-2"></i>Generate Report</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label" for="reportScope">Report for</label>
          <select id="reportScope" class="form-select form-select-sm" required onchange="updateReportSections()">
            <?php if ($range !== null): ?>
            <option value="range:<?= $range['start'] ?>-<?= $range['end'] ?>" selected>SY <?= $range['start'] ?>–<?= $range['start'] + 1 ?> to SY <?= $range['end'] - 1 ?>–<?= $range['end'] ?> (current filter)</option>
            <?php endif; ?>
            <?php foreach ($years as $y): ?>
            <option value="year:<?= e($y) ?>" <?= $range === null && $y === $currentYear ? 'selected' : '' ?>>SY <?= e(str_replace('-', '–', $y)) ?><?= $range === null && $y === $currentYear ? ' (current filter)' : '' ?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Defaults to the year the dashboard is showing. For a multi-year range, use <strong>Custom…</strong> in the School Year filter first.</div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="small fw-semibold">Include in the report</span>
          <span class="small">
            <a href="#" class="link-maroon" onclick="setReportSections(true); return false;">Select all</a> ·
            <a href="#" class="link-maroon" onclick="setReportSections(false); return false;">Clear</a>
          </span>
        </div>
        <div class="report-section-list">
          <?php foreach ($reportSections as $key => $label): ?>
          <label class="report-section-option">
            <input type="checkbox" class="form-check-input mt-0 report-section-cb" value="<?= e($key) ?>" checked>
            <span><?= e($label) ?></span>
            <span class="report-section-nodata">No data</span>
          </label>
          <?php endforeach; ?>
        </div>
        <div class="form-text d-none" id="reportNoDataNote">Sections marked <em>No data</em> have nothing recorded for the selected period.</div>
        <div class="text-danger small mt-2 d-none" id="reportError">Tick at least one section.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="generateReport()"><i class="bi bi-file-earmark-arrow-down me-1"></i>Generate</button>
      </div>
    </div>
  </div>
</div>
<!-- Year range ("Custom…" in the School Year filter) -->
<div class="modal fade" id="yearRangeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-calendar-range me-2"></i>Choose a Year Range</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3">Trend charts, the DepEd history table and the year-over-year comparisons will only cover these school years.</p>
        <?php
          $syNow = $currentSchoolYearStart;
          $presets = [5 => [$syNow - 4, $syNow + 1], 10 => [$syNow - 9, $syNow + 1]];
        ?>
        <div class="d-flex flex-column gap-2">
          <?php foreach ($presets as $n => [$from, $to]): ?>
          <label class="year-range-option">
            <input type="radio" name="yearRangeChoice" value="<?= $from ?>-<?= $to ?>" class="form-check-input mt-0">
            <span>
              <span class="fw-semibold d-block">Last <?= $n ?> years</span>
              <span class="small text-muted">SY <?= $from ?>–<?= $from + 1 ?> to SY <?= $to - 1 ?>–<?= $to ?></span>
            </span>
          </label>
          <?php endforeach; ?>
          <label class="year-range-option">
            <input type="radio" name="yearRangeChoice" value="custom" class="form-check-input mt-0">
            <span class="flex-grow-1">
              <span class="fw-semibold d-block">Custom range</span>
              <span class="d-flex align-items-center gap-2 mt-2">
                <input type="text" id="yearRangeFrom" class="form-control form-control-sm text-center" style="width:90px;"
                       inputmode="numeric" maxlength="4" placeholder="From" value="<?= $range['start'] ?? '' ?>">
                <span class="fw-semibold">–</span>
                <input type="text" id="yearRangeTo" class="form-control form-control-sm text-center" style="width:90px;"
                       inputmode="numeric" maxlength="4" placeholder="To" value="<?= $range['end'] ?? '' ?>">
              </span>
              <span class="small text-muted d-block mt-1" id="yearRangePreview">e.g. 2014 – 2022</span>
            </span>
          </label>
        </div>
        <div class="text-danger small mt-2 d-none" id="yearRangeError"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="applyYearRange()"><i class="bi bi-funnel me-1"></i>Apply</button>
      </div>
    </div>
  </div>
</div>

<!-- Learning-area detail: one subject's MPS per grade level and teacher (clicked row in Performance by Learning Area) -->
<div class="modal fade" id="subjectDetailModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-mortarboard me-2"></i><span id="subjectDetailTitle"></span></h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php foreach ($avgPerf as $p): $rows = $perfBySubject[$p['subject']] ?? []; usort($rows, static fn ($a, $b) => strnatcmp((string) $a['grade_level'], (string) $b['grade_level'])); $badge = $mpsBadge($p['mps']); ?>
        <div class="d-none" data-subject-detail="<?= e($p['subject']) ?>">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-3 small text-muted">
            <span>SY <?= e(str_replace('-', '–', $currentYear)) ?> · Term <?= (int) $currentTerm ?></span>
            <span class="ms-auto">Average MPS <span class="badge bg-light text-dark border fw-bold ms-1"><?= $p['mps'] ?>%</span></span>
            <span class="status-pill <?= $badge[1] ?>"><?= $badge[0] ?></span>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr><th>Grade Level</th><th>Teacher</th><th class="text-end">MPS</th><th class="text-center">Status</th></tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): $rowBadge = $mpsBadge($r['mps']); ?>
                <tr>
                  <td class="fw-semibold"><?= e($r['grade_level']) ?></td>
                  <td><?= $teacherCell((string) $r['instructor']) ?></td>
                  <td class="text-end"><span class="badge bg-light text-dark border fw-bold"><?= $r['mps'] ?>%</span></td>
                  <td class="text-center"><span class="status-pill <?= $rowBadge[1] ?>"><?= $rowBadge[0] ?></span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php
$extraScript = '<script>
/* ── Performance by Learning Area: clicked subject => its per-grade / per-teacher breakdown ── */
function openSubjectDetail(row) {
  const subject = row.dataset.subject;
  document.getElementById("subjectDetailTitle").textContent = subject;
  document.querySelectorAll("[data-subject-detail]").forEach(el => {
    el.classList.toggle("d-none", el.dataset.subjectDetail !== subject);
  });
  bootstrap.Modal.getOrCreateInstance(document.getElementById("subjectDetailModal")).show();
}

/* ── Generate Report: printable report of the ticked sections, same filters as the dashboard ── */
const REPORT_URL   = ' . json_encode(base_url('dashboard/report')) . ';
const REPORT_MONTH = ' . json_encode($enrollmentMonth ?? null) . ';
// "Report for" option => sections that have data for it (Dashboard::reportAvailability()).
const REPORT_AVAILABILITY = ' . json_encode($reportAvailability) . ';

function openReportModal() {
  document.getElementById("reportError").classList.add("d-none");
  updateReportSections();
  bootstrap.Modal.getOrCreateInstance(document.getElementById("reportModal")).show();
}

/* Sections with no data for the chosen period are unticked and disabled; the rest are ticked. */
function updateReportSections() {
  const available = REPORT_AVAILABILITY[document.getElementById("reportScope").value] || [];
  let missing = 0;
  document.querySelectorAll(".report-section-cb").forEach(cb => {
    const ok = available.includes(cb.value);
    cb.disabled = !ok;
    cb.checked  = ok;
    cb.closest(".report-section-option").classList.toggle("is-disabled", !ok);
    if (!ok) missing++;
  });
  document.getElementById("reportNoDataNote").classList.toggle("d-none", missing === 0);
  document.getElementById("reportError").classList.add("d-none");
}

function setReportSections(checked) {
  document.querySelectorAll(".report-section-cb:not(:disabled)").forEach(cb => { cb.checked = checked; });
}

function generateReport() {
  const sections = [...document.querySelectorAll(".report-section-cb:checked")].map(cb => cb.value);
  if (!sections.length) {
    document.getElementById("reportError").classList.remove("d-none");
    return;
  }

  const [kind, value] = document.getElementById("reportScope").value.split(":");
  const params = new URLSearchParams();
  params.set(kind === "range" ? "range" : "year", value);
  // Keep the enrollment month the dashboard is showing, but only for that same year/range.
  if (REPORT_MONTH && document.getElementById("reportScope").selectedOptions[0].text.includes("(current filter)")) {
    params.set("month", REPORT_MONTH);
  }
  sections.forEach(s => params.append("sections[]", s));

  window.open(REPORT_URL + "?" + params.toString(), "_blank");
  bootstrap.Modal.getInstance(document.getElementById("reportModal"))?.hide();
}

/* ── School Year filter: a single year, or "Custom…" for a year range ── */
const DASHBOARD_URL = ' . json_encode(base_url('dashboard')) . ';
const yearFilterEl = document.getElementById("dashboard-year-filter");
yearFilterEl.dataset.prev = yearFilterEl.value;

function onDashboardYearChange(sel) {
  if (sel.value === "__other") {
    sel.value = sel.dataset.prev; // keep showing the current choice behind the modal
    openYearRangeModal();
    return;
  }
  if (sel.value === "__range") return;
  location.href = DASHBOARD_URL + "?year=" + encodeURIComponent(sel.value);
}

const CURRENT_RANGE = ' . json_encode($range !== null ? $range['start'] . '-' . $range['end'] : null) . ';

function openYearRangeModal() {
  document.getElementById("yearRangeError").classList.add("d-none");
  if (CURRENT_RANGE) {
    const preset = document.querySelector("input[name=yearRangeChoice][value=\"" + CURRENT_RANGE + "\"]");
    (preset || document.querySelector("input[name=yearRangeChoice][value=custom]")).checked = true;
  }
  updateYearRangePreview();
  bootstrap.Modal.getOrCreateInstance(document.getElementById("yearRangeModal")).show();
}

function updateYearRangePreview() {
  const from = parseInt(document.getElementById("yearRangeFrom").value, 10);
  const to   = parseInt(document.getElementById("yearRangeTo").value, 10);
  const el   = document.getElementById("yearRangePreview");
  el.textContent = from >= 1000 && to > from
    ? "SY " + from + "–" + (from + 1) + " to SY " + (to - 1) + "–" + to + " (" + (to - from) + " school year" + (to - from === 1 ? "" : "s") + ")"
    : "e.g. 2014 – 2022";
}

["yearRangeFrom", "yearRangeTo"].forEach(id => {
  const input = document.getElementById(id);
  input.addEventListener("input", () => {
    input.value = input.value.replace(/\D/g, "").slice(0, 4);
    document.querySelector("input[name=yearRangeChoice][value=custom]").checked = true;
    updateYearRangePreview();
  });
  input.addEventListener("focus", () => { document.querySelector("input[name=yearRangeChoice][value=custom]").checked = true; });
});

function applyYearRange() {
  const choice = document.querySelector("input[name=yearRangeChoice]:checked");
  const errEl  = document.getElementById("yearRangeError");
  const fail   = msg => { errEl.textContent = msg; errEl.classList.remove("d-none"); };

  if (!choice) return fail("Pick a range first.");

  let range = choice.value;
  if (range === "custom") {
    const from = parseInt(document.getElementById("yearRangeFrom").value, 10);
    const to   = parseInt(document.getElementById("yearRangeTo").value, 10);
    if (!(from >= 1000) || !(to >= 1000)) return fail("Enter both years as 4 digits, e.g. 2014 and 2022.");
    if (to <= from) return fail("The second year must be after the first.");
    if (to - from > 30) return fail("Please choose 30 years or fewer.");
    range = from + "-" + to;
  }

  bootstrap.Modal.getInstance(document.getElementById("yearRangeModal"))?.hide();
  location.href = DASHBOARD_URL + "?range=" + encodeURIComponent(range);
}

const maroon = chartColor(), maroonLight = chartColorAlt(), maroonDark = "#560000", crimson = "#dc143c";
const chartSoft = getComputedStyle(document.documentElement).getPropertyValue("--chart-soft").trim();

function togglePerfBreakdown() {
  const summary = document.getElementById("perfSummaryTable");
  const full = document.getElementById("perfFullTable");
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

// Enrollment Breakdown keeps the By Grade & Section size on every tab: measure
// that panel once (and on resize) and the other tabs scroll inside the same height.
function lockBreakdownHeight() {
  const card = document.getElementById("dash-breakdown");
  const base = card && card.querySelector(".dash-card-body[data-tab-panel=\"data:breakdown\"]");
  if (!base) return;
  const wasHidden = base.classList.contains("d-none");
  card.style.removeProperty("--breakdown-h");
  base.classList.remove("d-none");
  card.style.setProperty("--breakdown-h", base.offsetHeight + "px");
  if (wasHidden) base.classList.add("d-none");
}
lockBreakdownHeight();
if (document.fonts) document.fonts.ready.then(lockBreakdownHeight); // row heights settle once web fonts load
window.removeEventListener("resize", window.__lockBreakdownOnResize || lockBreakdownHeight);
window.__lockBreakdownOnResize = lockBreakdownHeight;
window.addEventListener("resize", lockBreakdownHeight);

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
        { label: "Male",   data: ' . json_encode(array_map(static fn ($r) => (int) $r['male'], $enrollment)) . ',   backgroundColor: maroon, borderRadius: 4, maxBarThickness: 22 },
        { label: "Female", data: ' . json_encode(array_map(static fn ($r) => (int) $r['female'], $enrollment)) . ', backgroundColor: chartSoft, borderRadius: 4, maxBarThickness: 22 }
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
        { label:"Average MPS", data:' . json_encode(array_column($perfLevel, 'mps')) . ', backgroundColor:maroon, borderRadius:4, maxBarThickness:26 }
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
        ? [chartColor(1), chartColor(.7), chartColor(.45), chartColor(.25)]
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

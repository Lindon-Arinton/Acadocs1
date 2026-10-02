<?php include APPPATH . 'Views/layout/header.php'; ?>

<!-- Page Header: solid-maroon banner, same shared style as every other page. -->
<div class="page-header">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <div class="small mb-1" style="opacity:.8;">Welcome back,</div>
      <h4 class="mb-1"><?= e($user['name'] ?? '') ?></h4>
      <p class="mb-0">Here's an overview of your school's performance.</p>
    </div>
    <div class="dash-header-actions">
      <?php if (hasRole('admin', 'adas')): ?>
      <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#addEnrollmentModal">
        <i class="bi bi-people me-1"></i>Add Enrollment
      </button>
      <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#importKpiModal">
        <i class="bi bi-upload me-1"></i>Import KPI Report
      </button>
      <?php endif; ?>
      <button type="button" class="btn btn-sm btn-light fw-semibold" style="color:var(--primary);" onclick="openReportModal()">
        <i class="bi bi-file-earmark-bar-graph me-1"></i>Generate Report
      </button>
      <div class="dash-header-year">
      <label class="text-white small fw-semibold mb-0 text-nowrap" for="dashboard-year-filter">School Year:</label>
      <div class="maroon-select maroon-select-sm">
        <select id="dashboard-year-filter" class="maroon-select-native" onchange="onDashboardYearChange(this)">
          <option value="__other">Custom…</option>
          <?php if ($range !== null): ?>
          <option value="__range" selected><?= e($range['label']) ?> (<?= (int) $range['count'] ?> yrs)</option>
          <?php endif; ?>
          <?php foreach ($years as $y): ?>
          <option value="<?= e($y) ?>" <?= $range === null && $y === $currentYear ? 'selected' : '' ?>><?= e(str_replace('-', '–', $y)) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
        <div class="maroon-select-panel"></div>
      </div>
      </div>
    </div>
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

<?php
// Sparkline SVG markup is identical for the three trend tiles — only the
// accent color and computed points differ, so build it once here. Both the
// line and its area wash carry the tile's own accent color (per-metric
// color story), with the current point as a solid dot.
$sparklineSvg = static function (?array $spark, string $accentColor): string {
    if ($spark === null) {
        return '';
    }

    return '<svg class="stat-sparkline" viewBox="0 0 64 22" width="64" height="22" preserveAspectRatio="none">'
        . '<polygon points="' . e($spark['areaPoints']) . '" fill="' . $accentColor . '"></polygon>'
        . '<polyline points="' . e($spark['points']) . '" stroke="' . $accentColor . '"></polyline>'
        . '<circle cx="' . $spark['lastX'] . '" cy="' . $spark['lastY'] . '" r="2.25" fill="' . $accentColor . '"></circle>'
        . '</svg>';
};

// Delta row: arrow + magnitude pill, colored by whether the direction is
// GOOD for that particular metric (down is good for drop-out, up is good for
// enrollees/MPS) — not just by up/down — plus a "vs SY ..." caption naming
// what it's compared against.
$deltaRow = static function (?array $delta, bool $upIsGood, string $suffix = '%'): string {
    if ($delta === null) {
        return '';
    }

    $isUp   = $delta['delta'] >= 0;
    $isGood = $isUp === $upIsGood;
    $cls    = $isGood ? 'stat-delta-up' : 'stat-delta-down';
    $icon   = $isUp ? 'bi-arrow-up-short' : 'bi-arrow-down-short';

    return '<div class="stat-tile-delta-row">'
        . '<span class="stat-delta ' . $cls . '"><i class="bi ' . $icon . '"></i>' . number_format(abs($delta['delta']), 2) . $suffix . '</span>'
        . '<span class="text-muted">vs ' . e($delta['vsLabel']) . '</span>'
        . '</div>';
};

// Submission compliance is a meter against a target, not a plain magnitude —
// its fill carries severity (good / needs attention / critical) the same way
// the rest of the app's status badges do, rather than a fixed brand color.
$complianceSeverity = 'danger';
if ($complianceRate === null) {
    $complianceSeverity = '';
} elseif ($complianceRate >= 85) {
    $complianceSeverity = 'success';
} elseif ($complianceRate >= 60) {
    $complianceSeverity = 'warning';
}
?>

<!-- Stat tile strip: 3 clickable (drive the chart below) + 1 static. All
     four share the brand maroon accent for icon/sparkline/delta-arrow, with
     the delta pill itself carrying the green/red good-or-bad read. -->
<div class="stat-tile-row mb-3">
  <div class="stat-tile stat-tile-clickable active" data-metric="enrollees" onclick="selectKpiMetric('enrollees')">
    <div class="stat-tile-top">
      <div class="stat-tile-icon" style="background:#fff0f0;color:#800000;"><i class="bi bi-people-fill"></i></div>
      <span class="stat-tile-name">Total Enrollees</span>
    </div>
    <div class="stat-tile-body">
      <div>
        <div class="stat-tile-value" id="kpiCardValue-enrollees">—</div>
        <div class="stat-tile-caption">SY <?= e($currentYear) ?></div>
      </div>
      <div class="stat-tile-visual">
        <?php if ($enrolleesSparkline !== null): ?>
          <?= $sparklineSvg($enrolleesSparkline, '#800000') ?>
        <?php else: ?>
          <i class="bi bi-people-fill stat-tile-empty-icon"></i>
        <?php endif; ?>
      </div>
    </div>
    <?= $deltaRow($enrolleesDelta, true) ?>
  </div>

  <div class="stat-tile stat-tile-clickable" data-metric="dropout" onclick="selectKpiMetric('dropout')">
    <div class="stat-tile-top">
      <div class="stat-tile-icon" style="background:#fff0f0;color:#800000;"><i class="bi bi-exclamation-triangle-fill"></i></div>
      <span class="stat-tile-name">Drop-Out Rate</span>
    </div>
    <div class="stat-tile-body">
      <div>
        <div class="stat-tile-value" id="kpiCardValue-dropout">—</div>
        <div class="stat-tile-caption" id="kpiCardYearNote-dropout">SY <?= e($currentYear) ?></div>
      </div>
      <div class="stat-tile-visual">
        <?php if ($dropoutSparkline !== null): ?>
          <?= $sparklineSvg($dropoutSparkline, '#800000') ?>
        <?php else: ?>
          <i class="bi bi-exclamation-triangle-fill stat-tile-empty-icon"></i>
        <?php endif; ?>
      </div>
    </div>
    <?= $deltaRow($dropoutDelta, false) ?>
  </div>

  <div class="stat-tile stat-tile-clickable" data-metric="mps" onclick="selectKpiMetric('mps')">
    <div class="stat-tile-top">
      <div class="stat-tile-icon" style="background:#fff0f0;color:#800000;"><i class="bi bi-graph-up-arrow"></i></div>
      <span class="stat-tile-name">Average MPS</span>
    </div>
    <div class="stat-tile-body">
      <div>
        <div class="stat-tile-value"><?= $mpsOverallAvg !== null ? number_format($mpsOverallAvg, 2) . '%' : 'No data' ?></div>
        <div class="stat-tile-caption">SY <?= e($mpsSourceYear) ?></div>
      </div>
      <div class="stat-tile-visual">
        <?php if ($mpsSparkline !== null): ?>
          <?= $sparklineSvg($mpsSparkline, '#800000') ?>
        <?php else: ?>
          <i class="bi bi-graph-up-arrow stat-tile-empty-icon"></i>
        <?php endif; ?>
      </div>
    </div>
    <?= $deltaRow($mpsDelta, true) ?>
  </div>

  <div class="stat-tile">
    <div class="stat-tile-top">
      <div class="stat-tile-icon" style="background:#fff0f0;color:#800000;"><i class="bi bi-clipboard-check-fill"></i></div>
      <span class="stat-tile-name">Submission Compliance</span>
    </div>
    <div class="stat-tile-body">
      <div style="width:100%;">
        <div class="stat-tile-value"><?= $complianceRate !== null ? number_format($complianceRate, 1) . '%' : 'No data' ?></div>
        <div class="stat-tile-caption">Documents</div>
        <div class="progress mt-2" style="height:5px;">
          <div class="progress-bar <?= $complianceSeverity ?>" style="width:<?= $complianceRate !== null ? $complianceRate : 0 ?>%;"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bento row: primary interactive chart + compact charts (left) / side rail (right) -->
<div class="dashboard-grid mb-3">
  <div>
    <!-- Interactive KPI chart: one canvas, switch chart type via tabs -->
    <div class="card">
      <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span class="fw-semibold small"><i class="bi bi-graph-up me-2 text-muted"></i><span id="kpiChartTitle">Total Enrollees</span> Trend</span>
        <div class="tab-pills" data-tab-group="charttype">
          <button type="button" class="tab-pill active" data-tab-key="line" onclick="selectChartKind('line')">Line</button>
          <button type="button" class="tab-pill" data-tab-key="bar" onclick="selectChartKind('bar')">Bar</button>
        </div>
      </div>
      <div class="card-body">
        <canvas id="kpiChart" height="220"></canvas>
        <p id="kpiChartEmpty" class="text-muted text-center py-4 mb-0 d-none small">
          <i class="bi bi-bar-chart fs-4 d-block mb-2"></i><span id="kpiChartEmptyText">No data available.</span>
        </p>
      </div>
    </div>

    <div class="row g-3 mt-1">
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header bg-white py-2">
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
              <span class="fw-semibold small"><i class="bi bi-bar-chart-steps me-2 text-muted"></i>Enrolment by Grade Level</span>
              <?php if (! empty($enrollmentMonths)): ?>
              <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0" for="enrollment-month-filter">Month:</label>
                <div class="maroon-select maroon-select-sm" style="width:auto;">
                  <select id="enrollment-month-filter" class="maroon-select-native"
                          onchange="loadPage('<?= base_url('dashboard') ?>?<?= $range !== null ? 'range=' . $range['start'] . '-' . $range['end'] : 'year=' . urlencode($currentYear) ?>&month=' + encodeURIComponent(this.value), { scroll: false })">
                    <?php foreach (array_reverse($enrollmentMonths) as $m): ?>
                    <option value="<?= e($m) ?>" <?= $m === $enrollmentMonth ? 'selected' : '' ?>><?= e(date('F Y', strtotime($m . '-01'))) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
                  <div class="maroon-select-panel"></div>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="card-body card-body-tight">
            <?php if (empty($enrollment)): ?>
            <p class="text-muted text-center py-4 mb-0 small"><i class="bi bi-bar-chart fs-4 d-block mb-2"></i>No enrolment data for SY <?= e($currentYear) ?>.</p>
            <?php else: ?>
            <canvas id="enrollChart" height="160"></canvas>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header bg-white py-2">
            <span class="fw-semibold small"><i class="bi bi-graph-up me-2 text-muted"></i>Performance by Level</span>
          </div>
          <div class="card-body card-body-tight">
            <?php if (empty($perfLevel)): ?>
            <p class="text-muted text-center py-4 mb-0 small"><i class="bi bi-graph-up fs-4 d-block mb-2"></i>No performance data for SY <?= e($currentYear) ?>.</p>
            <?php else: ?>
            <canvas id="perfChart" height="160"></canvas>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="side-rail-stack">
    <?php if (! empty($insights)):
      $insightTone = [
        'danger'  => ['bg' => '#fee2e2', 'fg' => '#ef4444', 'tint' => 'rgba(239,68,68,.08)'],
        'warning' => ['bg' => '#fef9c3', 'fg' => '#b45309', 'tint' => 'rgba(180,83,9,.08)'],
        'success' => ['bg' => '#d1fae5', 'fg' => '#059669', 'tint' => 'rgba(5,150,105,.08)'],
        'info'    => ['bg' => '#fff0f0', 'fg' => '#800000', 'tint' => 'rgba(128,0,0,.06)'],
      ];
    ?>
    <div class="card">
      <div class="card-header bg-white py-2">
        <span class="fw-semibold small"><i class="bi bi-lightbulb-fill me-2 text-muted"></i>Insights</span>
      </div>
      <div class="card-body card-body-tight d-flex flex-column gap-2">
        <?php foreach ($insights as $insight): $t = $insightTone[$insight['tone']]; ?>
        <div class="d-flex align-items-start gap-2 p-2 rounded-3" style="background:<?= $t['tint'] ?>;">
          <div class="stat-tile-icon" style="width:28px;height:28px;font-size:.8rem;background:<?= $t['bg'] ?>;color:<?= $t['fg'] ?>;flex-shrink:0;">
            <i class="bi <?= $insight['icon'] ?>"></i>
          </div>
          <p class="small mb-0" style="padding-top:.15rem;"><?= $insight['text'] ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php $docTotal = array_sum($docSummary); ?>
    <div class="card">
      <div class="card-header bg-white py-2">
        <span class="fw-semibold small"><i class="bi bi-pie-chart me-2 text-muted"></i>Document Status</span>
      </div>
      <div class="card-body card-body-tight d-flex align-items-center gap-3">
        <div class="donut-wrap" style="width:110px;height:110px;">
          <canvas id="docChart" height="110" width="110"></canvas>
          <div class="donut-center-label">
            <div class="donut-center-value"><?= $docTotal ?></div>
            <div class="donut-center-caption">Total</div>
          </div>
        </div>
        <div class="w-100">
          <?php foreach (['Submitted'=>'rgba(var(--chart-rgb),1)','Reviewed'=>'rgba(var(--chart-rgb),.75)','Pending'=>'rgba(var(--chart-rgb),.5)','Returned'=>'rgba(var(--chart-rgb),.3)'] as $status=>$dotColor): ?>
          <div class="d-flex justify-content-between small mb-1">
            <span><span class="d-inline-block rounded-circle me-1" style="width:9px;height:9px;background:<?= $dotColor ?>;"></span><?= $status ?></span>
            <strong><?= $docSummary[$status] ?? 0 ?></strong>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Tabbed data panel: Enrollment Breakdown / Performance by Learning Area / DepEd Historical -->
<div class="card mb-4">
  <div class="card-header bg-white py-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <ul class="nav nav-tabs border-bottom-0" data-tab-group="data">
      <li class="nav-item">
        <button type="button" class="nav-link active" data-tab-key="breakdown" onclick="switchTab('data','breakdown')">
          <i class="bi bi-table me-1"></i>Enrolment Breakdown<?php if (! empty($enrollmentMonth)): ?> <span class="text-muted fw-normal">(<?= e(date('F Y', strtotime($enrollmentMonth . '-01'))) ?>)</span><?php endif; ?>
        </button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link" data-tab-key="subject" onclick="switchTab('data','subject')">
          <i class="bi bi-mortarboard me-1"></i>Performance by Learning Area
        </button>
      </li>
      <?php if (! empty($depedKpis)): ?>
      <li class="nav-item">
        <button type="button" class="nav-link" data-tab-key="deped" onclick="switchTab('data','deped')">
          <i class="bi bi-clipboard-data me-1"></i>DepEd Historical KPIs
        </button>
      </li>
      <?php endif; ?>
    </ul>
    <?php if (! empty($depedKpis)): ?>
    <span class="text-muted small">DepEd source: Guidance Office official reports</span>
    <?php endif; ?>
  </div>

  <!-- Enrollment Breakdown -->
  <div class="card-body p-0" data-tab-panel="data:breakdown">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr><th>Grade Level</th><th class="text-center">Sections</th>
                    <th class="text-end">Students</th><th class="text-end">Share</th></tr></thead>
        <tbody>
          <?php if (empty($enrollment)): ?>
          <tr>
            <td colspan="4" class="text-center text-muted py-4">No enrolment data available for <?= e(str_replace('-', '–', $currentYear)) ?>.</td>
          </tr>
          <?php endif; ?>
          <?php $enrollTotal = array_sum(array_column($enrollment, 'students')); ?>
          <?php foreach ($enrollment as $e): $pct = $enrollTotal > 0 ? round($e['students']/$enrollTotal*100,1) : 0; ?>
          <tr>
            <td class="fw-semibold"><?= e($e['grade_level']) ?></td>
            <td class="text-center"><?= $e['sections'] ?></td>
            <td class="text-end fw-bold"><?= number_format($e['students']) ?></td>
            <td class="text-end">
              <div class="d-flex align-items-center gap-2 justify-content-end">
                <div class="progress flex-grow-1" style="height:6px;min-width:60px;">
                  <div class="progress-bar" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="small text-muted"><?= $pct ?>%</span>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light">
          <tr>
            <td class="fw-bold">Total</td>
            <td class="text-center fw-bold"><?= array_sum(array_column($enrollment,'sections')) ?></td>
            <td class="text-end fw-bold"><?= number_format($enrollTotal) ?></td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <!-- Performance by Learning Area -->
  <div class="card-body p-0 d-none" data-tab-panel="data:subject">
    <div class="d-flex justify-content-end p-2 border-bottom">
      <button type="button" id="perfViewAllBtn" class="btn btn-sm btn-outline-secondary" onclick="togglePerfBreakdown()">
        <i class="bi bi-list-ul me-1"></i>View All
      </button>
    </div>
    <?php
      $mpsBadge = static fn ($mps): array => $mps >= 85 ? ['Excellent', 'badge-submitted'] : ($mps >= 75 ? ['Satisfactory', 'badge-reviewed'] : ['Needs Improvement', 'badge-returned']);
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
      <table class="table table-hover mb-0">
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
      <table class="table table-hover mb-0">
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
  <!-- DepEd Historical KPI Table -->
  <div class="card-body p-0 d-none" data-tab-panel="data:deped">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>School Year</th>
            <th class="text-end">Gross Enrolment</th>
            <th class="text-end">Net Enrolment</th>
            <th class="text-end">Cohort Survival</th>
            <th class="text-end">Repetition</th>
            <th class="text-end">Promotion</th>
            <th class="text-end">Retention</th>
            <th class="text-end">Graduation</th>
            <th class="text-end">Completion</th>
            <th class="text-end">Transition</th>
            <th class="text-end">Drop Out</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($depedKpis as $k): ?>
          <tr>
            <td class="fw-semibold"><?= e(str_replace('-', '–', $k['school_year'])) ?></td>
            <td class="text-end"><?= $k['gross_enrolment_rate'] !== null ? number_format((float) $k['gross_enrolment_rate'], 2) . '%' : '—' ?></td>
            <td class="text-end"><?= $k['net_enrolment_rate'] !== null ? number_format((float) $k['net_enrolment_rate'], 2) . '%' : '—' ?></td>
            <td class="text-end"><?= $k['cohort_survival_rate'] !== null ? number_format((float) $k['cohort_survival_rate'], 2) . '%' : '—' ?></td>
            <td class="text-end"><?= $k['repetition_rate'] !== null ? number_format((float) $k['repetition_rate'], 2) . '%' : '—' ?></td>
            <td class="text-end"><?= $k['promotion_rate'] !== null ? number_format((float) $k['promotion_rate'], 2) . '%' : '—' ?></td>
            <td class="text-end"><?= $k['retention_rate'] !== null ? number_format((float) $k['retention_rate'], 2) . '%' : '—' ?></td>
            <td class="text-end"><?= $k['graduation_rate'] !== null ? number_format((float) $k['graduation_rate'], 2) . '%' : '—' ?></td>
            <td class="text-end"><?= $k['completion_rate'] !== null ? number_format((float) $k['completion_rate'], 2) . '%' : '—' ?></td>
            <td class="text-end"><?= $k['transition_rate'] !== null ? number_format((float) $k['transition_rate'], 2) . '%' : '—' ?></td>
            <td class="text-end <?= (float) $k['dropout_rate'] > 1.5 ? 'text-danger fw-semibold' : '' ?>"><?= $k['dropout_rate'] !== null ? number_format((float) $k['dropout_rate'], 2) . '%' : '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
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
        { label: "Male",   data: ' . json_encode(array_map(static fn ($r) => (int) $r['male'], $enrollment)) . ',   backgroundColor: maroon,      borderRadius: 4, maxBarThickness: 22 },
        { label: "Female", data: ' . json_encode(array_map(static fn ($r) => (int) $r['female'], $enrollment)) . ', backgroundColor: chartColor(.3), borderColor: maroon, borderWidth: 1, borderRadius: 4, maxBarThickness: 22 }
      ] : [
        { label: "Students", data: enrollTotals, backgroundColor: chartColor(.25), borderColor: maroon, borderWidth: 1, borderRadius: 4, maxBarThickness: 28 }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { display: enrollHasSplit, position: "bottom", labels: { boxWidth: 12, boxHeight: 12 } },
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
        { label:"MPS", data:' . json_encode(array_column($perfLevel, 'mps')) . ', backgroundColor:maroon, borderRadius:4, maxBarThickness:28 }
      ]
    },
    options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:false,min:60,grid:{color:chartGridColor()}},x:{grid:{display:false}}} }
  });
}

// Document Pie
new Chart(document.getElementById("docChart"), {
  type: "doughnut",
  data: {
    labels: ["Submitted","Reviewed","Pending","Returned"],
    datasets: [{ data:[' . implode(',', [
      $docSummary['Submitted'] ?? 0,
      $docSummary['Reviewed']  ?? 0,
      $docSummary['Pending']   ?? 0,
      $docSummary['Returned']  ?? 0,
    ]) . '], backgroundColor:[chartColor(1),chartColor(.75),chartColor(.5),chartColor(.3)], borderWidth:0 }]
  },
  options: { responsive:true, maintainAspectRatio:false, cutout:"64%", plugins:{legend:{display:false}} }
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
        backgroundColor: type === "line" ? chartColor(.1) : cfg.color,
        borderColor: cfg.color,
        borderRadius: type === "bar" ? 6 : undefined,
        maxBarThickness: type === "bar" ? 40 : undefined,
        fill: type === "line",
        tension: .3,
        borderWidth: type === "line" ? 2 : undefined,
        pointRadius: type === "line" ? 3 : undefined,
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

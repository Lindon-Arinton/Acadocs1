<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h4><i class="bi bi-people-fill me-2"></i>Enrollment KPIs</h4>
      <p>Student enrollment statistics by grade level</p>
    </div>
    <div class="d-flex gap-2 align-items-center" style="position:relative;z-index:1;">
      <label class="text-white small fw-semibold" for="year-filter">School Year:</label>
      <select id="year-filter" class="form-select form-select-sm" style="width:auto;">
        <?php foreach ($years as $y): ?>
          <option value="<?= e($y) ?>" <?= $y === $year ? 'selected' : '' ?>><?= e(str_replace('-', '–', $y)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>

<!-- Clickable KPI cards: click one to highlight its chart below -->
<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="kpi-card kpi-card-clickable active" data-metric="enrollees" onclick="selectKpiMetric('enrollees')">
      <i class="bi bi-people-fill fs-2 mb-2" style="color:#800000"></i>
      <div class="fw-bold" id="kpiCardValue-enrollees" style="font-size:1.6rem;color:#800000">—</div>
      <div class="text-muted small mt-1">Enrollees (Gross Enrolment Rate)</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="kpi-card kpi-card-clickable" data-metric="dropout" onclick="selectKpiMetric('dropout')">
      <i class="bi bi-exclamation-triangle fs-2 mb-2" style="color:#a52a2a"></i>
      <div class="fw-bold" id="kpiCardValue-dropout" style="font-size:1.6rem;color:#a52a2a">—</div>
      <div class="text-muted small mt-1">Drop-Out Rate</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="kpi-card kpi-card-clickable" data-metric="mps" onclick="selectKpiMetric('mps')">
      <i class="bi bi-graph-up fs-2 mb-2" style="color:#560000"></i>
      <div class="fw-bold" id="kpiCardValue-mps" style="font-size:1.6rem;color:#560000">—</div>
      <div class="text-muted small mt-1">Average MPS</div>
    </div>
  </div>
</div>

<!-- 3 charts side by side (bar / line / pie), all showing whichever metric was clicked above -->
<div class="row g-3 mb-4">
  <div class="col-lg-4">
    <div class="card chart-card-sm h-100">
      <div class="card-header bg-white py-2 fw-semibold small"><i class="bi bi-bar-chart me-2 text-muted"></i>Bar — <span id="kpiChartLabel-bar">Enrollees</span></div>
      <div class="card-body">
        <canvas id="kpiChartBar" height="170"></canvas>
        <p id="kpiChartBarEmpty" class="text-muted text-center py-4 mb-0 d-none small">
          <i class="bi bi-bar-chart fs-4 d-block mb-2"></i><span id="kpiChartBarEmptyText">No data available.</span>
        </p>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card chart-card-sm h-100">
      <div class="card-header bg-white py-2 fw-semibold small"><i class="bi bi-graph-up me-2 text-muted"></i>Line — <span id="kpiChartLabel-line">Enrollees</span></div>
      <div class="card-body">
        <canvas id="kpiChartLine" height="170"></canvas>
        <p id="kpiChartLineEmpty" class="text-muted text-center py-4 mb-0 d-none small">
          <i class="bi bi-graph-up fs-4 d-block mb-2"></i><span id="kpiChartLineEmptyText">No data available.</span>
        </p>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card chart-card-sm h-100">
      <div class="card-header bg-white py-2 fw-semibold small"><i class="bi bi-pie-chart me-2 text-muted"></i>Pie — <span id="kpiChartLabel-pie">Enrollees</span></div>
      <div class="card-body">
        <canvas id="kpiChartPie" height="170"></canvas>
        <p id="kpiChartPieEmpty" class="text-muted text-center py-4 mb-0 d-none small">
          <i class="bi bi-pie-chart fs-4 d-block mb-2"></i><span id="kpiChartPieEmptyText">No data available.</span>
        </p>
      </div>
    </div>
  </div>
</div>

<div id="enrollment-content">
  <?php include APPPATH . 'Views/pages/admin/enrollment_kpis_content.php'; ?>
</div>

<?php if (! empty($depedKpis)): ?>
<div class="card mt-4">
  <div class="card-header bg-white py-3 fw-semibold">
    <i class="bi bi-clipboard-data me-2 text-muted"></i>DepEd Key Performance Indicators (Historical)
  </div>
  <div class="card-body p-0">
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
            <th class="text-end">Enrolment (M / F)</th>
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
            <td class="text-end text-muted">
              <?= $k['enrolment_total'] !== null
                    ? number_format((int) $k['enrolment_total']) . ' (' . (int) $k['enrolment_male'] . ' / ' . (int) $k['enrolment_female'] . ')'
                    : '—' ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer bg-white text-muted small">
    Source: Matabungkay National High School's official DepEd KPI reports (Guidance Office).
  </div>
</div>
<?php endif; ?>

<?php
$extraScript = '<script>
const ENROLLMENT_URL = ' . json_encode(current_url()) . ';
const DEPED_KPI_DATA = ' . json_encode($depedKpis) . ';
let enrollChart = null;
let kpiChartBar = null;
let kpiChartLine = null;
let kpiChartPie = null;

const KPI_METRIC_CONFIG = {
  enrollees: { name: "Enrollees",  label: "Gross Enrolment Rate", field: "gross_enrolment_rate", color: "#800000" },
  dropout:   { name: "Drop-Out",   label: "Drop-Out Rate",        field: "dropout_rate",        color: "#a52a2a" },
  mps:       { name: "Average MPS", label: "Average MPS",          field: null,                  color: "#560000" },
};

function findDepedKpiForYear(year) {
  return DEPED_KPI_DATA.find(r => r.school_year === year) || null;
}

function formatKpiValue(row, field) {
  if (!row || row[field] === null || row[field] === undefined) return "No data";
  return parseFloat(row[field]).toFixed(2) + "%";
}

function renderKpiCards(year) {
  const row = findDepedKpiForYear(year);
  document.getElementById("kpiCardValue-enrollees").textContent = formatKpiValue(row, "gross_enrolment_rate");
  document.getElementById("kpiCardValue-dropout").textContent = formatKpiValue(row, "dropout_rate");
  document.getElementById("kpiCardValue-mps").textContent = "No data";
}

const YEAR_PALETTE = ["#800000", "#a52a2a", "#c2410c", "#560000"];

function buildOneKpiChart(kind, canvasId, emptyId, emptyTextId, cfg, years, values) {
  const canvas = document.getElementById(canvasId);
  const empty  = document.getElementById(emptyId);
  const hasAny = cfg.field && values.some(v => v !== null);

  if (!hasAny) {
    canvas.classList.add("d-none");
    empty.classList.remove("d-none");
    document.getElementById(emptyTextId).textContent = "No " + cfg.label + " data available for these school years.";
    return null;
  }

  canvas.classList.remove("d-none");
  empty.classList.add("d-none");

  const type = kind === "bar" ? "bar" : (kind === "line" ? "line" : "pie");

  return new Chart(canvas, {
    type: type,
    data: {
      labels: years.map(r => r.school_year),
      datasets: [{
        label: cfg.label,
        data: values,
        backgroundColor: type === "pie" ? YEAR_PALETTE : (type === "line" ? cfg.color + "33" : cfg.color),
        borderColor: cfg.color,
        borderRadius: type === "bar" ? 8 : undefined,
        fill: type === "line",
        tension: .3,
        borderWidth: type === "pie" ? 0 : (type === "line" ? 2 : undefined),
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: type === "pie", position: "bottom", labels: { boxWidth: 10, font: { size: 10 } } } },
      scales: type === "pie" ? {} : { y: { beginAtZero: true, grid: { color: "#f0f0f0" } }, x: { grid: { display: false } } },
    },
  });
}

function renderKpiChart(metric) {
  document.querySelectorAll(".kpi-card-clickable").forEach(el => el.classList.toggle("active", el.dataset.metric === metric));

  const cfg = KPI_METRIC_CONFIG[metric];
  document.getElementById("kpiChartLabel-bar").textContent = cfg.name;
  document.getElementById("kpiChartLabel-line").textContent = cfg.name;
  document.getElementById("kpiChartLabel-pie").textContent = cfg.name;

  if (kpiChartBar)  { kpiChartBar.destroy();  kpiChartBar = null; }
  if (kpiChartLine) { kpiChartLine.destroy(); kpiChartLine = null; }
  if (kpiChartPie)  { kpiChartPie.destroy();  kpiChartPie = null; }

  const years  = [...DEPED_KPI_DATA].sort((a, b) => a.school_year.localeCompare(b.school_year));
  const values = cfg.field ? years.map(r => (r[cfg.field] !== null ? parseFloat(r[cfg.field]) : null)) : [];

  kpiChartBar  = buildOneKpiChart("bar",  "kpiChartBar",  "kpiChartBarEmpty",  "kpiChartBarEmptyText",  cfg, years, values);
  kpiChartLine = buildOneKpiChart("line", "kpiChartLine", "kpiChartLineEmpty", "kpiChartLineEmptyText", cfg, years, values);
  kpiChartPie  = buildOneKpiChart("pie",  "kpiChartPie",  "kpiChartPieEmpty",  "kpiChartPieEmptyText",  cfg, years, values);
}

function selectKpiMetric(metric) {
  renderKpiChart(metric);
}

function renderEnrollmentChart(enrollment) {
  if (enrollChart) { enrollChart.destroy(); enrollChart = null; }
  const enrollCanvas = document.getElementById("enrollChart");
  if (!enrollCanvas) return;

  enrollChart = new Chart(enrollCanvas, {
    type: "bar",
    data: {
      labels: enrollment.map(e => e.grade_level),
      datasets: [{
        label: "Students",
        data: enrollment.map(e => e.students),
        backgroundColor: ["#800000","#a52a2a","#8b0000","#560000"],
        borderRadius: 10
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: false, min: 290, grid: { color: "#f0f0f0" } }, x: { grid: { display: false } } }
    }
  });
}

const yearFilter = document.getElementById("year-filter");
const contentBox = document.getElementById("enrollment-content");

yearFilter?.addEventListener("change", () => {
  const year = yearFilter.value;
  contentBox.style.opacity = "0.5";

  renderKpiCards(year);

  fetch(ENROLLMENT_URL + "?year=" + encodeURIComponent(year), {
    headers: { "X-Requested-With": "XMLHttpRequest" },
  })
    .then(res => {
      if (!res.ok) throw new Error("HTTP " + res.status);
      return res.json();
    })
    .then(data => {
      contentBox.innerHTML = data.html;
      renderEnrollmentChart(data.enrollment);
      history.replaceState(null, "", "?year=" + encodeURIComponent(year));
    })
    .catch(err => {
      console.error("Enrollment year switch failed:", err);
      showToast("Could not load enrollment data for that school year.", "danger");
    })
    .finally(() => {
      contentBox.style.opacity = "1";
    });
});

try {
  renderEnrollmentChart(' . json_encode($enrollment) . ');
} catch (err) {
  console.error("Initial chart render failed:", err);
}

renderKpiCards(' . json_encode($year) . ');
selectKpiMetric("enrollees");
</script>';
include APPPATH . 'Views/layout/footer.php';
?>

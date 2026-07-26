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

<!-- Clickable KPI cards: click one to change which trend the chart below shows -->
<div class="row g-3 mb-4">
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

<div class="card mb-4">
  <div class="card-header bg-white py-3 fw-semibold" id="kpiChartTitle">
    <i class="bi bi-bar-chart me-2 text-muted"></i>Enrollees — Gross Enrolment Rate by Year
  </div>
  <div class="card-body">
    <canvas id="kpiTrendChart" height="220"></canvas>
    <p id="kpiChartEmpty" class="text-muted text-center py-4 mb-0 d-none">
      <i class="bi bi-bar-chart fs-4 d-block mb-2"></i><span id="kpiChartEmptyText">No data available for the selected metric.</span>
    </p>
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
let kpiTrendChart = null;
let activeKpiMetric = "enrollees";

const KPI_METRIC_CONFIG = {
  enrollees: { label: "Gross Enrolment Rate", field: "gross_enrolment_rate", color: "#800000", title: "Enrollees — Gross Enrolment Rate by Year" },
  dropout:   { label: "Drop-Out Rate",        field: "dropout_rate",        color: "#a52a2a", title: "Drop-Out Rate by Year" },
  mps:       { label: "Average MPS",          field: null,                  color: "#560000", title: "Average MPS by Year" },
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

function renderKpiChart(metric) {
  activeKpiMetric = metric;
  document.querySelectorAll(".kpi-card-clickable").forEach(el => el.classList.toggle("active", el.dataset.metric === metric));

  const cfg = KPI_METRIC_CONFIG[metric];
  document.getElementById("kpiChartTitle").innerHTML = "<i class=\"bi bi-bar-chart me-2 text-muted\"></i>" + cfg.title;

  const canvas = document.getElementById("kpiTrendChart");
  const empty  = document.getElementById("kpiChartEmpty");

  if (kpiTrendChart) { kpiTrendChart.destroy(); kpiTrendChart = null; }

  const years  = [...DEPED_KPI_DATA].sort((a, b) => a.school_year.localeCompare(b.school_year));
  const values = cfg.field ? years.map(r => (r[cfg.field] !== null ? parseFloat(r[cfg.field]) : null)) : [];
  const hasAny = cfg.field && values.some(v => v !== null);

  if (!hasAny) {
    canvas.classList.add("d-none");
    empty.classList.remove("d-none");
    document.getElementById("kpiChartEmptyText").textContent = "No " + cfg.label + " data available for these school years.";
    return;
  }

  canvas.classList.remove("d-none");
  empty.classList.add("d-none");

  kpiTrendChart = new Chart(canvas, {
    type: "bar",
    data: {
      labels: years.map(r => r.school_year),
      datasets: [{ label: cfg.label, data: values, backgroundColor: cfg.color, borderRadius: 8 }],
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, grid: { color: "#f0f0f0" } }, x: { grid: { display: false } } },
    },
  });
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
renderKpiChart("enrollees");
</script>';
include APPPATH . 'Views/layout/footer.php';
?>

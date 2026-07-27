<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h4><i class="bi bi-people-fill me-2"></i>Enrollment KPIs</h4>
      <p>Student enrollment statistics by grade level</p>
    </div>
    <div class="d-flex gap-2 align-items-center" style="position:relative;z-index:1;">
      <?php if (hasRole('admin')): ?>
      <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#importKpiModal">
        <i class="bi bi-upload me-1"></i>Import KPI Report (Word)
      </button>
      <?php endif; ?>
      <label class="text-white small fw-semibold" for="year-filter">School Year:</label>
      <div class="maroon-select maroon-select-sm" style="width:auto;">
        <select id="year-filter" class="maroon-select-native">
          <?php foreach ($years as $y): ?>
            <option value="<?= e($y) ?>" <?= $y === $year ? 'selected' : '' ?>><?= e(str_replace('-', '–', $y)) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
        <div class="maroon-select-panel"></div>
      </div>
    </div>
  </div>
</div>

<!-- Clickable KPI cards: click one to highlight its chart below -->
<div class="row g-2 mb-3">
  <div class="col-md-4">
    <div class="kpi-card kpi-card-clickable kpi-card-compact active d-flex align-items-center gap-2" data-metric="enrollees" onclick="selectKpiMetric('enrollees')">
      <i class="bi bi-people-fill fs-5" style="color:#800000"></i>
      <div>
        <div class="fw-bold" id="kpiCardValue-enrollees" style="font-size:1.1rem;color:var(--text);line-height:1.1;">—</div>
        <div class="text-muted" style="font-size:.72rem;">Total Enrollees (all grade levels)</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="kpi-card kpi-card-clickable kpi-card-compact d-flex align-items-center gap-2" data-metric="dropout" onclick="selectKpiMetric('dropout')">
      <i class="bi bi-exclamation-triangle fs-5" style="color:#a52a2a"></i>
      <div>
        <div class="fw-bold" id="kpiCardValue-dropout" style="font-size:1.1rem;color:var(--text);line-height:1.1;">—</div>
        <div class="text-muted" style="font-size:.72rem;">Drop-Out Rate <span id="kpiCardYearNote-dropout" class="fst-italic"></span></div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="kpi-card kpi-card-clickable kpi-card-compact d-flex align-items-center gap-2" data-metric="mps" onclick="selectKpiMetric('mps')">
      <i class="bi bi-graph-up fs-5" style="color:#560000"></i>
      <div>
        <div class="fw-bold" id="kpiCardValue-mps" style="font-size:1.1rem;color:var(--text);line-height:1.1;">
          <?= $mpsOverallAvg !== null ? number_format($mpsOverallAvg, 2) . '%' : 'No data' ?>
        </div>
        <div class="text-muted" style="font-size:.72rem;">Average MPS (SY <?= e($mpsSourceYear) ?>)</div>
      </div>
    </div>
  </div>
</div>

<!-- 3 charts side by side (bar / line / pie), all showing whichever metric was clicked above -->
<div class="row g-3 mb-4">
  <div class="col-lg-4">
    <div class="card chart-card-sm h-100">
      <div class="card-header bg-white py-2 fw-semibold small"><i class="bi bi-bar-chart me-2 text-muted"></i>Bar — <span id="kpiChartLabel-bar">Enrollees</span></div>
      <div class="card-body">
        <canvas id="kpiChartBar" height="230"></canvas>
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
        <canvas id="kpiChartLine" height="230"></canvas>
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
        <canvas id="kpiChartPie" height="230"></canvas>
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
  <div class="card-footer bg-white text-muted small">
    Source: Matabungkay National High School's official DepEd KPI reports (Guidance Office).
  </div>
</div>
<?php endif; ?>

<?php if (hasRole('admin')): ?>
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
            <a href="<?= base_url('enrollment-kpis/template') ?>">Download the template</a>.
          </p>
          <div class="mb-3">
            <label class="form-label">School Year</label>
            <input type="text" name="school_year" class="form-control form-control-sm" list="kpiYearOptions"
                   value="<?= e($year) ?>" placeholder="e.g. 2025-2026" pattern="\d{4}-\d{4}" required>
            <datalist id="kpiYearOptions">
              <?php foreach ($years as $y): ?>
              <option value="<?= e($y) ?>"></option>
              <?php endforeach; ?>
            </datalist>
            <div class="form-text">Type a new school year (YYYY-YYYY) or pick an existing one.</div>
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
const ENROLLMENT_URL = ' . json_encode(current_url()) . ';
const DEPED_KPI_DATA = ' . json_encode($depedKpis) . ';
const ENROLLMENT_TOTALS_DATA = ' . json_encode($enrollmentTotals) . ';
const MPS_TREND_DATA = ' . json_encode($mpsTrend) . ';
const MPS_OVERALL_AVG = ' . json_encode($mpsOverallAvg) . ';
const MPS_SOURCE_YEAR = ' . json_encode($mpsSourceYear) . ';
let kpiChartBar = null;
let kpiChartLine = null;
let kpiChartPie = null;

// "enrollees" is sourced from the actual per-grade-level headcounts (Enrolment per
// Grade Level table), not gross_enrolment_rate — that\'s a computed rate the KPI
// report often leaves blank, while the headcount table is what schools reliably fill in.
const KPI_METRIC_CONFIG = {
  enrollees: { name: "Enrollees",  label: "Total Enrollees",  field: "total",         unit: "",  color: "#800000", axisNoun: "school years" },
  dropout:   { name: "Drop-Out",   label: "Drop-Out Rate",    field: "dropout_rate",  unit: "%", color: "#a52a2a", axisNoun: "school years" },
  mps:       { name: "Average MPS", label: "Average MPS",     field: "avg_mps",       unit: "%", color: "#560000", axisNoun: "grading periods" },
};

function findDepedKpiForYear(year) {
  return DEPED_KPI_DATA.find(r => r.school_year === year) || null;
}

// Not every year in the School Year filter has a DepEd KPI report imported yet
// (the filter also includes years that only have enrollment headcounts) — the
// bar/line/pie charts below already plot every year that DOES have KPI data,
// which is why they show bars even when the selected year has none. Falling
// back to the latest reported year, with a note, keeps the card from reading
// "No data" while the charts clearly are not empty.
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
  // Average MPS is not tied to the year filter above — it comes from Performance
  // Analytics data for the only year with real scores.
  document.getElementById("kpiCardValue-mps").textContent = MPS_OVERALL_AVG !== null ? MPS_OVERALL_AVG.toFixed(2) + "%" : "No data";
}

const YEAR_PALETTE = ["#800000", "#a52a2a", "#c2410c", "#560000"];

function buildOneKpiChart(kind, canvasId, emptyId, emptyTextId, cfg, labels, values) {
  const canvas = document.getElementById(canvasId);
  const empty  = document.getElementById(emptyId);
  const hasAny = values.length > 0 && values.some(v => v !== null);

  if (!hasAny) {
    canvas.classList.add("d-none");
    empty.classList.remove("d-none");
    document.getElementById(emptyTextId).textContent = "No " + cfg.label + " data available for these " + cfg.axisNoun + ".";
    return null;
  }

  canvas.classList.remove("d-none");
  empty.classList.add("d-none");

  const type = kind === "bar" ? "bar" : (kind === "line" ? "line" : "pie");

  return new Chart(canvas, {
    type: type,
    data: {
      labels: labels,
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
      scales: type === "pie" ? {} : { y: { beginAtZero: true, grid: { color: chartGridColor() } }, x: { grid: { display: false } } },
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

  let labels, values;
  if (metric === "mps") {
    const terms = [...MPS_TREND_DATA].sort((a, b) => a.term - b.term);
    labels = terms.map(t => "Term " + t.term + " (SY " + MPS_SOURCE_YEAR + ")");
    values = terms.map(t => t.avg_mps);
  } else if (metric === "enrollees") {
    const years = [...ENROLLMENT_TOTALS_DATA].sort((a, b) => a.school_year.localeCompare(b.school_year));
    labels = years.map(r => r.school_year);
    values = years.map(r => r.total);
  } else {
    const years = [...DEPED_KPI_DATA].sort((a, b) => a.school_year.localeCompare(b.school_year));
    labels = years.map(r => r.school_year);
    values = years.map(r => (r[cfg.field] !== null ? parseFloat(r[cfg.field]) : null));
  }

  kpiChartBar  = buildOneKpiChart("bar",  "kpiChartBar",  "kpiChartBarEmpty",  "kpiChartBarEmptyText",  cfg, labels, values);
  kpiChartLine = buildOneKpiChart("line", "kpiChartLine", "kpiChartLineEmpty", "kpiChartLineEmptyText", cfg, labels, values);
  kpiChartPie  = buildOneKpiChart("pie",  "kpiChartPie",  "kpiChartPieEmpty",  "kpiChartPieEmptyText",  cfg, labels, values);
}

function selectKpiMetric(metric) {
  renderKpiChart(metric);
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

renderKpiCards(' . json_encode($year) . ');
selectKpiMetric("enrollees");
</script>';
include APPPATH . 'Views/layout/footer.php';
?>

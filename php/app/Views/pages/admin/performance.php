<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h4><i class="bi bi-graph-up-arrow me-2"></i>Performance Analytics</h4>
      <p>MPS & NDS breakdown by grade level and subject area</p>
    </div>
    <div class="d-flex gap-2 align-items-center" style="position:relative;z-index:1;">
      <label class="text-white small fw-semibold" for="year-filter">School Year:</label>
      <select id="year-filter" class="form-select form-select-sm" style="width:auto;">
        <?php foreach ($years as $y): ?>
          <option value="<?= e($y) ?>" <?= $y === $year ? 'selected' : '' ?>><?= e(str_replace('-', '–', $y)) ?></option>
        <?php endforeach; ?>
      </select>
      <label class="text-white small fw-semibold" for="term-filter">Term:</label>
      <select id="term-filter" class="form-select form-select-sm" style="width:auto;">
        <?php foreach ($termOptions as $t): ?>
          <option value="<?= (int) $t ?>" <?= $t === $term ? 'selected' : '' ?>>Term <?= (int) $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>

<div id="performance-content">
  <?php include APPPATH . 'Views/pages/admin/performance_content.php'; ?>
</div>

<?php
$extraScript = '<script>
const PERFORMANCE_URL = ' . json_encode(current_url()) . ';
let levelChart = null;
let subjectChart = null;
let currentBySubject = [];
// Persists across term/year reloads (which re-render #performance-content from scratch)
// so switching terms keeps whatever grade the user had selected instead of resetting to "all".
let currentGrade = "all";

function buildSubjectChart(rows, grade) {
  if (subjectChart) { subjectChart.destroy(); subjectChart = null; }
  const subjectCanvas = document.getElementById("subjectChart");
  if (!subjectCanvas) return;

  let sLabels, sMPS;
  if (grade === "all") {
    // Combine each subject\'s scores across all grade levels into one bar
    // instead of listing every subject once per grade.
    const bySubjectName = {};
    rows.forEach(s => { (bySubjectName[s.subject] = bySubjectName[s.subject] || []).push(parseFloat(s.mps)); });
    sLabels = Object.keys(bySubjectName);
    sMPS = sLabels.map(name => {
      const vals = bySubjectName[name];
      return Math.round((vals.reduce((a, b) => a + b, 0) / vals.length) * 100) / 100;
    });
  } else {
    sLabels = rows.map(s => s.subject);
    sMPS = rows.map(s => parseFloat(s.mps));
  }

  subjectChart = new Chart(subjectCanvas, {
    type: "bar",
    data: { labels: sLabels, datasets: [{ label: "MPS", data: sMPS, backgroundColor: "#800000", borderRadius: 4 }] },
    options: {
      indexAxis: "y",
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { x: { min: 60, max: 100, grid: { color: "#f0f0f0" } }, y: { grid: { display: false }, ticks: { font: { size: 11 } } } }
    }
  });
}

function applyGradeFilter() {
  const gradeFilter = document.getElementById("gradeFilter");
  currentGrade = gradeFilter ? gradeFilter.value : "all";

  const rows = currentGrade === "all" ? currentBySubject : currentBySubject.filter(s => s.grade_level === currentGrade);
  buildSubjectChart(rows, currentGrade);

  let anyVisible = false;
  document.querySelectorAll("#performance-content tr[data-grade]").forEach(row => {
    const visible = currentGrade === "all" || row.dataset.grade === currentGrade;
    row.classList.toggle("d-none", !visible);
    if (visible) anyVisible = true;
  });
  document.getElementById("gradeFilterEmpty")?.classList.toggle("d-none", anyVisible || currentBySubject.length === 0);
}

function initGradeFilter() {
  const gradeFilter = document.getElementById("gradeFilter");
  if (!gradeFilter) return;
  gradeFilter.value = currentGrade;
  gradeFilter.addEventListener("change", applyGradeFilter);
  applyGradeFilter();
}

function renderPerformanceCharts(byLevel, bySubject) {
  if (levelChart) { levelChart.destroy(); levelChart = null; }
  const levelCanvas = document.getElementById("levelChart");
  if (levelCanvas) {
    const labels = byLevel.map(r => r.grade_level);
    const mps    = byLevel.map(r => r.mps);
    const nds    = byLevel.map(r => r.nds);

    levelChart = new Chart(levelCanvas, {
      type: "bar",
      data: { labels, datasets: [
        { label: "MPS", data: mps, backgroundColor: "#800000", borderRadius: 6 },
        { label: "NDS", data: nds, backgroundColor: "#a52a2a", borderRadius: 6 }
      ]},
      options: { responsive: true, scales: { y: { min: 60, grid: { color: "#f0f0f0" } }, x: { grid: { display: false } } } }
    });
  }

  currentBySubject = bySubject;
  initGradeFilter();
}

const yearFilter = document.getElementById("year-filter");
const termFilter = document.getElementById("term-filter");
const contentBox = document.getElementById("performance-content");

function reloadPerformance() {
  const year = yearFilter.value;
  const term = termFilter.value;
  contentBox.style.opacity = "0.5";

  fetch(PERFORMANCE_URL + "?year=" + encodeURIComponent(year) + "&term=" + encodeURIComponent(term), {
    headers: { "X-Requested-With": "XMLHttpRequest" },
  })
    .then(res => {
      if (!res.ok) throw new Error("HTTP " + res.status);
      return res.json();
    })
    .then(data => {
      contentBox.innerHTML = data.html;
      renderPerformanceCharts(data.byLevel, data.bySubject);
      history.replaceState(null, "", "?year=" + encodeURIComponent(year) + "&term=" + encodeURIComponent(term));
    })
    .catch(err => {
      console.error("Performance year/term switch failed:", err);
      showToast("Could not load performance data for that school year/term.", "danger");
    })
    .finally(() => {
      contentBox.style.opacity = "1";
    });
}

yearFilter?.addEventListener("change", reloadPerformance);
termFilter?.addEventListener("change", reloadPerformance);

try {
  renderPerformanceCharts(' . json_encode($byLevel) . ', ' . json_encode($bySubject) . ');
} catch (err) {
  console.error("Initial chart render failed:", err);
}
</script>';
include APPPATH . 'Views/layout/footer.php';
?>

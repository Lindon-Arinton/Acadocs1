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

  if (subjectChart) { subjectChart.destroy(); subjectChart = null; }
  const subjectCanvas = document.getElementById("subjectChart");
  if (subjectCanvas) {
    const sLabels = bySubject.map(s => s.subject + " (" + s.grade_level + ")");
    const sMPS    = bySubject.map(s => s.mps);

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
}

const yearFilter = document.getElementById("year-filter");
const contentBox = document.getElementById("performance-content");

yearFilter?.addEventListener("change", () => {
  const year = yearFilter.value;
  contentBox.style.opacity = "0.5";

  fetch(PERFORMANCE_URL + "?year=" + encodeURIComponent(year), {
    headers: { "X-Requested-With": "XMLHttpRequest" },
  })
    .then(res => {
      if (!res.ok) throw new Error("HTTP " + res.status);
      return res.json();
    })
    .then(data => {
      contentBox.innerHTML = data.html;
      renderPerformanceCharts(data.byLevel, data.bySubject);
      history.replaceState(null, "", "?year=" + encodeURIComponent(year));
    })
    .catch(err => {
      console.error("Performance year switch failed:", err);
      showToast("Could not load performance data for that school year.", "danger");
    })
    .finally(() => {
      contentBox.style.opacity = "1";
    });
});

try {
  renderPerformanceCharts(' . json_encode($byLevel) . ', ' . json_encode($bySubject) . ');
} catch (err) {
  console.error("Initial chart render failed:", err);
}
</script>';
include APPPATH . 'Views/layout/footer.php';
?>

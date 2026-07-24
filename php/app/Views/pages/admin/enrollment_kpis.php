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

<div id="enrollment-content">
  <?php include APPPATH . 'Views/pages/admin/enrollment_kpis_content.php'; ?>
</div>

<?php
$extraScript = '<script>
const ENROLLMENT_URL = ' . json_encode(current_url()) . ';
let enrollChart = null;

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
</script>';
include APPPATH . 'Views/layout/footer.php';
?>

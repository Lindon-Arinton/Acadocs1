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

<?php if (hasRole('admin')): ?>
<!-- Import KPI Report Modal -->
<div class="modal fade" id="importKpiModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-upload me-2"></i>Import KPI Report</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('enrollment-kpis/import') ?>" class="ajax-form" enctype="multipart/form-data" data-confirm-title="Import this file?" data-confirm-text="Existing KPI indicators for the selected school year will be overwritten.">
        <div class="modal-body">
          <p class="text-muted" style="font-size:.82rem;">
            Upload the DepEd "Key Performance Indicator" Word report (the table with Gross/Net Enrolment Rate,
            Cohort Survival, Repetition, Promotion, Retention, Graduation, Completion, Transition, Drop Out Rate,
            and Enrolment). The document doesn't state its own school year, so choose it below.
          </p>
          <div class="mb-3">
            <label class="form-label">School Year</label>
            <select name="school_year" class="form-select form-select-sm" required>
              <?php foreach ($years as $y): ?>
              <option value="<?= e($y) ?>" <?= $y === $year ? 'selected' : '' ?>><?= e(str_replace('-', '–', $y)) ?></option>
              <?php endforeach; ?>
            </select>
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

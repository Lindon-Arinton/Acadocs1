<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h4><i class="bi bi-people-fill me-2"></i>Enrollment KPIs</h4>
      <p>Student enrollment statistics by grade level</p>
    </div>
    <form class="d-flex gap-2 align-items-center">
      <label class="text-white small fw-semibold">School Year:</label>
      <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
        <option value="2025-2026" <?= $year==='2025-2026'?'selected':'' ?>>2025–2026</option>
        <option value="2024-2025" <?= $year==='2024-2025'?'selected':'' ?>>2024–2025</option>
      </select>
    </form>
  </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
  <?php foreach ([
    ['Total Enrollment',     number_format($kpi['total_enrollment']??0),   'bi-people-fill',           '#800000'],
    ['Submission Compliance',($kpi['submission_compliance']??0).'%',        'bi-file-earmark-check',    '#560000'],
    ['Average MPS',          ($kpi['average_mps']??0).'%',                 'bi-graph-up',              '#a52a2a'],
    ['Dropout Count',        $kpi['dropout_count']??0,                     'bi-exclamation-triangle',  '#6b0000'],
    ['Parent Attendance',    ($kpi['parent_attendance']??0).'%',            'bi-people',                '#800000'],
  ] as [$l,$v,$i,$c]): ?>
  <div class="col-6 col-xl">
    <div class="kpi-card text-center">
      <i class="bi <?= $i ?> fs-2 mb-2" style="color:<?= $c ?>"></i>
      <div class="fw-bold" style="font-size:1.6rem;color:<?= $c ?>"><?= $v ?></div>
      <div class="text-muted small mt-1"><?= $l ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <!-- Bar Chart -->
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header bg-white py-3 fw-semibold">
        <i class="bi bi-bar-chart me-2 text-muted"></i>Students per Grade Level
      </div>
      <div class="card-body"><canvas id="enrollChart" height="250"></canvas></div>
    </div>
  </div>

  <!-- Breakdown Table -->
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header bg-white py-3 fw-semibold">
        <i class="bi bi-table me-2 text-muted"></i>Enrollment Breakdown
      </div>
      <div class="card-body p-0">
        <table class="table table-hover mb-0">
          <thead><tr><th>Grade Level</th><th class="text-center">Sections</th>
                      <th class="text-end">Students</th><th class="text-end">Share</th></tr></thead>
          <tbody>
            <?php foreach ($enrollment as $e): $pct = $total > 0 ? round($e['students']/$total*100,1) : 0; ?>
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
              <td class="text-end fw-bold"><?= number_format($total) ?></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>

<?php
$extraScript = '<script>
new Chart(document.getElementById("enrollChart"),{
  type:"bar",
  data:{
    labels:' . json_encode(array_column($enrollment,'grade_level')) . ',
    datasets:[{
      label:"Students",
      data:' . json_encode(array_column($enrollment,'students')) . ',
      backgroundColor:["#800000","#a52a2a","#8b0000","#560000"],
      borderRadius:10
    }]
  },
  options:{
    responsive:true,
    plugins:{legend:{display:false}},
    scales:{y:{beginAtZero:false,min:290,grid:{color:"#f0f0f0"}},x:{grid:{display:false}}}
  }
});
</script>';
include APPPATH . 'Views/layout/footer.php';
?>

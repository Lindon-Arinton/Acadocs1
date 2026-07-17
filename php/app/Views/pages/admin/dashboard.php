<?php include APPPATH . 'Views/layout/header.php'; ?>

<!-- Page Header -->
<div class="page-header">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <h4><i class="bi bi-speedometer2 me-2"></i>School-Wide Dashboard</h4>
      <p>Real-time overview of academic performance, enrollment metrics & key indicators</p>
    </div>
    <span class="badge rounded-pill px-3 py-2" style="background:rgba(255,255,255,.2);font-size:.8rem;">
      <i class="bi bi-circle-fill text-success me-1" style="font-size:.5rem;vertical-align:middle;"></i>Live Data
    </span>
  </div>
</div>

<!-- KPI Cards -->
<div class="row g-4 mb-4">
  <?php
  $kpis = [
    ['icon'=>'bi-people-fill','color'=>'#fff0f0','icolor'=>'#800000','label'=>'Total Enrollment','value'=>number_format($kpi['total_enrollment']??0),'sub'=>'+2.6% from last year'],
    ['icon'=>'bi-file-earmark-check-fill','color'=>'#fff0f0','icolor'=>'#800000','label'=>'Submission Compliance','value'=>($kpi['submission_compliance']??0).'%','sub'=>'Documents submitted on time'],
    ['icon'=>'bi-graph-up-arrow','color'=>'#fff0f0','icolor'=>'#560000','label'=>'Average MPS','value'=>($kpi['average_mps']??0).'%','sub'=>'School-wide performance'],
    ['icon'=>'bi-exclamation-triangle-fill','color'=>'#ffe0e0','icolor'=>'#800000','label'=>'Dropout Count','value'=>$kpi['dropout_count']??0,'sub'=>'Students this year'],
  ];
  foreach ($kpis as $k): ?>
  <div class="col-sm-6 col-xl-3">
    <div class="kpi-card">
      <div class="d-flex align-items-start justify-content-between mb-3">
        <div class="kpi-icon" style="background:<?= $k['color'] ?>">
          <i class="bi <?= $k['icon'] ?>" style="color:<?= $k['icolor'] ?>;font-size:1.3rem;"></i>
        </div>
        <span class="kpi-value" style="color:#1a1a1a"><?= $k['value'] ?></span>
      </div>
      <div class="fw-semibold text-muted small text-uppercase tracking-wide"><?= $k['label'] ?></div>
      <div class="text-muted mt-1" style="font-size:.75rem;"><?= $k['sub'] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Low Performance Alert -->
<?php if ($lowest && $lowest['mps'] < 80): ?>
<div class="alert d-flex align-items-start gap-3 mb-4 p-4 rounded-3 border-0"
     style="background:#fff5f5;border-left:4px solid #ef4444!important;border-left-width:4px;">
  <i class="bi bi-exclamation-circle-fill text-danger fs-4 mt-1 flex-shrink-0"></i>
  <div>
    <strong class="text-danger">Performance Alert — Action Required</strong>
    <p class="mb-0 mt-1 text-muted small">
      <strong class="text-danger"><?= e($lowest['subject']) ?></strong>
      (<?= e($lowest['grade_level']) ?>) has the lowest MPS at
      <strong class="text-danger"><?= $lowest['mps'] ?>%</strong>.
      Instructor: <strong><?= e($lowest['instructor']) ?></strong>.
      Consider targeted intervention and additional instructional support.
    </p>
  </div>
</div>
<?php endif; ?>

<!-- Charts Row -->
<div class="row g-4 mb-4">
  <!-- Enrollment Chart -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <span class="fw-semibold"><i class="bi bi-people me-2 text-muted"></i>Enrollment by Grade Level</span>
        <a href="<?= base_url('enrollment-kpis') ?>" class="btn btn-sm btn-outline-secondary">View All</a>
      </div>
      <div class="card-body">
        <canvas id="enrollChart" height="220"></canvas>
      </div>
    </div>
  </div>

  <!-- Performance Chart -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <span class="fw-semibold"><i class="bi bi-graph-up me-2 text-muted"></i>Academic Performance by Level</span>
        <a href="<?= base_url('performance') ?>" class="btn btn-sm btn-outline-secondary">View All</a>
      </div>
      <div class="card-body">
        <canvas id="perfChart" height="220"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Document Status + Recent Documents -->
<div class="row g-4 mb-4">
  <!-- Document Status -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-white py-3">
        <span class="fw-semibold"><i class="bi bi-pie-chart me-2 text-muted"></i>Document Status</span>
      </div>
      <div class="card-body d-flex flex-column align-items-center">
        <canvas id="docChart" height="200" style="max-width:200px;"></canvas>
        <div class="mt-3 w-100">
          <?php foreach (['Submitted'=>'primary','Reviewed'=>'success','Pending'=>'warning','Returned'=>'danger'] as $status=>$color): ?>
          <div class="d-flex justify-content-between small mb-1">
            <span><span class="badge bg-<?= $color ?> me-2">&nbsp;</span><?= $status ?></span>
            <strong><?= $docSummary[$status] ?? 0 ?></strong>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Documents -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-clock-history me-2 text-muted"></i>Recent Submissions</span>
        <a href="<?= base_url('documents') ?>" class="btn btn-sm btn-outline-secondary">View All</a>
      </div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush">
          <?php foreach ($recentDocs as $doc): ?>
          <li class="list-group-item border-0 py-2 px-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-semibold small"><?= e($doc['teacher_name']) ?></div>
                <div class="text-muted" style="font-size:.72rem;"><?= e($doc['type']) ?> · <?= e($doc['subject']) ?></div>
              </div>
              <span class="status-pill <?= 'status-'.strtolower($doc['status']) === 'status-submitted' ? 'badge-submitted' : (strtolower($doc['status']) === 'reviewed' ? 'badge-reviewed' : 'badge-pending') ?>">
                <?= e($doc['status']) ?>
              </span>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Performance by Subject Table -->
<div class="card">
  <div class="card-header py-3 text-white" style="background:linear-gradient(135deg,#9d174d,#800000,#c2410c);">
    <span class="fw-bold"><i class="bi bi-table me-2"></i>Performance by Learning Area</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Subject</th><th>Grade Level</th><th>Instructor</th>
            <th class="text-end">MPS</th><th class="text-center">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allPerf as $p):
            $badge = $p['mps'] >= 85 ? ['Excellent','badge-submitted'] : ($p['mps'] >= 75 ? ['Satisfactory','badge-reviewed'] : ['Needs Improvement','badge-returned']);
          ?>
          <tr>
            <td class="fw-semibold"><?= e($p['subject']) ?></td>
            <td class="text-muted"><?= e($p['grade_level']) ?></td>
            <td class="text-muted"><?= e($p['instructor']) ?></td>
            <td class="text-end"><span class="badge bg-light text-dark border fw-bold"><?= $p['mps'] ?>%</span></td>
            <td class="text-center"><span class="status-pill <?= $badge[1] ?>"><?= $badge[0] ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
$extraScript = '<script>
const maroon = "#800000", maroonLight = "#a52a2a", maroonDark = "#560000", crimson = "#dc143c";

// Enrollment Chart
new Chart(document.getElementById("enrollChart"), {
  type: "bar",
  data: {
    labels: ' . json_encode(array_column($enrollment, 'grade_level')) . ',
    datasets: [{
      label: "Students",
      data: ' . json_encode(array_column($enrollment, 'students')) . ',
      backgroundColor: maroon,
      borderRadius: 8,
    }]
  },
  options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,grid:{color:"#f0f0f0"}},x:{grid:{display:false}}} }
});

// Performance Chart
new Chart(document.getElementById("perfChart"), {
  type: "bar",
  data: {
    labels: ' . json_encode(array_column($perfLevel, 'grade_level')) . ',
    datasets: [
      { label:"MPS", data:' . json_encode(array_column($perfLevel, 'mps')) . ', backgroundColor:maroon, borderRadius:6 },
      { label:"NDS", data:' . json_encode(array_column($perfLevel, 'nds')) . ', backgroundColor:maroonLight, borderRadius:6 }
    ]
  },
  options: { responsive:true, scales:{y:{beginAtZero:false,min:60,grid:{color:"#f0f0f0"}},x:{grid:{display:false}}} }
});

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
    ]) . '], backgroundColor:["#60a5fa","#34d399","#fbbf24","#f87171"], borderWidth:0 }]
  },
  options: { responsive:true, cutout:"70%", plugins:{legend:{display:false}} }
});
</script>';
include APPPATH . 'Views/layout/footer.php';

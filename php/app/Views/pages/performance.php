<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h4><i class="bi bi-graph-up-arrow me-2"></i>Performance Analytics</h4>
      <p>MPS & NDS breakdown by grade level and subject area</p>
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

<!-- Summary KPI -->
<div class="row g-3 mb-4">
  <?php foreach ([
    ['Average MPS', ($kpi['average_mps']??0).'%', 'bi-graph-up', '#800000'],
    ['Total Enrollment', number_format($kpi['total_enrollment']??0), 'bi-people-fill', '#560000'],
    ['Dropout Count', $kpi['dropout_count']??0, 'bi-exclamation-triangle', '#a52a2a'],
    ['Submission Rate', ($kpi['submission_compliance']??0).'%', 'bi-file-earmark-check', '#6b0000'],
  ] as [$lbl,$val,$ico,$col]): ?>
  <div class="col-sm-6 col-xl-3">
    <div class="kpi-card text-center">
      <i class="bi <?= $ico ?> fs-2 mb-2" style="color:<?= $col ?>"></i>
      <div class="kpi-value mb-1" style="color:<?= $col ?>"><?= $val ?></div>
      <div class="text-muted small"><?= $lbl ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
  <!-- MPS by Level chart -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-white py-3 fw-semibold">
        <i class="bi bi-bar-chart me-2 text-muted"></i>MPS & NDS by Grade Level
      </div>
      <div class="card-body"><canvas id="levelChart" height="260"></canvas></div>
    </div>
  </div>

  <!-- MPS by Subject horizontal bar -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-white py-3 fw-semibold">
        <i class="bi bi-bar-chart-horizontal me-2 text-muted"></i>MPS by Subject
      </div>
      <div class="card-body"><canvas id="subjectChart" height="260"></canvas></div>
    </div>
  </div>
</div>

<!-- Detailed Table -->
<div class="card">
  <div class="card-header py-3 text-white fw-bold" style="background:linear-gradient(135deg,#9d174d,#800000,#c2410c);">
    <i class="bi bi-table me-2"></i>Detailed Performance by Learning Area
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>#</th><th>Subject</th><th>Grade</th><th>Instructor</th>
              <th class="text-end">MPS</th><th>MPS Bar</th><th class="text-center">Rating</th></tr>
        </thead>
        <tbody>
          <?php foreach ($bySubject as $i => $s):
            $rating = $s['mps'] >= 85 ? ['Excellent','success'] : ($s['mps'] >= 75 ? ['Satisfactory','primary'] : ['Needs Improvement','danger']);
          ?>
          <tr>
            <td class="text-muted small"><?= $i+1 ?></td>
            <td class="fw-semibold"><?= e($s['subject']) ?></td>
            <td><?= e($s['grade_level']) ?></td>
            <td class="text-muted"><?= e($s['instructor']) ?></td>
            <td class="text-end fw-bold"><?= $s['mps'] ?>%</td>
            <td style="width:160px">
              <div class="progress" style="height:8px;">
                <div class="progress-bar" style="width:<?= $s['mps'] ?>%;background:var(--maroon);"></div>
              </div>
            </td>
            <td class="text-center">
              <span class="badge bg-<?= $rating[1] ?>"><?= $rating[0] ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
$extraScript = '<script>
const labels = ' . json_encode(array_column($byLevel,'grade_level')) . ';
const mps    = ' . json_encode(array_column($byLevel,'mps')) . ';
const nds    = ' . json_encode(array_column($byLevel,'nds')) . ';

new Chart(document.getElementById("levelChart"),{
  type:"bar",
  data:{ labels, datasets:[
    {label:"MPS",data:mps,backgroundColor:"#800000",borderRadius:6},
    {label:"NDS",data:nds,backgroundColor:"#a52a2a",borderRadius:6}
  ]},
  options:{responsive:true,scales:{y:{min:60,grid:{color:"#f0f0f0"}},x:{grid:{display:false}}}}
});

const sLabels = ' . json_encode(array_map(fn($s)=>$s['subject'].' ('.$s['grade_level'].')',$bySubject)) . ';
const sMPS    = ' . json_encode(array_column($bySubject,'mps')) . ';

new Chart(document.getElementById("subjectChart"),{
  type:"bar",
  data:{ labels:sLabels, datasets:[{label:"MPS",data:sMPS,backgroundColor:"#800000",borderRadius:4}]},
  options:{
    indexAxis:"y",
    responsive:true,
    plugins:{legend:{display:false}},
    scales:{x:{min:60,max:100,grid:{color:"#f0f0f0"}},y:{grid:{display:false},ticks:{font:{size:11}}}}
  }
});
</script>';
include APPPATH . 'Views/layout/footer.php';
?>

<!-- Summary KPI -->
<div class="row g-3 mb-4">
  <?php foreach ([
    ['Average MPS', ($kpi['average_mps']??0).'%', 'bi-graph-up', '#800000'],
    ['Total Enrollment', number_format($kpi['total_enrollment']??0), 'bi-people-fill', '#560000'],
    ['Dropout Count', $kpi['dropout_count']??0, 'bi-exclamation-triangle', '#a52a2a'],
  ] as [$lbl,$val,$ico,$col]): ?>
  <div class="col-sm-6 col-xl-3">
    <div class="kpi-card text-center">
      <i class="bi <?= $ico ?> fs-2 mb-2" style="color:<?= $col ?>"></i>
      <div class="kpi-value mb-1" style="color:<?= $col ?>"><?= $val ?></div>
      <div class="text-muted small"><?= $lbl ?></div>
    </div>
  </div>
  <?php endforeach; ?>
  <div class="col-sm-6 col-xl-3">
    <div class="kpi-card text-center d-flex flex-column align-items-center justify-content-center">
      <i class="bi bi-funnel fs-2 mb-2" style="color:#6b0000"></i>
      <label for="gradeFilter" class="text-muted small mb-2">Filter by Grade</label>
      <div class="maroon-select maroon-select-sm" style="width:auto;">
        <select id="gradeFilter" class="maroon-select-native" <?= empty($bySubject) ? 'disabled' : '' ?>>
          <option value="all">All Levels</option>
          <?php foreach (['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'] as $g): ?>
          <option value="<?= e($g) ?>"><?= e($g) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
        <div class="maroon-select-panel"></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <!-- MPS by Level chart -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-white py-3 fw-semibold">
        <button type="button" class="btn section-toggle-btn <?= empty($byLevel) ? 'collapsed' : '' ?> d-flex align-items-center text-start p-0 border-0 bg-transparent w-100"
                data-bs-toggle="collapse" data-bs-target="#levelChartBody"
                aria-expanded="<?= empty($byLevel) ? 'false' : 'true' ?>" aria-controls="levelChartBody">
          <i class="bi bi-chevron-down section-toggle-arrow me-2 text-muted"></i>
          <i class="bi bi-bar-chart me-2 text-muted"></i>MPS & NDS by Grade Level
        </button>
      </div>
      <div class="collapse <?= empty($byLevel) ? '' : 'show' ?>" id="levelChartBody">
        <div class="card-body">
          <?php if (empty($byLevel)): ?>
          <p class="text-muted text-center py-4 mb-0"><i class="bi bi-bar-chart fs-4 d-block mb-2"></i>No grade-level data available for <?= e(str_replace('-', '–', $year)) ?>.</p>
          <?php else: ?>
          <canvas id="levelChart" height="260"></canvas>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- MPS by Subject horizontal bar -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-white py-3 fw-semibold">
        <button type="button" class="btn section-toggle-btn <?= empty($bySubject) ? 'collapsed' : '' ?> d-flex align-items-center text-start p-0 border-0 bg-transparent w-100"
                data-bs-toggle="collapse" data-bs-target="#subjectChartBody"
                aria-expanded="<?= empty($bySubject) ? 'false' : 'true' ?>" aria-controls="subjectChartBody">
          <i class="bi bi-chevron-down section-toggle-arrow me-2 text-muted"></i>
          <i class="bi bi-bar-chart-horizontal me-2 text-muted"></i>MPS by Subject
        </button>
      </div>
      <div class="collapse <?= empty($bySubject) ? '' : 'show' ?>" id="subjectChartBody">
        <div class="card-body">
          <?php if (empty($bySubject)): ?>
          <p class="text-muted text-center py-4 mb-0"><i class="bi bi-bar-chart-horizontal fs-4 d-block mb-2"></i>No subject data available for <?= e(str_replace('-', '–', $year)) ?>.</p>
          <?php else: ?>
          <canvas id="subjectChart" height="260"></canvas>
          <?php endif; ?>
        </div>
      </div>
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
          <?php if (empty($bySubject)): ?>
          <tr>
            <td colspan="7" class="text-center text-muted py-4">No performance data available for <?= e(str_replace('-', '–', $year)) ?>.</td>
          </tr>
          <?php endif; ?>
          <?php foreach ($bySubject as $i => $s):
            $rating = $s['mps'] >= 85 ? ['Excellent','success'] : ($s['mps'] >= 75 ? ['Satisfactory','primary'] : ['Needs Improvement','danger']);
          ?>
          <tr data-grade="<?= e($s['grade_level']) ?>">
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
          <tr id="gradeFilterEmpty" class="d-none">
            <td colspan="7" class="text-center text-muted py-4">No subjects for this grade.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

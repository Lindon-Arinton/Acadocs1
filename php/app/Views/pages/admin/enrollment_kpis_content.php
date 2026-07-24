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
        <button type="button" class="btn section-toggle-btn <?= empty($enrollment) ? 'collapsed' : '' ?> d-flex align-items-center text-start p-0 border-0 bg-transparent w-100"
                data-bs-toggle="collapse" data-bs-target="#enrollChartBody"
                aria-expanded="<?= empty($enrollment) ? 'false' : 'true' ?>" aria-controls="enrollChartBody">
          <i class="bi bi-chevron-down section-toggle-arrow me-2 text-muted"></i>
          <i class="bi bi-bar-chart me-2 text-muted"></i>Students per Grade Level
        </button>
      </div>
      <div class="collapse <?= empty($enrollment) ? '' : 'show' ?>" id="enrollChartBody">
        <div class="card-body">
          <?php if (empty($enrollment)): ?>
          <p class="text-muted text-center py-4 mb-0"><i class="bi bi-bar-chart fs-4 d-block mb-2"></i>No enrollment data available for <?= e(str_replace('-', '–', $year)) ?>.</p>
          <?php else: ?>
          <canvas id="enrollChart" height="250"></canvas>
          <?php endif; ?>
        </div>
      </div>
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
            <?php if (empty($enrollment)): ?>
            <tr>
              <td colspan="4" class="text-center text-muted py-4">No enrollment data available for <?= e(str_replace('-', '–', $year)) ?>.</td>
            </tr>
            <?php endif; ?>
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

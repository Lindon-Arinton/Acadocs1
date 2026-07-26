<div class="row g-4">
  <!-- Breakdown Table -->
  <div class="col-lg-12">
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

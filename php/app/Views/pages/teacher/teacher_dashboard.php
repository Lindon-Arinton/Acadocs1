<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <div>
    <h4><i class="bi bi-house-fill me-2"></i>Welcome back, <?= e($user['name']) ?></h4>
    <?php $subjects = $teacher ? implode(', ', json_decode($teacher['subjects'] ?? '[]', true) ?: []) : ''; ?>
    <p class="mb-0"><?= $teacher ? e($teacher['grade_level'] ?: 'Not set') . ($subjects !== '' ? ' · ' . e($subjects) : '') : 'Teacher Dashboard' ?></p>
  </div>
</div>

<?php
$completionRate = $taskStats['total'] > 0 ? round($taskStats['completed'] / $taskStats['total'] * 100, 1) : null;
$insightTone = [
  'danger'  => ['bg' => '#fee2e2', 'fg' => '#ef4444', 'tint' => 'rgba(239,68,68,.08)'],
  'warning' => ['bg' => '#fef9c3', 'fg' => '#b45309', 'tint' => 'rgba(180,83,9,.08)'],
  'success' => ['bg' => '#d1fae5', 'fg' => '#059669', 'tint' => 'rgba(5,150,105,.08)'],
  'info'    => ['bg' => '#fff0f0', 'fg' => '#800000', 'tint' => 'rgba(128,0,0,.06)'],
];
?>

<!-- Stat tile strip -->
<div class="stat-tile-row mb-3">
  <?php foreach ([
    ['total',     'Assigned Tasks', 'bi-list-task'],
    ['completed', 'Completed',      'bi-check-circle-fill'],
    ['pending',   'Pending',        'bi-hourglass-split'],
    ['overdue',   'Overdue',        'bi-exclamation-triangle-fill'],
  ] as [$key, $label, $icon]): ?>
  <a href="<?= base_url('submit-documents') ?>" class="stat-tile dashboard-card-link">
    <div class="stat-tile-top">
      <div class="stat-tile-icon" style="background:#fff0f0;color:#800000;"><i class="bi <?= $icon ?>"></i></div>
      <span class="stat-tile-name"><?= $label ?></span>
    </div>
    <div class="stat-tile-body">
      <div>
        <div class="stat-tile-value"><?= $taskStats[$key] ?></div>
        <div class="stat-tile-caption">Tasks</div>
      </div>
    </div>
  </a>
  <?php endforeach; ?>
</div>

<!-- Bento row: task progress + activity (left) / insights + side rail (right) -->
<div class="dashboard-grid mb-3">
  <div>
    <?php if ($completionRate !== null): ?>
    <a href="<?= base_url('submit-documents') ?>" class="dashboard-card-link">
    <div class="card mb-3">
      <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
          <span class="fw-semibold">Task Completion Rate</span>
          <strong style="color:var(--maroon)"><?= $completionRate ?>%</strong>
        </div>
        <div class="progress" style="height:12px;">
          <div class="progress-bar" style="width:<?= $completionRate ?>%;background:var(--maroon)!important;border-radius:8px;"></div>
        </div>
      </div>
    </div>
    </a>
    <?php endif; ?>

    <div class="card">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-clock-history me-2 text-muted"></i>Recent Task Activity</span>
        <a href="<?= base_url('submit-documents') ?>" class="btn btn-maroon btn-sm">
          <i class="bi bi-list-task me-1"></i>To Do List
        </a>
      </div>
      <div class="card-body p-0">
        <table class="table table-hover mb-0">
          <thead><tr><th>Task</th><th>Submitted</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($recentActivity as $a):
              $bm = ['Submitted' => 'badge-submitted', 'Reviewed' => 'badge-reviewed'];
            ?>
            <tr>
              <td><?= e($a['title']) ?></td>
              <td class="small text-muted"><?= date('M d, Y', strtotime($a['submitted_at'])) ?></td>
              <td><span class="status-pill <?= $bm[$a['status']] ?? '' ?>"><?= e($a['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentActivity)): ?>
            <tr><td colspan="3" class="text-center text-muted py-4">No submissions yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="side-rail-stack">
    <?php if (! empty($insights)): ?>
    <div class="card">
      <div class="card-header bg-white py-2">
        <span class="fw-semibold small"><i class="bi bi-lightbulb-fill me-2 text-muted"></i>Insights</span>
      </div>
      <div class="card-body card-body-tight d-flex flex-column gap-2">
        <?php foreach ($insights as $insight): $t = $insightTone[$insight['tone']]; ?>
        <div class="d-flex align-items-start gap-2 p-2 rounded-3" style="background:<?= $t['tint'] ?>;">
          <div class="stat-tile-icon" style="width:28px;height:28px;font-size:.8rem;background:<?= $t['bg'] ?>;color:<?= $t['fg'] ?>;flex-shrink:0;">
            <i class="bi <?= $insight['icon'] ?>"></i>
          </div>
          <p class="small mb-0" style="padding-top:.15rem;"><?= $insight['text'] ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Announcements: kept high in the side rail, right under Insights, since these are time-sensitive and shouldn't get buried. The badge counts only unread ones and clears once the Announcements page is visited. -->
    <a href="<?= base_url('announcements') ?>" class="dashboard-card-link">
    <div class="card">
      <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <span class="fw-semibold"><i class="bi bi-megaphone-fill me-2 text-muted"></i>Announcements</span>
        <?php if ($unreadAnnouncementsCount > 0): ?>
        <span class="badge rounded-pill" style="background:#fbbf24;color:#7c2d12;"><?= $unreadAnnouncementsCount ?></span>
        <?php endif; ?>
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($announcements as $a): ?>
        <li class="list-group-item py-2 px-3">
          <div class="fw-semibold small"><?= e($a['title']) ?></div>
          <div class="text-muted" style="font-size:.72rem;"><?= date('M d', strtotime($a['date'])) ?> · <?= e($a['type']) ?></div>
        </li>
        <?php endforeach; ?>
        <?php if (empty($announcements)): ?>
        <li class="list-group-item py-3 px-3 text-center text-muted small">No announcements yet.</li>
        <?php endif; ?>
      </ul>
    </div>
    </a>

    <?php if (! empty($docFeedback)): ?>
    <!-- Read-only: the document submission form is retired, but historical
         documents and any private principal feedback on them still need a
         home — this is what document_feedback notifications link to. -->
    <div class="card" id="document-feedback">
      <div class="card-header bg-white py-3 fw-semibold">
        <i class="bi bi-chat-square-text-fill me-2 text-muted"></i>Document Feedback
      </div>
      <div class="card-body card-body-tight d-flex flex-column gap-2">
        <?php foreach ($docFeedback as $doc): ?>
        <div class="p-2 rounded-3" style="background:rgba(128,0,0,.06);">
          <div class="fw-semibold small"><?= e($doc['type']) ?> — <?= e($doc['subject']) ?></div>
          <?php foreach (explode('|||', $doc['feedback_comments']) as $fb): ?>
          <p class="small text-muted mb-0 mt-1"><?= e($fb) ?></p>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($taskStats['total'] > 0): ?>
    <a href="<?= base_url('submit-documents') ?>" class="dashboard-card-link">
    <div class="card">
      <div class="card-header bg-white py-2">
        <span class="fw-semibold small"><i class="bi bi-pie-chart me-2 text-muted"></i>Task Status</span>
      </div>
      <div class="card-body card-body-tight d-flex align-items-center gap-3">
        <div class="donut-wrap" style="width:90px;height:90px;">
          <canvas id="taskStatusChart" height="90" width="90"></canvas>
          <div class="donut-center-label">
            <div class="donut-center-value"><?= $taskStats['total'] ?></div>
            <div class="donut-center-caption">Total</div>
          </div>
        </div>
        <div class="w-100">
          <?php foreach (['Completed' => ['completed', 'rgba(128,0,0,1)'], 'Pending' => ['pending', 'rgba(128,0,0,.5)'], 'Overdue' => ['overdue', 'rgba(220,38,38,1)']] as $label => [$key, $dotColor]): ?>
          <div class="d-flex justify-content-between small mb-1">
            <span><span class="d-inline-block rounded-circle me-1" style="width:9px;height:9px;background:<?= $dotColor ?>;"></span><?= $label ?></span>
            <strong><?= $taskStats[$key] ?></strong>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    </a>
    <?php endif; ?>

    <div class="card">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-calendar-check me-2 text-muted"></i>My Attendance</span>
        <?php if (! empty($myAttendanceMonths)): ?>
        <form method="GET" action="<?= base_url('teacher-dashboard') ?>">
          <div class="maroon-select maroon-select-sm" style="width:auto;">
            <select name="att_month" class="maroon-select-native" onchange="this.form.requestSubmit()">
              <option value="all" <?= $attMonth === 'all' ? 'selected' : '' ?>>All Time</option>
              <?php foreach ($myAttendanceMonths as $ym): ?>
              <option value="<?= e($ym) ?>" <?= $attMonth === $ym ? 'selected' : '' ?>><?= date('F Y', strtotime($ym . '-01')) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
            <div class="maroon-select-panel"></div>
          </div>
        </form>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <div class="row g-3 text-center">
          <div class="col-6">
            <div class="fw-bold fs-4 text-success"><?= $myAttendance['Present'] ?></div>
            <div class="text-muted small">Present</div>
          </div>
          <div class="col-6">
            <div class="fw-bold fs-4 text-danger"><?= $myAttendance['Absent'] ?></div>
            <div class="text-muted small">Absent</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header bg-white py-3 fw-semibold">
        <i class="bi bi-link-45deg me-2 text-muted"></i>Quick Links
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($links as $l): ?>
        <li class="list-group-item py-2 px-3">
          <a href="<?= e($l['url']) ?>" target="_blank" rel="noopener" class="text-decoration-none small fw-semibold d-flex align-items-center gap-2">
            <i class="bi bi-box-arrow-up-right text-muted"></i><?= e($l['title']) ?>
          </a>
        </li>
        <?php endforeach; ?>
        <?php if (empty($links)): ?>
        <li class="list-group-item py-3 px-3 text-center text-muted small">No links yet.</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>

<?php if ($taskStats['total'] > 0):
$extraScript = '<script>
new Chart(document.getElementById("taskStatusChart"), {
  type: "doughnut",
  data: {
    labels: ["Completed","Pending","Overdue"],
    datasets: [{ data:[' . implode(',', [$taskStats['completed'], $taskStats['pending'], $taskStats['overdue']]) . '], backgroundColor:["rgba(128,0,0,1)","rgba(128,0,0,.5)","rgba(220,38,38,1)"], borderWidth:0 }]
  },
  options: { responsive:true, maintainAspectRatio:false, cutout:"64%", plugins:{legend:{display:false}} }
});
</script>';
endif;
include APPPATH . 'Views/layout/footer.php';
?>

<?php include APPPATH . 'Views/layout/header.php'; ?>

<?php
// teacher['subjects'] is a list of structured rows (subject/grade_level/section,
// one per section — see TeacherModel::findWithSubjects()); collapse them into a
// short "SUBJECT (n sections)" summary for the one-line header.
$subjectCounts = [];
foreach ($teacher['subjects'] ?? [] as $subjectRow) {
    $label = trim((string) ($subjectRow['subject'] ?? ''));
    if ($label === '') {
        continue;
    }
    $subjectCounts[$label] = ($subjectCounts[$label] ?? 0) + 1;
}
$subjectParts = [];
foreach ($subjectCounts as $label => $count) {
    $subjectParts[] = $count > 1 ? "{$label} ({$count})" : $label;
}
$subjects  = implode(', ', $subjectParts);
$metaParts = [];
if ($teacher) {
    $metaParts[] = $teacher['grade_level'] ?: 'Not set';
    if (! empty($teacher['advisory'])) {
        $metaParts[] = 'Adviser, ' . $teacher['advisory'];
    }
    if ($subjects !== '') {
        $metaParts[] = $subjects;
    }
}

$completionRate = $taskStats['total'] > 0 ? round($taskStats['completed'] / $taskStats['total'] * 100) : null;
$insightTone    = ['danger' => 'is-danger', 'warning' => 'is-warning', 'success' => 'is-success', 'info' => 'is-info'];
$mpsTone        = static fn (?float $v) => mpsDescriptor($v)['class'] ?? ''; // DepEd descriptor color
// Task Status donut + legend: shades of the theme chart color (maroon in light mode, gold in dark).
$taskStatusAlpha = ['completed' => 1, 'pending' => .55, 'overdue' => .25];
?>

<!-- Welcome banner -->
<div class="td-hero">
  <span class="td-hero-icon"><i class="bi bi-house-fill"></i></span>
  <div class="td-hero-text">
    <h4>Welcome back, <?= e($user['name']) ?></h4>
    <p><?= $teacher ? e(implode(' · ', $metaParts)) : 'Teacher Dashboard' ?></p>
  </div>
  <a href="<?= base_url('time-records') ?>" class="td-hero-date" title="Open Time Records">
    <i class="bi bi-calendar3"></i>
    <span><small>Today is</small><?= date('M d, Y') ?></span>
    <i class="bi bi-chevron-right td-hero-date-arrow"></i>
  </a>
</div>

<div class="dashboard-grid td-grid">
  <div class="td-main">
    <!-- Task KPI tiles -->
    <div class="td-kpis">
      <?php foreach ([
        ['total',     'Assigned Tasks', 'bi-list-ul'],
        ['completed', 'Completed',      'bi-check-circle-fill'],
        ['pending',   'Pending',        'bi-hourglass-split'],
        ['overdue',   'Overdue',        'bi-exclamation-triangle-fill'],
      ] as [$key, $label, $icon]): ?>
      <a href="<?= base_url('submit-documents') ?>" class="td-kpi">
        <div class="td-kpi-top"><span class="td-icon"><i class="bi <?= $icon ?>"></i></span><?= $label ?></div>
        <div class="td-kpi-value"><?= $taskStats[$key] ?></div>
        <div class="td-kpi-caption">Task(s)</div>
        <i class="bi bi-chevron-right td-kpi-arrow"></i>
      </a>
      <?php endforeach; ?>
    </div>

    <?php if ($completionRate !== null): ?>
    <a href="<?= base_url('submit-documents') ?>" class="td-card td-progress-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="td-card-title"><i class="bi bi-check-circle-fill"></i>Tasks Completed</span>
        <strong class="td-accent"><?= $taskStats['completed'] ?> of <?= $taskStats['total'] ?></strong>
      </div>
      <div class="d-flex align-items-center gap-3">
        <div class="td-progress"><div style="width:<?= $completionRate ?>%"></div></div>
        <strong class="td-progress-pct"><?= $completionRate ?>%</strong>
      </div>
    </a>
    <?php endif; ?>

    <!-- Recent task activity -->
    <div class="td-card p-0">
      <div class="td-card-head">
        <span class="td-card-title"><i class="bi bi-clock-history"></i>Recent Task Activity</span>
        <a href="<?= base_url('submit-documents') ?>" class="btn btn-maroon btn-sm td-btn"><i class="bi bi-list-ul me-1"></i>To Do List</a>
      </div>
      <div class="table-responsive">
        <table class="table td-table mb-0">
          <thead><tr><th>Task</th><th>Submitted</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($recentActivity as $a): ?>
            <tr>
              <td class="fw-semibold"><?= e($a['title']) ?></td>
              <td class="text-muted"><?= date('M d, Y', strtotime($a['submitted_at'])) ?></td>
              <td><span class="status-pill <?= submissionBadge($a['status']) ?>"><?= e($a['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentActivity)): ?>
            <tr><td colspan="3" class="text-center text-muted py-4">No submissions yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- My MPS Performance: the teacher's own subjects & sections -->
    <div class="td-card p-0" id="my-mps">
      <div class="td-card-head flex-wrap gap-2">
        <span class="td-card-title"><i class="bi bi-graph-up-arrow"></i>My MPS Performance</span>
        <div class="d-flex align-items-center gap-2">
          <?php if ($mps['periods']): ?>
          <form method="GET" action="<?= base_url('teacher-dashboard') ?>">
            <?php if ($attMonth !== 'all'): ?><input type="hidden" name="att_month" value="<?= e($attMonth) ?>"><?php endif; ?>
            <div class="maroon-select maroon-select-sm" style="width:auto;">
              <select name="mps" class="maroon-select-native" onchange="this.form.requestSubmit()">
                <?php foreach ($mps['periods'] as $p): ?>
                <option value="<?= e($p['key']) ?>" <?= $p['key'] === $mps['period'] ? 'selected' : '' ?>><?= e($p['label']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
              <div class="maroon-select-panel"></div>
            </div>
          </form>
          <?php endif; ?>
          <a href="<?= base_url('performance/mps' . ($mps['year'] ? '?year=' . urlencode($mps['year']) . '&term=' . (int) $mps['term'] : '')) ?>" class="btn btn-maroon btn-sm td-btn">
            <i class="bi bi-pencil-square me-1"></i>Enter Scores
          </a>
        </div>
      </div>
      <?php if ($mps['scored'] === 0): ?>
      <div class="text-center text-muted py-4 px-3 small">
        <i class="bi bi-graph-up fs-3 d-block mb-2"></i>
        No MPS scores yet for your subjects<?= $mps['period'] ? ' in this term' : '' ?>. Enter them to see how each of your sections is doing.
      </div>
      <?php else: ?>
      <div class="td-mps-summary">
        <div>
          <div class="td-label">Overall Average</div>
          <span class="mps-avg <?= $mpsTone($mps['overall']) ?>"><?= number_format($mps['overall'], 2) ?>%</span>
          <div class="small text-muted mt-1"><?= e(mpsDescriptor($mps['overall'])['label']) ?></div>
        </div>
        <div>
          <div class="td-label">At Mastery (75%+)</div>
          <div class="fw-bold"><?= $mps['atMastery'] ?> <span class="text-muted fw-normal small">of <?= $mps['scored'] ?> classes</span></div>
        </div>
        <div class="min-w-0">
          <div class="td-label">Needs Most Help</div>
          <div class="small fw-semibold text-truncate" title="<?= e($mps['lowest']['subject'] . ' · ' . ($mps['lowest']['section'] ?? $mps['lowest']['grade'])) ?>">
            <?= e($mps['lowest']['subject']) ?> · <?= e($mps['lowest']['section'] ?? $mps['lowest']['grade']) ?>
          </div>
          <span class="mps-pill <?= $mpsTone($mps['lowest']['avg']) ?>"><?= number_format($mps['lowest']['avg'], 2) ?>%</span>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table td-table mb-0 align-middle">
          <thead>
            <tr><th>Subject</th><th>Grade · Section</th><th class="text-center">Sum. 1</th><th class="text-center">Sum. 2</th><th class="text-center">Exam</th><th class="text-center">Average</th><th>Descriptor</th></tr>
          </thead>
          <tbody>
            <?php foreach ($mps['rows'] as $r): ?>
            <tr>
              <td class="fw-semibold"><?= e($r['subject']) ?></td>
              <td class="text-muted"><?= e($r['grade']) ?><?= $r['section'] ? ' · ' . e($r['section']) : '' ?></td>
              <?php foreach (['s1', 's2', 'exam'] as $k): ?>
              <td class="text-center"><?= $r[$k] !== null ? number_format($r[$k], 2) : '' ?></td>
              <?php endforeach; ?>
              <td class="text-center">
                <?php if ($r['avg'] !== null): ?>
                <span class="mps-pill <?= $mpsTone($r['avg']) ?>"><?= number_format($r['avg'], 2) ?>%</span>
                <?php endif; ?>
              </td>
              <td class="small"><?= e(mpsDescriptor($r['avg'])['label'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="desc-legend px-3 py-2 border-top">
        <?php foreach (MPS_DESCRIPTORS as [$min, $label, $range]): ?>
        <span class="mps-pill desc-<?= strtolower($label) ?>"><?= $range ?> <?= $label ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Side rail -->
  <div class="td-rail">
    <?php $calendarCardClass = 'td-card'; include APPPATH . 'Views/partials/school_calendar.php'; ?>

    <?php if (! empty($insights)): ?>
    <div class="td-card">
      <div class="td-card-title mb-3"><i class="bi bi-lightbulb"></i>Insights</div>
      <div class="d-flex flex-column gap-2">
        <?php foreach ($insights as $insight): ?>
        <div class="td-insight <?= $insightTone[$insight['tone']] ?>">
          <i class="bi <?= $insight['icon'] ?>"></i>
          <p class="mb-0"><?= $insight['text'] ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Announcements: high in the rail since they're time-sensitive. The badge counts only unread ones and clears once the Announcements page is visited. -->
    <div class="td-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="td-card-title"><i class="bi bi-megaphone-fill"></i>Announcements
          <?php if ($unreadAnnouncementsCount > 0): ?><span class="badge rounded-pill ms-1" style="background:#fbbf24;color:#7c2d12;"><?= $unreadAnnouncementsCount ?></span><?php endif; ?>
        </span>
        <a href="<?= base_url('announcements') ?>" class="td-link">View All</a>
      </div>
      <div class="d-flex flex-column gap-2">
        <?php foreach (array_slice($announcements, 0, 3) as $a): ?>
        <a href="<?= base_url('announcements?id=' . (int) $a['id']) ?>" class="td-list-item">
          <i class="bi bi-calendar-event td-list-icon"></i>
          <span class="min-w-0 flex-grow-1">
            <span class="td-list-title"><?= e($a['title']) ?></span>
            <span class="td-list-meta"><?= date('M d', strtotime($a['date'])) ?> · <?= e($a['type']) ?></span>
          </span>
          <i class="bi bi-chevron-right text-muted"></i>
        </a>
        <?php endforeach; ?>
        <?php if (empty($announcements)): ?>
        <div class="text-center text-muted small py-2">No announcements yet.</div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (! empty($docFeedback)): ?>
    <!-- Read-only: the document submission form is retired, but historical
         documents and any private principal feedback on them still need a
         home — this is what document_feedback notifications link to. -->
    <div class="td-card" id="document-feedback">
      <div class="td-card-title mb-3"><i class="bi bi-chat-square-text-fill"></i>Document Feedback</div>
      <div class="d-flex flex-column gap-2">
        <?php foreach ($docFeedback as $doc): ?>
        <div class="td-list-item d-block">
          <div class="td-list-title"><?= e($doc['type']) ?> — <?= e($doc['subject']) ?></div>
          <?php foreach (explode('|||', $doc['feedback_comments']) as $fb): ?>
          <p class="small text-muted mb-0 mt-1"><?= e($fb) ?></p>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($taskStats['total'] > 0): ?>
    <a href="<?= base_url('submit-documents') ?>" class="td-card td-card-link">
      <div class="td-card-title mb-3"><i class="bi bi-pie-chart-fill"></i>Task Status</div>
      <div class="d-flex align-items-center gap-4">
        <div class="donut-wrap" style="width:120px;height:120px;">
          <canvas id="taskStatusChart" data-label-header="Status" data-source="Tasks assigned to you in Tasks &amp; Assignments, matched against your own uploads on the To Do List." height="120" width="120"></canvas>
          <div class="donut-center-label">
            <div class="donut-center-value" style="font-size:1.5rem;"><?= $taskStats['total'] ?></div>
            <div class="donut-center-caption">Total</div>
          </div>
        </div>
        <div class="flex-grow-1 d-flex flex-column gap-2">
          <?php foreach (['Completed' => 'completed', 'Pending' => 'pending', 'Overdue' => 'overdue'] as $label => $key): ?>
          <div class="d-flex justify-content-between small">
            <span><span class="d-inline-block rounded-circle me-2" style="width:10px;height:10px;background:rgba(var(--chart-rgb),<?= $taskStatusAlpha[$key] ?>);"></span><?= $label ?></span>
            <strong><?= $taskStats[$key] ?></strong>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </a>
    <?php endif; ?>

    <div class="td-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="td-card-title"><i class="bi bi-calendar-check"></i>My Attendance</span>
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
      <div class="td-attendance">
        <div><div class="td-att-value text-success"><?= $myAttendance['Present'] ?></div><div class="td-att-label">Present</div></div>
        <div><div class="td-att-value text-danger"><?= $myAttendance['Absent'] ?></div><div class="td-att-label">Absent</div></div>
      </div>
    </div>

    <div class="td-card">
      <div class="td-card-title mb-3"><i class="bi bi-link-45deg"></i>Quick Links</div>
      <?php if (empty($links)): ?>
      <div class="text-center text-muted small py-2">
        <span class="td-empty-icon"><i class="bi bi-link-45deg"></i></span>
        No links yet.
      </div>
      <?php else: ?>
      <div class="d-flex flex-column gap-2">
        <?php foreach ($links as $l): ?>
        <a href="<?= e($l['url']) ?>" target="_blank" rel="noopener" class="td-list-item">
          <i class="bi bi-box-arrow-up-right td-list-icon"></i>
          <span class="td-list-title min-w-0 flex-grow-1 text-truncate"><?= e($l['title']) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($taskStats['total'] > 0):
$extraScript = '<script>
new Chart(document.getElementById("taskStatusChart"), {
  type: "doughnut",
  data: {
    labels: ["Completed","Pending","Overdue"],
    datasets: [{ data:[' . implode(',', [$taskStats['completed'], $taskStats['pending'], $taskStats['overdue']]) . '], backgroundColor:[' . implode(',', array_map(static fn ($a) => 'chartColor(' . $a . ')', $taskStatusAlpha)) . '], borderWidth:0 }]
  },
  options: { responsive:true, maintainAspectRatio:false, cutout:"74%", plugins:{legend:{display:false}} }
});
</script>';
endif;
include APPPATH . 'Views/layout/footer.php';
?>

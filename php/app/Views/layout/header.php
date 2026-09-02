<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$user      = currentUser();
$initials  = implode('', array_map(fn ($w) => strtoupper($w[0]), array_slice(explode(' ', $user['name'] ?? 'U'), 0, 2)));
$role      = $user['role'] ?? '';
$uri       = $_SERVER['REQUEST_URI'] ?? '/';
$photoUrl  = (! empty($user['photo']) && is_file(FCPATH . 'uploads/avatars/' . $user['photo']))
    ? base_url('uploads/avatars/' . $user['photo'])
    : null;

$notifTypeIcons = [
    'announcement'      => ['bi-megaphone-fill', '#fff0f0', '#800000'],
    'task_assigned'     => ['bi-list-task', '#fff0f0', '#800000'],
    'task_submission'   => ['bi-check2-square', '#f0fdf4', '#065f46'],
    'task_feedback'     => ['bi-chat-left-text-fill', '#eff6ff', '#1e40af'],
    'document_feedback' => ['bi-chat-left-text-fill', '#eff6ff', '#1e40af'],
    'parent_meeting'    => ['bi-people-fill', '#fff7ed', '#9a3412'],
];

// Safety net for a notification stored (past bug, or old data) without a
// url — routes it to a sensible page for the type instead of a dead "#",
// same way the 34 legacy "New announcement: Meeting" rows were backfilled.
$notifTypeFallbackUrl = static function (string $type) use ($role): string {
    return match ($type) {
        'announcement'                    => base_url('announcements'),
        'task_assigned', 'task_feedback'  => base_url($role === 'teacher' ? 'submit-documents' : 'my-tasks'),
        'task_submission'                 => base_url('tasks'),
        'document_feedback'               => base_url('teacher-dashboard') . '#document-feedback',
        default                           => base_url('announcements'),
    };
};

try {
    $notifModel       = new \App\Models\NotificationModel();
    $notifItems       = $user ? $notifModel->forUser((int) $user['id'], 8) : [];
    $unreadNotifCount = $user ? $notifModel->unreadCount((int) $user['id']) : 0;
} catch (\Throwable $e) {
    $notifItems       = [];
    $unreadNotifCount = 0;
}


try {
    $unreadChatCount = 0;
    if ($user) {
        $chatConvoModel = new \App\Models\ConversationModel();
        $chatMsgModel   = new \App\Models\MessageModel();
        foreach ($chatConvoModel->forUser((int) $user['id']) as $conv) {
            if ((bool) $conv['muted']) {
                continue;
            }
            $unreadChatCount += $chatMsgModel->unreadCount((int) $conv['id'], (int) $user['id'], $conv['last_read_at']);
        }
    }
} catch (\Throwable $e) {
    $unreadChatCount = 0;
}

// To Do List sidebar badge: open tasks assigned to this teacher that they
// haven't submitted anything for yet — vanishes once every task is done.
try {
    $pendingTaskCount = 0;
    if ($user && $role === 'teacher') {
        $specificTaskIds = (new \App\Models\TaskAssigneeModel())->taskIdsForUser((int) $user['id']);
        $taskBuilder = (new \App\Models\TaskModel())
            ->select('id')
            ->groupStart()->where('assigned_role', 'teacher')->where('status', 'Open');
        if ($specificTaskIds) {
            $taskBuilder->orGroupStart()->where('assigned_role', 'specific')->where('status', 'Open')->whereIn('id', $specificTaskIds)->groupEnd();
        }
        $openTaskIds = array_column($taskBuilder->groupEnd()->findAll(), 'id');

        if ($openTaskIds) {
            $submittedTaskIds = array_column(
                (new \App\Models\TaskSubmissionModel())->select('task_id')
                    ->where('user_id', (int) $user['id'])
                    ->whereIn('task_id', $openTaskIds)
                    ->findAll(),
                'task_id'
            );
            $pendingTaskCount = count(array_diff($openTaskIds, $submittedTaskIds));
        }
    }
} catch (\Throwable $e) {
    $pendingTaskCount = 0;
}

// Announcements sidebar badge: same "unread since last visit" logic as the
// Teacher Dashboard's Announcements card, but computed once here so every
// role's sidebar link can show it. currentUser() is a login-time session
// snapshot, so last_viewed_announcements_at needs a fresh DB read.
try {
    $unreadAnnouncementsCount = 0;
    if ($user) {
        $freshUserRow = (new \App\Models\UserModel())->select('last_viewed_announcements_at')->find((int) $user['id']);
        $lastViewedAt = $freshUserRow['last_viewed_announcements_at'] ?? null;
        $annCountQuery = (new \App\Models\AnnouncementModel())->where('status', 'active');
        if ($lastViewedAt !== null) {
            $annCountQuery->where('created_at >', $lastViewedAt);
        }
        $unreadAnnouncementsCount = $annCountQuery->countAllResults();
    }
} catch (\Throwable $e) {
    $unreadAnnouncementsCount = 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> — ACADOCS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<link rel="icon" type="image/png" href="<?= base_url('assets/img/logo-icon.png') ?>">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Applied before first paint (this early, inline, in <head>) so the page
// never flashes light before switching to a saved dark preference.
(function () {
    var saved = localStorage.getItem('theme');
    var theme = saved === 'dark' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-bs-theme', theme);
})();
</script>
<?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body>

<!-- AJAX navigation progress bar -->
<div id="ajax-progress"></div>

<!-- Sidebar overlay (mobile) -->
<div id="sidebar-overlay" onclick="closeSidebar()"></div>

<!-- Upload motion loading overlay (shown while an ajax-form with a file input is uploading) -->
<div id="upload-loading-overlay" class="upload-loading-overlay">
  <div class="upload-loading-card">
    <svg class="upload-motion-svg" viewBox="0 0 160 160" width="120" height="120" aria-hidden="true">
      <defs>
        <linearGradient id="ulCloudGrad" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="var(--primary)"/>
          <stop offset="100%" stop-color="#4a0000"/>
        </linearGradient>
      </defs>
      <circle class="ul-ring" cx="80" cy="78" r="56" fill="none" stroke="var(--primary)" stroke-width="3"/>
      <path class="ul-cloud" d="M52,98 a20,20 0 0,1 -2,-39.8 a26,26 0 0,1 50.5,-9.6 a19,19 0 0,1 -3.5,49.4 z" fill="url(#ulCloudGrad)"/>
      <g class="ul-arrow">
        <rect x="76" y="72" width="8" height="26" rx="3" fill="#fff"/>
        <path d="M80,58 L95,75 L65,75 Z" fill="#fff"/>
      </g>
      <circle class="ul-dot ul-dot-1" cx="42" cy="134" r="4" fill="var(--primary)"/>
      <circle class="ul-dot ul-dot-2" cx="80" cy="142" r="4" fill="var(--primary)"/>
      <circle class="ul-dot ul-dot-3" cx="118" cy="134" r="4" fill="var(--primary)"/>
    </svg>
    <div class="upload-loading-text">Uploading document<span class="upload-loading-file-count"></span>&hellip;</div>
    <div class="upload-progress-track">
      <div class="upload-progress-fill"></div>
    </div>
    <div class="upload-progress-pct">0%</div>
  </div>
</div>

<!-- Page-navigation motion loading overlay (shown by loadPage() during AJAX page switches) -->
<div id="page-loading-overlay" class="page-loading-overlay">
  <div class="page-loading-card">
    <div class="page-flip-scene">
      <div class="page-flip-ring"></div>
      <div class="page-flip-book">
        <div class="page-flip-face page-flip-front"></div>
        <div class="page-flip-face page-flip-back"></div>
      </div>
    </div>
    <div class="page-loading-text">Loading</div>
  </div>
</div>

<!-- ══════════════ SIDEBAR ══════════════ -->
<nav id="sidebar" role="navigation">

  <!-- Brand -->
  <button id="sidebar-brand-toggle" class="sidebar-brand" type="button" onclick="toggleCollapse()" title="Toggle sidebar" aria-expanded="true">
    <div class="sidebar-brand-icon">
      <img src="<?= base_url('assets/img/logo-icon.png') ?>" alt="ACADOCS">
    </div>
    <div class="sidebar-brand-text">
      <h6>ACADOCS</h6>
      <small>School MIS</small>
    </div>
  </button>

  <!-- Scrollable nav -->
  <div class="sidebar-scroll">

    <?php if ($role === 'admin'): ?>

    <!-- MAIN -->
    <div class="sidebar-section">
      <div class="sidebar-section-label">Main</div>
      <div class="sidebar-section-items">
        <a href="<?= base_url('dashboard') ?>"
           class="nav-link <?= str_contains($uri,'dashboard')?'active':'' ?>">
          <i class="bi bi-speedometer2 nav-icon"></i>
          <span class="sidebar-label">Dashboard</span>
        </a>
        <a href="<?= base_url('announcements') ?>"
           class="nav-link <?= str_contains($uri,'announcements')?'active':'' ?>">
          <i class="bi bi-megaphone-fill nav-icon"></i>
          <span class="sidebar-label">Announcements</span>
          <?php if ($unreadAnnouncementsCount > 0): ?>
          <span class="sidebar-nav-badge sidebar-nav-badge-amber"><?= $unreadAnnouncementsCount ?></span>
          <?php endif; ?>
        </a>
      </div>
    </div>

    <!-- DOCUMENTS -->
    <div class="sidebar-section">
      <div class="sidebar-section-label">Documents</div>
      <div class="sidebar-section-items">
        <a href="<?= base_url('documents') ?>"
           class="nav-link <?= preg_match('#/documents/?$#',$uri)?'active':'' ?>">
          <i class="bi bi-folder2-open nav-icon"></i>
          <span class="sidebar-label">Manage Documents</span>
        </a>
        <a href="<?= base_url('tasks') ?>"
           class="nav-link <?= str_contains($uri,'tasks')?'active':'' ?>">
          <i class="bi bi-list-task nav-icon"></i>
          <span class="sidebar-label">Tasks &amp; Assignments</span>
        </a>
      </div>
    </div>

    <!-- OPERATIONS -->
    <div class="sidebar-section">
      <div class="sidebar-section-label">Operations</div>
      <div class="sidebar-section-items">
        <a href="<?= base_url('time-records') ?>"
           class="nav-link <?= str_contains($uri,'time-records')?'active':'' ?>">
          <i class="bi bi-clock nav-icon"></i>
          <span class="sidebar-label">Time Records</span>
        </a>
        <a href="<?= base_url('document-links') ?>"
           class="nav-link <?= str_contains($uri,'document-links')?'active':'' ?>">
          <i class="bi bi-link-45deg nav-icon"></i>
          <span class="sidebar-label">Document Links</span>
        </a>
        <a href="<?= base_url('templates') ?>"
           class="nav-link <?= str_contains($uri,'templates')?'active':'' ?>">
          <i class="bi bi-folder2-open nav-icon"></i>
          <span class="sidebar-label">Templates</span>
        </a>
        <a href="<?= base_url('property-management') ?>"
           class="nav-link <?= str_contains($uri,'property')?'active':'' ?>">
          <i class="bi bi-building nav-icon"></i>
          <span class="sidebar-label">Property Management</span>
        </a>
      </div>
    </div>

    <!-- SYSTEM -->
    <div class="sidebar-section">
      <div class="sidebar-section-label">System</div>
      <div class="sidebar-section-items">
        <a href="<?= base_url('users') ?>"
           class="nav-link <?= str_contains($uri,'users')?'active':'' ?>">
          <i class="bi bi-person-gear nav-icon"></i>
          <span class="sidebar-label">User Management</span>
        </a>
      </div>
    </div>

    <?php elseif ($role === 'teacher'): ?>

    <div class="sidebar-section">
      <div class="sidebar-section-label">My Workspace</div>
      <div class="sidebar-section-items">
        <a href="<?= base_url('teacher-dashboard') ?>"
           class="nav-link <?= str_contains($uri,'teacher-dashboard')?'active':'' ?>">
          <i class="bi bi-house-fill nav-icon"></i>
          <span class="sidebar-label">My Dashboard</span>
        </a>
        <a href="<?= base_url('submit-documents') ?>"
           class="nav-link <?= str_contains($uri,'submit')?'active':'' ?>">
          <i class="bi bi-list-task nav-icon"></i>
          <span class="sidebar-label">To Do List</span>
          <?php if ($pendingTaskCount > 0): ?>
          <span class="sidebar-nav-badge"><?= $pendingTaskCount ?></span>
          <?php endif; ?>
        </a>
        <a href="<?= base_url('performance/mps') ?>"
           class="nav-link <?= str_contains($uri,'performance/mps')?'active':'' ?>">
          <i class="bi bi-pencil-square nav-icon"></i>
          <span class="sidebar-label">Enter MPS Scores</span>
        </a>
        <a href="<?= base_url('announcements') ?>"
           class="nav-link <?= str_contains($uri,'announcements')?'active':'' ?>">
          <i class="bi bi-megaphone-fill nav-icon"></i>
          <span class="sidebar-label">Announcements</span>
          <?php if ($unreadAnnouncementsCount > 0): ?>
          <span class="sidebar-nav-badge sidebar-nav-badge-amber"><?= $unreadAnnouncementsCount ?></span>
          <?php endif; ?>
        </a>
        <a href="<?= base_url('document-links') ?>"
           class="nav-link <?= str_contains($uri,'document-links')?'active':'' ?>">
          <i class="bi bi-link-45deg nav-icon"></i>
          <span class="sidebar-label">Document Links</span>
        </a>
        <a href="<?= base_url('templates') ?>"
           class="nav-link <?= str_contains($uri,'templates')?'active':'' ?>">
          <i class="bi bi-folder2-open nav-icon"></i>
          <span class="sidebar-label">Templates</span>
        </a>
        <a href="<?= base_url('property-management') ?>"
           class="nav-link <?= str_contains($uri,'property')?'active':'' ?>">
          <i class="bi bi-building nav-icon"></i>
          <span class="sidebar-label">Property Management</span>
        </a>
      </div>
    </div>

    <?php elseif ($role === 'adas'): ?>
    <div class="sidebar-section">
      <div class="sidebar-section-label">ADAS</div>
      <div class="sidebar-section-items">
        <a href="<?= base_url('adas-dashboard') ?>" class="nav-link <?= str_contains($uri,'adas-dashboard')?'active':'' ?>">
          <i class="bi bi-house-fill nav-icon"></i><span class="sidebar-label">My Dashboard</span>
        </a>
        <a href="<?= base_url('my-tasks') ?>" class="nav-link <?= str_contains($uri,'my-tasks')?'active':'' ?>">
          <i class="bi bi-list-task nav-icon"></i><span class="sidebar-label">My Tasks</span>
        </a>
        <a href="<?= base_url('announcements') ?>" class="nav-link <?= str_contains($uri,'announcements')?'active':'' ?>">
          <i class="bi bi-megaphone-fill nav-icon"></i><span class="sidebar-label">Announcements</span>
          <?php if ($unreadAnnouncementsCount > 0): ?>
          <span class="sidebar-nav-badge sidebar-nav-badge-amber"><?= $unreadAnnouncementsCount ?></span>
          <?php endif; ?>
        </a>
        <a href="<?= base_url('document-links') ?>" class="nav-link <?= str_contains($uri,'document-links')?'active':'' ?>">
          <i class="bi bi-link-45deg nav-icon"></i><span class="sidebar-label">Document Links</span>
        </a>
        <a href="<?= base_url('time-records') ?>" class="nav-link <?= str_contains($uri,'time-records')?'active':'' ?>">
          <i class="bi bi-clock nav-icon"></i><span class="sidebar-label">Time Records</span>
        </a>
        <a href="<?= base_url('templates') ?>" class="nav-link <?= str_contains($uri,'templates')?'active':'' ?>">
          <i class="bi bi-folder2-open nav-icon"></i><span class="sidebar-label">Templates</span>
        </a>
        <a href="<?= base_url('property-management') ?>" class="nav-link <?= str_contains($uri,'property')?'active':'' ?>">
          <i class="bi bi-building nav-icon"></i><span class="sidebar-label">Property Management</span>
        </a>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /.sidebar-scroll -->

  <!-- User strip -->
  <a href="<?= base_url('profile') ?>" class="sidebar-user text-decoration-none" title="My Profile">
    <?php if ($photoUrl): ?>
    <img src="<?= e($photoUrl) ?>" alt="" class="sidebar-user-avatar" style="object-fit:cover;">
    <?php else: ?>
    <div class="sidebar-user-avatar"><?= $initials ?></div>
    <?php endif; ?>
    <div class="sidebar-user-info">
      <div class="sidebar-user-name"><?= e($user['name'] ?? '') ?></div>
      <div class="sidebar-user-role"><?= e(ucfirst($role)) ?></div>
    </div>
    <button id="sidebar-collapse-btn" class="sidebar-collapse-btn d-none d-lg-flex" type="button" onclick="event.preventDefault(); event.stopPropagation(); toggleCollapse()" title="Collapse sidebar" aria-expanded="true">
      <i class="bi bi-chevron-double-left" id="collapse-icon"></i>
    </button>
  </a>
</nav>

<!-- ══════════════ TOP BAR ══════════════ -->
<header id="topbar">
  <!-- Mobile toggle -->
  <button class="btn btn-ghost btn-sm d-lg-none me-1" onclick="openSidebar()">
    <i class="bi bi-list fs-5"></i>
  </button>

  <!-- Actions -->
  <div class="d-flex align-items-center gap-2 ms-auto">
    <!-- Dark mode toggle -->
    <button type="button" class="notif-btn" id="theme-toggle-btn" onclick="toggleTheme()" title="Toggle dark mode">
      <i class="bi bi-moon-stars fs-5" id="theme-toggle-icon"></i>
    </button>

    <!-- Chat Shortcut -->
    <a href="<?= base_url('chat') ?>" class="notif-btn" title="Chat">
      <i class="bi bi-chat-dots fs-5"></i>
      <?php if ($unreadChatCount > 0): ?>
      <span class="notif-badge"><?= $unreadChatCount ?></span>
      <?php endif; ?>
    </a>

    <!-- Notification Bell -->
    <div class="position-relative">
      <button class="notif-btn" onclick="toggleNotif(event)" title="Notifications">
        <i class="bi bi-bell fs-5"></i>
        <?php if ($unreadNotifCount > 0): ?>
        <span class="notif-badge"><?= $unreadNotifCount ?></span>
        <?php endif; ?>
      </button>

      <div class="notif-panel" id="notif-panel">
        <div class="notif-panel-header d-flex justify-content-between align-items-center">
          <span>Notifications</span>
          <span class="badge badge-maroon" style="font-size:.65rem;"><?= $unreadNotifCount ?> new</span>
        </div>
        <?php foreach ($notifItems as $n):
          $ico     = $notifTypeIcons[$n['type']] ?? ['bi-bell', 'var(--surface-hover)', 'var(--text-secondary)'];
          $notifUrl = $n['url'] ?: $notifTypeFallbackUrl($n['type']);
        ?>
        <a href="<?= e($notifUrl) ?>" class="notif-item text-decoration-none <?= $n['is_read'] ? '' : 'notif-unread' ?>"
           style="color:inherit;" data-notif-id="<?= $n['id'] ?>">
          <div class="notif-item-icon" style="background:<?= $ico[1] ?>">
            <i class="bi <?= $ico[0] ?>" style="color:<?= $ico[2] ?>;font-size:.85rem;"></i>
          </div>
          <div>
            <div class="notif-item-title"><?= e($n['title']) ?><?= $n['is_read'] ? '' : ' <span class="notif-dot"></span>' ?></div>
            <?php if ($n['sub']): ?>
            <div class="notif-item-sub"><?= e($n['sub']) ?></div>
            <?php endif; ?>
          </div>
        </a>
        <?php endforeach; ?>
        <?php if (empty($notifItems)): ?>
        <p class="text-muted text-center small p-4 mb-0">No notifications yet.</p>
        <?php endif; ?>
        <div class="p-2 text-center d-flex gap-2">
          <a href="<?= base_url('announcements') ?>" class="btn btn-sm btn-outline-maroon w-100" style="font-size:.75rem;">
            View announcements
          </a>
          <?php if (in_array($role, ['teacher', 'adas'], true)): ?>
          <a href="<?= base_url('my-tasks') ?>" class="btn btn-sm btn-outline-maroon w-100" style="font-size:.75rem;">
            View my tasks
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- User chip / Profile dropdown -->
    <div class="dropdown ps-2 border-start">
      <button type="button" class="btn d-flex align-items-center gap-2 profile-dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        <?php if ($photoUrl): ?>
        <img src="<?= e($photoUrl) ?>" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
        <?php else: ?>
        <div style="width:32px;height:32px;border-radius:50%;background:var(--primary);color:#fff;font-size:.72rem;font-weight:700;display:flex;align-items:center;justify-content:center;">
          <?= $initials ?>
        </div>
        <?php endif; ?>
        <div class="d-none d-sm-block text-start">
          <div style="font-size:.78rem;font-weight:600;color:var(--text);"><?= e($user['name'] ?? '') ?></div>
          <div style="font-size:.68rem;color:var(--muted);"><?= e(ucfirst($role)) ?></div>
        </div>
        <i class="bi bi-chevron-down d-none d-sm-inline text-muted" style="font-size:.65rem;"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="<?= base_url('profile') ?>"><i class="bi bi-person me-2"></i>My Profile</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="<?= base_url('logout') ?>" onclick="return confirmLogout(event, this)"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</header>

<!-- ══════════════ MAIN CONTENT ══════════════ -->
<main id="main-content" class="animate-in">

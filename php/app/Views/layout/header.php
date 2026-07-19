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
];

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
            $unreadChatCount += $chatMsgModel->unreadCount((int) $conv['id'], (int) $user['id'], $conv['last_read_at']);
        }
    }
} catch (\Throwable $e) {
    $unreadChatCount = 0;
}

$chatNavBadge = $unreadChatCount > 0
    ? '<span class="badge bg-danger ms-auto" style="font-size:.62rem;">' . $unreadChatCount . '</span>'
    : '';
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body>

<!-- Sidebar overlay (mobile) -->
<div id="sidebar-overlay" onclick="closeSidebar()"></div>

<!-- ══════════════ SIDEBAR ══════════════ -->
<nav id="sidebar" role="navigation">

  <!-- Brand -->
  <button id="sidebar-brand-toggle" class="sidebar-brand" type="button" onclick="toggleCollapse()" title="Toggle sidebar" aria-expanded="true">
    <div class="sidebar-brand-icon">
      <i class="bi bi-mortarboard-fill fs-6"></i>
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
      <button class="sidebar-section-btn open" onclick="toggleSection(this)">
        <span class="sidebar-section-label">Main</span>
        <i class="bi bi-chevron-down sidebar-section-arrow"></i>
      </button>
      <div class="sidebar-section-items open">
        <a href="<?= base_url('dashboard') ?>"
           class="nav-link <?= str_contains($uri,'dashboard')?'active':'' ?>">
          <i class="bi bi-speedometer2 nav-icon"></i>
          <span class="sidebar-label">Dashboard</span>
        </a>
        <a href="<?= base_url('chat') ?>"
           class="nav-link <?= str_contains($uri,'/chat')?'active':'' ?>">
          <i class="bi bi-chat-dots-fill nav-icon"></i>
          <span class="sidebar-label">Chat</span>
          <?= $chatNavBadge ?>
        </a>
      </div>
    </div>

    <!-- DOCUMENTS -->
    <div class="sidebar-section">
      <button class="sidebar-section-btn <?= str_contains($uri,'document')||str_contains($uri,'tasks')?'open':'' ?>"
              onclick="toggleSection(this)">
        <span class="sidebar-section-label">Documents</span>
        <i class="bi bi-chevron-down sidebar-section-arrow"></i>
      </button>
      <div class="sidebar-section-items <?= str_contains($uri,'document')||str_contains($uri,'tasks')?'open':'' ?>">
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

    <!-- ACADEMIC -->
    <div class="sidebar-section">
      <button class="sidebar-section-btn <?= str_contains($uri,'performance')||str_contains($uri,'enrollment')||str_contains($uri,'announcements')||str_contains($uri,'parent')?'open':'' ?>"
              onclick="toggleSection(this)">
        <span class="sidebar-section-label">Academic</span>
        <i class="bi bi-chevron-down sidebar-section-arrow"></i>
      </button>
      <div class="sidebar-section-items <?= str_contains($uri,'performance')||str_contains($uri,'enrollment')||str_contains($uri,'announcements')||str_contains($uri,'parent')?'open':'' ?>">
        <a href="<?= base_url('performance') ?>"
           class="nav-link <?= str_contains($uri,'performance')?'active':'' ?>">
          <i class="bi bi-graph-up-arrow nav-icon"></i>
          <span class="sidebar-label">Performance Analytics</span>
        </a>
        <a href="<?= base_url('enrollment-kpis') ?>"
           class="nav-link <?= str_contains($uri,'enrollment')?'active':'' ?>">
          <i class="bi bi-people-fill nav-icon"></i>
          <span class="sidebar-label">Enrollment &amp; KPIs</span>
        </a>
        <a href="<?= base_url('announcements') ?>"
           class="nav-link <?= str_contains($uri,'announcements')?'active':'' ?>">
          <i class="bi bi-megaphone-fill nav-icon"></i>
          <span class="sidebar-label">Announcements</span>
        </a>
        <a href="<?= base_url('parent-meetings') ?>"
           class="nav-link <?= str_contains($uri,'parent')?'active':'' ?>">
          <i class="bi bi-calendar3 nav-icon"></i>
          <span class="sidebar-label">Parent Meetings</span>
        </a>
      </div>
    </div>

    <!-- OPERATIONS -->
    <div class="sidebar-section">
      <button class="sidebar-section-btn <?= str_contains($uri,'time-records')||str_contains($uri,'deped')||str_contains($uri,'document-links')||str_contains($uri,'property')||str_contains($uri,'templates')?'open':'' ?>"
              onclick="toggleSection(this)">
        <span class="sidebar-section-label">Operations</span>
        <i class="bi bi-chevron-down sidebar-section-arrow"></i>
      </button>
      <div class="sidebar-section-items <?= str_contains($uri,'time-records')||str_contains($uri,'deped')||str_contains($uri,'document-links')||str_contains($uri,'property')||str_contains($uri,'templates')?'open':'' ?>">
        <a href="<?= base_url('time-records') ?>"
           class="nav-link <?= str_contains($uri,'time-records')?'active':'' ?>">
          <i class="bi bi-clock nav-icon"></i>
          <span class="sidebar-label">Time Records</span>
        </a>
        <a href="<?= base_url('deped-documents') ?>"
           class="nav-link <?= str_contains($uri,'deped')?'active':'' ?>">
          <i class="bi bi-clipboard2-check nav-icon"></i>
          <span class="sidebar-label">DepEd Documents</span>
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
      <button class="sidebar-section-btn <?= str_contains($uri,'users')?'open':'' ?>"
              onclick="toggleSection(this)">
        <span class="sidebar-section-label">System</span>
        <i class="bi bi-chevron-down sidebar-section-arrow"></i>
      </button>
      <div class="sidebar-section-items <?= str_contains($uri,'users')?'open':'' ?>">
        <a href="<?= base_url('users') ?>"
           class="nav-link <?= str_contains($uri,'users')?'active':'' ?>">
          <i class="bi bi-person-gear nav-icon"></i>
          <span class="sidebar-label">User Management</span>
        </a>
      </div>
    </div>

    <?php elseif ($role === 'teacher'): ?>

    <div class="sidebar-section">
      <button class="sidebar-section-btn open" onclick="toggleSection(this)">
        <span class="sidebar-section-label">My Workspace</span>
        <i class="bi bi-chevron-down sidebar-section-arrow"></i>
      </button>
      <div class="sidebar-section-items open">
        <a href="<?= base_url('teacher-dashboard') ?>"
           class="nav-link <?= str_contains($uri,'teacher-dashboard')?'active':'' ?>">
          <i class="bi bi-house-fill nav-icon"></i>
          <span class="sidebar-label">My Dashboard</span>
        </a>
        <a href="<?= base_url('chat') ?>"
           class="nav-link <?= str_contains($uri,'/chat')?'active':'' ?>">
          <i class="bi bi-chat-dots-fill nav-icon"></i>
          <span class="sidebar-label">Chat</span>
          <?= $chatNavBadge ?>
        </a>
        <a href="<?= base_url('submit-documents') ?>"
           class="nav-link <?= str_contains($uri,'submit')?'active':'' ?>">
          <i class="bi bi-upload nav-icon"></i>
          <span class="sidebar-label">Submit Documents</span>
        </a>
        <a href="<?= base_url('my-tasks') ?>"
           class="nav-link <?= str_contains($uri,'my-tasks')?'active':'' ?>">
          <i class="bi bi-list-task nav-icon"></i>
          <span class="sidebar-label">My Tasks</span>
        </a>
        <a href="<?= base_url('announcements') ?>"
           class="nav-link <?= str_contains($uri,'announcements')?'active':'' ?>">
          <i class="bi bi-megaphone-fill nav-icon"></i>
          <span class="sidebar-label">Announcements</span>
        </a>
        <a href="<?= base_url('document-links') ?>"
           class="nav-link <?= str_contains($uri,'document-links')?'active':'' ?>">
          <i class="bi bi-link-45deg nav-icon"></i>
          <span class="sidebar-label">Document Links</span>
        </a>
      </div>
    </div>

    <?php elseif ($role === 'secretary'): ?>
    <div class="sidebar-section">
      <button class="sidebar-section-btn open" onclick="toggleSection(this)">
        <span class="sidebar-section-label">Secretary</span>
        <i class="bi bi-chevron-down sidebar-section-arrow"></i>
      </button>
      <div class="sidebar-section-items open">
        <a href="<?= base_url('secretary-dashboard') ?>" class="nav-link <?= str_contains($uri,'secretary-dashboard')?'active':'' ?>">
          <i class="bi bi-house-fill nav-icon"></i><span class="sidebar-label">My Dashboard</span>
        </a>
        <a href="<?= base_url('chat') ?>" class="nav-link <?= str_contains($uri,'/chat')?'active':'' ?>">
          <i class="bi bi-chat-dots-fill nav-icon"></i><span class="sidebar-label">Chat</span>
          <?= $chatNavBadge ?>
        </a>
        <a href="<?= base_url('my-tasks') ?>" class="nav-link <?= str_contains($uri,'my-tasks')?'active':'' ?>">
          <i class="bi bi-list-task nav-icon"></i><span class="sidebar-label">My Tasks</span>
        </a>
        <a href="<?= base_url('document-links') ?>" class="nav-link <?= str_contains($uri,'document-links')?'active':'' ?>">
          <i class="bi bi-link-45deg nav-icon"></i><span class="sidebar-label">Document Links</span>
        </a>
        <a href="<?= base_url('announcements') ?>" class="nav-link <?= str_contains($uri,'announcements')?'active':'' ?>">
          <i class="bi bi-megaphone-fill nav-icon"></i><span class="sidebar-label">Announcements</span>
        </a>
      </div>
    </div>

    <?php elseif ($role === 'adas'): ?>
    <div class="sidebar-section">
      <button class="sidebar-section-btn open" onclick="toggleSection(this)">
        <span class="sidebar-section-label">ADAS</span>
        <i class="bi bi-chevron-down sidebar-section-arrow"></i>
      </button>
      <div class="sidebar-section-items open">
        <a href="<?= base_url('adas-dashboard') ?>" class="nav-link <?= str_contains($uri,'adas-dashboard')?'active':'' ?>">
          <i class="bi bi-house-fill nav-icon"></i><span class="sidebar-label">My Dashboard</span>
        </a>
        <a href="<?= base_url('chat') ?>" class="nav-link <?= str_contains($uri,'/chat')?'active':'' ?>">
          <i class="bi bi-chat-dots-fill nav-icon"></i><span class="sidebar-label">Chat</span>
          <?= $chatNavBadge ?>
        </a>
        <a href="<?= base_url('my-tasks') ?>" class="nav-link <?= str_contains($uri,'my-tasks')?'active':'' ?>">
          <i class="bi bi-list-task nav-icon"></i><span class="sidebar-label">My Tasks</span>
        </a>
        <a href="<?= base_url('time-records') ?>" class="nav-link <?= str_contains($uri,'time-records')?'active':'' ?>">
          <i class="bi bi-clock nav-icon"></i><span class="sidebar-label">Time Records</span>
        </a>
        <a href="<?= base_url('deped-documents') ?>" class="nav-link <?= str_contains($uri,'deped')?'active':'' ?>">
          <i class="bi bi-clipboard2-check nav-icon"></i><span class="sidebar-label">DepEd Documents</span>
        </a>
        <a href="<?= base_url('templates') ?>" class="nav-link <?= str_contains($uri,'templates')?'active':'' ?>">
          <i class="bi bi-folder2-open nav-icon"></i><span class="sidebar-label">Templates</span>
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

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="flex-grow-1">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?= base_url('dashboard') ?>"><i class="bi bi-house me-1"></i>Home</a>
      </li>
      <li class="breadcrumb-sep"><i class="bi bi-chevron-right"></i></li>
      <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
    </ol>
  </nav>

  <!-- Actions -->
  <div class="d-flex align-items-center gap-2 ms-auto">
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
          $ico = $notifTypeIcons[$n['type']] ?? ['bi-bell', '#f9fafb', '#374151'];
        ?>
        <a href="<?= e($n['url'] ?? '#') ?>" class="notif-item text-decoration-none <?= $n['is_read'] ? '' : 'notif-unread' ?>"
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
          <?php if (in_array($role, ['teacher', 'secretary', 'adas'], true)): ?>
          <a href="<?= base_url('my-tasks') ?>" class="btn btn-sm btn-outline-maroon w-100" style="font-size:.75rem;">
            View my tasks
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- User chip -->
    <a href="<?= base_url('profile') ?>" class="d-flex align-items-center gap-2 ps-2 border-start text-decoration-none" title="My Profile">
      <?php if ($photoUrl): ?>
      <img src="<?= e($photoUrl) ?>" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
      <?php else: ?>
      <div style="width:32px;height:32px;border-radius:50%;background:var(--primary);color:#fff;font-size:.72rem;font-weight:700;display:flex;align-items:center;justify-content:center;">
        <?= $initials ?>
      </div>
      <?php endif; ?>
      <div class="d-none d-sm-block">
        <div style="font-size:.78rem;font-weight:600;color:#111;"><?= e($user['name'] ?? '') ?></div>
        <div style="font-size:.68rem;color:var(--muted);"><?= e(ucfirst($role)) ?></div>
      </div>
    </a>

    <!-- Logout -->
    <a href="<?= base_url('logout') ?>" class="btn btn-ghost btn-sm" title="Logout" onclick="return confirmLogout(event, this)">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>
</header>

<!-- ══════════════ MAIN CONTENT ══════════════ -->
<main id="main-content" class="animate-in">

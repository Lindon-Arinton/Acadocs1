<?php
if (!defined('PAGE_TITLE')) define('PAGE_TITLE', 'Dashboard');
$user     = currentUser();
$initials = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $user['name'] ?? 'U'), 0, 2)));
$role     = $user['role'] ?? '';
$uri      = $_SERVER['REQUEST_URI'];

// Notification data from DB
try {
  $db = getDB();
  $recentAnnouncements = $db->query("SELECT * FROM announcements WHERE status='active' ORDER BY date DESC LIMIT 4")->fetchAll();
} catch (Exception $e) { $recentAnnouncements = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(PAGE_TITLE) ?> — ACADOCS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css">
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
        <a href="<?= BASE_URL ?>dashboard"
           class="nav-link <?= str_contains($uri,'dashboard')?'active':'' ?>">
          <i class="bi bi-speedometer2 nav-icon"></i>
          <span class="sidebar-label">Dashboard</span>
        </a>
      </div>
    </div>

    <!-- DOCUMENTS -->
    <div class="sidebar-section">
      <button class="sidebar-section-btn <?= str_contains($uri,'document')||str_contains($uri,'submit')?'open':'' ?>"
              onclick="toggleSection(this)">
        <span class="sidebar-section-label">Documents</span>
        <i class="bi bi-chevron-down sidebar-section-arrow"></i>
      </button>
      <div class="sidebar-section-items <?= str_contains($uri,'document')||str_contains($uri,'submit')?'open':'' ?>">
        <a href="<?= BASE_URL ?>submit-documents"
           class="nav-link <?= str_contains($uri,'submit-documents')?'active':'' ?>">
          <i class="bi bi-upload nav-icon"></i>
          <span class="sidebar-label">Submit Documents</span>
        </a>
        <a href="<?= BASE_URL ?>documents"
           class="nav-link <?= preg_match('#/documents/?$#',$uri)?'active':'' ?>">
          <i class="bi bi-folder2-open nav-icon"></i>
          <span class="sidebar-label">Manage Documents</span>
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
        <a href="<?= BASE_URL ?>performance"
           class="nav-link <?= str_contains($uri,'performance')?'active':'' ?>">
          <i class="bi bi-graph-up-arrow nav-icon"></i>
          <span class="sidebar-label">Performance Analytics</span>
        </a>
        <a href="<?= BASE_URL ?>enrollment-kpis"
           class="nav-link <?= str_contains($uri,'enrollment')?'active':'' ?>">
          <i class="bi bi-people-fill nav-icon"></i>
          <span class="sidebar-label">Enrollment &amp; KPIs</span>
        </a>
        <a href="<?= BASE_URL ?>announcements"
           class="nav-link <?= str_contains($uri,'announcements')?'active':'' ?>">
          <i class="bi bi-megaphone-fill nav-icon"></i>
          <span class="sidebar-label">Announcements</span>
        </a>
        <a href="<?= BASE_URL ?>parent-meetings"
           class="nav-link <?= str_contains($uri,'parent')?'active':'' ?>">
          <i class="bi bi-calendar3 nav-icon"></i>
          <span class="sidebar-label">Parent Meetings</span>
        </a>
      </div>
    </div>

    <!-- OPERATIONS -->
    <div class="sidebar-section">
      <button class="sidebar-section-btn <?= str_contains($uri,'financial')||str_contains($uri,'time-records')||str_contains($uri,'deped')||str_contains($uri,'document-links')||str_contains($uri,'property')?'open':'' ?>"
              onclick="toggleSection(this)">
        <span class="sidebar-section-label">Operations</span>
        <i class="bi bi-chevron-down sidebar-section-arrow"></i>
      </button>
      <div class="sidebar-section-items <?= str_contains($uri,'financial')||str_contains($uri,'time-records')||str_contains($uri,'deped')||str_contains($uri,'document-links')||str_contains($uri,'property')?'open':'' ?>">
        <a href="<?= BASE_URL ?>financial-reports"
           class="nav-link <?= str_contains($uri,'financial')?'active':'' ?>">
          <i class="bi bi-cash-stack nav-icon"></i>
          <span class="sidebar-label">Financial Reports</span>
        </a>
        <a href="<?= BASE_URL ?>time-records"
           class="nav-link <?= str_contains($uri,'time-records')?'active':'' ?>">
          <i class="bi bi-clock nav-icon"></i>
          <span class="sidebar-label">Time Records</span>
        </a>
        <a href="<?= BASE_URL ?>deped-documents"
           class="nav-link <?= str_contains($uri,'deped')?'active':'' ?>">
          <i class="bi bi-clipboard2-check nav-icon"></i>
          <span class="sidebar-label">DepEd Documents</span>
        </a>
        <a href="<?= BASE_URL ?>document-links"
           class="nav-link <?= str_contains($uri,'document-links')?'active':'' ?>">
          <i class="bi bi-link-45deg nav-icon"></i>
          <span class="sidebar-label">Document Links</span>
        </a>
        <a href="<?= BASE_URL ?>property-management"
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
        <a href="<?= BASE_URL ?>users"
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
        <a href="<?= BASE_URL ?>teacher-dashboard"
           class="nav-link <?= str_contains($uri,'teacher-dashboard')?'active':'' ?>">
          <i class="bi bi-house-fill nav-icon"></i>
          <span class="sidebar-label">My Dashboard</span>
        </a>
        <a href="<?= BASE_URL ?>submit-documents"
           class="nav-link <?= str_contains($uri,'submit')?'active':'' ?>">
          <i class="bi bi-upload nav-icon"></i>
          <span class="sidebar-label">Submit Documents</span>
        </a>
        <a href="<?= BASE_URL ?>announcements"
           class="nav-link <?= str_contains($uri,'announcements')?'active':'' ?>">
          <i class="bi bi-megaphone-fill nav-icon"></i>
          <span class="sidebar-label">Announcements</span>
        </a>
        <a href="<?= BASE_URL ?>document-links"
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
        <a href="<?= BASE_URL ?>document-links" class="nav-link <?= str_contains($uri,'document-links')?'active':'' ?>">
          <i class="bi bi-link-45deg nav-icon"></i><span class="sidebar-label">Document Links</span>
        </a>
        <a href="<?= BASE_URL ?>announcements" class="nav-link <?= str_contains($uri,'announcements')?'active':'' ?>">
          <i class="bi bi-megaphone-fill nav-icon"></i><span class="sidebar-label">Announcements</span>
        </a>
      </div>
    </div>

    <?php elseif ($role === 'canteen'): ?>
    <div class="sidebar-section">
      <button class="sidebar-section-btn open" onclick="toggleSection(this)">
        <span class="sidebar-section-label">Canteen</span>
        <i class="bi bi-chevron-down sidebar-section-arrow"></i>
      </button>
      <div class="sidebar-section-items open">
        <a href="<?= BASE_URL ?>financial-reports" class="nav-link">
          <i class="bi bi-cash-stack nav-icon"></i><span class="sidebar-label">Financial Reports</span>
        </a>
      </div>
    </div>

    <?php elseif ($role === 'disbursing'): ?>
    <div class="sidebar-section">
      <button class="sidebar-section-btn open" onclick="toggleSection(this)">
        <span class="sidebar-section-label">Disbursing</span>
        <i class="bi bi-chevron-down sidebar-section-arrow"></i>
      </button>
      <div class="sidebar-section-items open">
        <a href="<?= BASE_URL ?>financial-reports" class="nav-link">
          <i class="bi bi-bank nav-icon"></i><span class="sidebar-label">School Funds</span>
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
        <a href="<?= BASE_URL ?>time-records" class="nav-link <?= str_contains($uri,'time-records')?'active':'' ?>">
          <i class="bi bi-clock nav-icon"></i><span class="sidebar-label">Time Records</span>
        </a>
        <a href="<?= BASE_URL ?>deped-documents" class="nav-link <?= str_contains($uri,'deped')?'active':'' ?>">
          <i class="bi bi-clipboard2-check nav-icon"></i><span class="sidebar-label">DepEd Documents</span>
        </a>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /.sidebar-scroll -->

  <!-- User strip -->
  <div class="sidebar-user">
    <div class="sidebar-user-avatar"><?= $initials ?></div>
    <div class="sidebar-user-info">
      <div class="sidebar-user-name"><?= e($user['name'] ?? '') ?></div>
      <div class="sidebar-user-role"><?= e(ucfirst($role)) ?></div>
    </div>
    <button id="sidebar-collapse-btn" class="sidebar-collapse-btn d-none d-lg-flex" type="button" onclick="toggleCollapse()" title="Collapse sidebar" aria-expanded="true">
      <i class="bi bi-chevron-double-left" id="collapse-icon"></i>
    </button>
  </div>
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
        <a href="<?= BASE_URL ?>dashboard"><i class="bi bi-house me-1"></i>Home</a>
      </li>
      <li class="breadcrumb-sep"><i class="bi bi-chevron-right"></i></li>
      <li class="breadcrumb-item active"><?= e(PAGE_TITLE) ?></li>
    </ol>
  </nav>

  <!-- Actions -->
  <div class="d-flex align-items-center gap-2 ms-auto">
    <!-- Notification Bell -->
    <div class="position-relative">
      <button class="notif-btn" onclick="toggleNotif(event)" title="Notifications">
        <i class="bi bi-bell fs-5"></i>
        <?php if (count($recentAnnouncements) > 0): ?>
        <span class="notif-badge"><?= count($recentAnnouncements) ?></span>
        <?php endif; ?>
      </button>

      <div class="notif-panel" id="notif-panel">
        <div class="notif-panel-header d-flex justify-content-between align-items-center">
          <span>Notifications</span>
          <span class="badge badge-maroon" style="font-size:.65rem;"><?= count($recentAnnouncements) ?> new</span>
        </div>
        <?php foreach ($recentAnnouncements as $a):
          $ico = ['Announcement'=>['bi-megaphone-fill','#fff0f0','#800000'],
                  'Questionnaires'=>['bi-card-list','#f0f4ff','#3730a3'],
                  'Forms'=>['bi-file-earmark-text-fill','#f0fdf4','#065f46']][$a['type']] ?? ['bi-bell','#f9fafb','#374151'];
        ?>
        <a href="<?= BASE_URL ?>announcements" class="notif-item text-decoration-none" style="color:inherit;">
          <div class="notif-item-icon" style="background:<?= $ico[1] ?>">
            <i class="bi <?= $ico[0] ?>" style="color:<?= $ico[2] ?>;font-size:.85rem;"></i>
          </div>
          <div>
            <div class="notif-item-title"><?= e($a['title']) ?></div>
            <div class="notif-item-sub"><?= e($a['type']) ?> · <?= date('M d', strtotime($a['date'])) ?></div>
          </div>
        </a>
        <?php endforeach; ?>
        <div class="p-2 text-center">
          <a href="<?= BASE_URL ?>announcements" class="btn btn-sm btn-outline-maroon w-100" style="font-size:.75rem;">
            View all announcements
          </a>
        </div>
      </div>
    </div>

    <!-- User chip -->
    <div class="d-flex align-items-center gap-2 ps-2 border-start">
      <div style="width:32px;height:32px;border-radius:50%;background:var(--primary);color:#fff;font-size:.72rem;font-weight:700;display:flex;align-items:center;justify-content:center;">
        <?= $initials ?>
      </div>
      <div class="d-none d-sm-block">
        <div style="font-size:.78rem;font-weight:600;color:#111;"><?= e($user['name'] ?? '') ?></div>
        <div style="font-size:.68rem;color:var(--muted);"><?= e(ucfirst($role)) ?></div>
      </div>
    </div>

    <!-- Logout -->
    <a href="<?= BASE_URL ?>logout" class="btn btn-ghost btn-sm" title="Logout">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>
</header>

<!-- ══════════════ MAIN CONTENT ══════════════ -->
<main id="main-content" class="animate-in">

<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <h4><i class="bi bi-megaphone-fill me-2"></i>Announcements</h4>
  <p>School-wide announcements, forms &amp; questionnaires</p>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-check-circle-fill"></i><?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<div class="row g-4">
  <!-- Left: list -->
  <div class="col-lg-<?= hasRole('admin','adas') ? '8' : '12' ?>">
    <!-- Filter tabs + search + sort -->
    <form method="GET" action="<?= base_url('announcements') ?>" id="filterForm" class="mb-4">
      <div class="tab-pills mb-3">
        <?php foreach (['all'=>'All','Announcement'=>'Announcements','Questionnaires'=>'Questionnaires','Forms'=>'Forms'] as $val=>$lbl): ?>
        <button type="submit" name="type" value="<?= $val ?>" class="tab-pill <?= $filter===$val?'active':'' ?>">
          <?= $lbl ?>
        </button>
        <?php endforeach; ?>
      </div>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <div class="input-group input-group-sm" style="max-width:260px;">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="q" id="announcementSearchInput" value="<?= e($search) ?>"
                 class="form-control border-start-0 ps-0" placeholder="Search title, content...">
        </div>
        <select name="sort" class="form-select form-select-sm" style="width:auto;" onchange="this.form.requestSubmit()">
          <option value="newest"   <?= $sort==='newest'   ? 'selected' : '' ?>>Newest First</option>
          <option value="oldest"   <?= $sort==='oldest'   ? 'selected' : '' ?>>Oldest First</option>
          <option value="title_az" <?= $sort==='title_az' ? 'selected' : '' ?>>Title A-Z</option>
        </select>
      </div>
    </form>

    <?php if (empty($announcements)): ?>
    <div class="card"><div class="card-body text-center py-5">
      <i class="bi bi-inbox fs-1 d-block mb-3 text-muted"></i>
      <p class="text-muted mb-0">No announcements found matching your search/filters.</p>
    </div></div>
    <?php endif; ?>

    <?php foreach ($announcements as $a):
      $cfg = [
        'Announcement'   => ['#fff0f0','#800000','bi-megaphone-fill'],
        'Questionnaires' => ['#eff6ff','#1e40af','bi-card-checklist'],
        'Forms'          => ['#f0fdf4','#065f46','bi-file-earmark-text-fill'],
      ][$a['type']] ?? ['#f9fafb','#374151','bi-bell'];
      [$bg,$tc,$icon] = $cfg;
      $preview = mb_strlen($a['content']) > 140 ? mb_substr($a['content'], 0, 140) . '…' : $a['content'];
      $modalData = $a + [
          'date_formatted' => date('F d, Y', strtotime($a['date'])),
          'content_html'   => richText($a['content']),
      ];
    ?>
    <div class="announcement-card" style="border-left:4px solid <?= $tc ?>;cursor:pointer;"
         onclick="viewAnnouncement(<?= htmlspecialchars(json_encode($modalData), ENT_QUOTES) ?>, '<?= $bg ?>', '<?= $tc ?>', '<?= $icon ?>')">
      <div class="d-flex gap-3 align-items-start">
        <div class="ac-icon flex-shrink-0" style="background:<?= $bg ?>;">
          <i class="bi <?= $icon ?>" style="color:<?= $tc ?>;font-size:1rem;"></i>
        </div>
        <div class="flex-grow-1">
          <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-1">
            <div>
              <h6 class="fw-bold mb-1" style="font-size:.88rem"><?= e($a['title']) ?></h6>
              <span class="badge" style="background:<?= $bg ?>;color:<?= $tc ?>;border:1px solid <?= $tc ?>33;font-size:.68rem;">
                <?= e($a['type']) ?>
              </span>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
              <span class="text-muted" style="font-size:.72rem;">
                <i class="bi bi-calendar3 me-1"></i><?= date('M d, Y', strtotime($a['date'])) ?>
              </span>
              <?php if (hasRole('admin','adas')): ?>
              <form method="POST" action="<?= base_url('announcements') ?>" class="d-inline ajax-form" onclick="event.stopPropagation()"
                    data-confirm-title="Delete this announcement?">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                <button class="btn btn-ghost btn-sm text-danger py-0 px-1"><i class="bi bi-trash"></i></button>
              </form>
              <?php endif; ?>
            </div>
          </div>
          <p class="text-muted mb-0" style="font-size:.8rem;line-height:1.5;"><?= richText($preview) ?></p>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Right: post form -->
  <?php if (hasRole('admin','adas')): ?>
  <div class="col-lg-4">
    <div class="card sticky-top" style="top:80px;">
      <div class="card-header" style="background:linear-gradient(135deg,var(--pink),var(--primary));color:#fff;">
        <div class="card-title" style="color:#fff;"><i class="bi bi-plus-circle me-2"></i>Post Announcement</div>
      </div>
      <div class="card-body">
        <form method="POST" action="<?= base_url('announcements') ?>" class="ajax-form"
              data-confirm-title="Post this announcement?" data-confirm-text="It will be visible to everyone right away.">
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
              <option>Announcement</option>
              <option>Questionnaires</option>
              <option>Forms</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Content</label>
            <div class="btn-group btn-group-sm mb-1" role="group" aria-label="Text formatting">
              <button type="button" class="btn btn-outline-secondary" title="Bold" onclick="wrapSelection('announcementContent','**')">
                <i class="bi bi-type-bold"></i>
              </button>
              <button type="button" class="btn btn-outline-secondary" title="Italic" onclick="wrapSelection('announcementContent','*')">
                <i class="bi bi-type-italic"></i>
              </button>
            </div>
            <textarea name="content" id="announcementContent" class="form-control" rows="4" required
                      placeholder="Select text and click Bold/Italic, or type **bold** / *italic* directly"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Date</label>
            <div class="maroon-dp" data-min="<?= date('Y-m-d') ?>">
              <input type="text" class="form-control maroon-dp-display" placeholder="Select date" readonly required>
              <input type="hidden" name="date" value="<?= date('Y-m-d') ?>">
              <div class="maroon-dp-panel">
                <div class="maroon-dp-header">
                  <button type="button" class="maroon-dp-nav" data-dir="-1"><i class="bi bi-chevron-left"></i></button>
                  <span class="maroon-dp-month-label"></span>
                  <button type="button" class="maroon-dp-nav" data-dir="1"><i class="bi bi-chevron-right"></i></button>
                </div>
                <div class="maroon-dp-dow"><span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span></div>
                <div class="maroon-dp-grid"></div>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-send me-2"></i>Post Announcement
          </button>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- View Announcement Modal -->
<div class="modal fade" id="viewAnnouncementModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" id="viewAnnouncementHeader" style="color:#fff;">
        <h6 class="modal-title fw-bold"><i class="bi bi-megaphone-fill me-2" id="viewAnnouncementIcon"></i><span id="viewAnnouncementTitle"></span></h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-center gap-2 mb-3">
          <span class="badge" id="viewAnnouncementType"></span>
          <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i><span id="viewAnnouncementDate"></span></span>
        </div>
        <p class="mb-0" id="viewAnnouncementContent"></p>
      </div>
    </div>
  </div>
</div>

<?php
$extraScript = "<script>
function viewAnnouncement(a, bg, tc, icon) {
    document.getElementById('viewAnnouncementHeader').style.background = tc;
    document.getElementById('viewAnnouncementIcon').className = 'bi ' + icon + ' me-2';
    document.getElementById('viewAnnouncementTitle').textContent = a.title;
    document.getElementById('viewAnnouncementType').textContent = a.type;
    document.getElementById('viewAnnouncementType').style.background = bg;
    document.getElementById('viewAnnouncementType').style.color = tc;
    document.getElementById('viewAnnouncementType').style.border = '1px solid ' + tc + '33';
    document.getElementById('viewAnnouncementDate').textContent = a.date_formatted;
    document.getElementById('viewAnnouncementContent').innerHTML = a.content_html;
    new bootstrap.Modal(document.getElementById('viewAnnouncementModal')).show();
}

function wrapSelection(id, marker) {
    const el = document.getElementById(id);
    const start = el.selectionStart, end = el.selectionEnd;
    const selected = el.value.substring(start, end) || 'text';
    el.value = el.value.substring(0, start) + marker + selected + marker + el.value.substring(end);
    el.focus();
    el.selectionStart = start + marker.length;
    el.selectionEnd = start + marker.length + selected.length;
}
initLiveSearch('announcementSearchInput', 'filterForm');
</script>";
include APPPATH . 'Views/layout/footer.php';
?>

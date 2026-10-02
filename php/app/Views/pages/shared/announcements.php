<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <h4><i class="bi bi-megaphone-fill me-2"></i>Announcements</h4>
      <p>School-wide announcements, forms &amp; questionnaires</p>
    </div>
    <?php if (hasRole('admin','adas')): ?>
    <button type="button" class="btn btn-light" style="position:relative;z-index:1;" onclick="openPostAnnouncementModal()">
      <i class="bi bi-plus-lg me-1"></i>New Announcement
    </button>
    <?php endif; ?>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-check-circle-fill"></i><?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<?php if (! empty($deletedNotice)): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-exclamation-triangle-fill"></i><?= e($deletedNotice) ?>
</div>
<?php endif; ?>

<div class="row g-4">
  <!-- Announcement list -->
  <div class="col-12">
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
        <div class="maroon-select maroon-select-sm" style="width:auto;">
          <select name="sort" class="maroon-select-native" onchange="this.form.requestSubmit()">
            <option value="newest"   <?= $sort==='newest'   ? 'selected' : '' ?>>Newest First</option>
            <option value="oldest"   <?= $sort==='oldest'   ? 'selected' : '' ?>>Oldest First</option>
            <option value="title_az" <?= $sort==='title_az' ? 'selected' : '' ?>>Title A-Z</option>
          </select>
          <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
          <div class="maroon-select-panel"></div>
        </div>
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
      ][$a['type']] ?? ['var(--surface-hover)','var(--text-secondary)','bi-bell'];
      [$bg,$tc,$icon] = $cfg;
      $content = (string) $a['content'];
      $preview = mb_strlen($content) > 140 ? mb_substr($content, 0, 140) . '…' : $content;
      $imageUrl  = ! empty($a['image']) && is_file(\App\Controllers\Shared\Announcements::imageDir() . $a['image'])
          ? base_url('uploads/announcements/' . $a['image']) : null;
      $modalData = $a + [
          'image_url'      => $imageUrl,
          'poster'         => $a['poster_name'] ?? null,
          'poster_label'   => ['admin' => 'Principal', 'adas' => 'ADAS', 'teacher' => 'Teacher'][$a['poster_role'] ?? ''] ?? null,
          'posted_on'      => ! empty($a['created_at']) ? date('F d, Y · h:i A', strtotime($a['created_at'])) : null,
          'date_formatted' => date('F d, Y', strtotime($a['date'])),
          'content_html'   => $content !== '' ? richText($content) : '<span class="text-muted">No additional details.</span>',
      ];
    ?>
    <div class="announcement-card" id="announcement-<?= $a['id'] ?>" style="border-left:4px solid <?= $tc ?>;cursor:pointer;"
         onclick="viewAnnouncement(<?= htmlspecialchars(json_encode($modalData), ENT_QUOTES) ?>, '<?= $bg ?>', '<?= $tc ?>', '<?= $icon ?>')">
      <div class="d-flex gap-3 align-items-start">
        <?php if ($imageUrl): ?>
        <img src="<?= e($imageUrl) ?>" alt="" class="ac-thumb flex-shrink-0" loading="lazy">
        <?php else: ?>
        <div class="ac-icon flex-shrink-0" style="background:<?= $bg ?>;">
          <i class="bi <?= $icon ?>" style="color:<?= $tc ?>;font-size:1rem;"></i>
        </div>
        <?php endif; ?>
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
          <?php if ($preview !== ''): ?>
          <p class="text-muted mb-0" style="font-size:.8rem;line-height:1.5;"><?= richText($preview) ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php if (hasRole('admin','adas')): ?>
<!-- Post Announcement Modal (opened from the header button / the floating megaphone button) -->
<div class="modal fade" id="postAnnouncementModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Post Announcement</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('announcements') ?>" class="ajax-form" enctype="multipart/form-data"
            data-confirm-title="Post this announcement?" data-confirm-text="It will be visible to everyone right away.">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Type</label>
            <div class="maroon-select" style="width:100%;">
              <select name="type" class="maroon-select-native">
                <option>Announcement</option>
                <option>Questionnaires</option>
                <option>Forms</option>
              </select>
              <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
              <div class="maroon-select-panel"></div>
            </div>
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
            <textarea name="content" id="announcementContent" class="form-control" rows="4"
                      placeholder="Select text and click Bold/Italic, or type **bold** / *italic* directly"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" for="announcementImage">Photo</label>
            <input type="file" name="image" id="announcementImage" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp"
                   onchange="previewAnnouncementImage(this)">
            <div class="form-text">JPG, PNG, GIF or WEBP, up to 5 MB. Shown as the banner of the announcement.</div>
            <div id="announcementImagePreview" class="ann-photo-preview d-none">
              <img alt="">
              <button type="button" class="btn btn-sm btn-light" onclick="clearAnnouncementImage()" title="Remove photo"><i class="bi bi-x-lg"></i></button>
            </div>
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
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-send me-2"></i>Post Announcement</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- View Announcement Modal: banner (photo, or the type's color + icon) over title, meta and content -->
<div class="modal fade" id="viewAnnouncementModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content ann-view">
      <div class="ann-view-banner" id="viewAnnouncementBanner">
        <img id="viewAnnouncementImage" alt="" class="d-none" title="Click to view the full photo" onclick="openAnnouncementPhoto()">
        <button type="button" class="ann-view-expand d-none" id="viewAnnouncementExpand" onclick="openAnnouncementPhoto()">
          <i class="bi bi-arrows-fullscreen me-1"></i>View full photo
        </button>
        <div class="ann-view-icon" id="viewAnnouncementIconWrap">
          <span class="ann-dot ann-dot-1"></span><span class="ann-dot ann-dot-2"></span><span class="ann-dot ann-dot-3"></span>
          <div class="ann-view-icon-circle"><i class="bi bi-megaphone-fill" id="viewAnnouncementIcon"></i></div>
        </div>
      </div>
      <div class="ann-view-body">
        <span class="badge mb-2" id="viewAnnouncementType"></span>
        <h4 class="ann-view-title" id="viewAnnouncementTitle"></h4>
        <div class="ann-view-meta">
          <span><i class="bi bi-calendar3 me-1"></i><span id="viewAnnouncementDate"></span></span>
          <span id="viewAnnouncementPostedWrap"><i class="bi bi-clock me-1"></i>Posted <span id="viewAnnouncementPosted"></span></span>
          <span id="viewAnnouncementPosterWrap"><i class="bi bi-person me-1"></i>by <strong id="viewAnnouncementPoster"></strong></span>
        </div>
        <div class="ann-view-content" id="viewAnnouncementContent"></div>
      </div>
      <div class="ann-view-footer">
        <button type="button" class="btn btn-outline-maroon ann-view-share" onclick="openShareAnnouncement()">
          <i class="bi bi-send me-1"></i>Share to Chat
        </button>
        <button type="button" class="btn btn-primary ann-view-ok" data-bs-dismiss="modal">I Understand</button>
      </div>
    </div>
  </div>
</div>

<!-- Share an announcement to chat: people (direct chats) and my group chats -->
<div class="modal fade" id="shareAnnouncementModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-send me-2"></i>Share to Chat</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="share-ann-preview mb-3">
          <i class="bi bi-megaphone-fill"></i>
          <span id="shareAnnouncementTitle" class="fw-semibold"></span>
        </div>
        <div class="input-group input-group-sm mb-2">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" id="shareSearch" class="form-control border-start-0 ps-0" placeholder="Search people or groups..." autocomplete="off" oninput="filterShareTargets(this.value)">
        </div>
        <div class="share-target-list">
          <?php if (! empty($shareGroups)): ?>
          <div class="share-section-label">Group chats</div>
          <?php foreach ($shareGroups as $g): ?>
          <label class="share-target" data-search="<?= e(mb_strtolower((string) $g['name'])) ?>">
            <input type="checkbox" class="form-check-input mt-0 share-cb" name="conversation_ids[]" value="<?= (int) $g['id'] ?>">
            <span class="share-avatar share-avatar-group"><i class="bi bi-people-fill"></i></span>
            <span class="flex-grow-1"><?= e($g['name'] ?: 'Group chat') ?></span>
          </label>
          <?php endforeach; ?>
          <?php endif; ?>
          <div class="share-section-label">People</div>
          <?php foreach ($sharePeople as $p): ?>
          <label class="share-target" data-search="<?= e(mb_strtolower($p['name'])) ?>">
            <input type="checkbox" class="form-check-input mt-0 share-cb" name="user_ids[]" value="<?= (int) $p['id'] ?>">
            <span class="share-avatar"><?= e(strtoupper(mb_substr($p['name'], 0, 1))) ?></span>
            <span class="flex-grow-1"><?= e($p['name']) ?></span>
            <span class="small text-muted"><?= e(['admin' => 'Principal', 'adas' => 'ADAS', 'teacher' => 'Teacher'][$p['role']] ?? ucfirst($p['role'])) ?></span>
          </label>
          <?php endforeach; ?>
          <div class="text-center text-muted small py-3 d-none" id="shareNoMatch">No matches.</div>
        </div>
        <label class="small fw-semibold mt-3 mb-1 d-block" for="shareNote">Add a message <span class="text-muted fw-normal">(optional)</span></label>
        <textarea id="shareNote" class="form-control form-control-sm" rows="2" maxlength="500" placeholder="e.g. Please read before Monday."></textarea>
        <div class="text-danger small mt-2 d-none" id="shareError"></div>
      </div>
      <div class="modal-footer">
        <span class="small text-muted me-auto" id="shareCount">0 selected</span>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="shareSendBtn" onclick="sendShareAnnouncement()"><i class="bi bi-send me-1"></i>Send</button>
      </div>
    </div>
  </div>
</div>

<!-- Full-size photo viewer (opened from the announcement banner) -->
<div class="ann-lightbox d-none" id="announcementLightbox" onclick="closeAnnouncementPhoto(event)">
  <img id="announcementLightboxImg" alt="">
  <div class="ann-lightbox-bar">
    <a id="announcementLightboxOpen" href="#" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i>Open original</a>
    <button type="button" onclick="closeAnnouncementPhoto()"><i class="bi bi-x-lg me-1"></i>Close</button>
  </div>
</div>

<?php
$extraScript = "<script>
function viewAnnouncement(a, bg, tc, icon) {
    // Banner: the photo if there is one, otherwise the type's color with its icon.
    const banner = document.getElementById('viewAnnouncementBanner');
    const img    = document.getElementById('viewAnnouncementImage');
    const hasImg = !!a.image_url;
    banner.style.background = hasImg ? '#000' : 'linear-gradient(135deg, ' + tc + ' 0%, ' + tc + 'cc 100%)';
    img.classList.toggle('d-none', !hasImg);
    document.getElementById('viewAnnouncementExpand').classList.toggle('d-none', !hasImg);
    currentAnnouncement = a;
    if (hasImg) { img.src = a.image_url; } else { img.removeAttribute('src'); }
    document.getElementById('viewAnnouncementIconWrap').classList.toggle('d-none', hasImg);
    document.getElementById('viewAnnouncementIcon').className = 'bi ' + icon;
    document.getElementById('viewAnnouncementIcon').style.color = tc;

    document.getElementById('viewAnnouncementPosted').textContent = a.posted_on || '';
    document.getElementById('viewAnnouncementPostedWrap').classList.toggle('d-none', !a.posted_on);
    document.getElementById('viewAnnouncementPoster').textContent = a.poster ? a.poster + (a.poster_label ? ' (' + a.poster_label + ')' : '') : '';
    document.getElementById('viewAnnouncementPosterWrap').classList.toggle('d-none', !a.poster);

    document.getElementById('viewAnnouncementTitle').textContent = a.title;
    document.getElementById('viewAnnouncementType').textContent = a.type;
    document.getElementById('viewAnnouncementType').style.background = bg;
    document.getElementById('viewAnnouncementType').style.color = tc;
    document.getElementById('viewAnnouncementType').style.border = '1px solid ' + tc + '33';
    document.getElementById('viewAnnouncementDate').textContent = a.date_formatted;
    document.getElementById('viewAnnouncementContent').innerHTML = a.content_html;
    new bootstrap.Modal(document.getElementById('viewAnnouncementModal')).show();
}

let currentAnnouncement = null;

/* Full-size photo: shown at its real size, or scaled down to fit the screen. */
function openAnnouncementPhoto() {
    if (!currentAnnouncement || !currentAnnouncement.image_url) return;
    document.getElementById('announcementLightboxImg').src = currentAnnouncement.image_url;
    document.getElementById('announcementLightboxOpen').href = currentAnnouncement.image_url;
    document.getElementById('announcementLightbox').classList.remove('d-none');
}

function closeAnnouncementPhoto(e) {
    // Clicking the photo itself or the bar's link shouldn't close it.
    if (e && (e.target.id === 'announcementLightboxImg' || e.target.closest('a'))) return;
    document.getElementById('announcementLightbox').classList.add('d-none');
}

document.addEventListener('keydown', function (e) {
    const box = document.getElementById('announcementLightbox');
    if (e.key === 'Escape' && box && !box.classList.contains('d-none')) {
        e.stopPropagation(); // close only the photo, not the announcement behind it
        box.classList.add('d-none');
    }
}, true);

/* Share to chat */
function openShareAnnouncement() {
    if (!currentAnnouncement) return;
    document.getElementById('shareAnnouncementTitle').textContent = currentAnnouncement.title;
    document.querySelectorAll('.share-cb').forEach(cb => { cb.checked = false; });
    document.getElementById('shareNote').value = '';
    document.getElementById('shareSearch').value = '';
    filterShareTargets('');
    updateShareCount();
    document.getElementById('shareError').classList.add('d-none');
    bootstrap.Modal.getInstance(document.getElementById('viewAnnouncementModal'))?.hide();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('shareAnnouncementModal')).show();
}

function filterShareTargets(q) {
    q = q.trim().toLowerCase();
    let shown = 0;
    document.querySelectorAll('.share-target').forEach(el => {
        const match = !q || el.dataset.search.includes(q);
        el.classList.toggle('d-none', !match);
        if (match) shown++;
    });
    document.querySelectorAll('.share-section-label').forEach(el => el.classList.toggle('d-none', !!q));
    document.getElementById('shareNoMatch').classList.toggle('d-none', shown > 0);
}

function updateShareCount() {
    const n = document.querySelectorAll('.share-cb:checked').length;
    document.getElementById('shareCount').textContent = n + ' selected';
}
document.addEventListener('change', e => { if (e.target.classList && e.target.classList.contains('share-cb')) updateShareCount(); });

function sendShareAnnouncement() {
    const err = document.getElementById('shareError');
    const checked = [...document.querySelectorAll('.share-cb:checked')];
    if (!checked.length) {
        err.textContent = 'Choose at least one person or group.';
        err.classList.remove('d-none');
        return;
    }

    const form = new FormData();
    checked.forEach(cb => form.append(cb.name, cb.value));
    form.append('note', document.getElementById('shareNote').value);

    const btn = document.getElementById('shareSendBtn');
    btn.disabled = true;
    fetch(" . json_encode(base_url('announcements')) . " + '/' + currentAnnouncement.id + '/share', {
        method: 'POST', body: form, headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success') throw new Error(data.message || 'Could not share.');
            bootstrap.Modal.getInstance(document.getElementById('shareAnnouncementModal'))?.hide();
            showToast(data.message, 'success');
        })
        .catch(e => { err.textContent = e.message; err.classList.remove('d-none'); })
        .finally(() => { btn.disabled = false; });
}

// Opened from a shared card in chat (?id=…): show that announcement right away.
(function () {
    const openId = " . json_encode((int) ($openId ?? 0)) . ";
    const card = openId ? document.getElementById('announcement-' + openId) : null;
    if (card) setTimeout(() => card.click(), 150);
})();

function previewAnnouncementImage(input) {
    const wrap = document.getElementById('announcementImagePreview');
    const file = input.files && input.files[0];
    if (!file) { wrap.classList.add('d-none'); return; }
    if (file.size > 5 * 1024 * 1024) {
        showToast('The photo must be 5 MB or smaller.', 'danger');
        clearAnnouncementImage();
        return;
    }
    wrap.querySelector('img').src = URL.createObjectURL(file);
    wrap.classList.remove('d-none');
}

function clearAnnouncementImage() {
    document.getElementById('announcementImage').value = '';
    document.getElementById('announcementImagePreview').classList.add('d-none');
}

function openPostAnnouncementModal() {
    const el = document.getElementById('postAnnouncementModal');
    if (el) bootstrap.Modal.getOrCreateInstance(el).show();
}

// The floating megaphone button links here with ?compose=1: open the form straight away.
if (" . json_encode(service('request')->getGet('compose') === '1') . ") {
    openPostAnnouncementModal();
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

// Landing here from a notification's ?id=X link: don't just dump the user on
// the generic list — scroll to that specific announcement, highlight it
// briefly, then open the same detail modal a manual click would.
const requestedAnnouncementId = " . json_encode($requestedId) . ";
if (requestedAnnouncementId) {
    const targetCard = document.getElementById('announcement-' + requestedAnnouncementId);
    if (targetCard) {
        targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        targetCard.classList.add('announcement-highlight');
        setTimeout(() => targetCard.click(), 450);
        setTimeout(() => targetCard.classList.remove('announcement-highlight'), 2800);
    }
}
</script>";
include APPPATH . 'Views/layout/footer.php';
?>

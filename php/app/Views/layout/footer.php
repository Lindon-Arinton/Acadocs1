</main><!-- /#main-content -->

<?php if (hasRole('admin')): ?>
<a href="<?= base_url('announcements?compose=1') ?>" class="fab-announcement" title="New Announcement"
   onclick="if (document.getElementById('postAnnouncementModal')) { event.preventDefault(); openPostAnnouncementModal(); }">
  <i class="bi bi-megaphone-fill"></i>
</a>
<?php endif; ?>

<!-- Expanded chart: clicking any chart opens a large copy here (see openChartModal()) -->
<div class="modal fade" id="chartExpandModal" tabindex="-1" aria-labelledby="chartExpandTitle">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title" id="chartExpandTitle"><i class="bi bi-bar-chart-line me-2"></i><span>Chart</span></h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="chart-expand-canvas-wrap"><canvas id="chartExpandCanvas"></canvas></div>
        <div class="chart-expand-table table-responsive" id="chartExpandTable"></div>
        <div class="chart-expand-source d-none" id="chartExpandSource">
          <i class="bi bi-database"></i>
          <div><strong>Data source</strong><span></span></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="chartExpandDownload"><i class="bi bi-download me-1"></i>Download PNG</button>
        <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Person card: opened by any name rendered through personLink() -->
<div class="modal fade" id="personInfoModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-person-badge me-2"></i>Profile</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="personInfoLoading" class="text-center text-muted py-4 small">
          <span class="spinner-border spinner-border-sm me-2"></span>Loading…
        </div>
        <div id="personInfoBody" class="d-none">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div id="personInfoAvatar" class="person-card-avatar"></div>
            <div>
              <div id="personInfoName" class="fw-bold"></div>
              <div id="personInfoRole" class="small text-muted"></div>
            </div>
          </div>
          <div class="mb-3">
            <div class="small text-muted mb-1"><i class="bi bi-envelope me-1"></i>Email</div>
            <a id="personInfoEmail" href="#" class="small"></a>
          </div>
          <div class="mb-3">
            <div class="small text-muted mb-1"><i class="bi bi-fingerprint me-1"></i>Biometric No. (AC-No)</div>
            <div id="personInfoAcNo" class="small"></div>
          </div>
          <div class="mb-3" id="personInfoAdvisoryWrap">
            <div class="small text-muted mb-1"><i class="bi bi-house-door me-1"></i>Advisory</div>
            <div id="personInfoAdvisory" class="small"></div>
          </div>
          <div id="personInfoTeaching">
            <div class="mb-3">
              <div class="small text-muted mb-1"><i class="bi bi-book me-1"></i>Subjects handled</div>
              <div id="personInfoSubjects"></div>
            </div>
            <div>
              <div class="small text-muted mb-1"><i class="bi bi-people me-1"></i>Sections handled</div>
              <div id="personInfoSections"></div>
            </div>
          </div>
        </div>
        <div id="personInfoError" class="alert alert-danger small mb-0 d-none"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <a id="personInfoChat" href="#" class="btn btn-primary d-none"><i class="bi bi-chat-dots me-1"></i>Chat</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>window.Chart || document.write('<script src="<?= base_url('assets/js/chart.umd.min.js') ?>"><\/script>');</script>

<script>
/* ── Dark mode toggle (persisted; data-bs-theme also drives Bootstrap's
   own dark styling for modals/dropdowns/forms) ──────────────────── */
const THEME_KEY = 'theme';

function syncThemeIcon() {
    const icon = document.getElementById('theme-toggle-icon');
    if (!icon) return;
    const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    icon.className = isDark ? 'bi bi-sun fs-5' : 'bi bi-moon-stars fs-5';
}

function toggleTheme() {
    const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    const next = isDark ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', next);
    localStorage.setItem(THEME_KEY, next);
    syncThemeIcon();

    // Charts bake their colors in at build time — re-render the page so
    // they pick up the new theme's palette.
    applyChartDefaults();
    if (Object.values(Chart.instances).some(c => c.canvas && c.canvas.isConnected)) {
        loadPage(window.location.href, { push: false, scroll: false });
    }
}

syncThemeIcon();

/* ── Sidebar collapse (desktop) ──────────────────────────── */
const COLLAPSED_KEY = 'sidebar_collapsed';
const sidebar = document.getElementById('sidebar');
const topbar  = document.getElementById('topbar');
const colIcon = document.getElementById('collapse-icon');
const collapseBtn = document.getElementById('sidebar-collapse-btn');
const overlay = document.getElementById('sidebar-overlay');
let userSidebarStateSet = false;

function applyCollapsed(on, persist = true) {
    if (!sidebar || !topbar) return;

    sidebar.classList.toggle('collapsed', on);
    topbar.classList.toggle('collapsed', on);
    document.body.classList.toggle('sidebar-collapsed', on);

    if (colIcon) {
        colIcon.className = on ? 'bi bi-chevron-double-right' : 'bi bi-chevron-double-left';
    }

    if (collapseBtn) {
        collapseBtn.setAttribute('aria-expanded', String(!on));
        collapseBtn.setAttribute('title', on ? 'Expand sidebar' : 'Collapse sidebar');
    }

    if (persist) {
        localStorage.setItem(COLLAPSED_KEY, on ? '1' : '0');
    }
}

function toggleCollapse() {
    if (!sidebar) return;
    userSidebarStateSet = true;
    applyCollapsed(!sidebar.classList.contains('collapsed'));
}

function syncSidebarState() {
    if (window.innerWidth < 992) {
        sidebar?.classList.remove('collapsed');
        document.body.classList.remove('sidebar-collapsed');
        topbar?.classList.remove('collapsed');
        if (overlay) overlay.style.display = 'none';
        return;
    }

    const storedValue = localStorage.getItem(COLLAPSED_KEY);
    const shouldCollapse = userSidebarStateSet ? storedValue === '1' : false;
    applyCollapsed(shouldCollapse, false);
}

window.addEventListener('DOMContentLoaded', () => {
    userSidebarStateSet = false;
    syncSidebarState();
});
window.addEventListener('resize', syncSidebarState);

/* ── Mobile sidebar ──────────────────────────────────────── */
function openSidebar() {
    sidebar?.classList.add('mobile-open');
    if (overlay) overlay.style.display = 'block';
}
function closeSidebar() {
    sidebar?.classList.remove('mobile-open');
    if (overlay) overlay.style.display = 'none';
}

/* ── Top bar quick search ────────────────────────────────────
   Matches the sidebar's own links (so it only ever offers pages this
   role can open), plus "search documents / templates for …" shortcuts. */
(function initTopbarSearch() {
    const input   = document.getElementById('topbarSearchInput');
    const results = document.getElementById('topbarSearchResults');
    if (!input || !results) return;

    const pages = [...document.querySelectorAll('#sidebar .nav-link')]
        .map(a => ({ label: a.querySelector('.sidebar-label')?.textContent.trim() || '', href: a.href, icon: a.querySelector('.nav-icon')?.className || 'bi bi-dot' }))
        .filter((p, i, all) => p.label && all.findIndex(o => o.href === p.href) === i);
    let active = 0;

    const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    function items(q) {
        const needle = q.toLowerCase();
        const list = pages.filter(p => p.label.toLowerCase().includes(needle)).slice(0, 6)
            .map(p => ({ ...p, hint: 'Page' }));
        if (input.dataset.docsUrl) list.push({ label: 'Search documents for “' + q + '”', href: input.dataset.docsUrl + '?q=' + encodeURIComponent(q), icon: 'bi bi-folder2-open', hint: 'Documents' });
        list.push({ label: 'Search templates for “' + q + '”', href: input.dataset.templatesUrl + '?q=' + encodeURIComponent(q), icon: 'bi bi-file-earmark-text', hint: 'Templates' });
        return list;
    }
    function render() {
        const q = input.value.trim();
        if (!q) { results.classList.remove('open'); results.innerHTML = ''; return; }
        const list = items(q);
        active = Math.min(active, list.length - 1);
        results.innerHTML = list.map((it, i) =>
            '<a href="' + esc(it.href) + '" class="topbar-search-item' + (i === active ? ' active' : '') + '" role="option">'
            + '<i class="' + esc(it.icon) + '"></i><span>' + esc(it.label) + '</span><small>' + it.hint + '</small></a>').join('');
        results.classList.add('open');
    }
    function go(href) {
        results.classList.remove('open');
        input.value = '';
        input.blur();
        typeof loadPage === 'function' ? loadPage(href) : (location.href = href);
    }
    input.addEventListener('input', () => { active = 0; render(); });
    input.addEventListener('focus', render);
    input.addEventListener('keydown', e => {
        const links = results.querySelectorAll('.topbar-search-item');
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            active = (active + (e.key === 'ArrowDown' ? 1 : -1) + links.length) % Math.max(links.length, 1);
            render();
        } else if (e.key === 'Enter' && links[active]) {
            e.preventDefault();
            go(links[active].href);
        } else if (e.key === 'Escape') {
            results.classList.remove('open');
            input.blur();
        }
    });
    results.addEventListener('click', e => {
        const link = e.target.closest('.topbar-search-item');
        if (!link) return;
        e.preventDefault();
        e.stopPropagation();
        go(link.href);
    });
    document.addEventListener('click', e => { if (!e.target.closest('#topbarSearch')) results.classList.remove('open'); });
    // Ctrl/Cmd + K focuses the search from anywhere.
    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); input.focus(); }
    });
})();

/* ── Notification panel ──────────────────────────────────── */
const notifPanel = document.getElementById('notif-panel');
function toggleNotif(e) {
    e.stopPropagation();
    notifPanel.classList.toggle('open');
}
document.addEventListener('click', () => notifPanel?.classList.remove('open'));

/* ── Mark notification as read on click ──────────────────── */
document.querySelectorAll('.notif-item[data-notif-id]').forEach(item => {
    item.addEventListener('click', () => {
        if (!item.classList.contains('notif-unread')) return;
        item.classList.remove('notif-unread');
        item.querySelector('.notif-dot')?.remove();

        const badge = document.querySelector('.notif-btn .notif-badge');
        if (badge) {
            const remaining = parseInt(badge.textContent || '0', 10) - 1;
            if (remaining > 0) {
                badge.textContent = remaining;
            } else {
                badge.remove();
            }
        }

        fetch('<?= base_url('notifications/') ?>' + item.dataset.notifId + '/read', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            keepalive: true,
        }).catch(() => {});
    });
});

/* ── Toast helper ────────────────────────────────────────── */
function showToast(message, type = 'success') {
    const icons = { success: 'bi-check-circle-fill', danger: 'bi-x-circle-fill', warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
    const el = document.createElement('div');
    el.className = 'toast align-items-center border-0';
    el.style.cssText = 'min-width:280px';
    el.innerHTML = `<div class="d-flex" style="background:${type==='success'?'#f0fdf4':type==='danger'?'#fef2f2':'#fffbeb'};border-radius:10px;padding:.75rem 1rem;border:1px solid ${type==='success'?'#bbf7d0':type==='danger'?'#fecaca':'#fde68a'}">
        <div class="toast-body d-flex align-items-center gap-2 p-0" style="color:${type==='success'?'#166534':type==='danger'?'#991b1b':'#92400e'}">
          <i class="bi ${icons[type]||icons.info}"></i>${message}
        </div>
        <button type="button" class="btn-close ms-auto me-0" data-bs-dismiss="toast"></button>
    </div>`;
    let container = document.getElementById('toast-container');
    if (!container) {
        container = Object.assign(document.createElement('div'), {
            id: 'toast-container',
            className: 'toast-container position-fixed bottom-0 end-0 p-3',
        });
        container.style.zIndex = 9999;
        document.body.appendChild(container);
    }
    container.appendChild(el);
    new bootstrap.Toast(el, { delay: 3500 }).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}

/* ── Number animation helper ─────────────────────────────── */
function animateCounter(el, target, duration = 1200) {
    let start = 0;
    const step = target / (duration / 16);
    const timer = setInterval(() => {
        start = Math.min(start + step, target);
        el.textContent = Math.floor(start).toLocaleString();
        if (start >= target) clearInterval(timer);
    }, 16);
}
document.querySelectorAll('[data-counter]').forEach(el => {
    animateCounter(el, parseInt(el.dataset.counter));
});

/* ── AJAX form handler (SweetAlert2 confirm / loading / success / error) ── */
const AJAX_ACTION_LABELS = {
    add:       { title: 'Add this record?',    confirmText: 'Yes, add it',    icon: 'question' },
    update:    { title: 'Save these changes?', confirmText: 'Yes, save',      icon: 'question' },
    reset_pw:  { title: 'Reset this password?',confirmText: 'Yes, reset it',  icon: 'warning', danger: true },
    delete:    { title: 'Are you sure?',       confirmText: 'Yes, delete it', icon: 'warning', danger: true },
    default:   { title: 'Proceed with this action?', confirmText: 'Yes, proceed', icon: 'question' },
};

function handleAjaxFormResult(data) {
    if (data.status === 'success') {
        return Swal.fire({
            icon: 'success',
            title: 'Success',
            text: data.message || 'Action completed successfully.',
            timer: 1600,
            showConfirmButton: false,
        }).then(() => {
            if (data.redirect) {
                loadPage(data.redirect, { scroll: true });
            } else {
                loadPage(window.location.href, { push: false, scroll: false });
            }
        });
    }
    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Something went wrong. Please try again.' });
}

/* ── Upload motion overlay controls (see app.css for the animation) ── */
function setUploadProgress(pct) {
    const overlay = document.getElementById('upload-loading-overlay');
    if (!overlay) return;
    overlay.querySelector('.upload-progress-fill').style.width = pct + '%';
    overlay.querySelector('.upload-progress-pct').textContent = Math.round(pct) + '%';
}
function showUploadOverlay(fileCount) {
    const overlay = document.getElementById('upload-loading-overlay');
    if (!overlay) return;
    const countLabel = overlay.querySelector('.upload-loading-file-count');
    if (countLabel) countLabel.textContent = fileCount > 1 ? 's (' + fileCount + ' files)' : '';
    setUploadProgress(0);
    overlay.classList.add('active');
}
function hideUploadOverlay() {
    document.getElementById('upload-loading-overlay')?.classList.remove('active');
}

function ajaxFormSubmit(form) {
    const formData  = new FormData(form);
    const fileCount = [...form.querySelectorAll('input[type="file"]')]
        .reduce((sum, input) => sum + (input.files ? input.files.length : 0), 0);

    // A plain confirm/save action gets the quick SweetAlert2 spinner; an
    // actual file upload gets the motion overlay wired to real XHR progress
    // instead, since those can take long enough that users need to see it
    // moving rather than a generic "Processing..." spinner.
    if (fileCount === 0) {
        Swal.fire({
            title: form.dataset.loadingText || 'Processing...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
        });

        fetch(form.getAttribute('action'), {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(res => res.json().catch(() => ({ status: 'error', message: 'Unexpected server response.' })))
            .then(handleAjaxFormResult)
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server. Please check your connection and try again.' });
            });
        return;
    }

    showUploadOverlay(fileCount);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', form.getAttribute('action'));
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) setUploadProgress((e.loaded / e.total) * 100);
    });

    xhr.addEventListener('load', () => {
        hideUploadOverlay();
        let data;
        try { data = JSON.parse(xhr.responseText); } catch { data = { status: 'error', message: 'Unexpected server response.' }; }
        handleAjaxFormResult(data);
    });

    xhr.addEventListener('error', () => {
        hideUploadOverlay();
        Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server. Please check your connection and try again.' });
    });

    xhr.send(formData);
}

document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form.classList.contains('ajax-form')) return;
    e.preventDefault();

    const actionKey = form.dataset.confirmAction || form.querySelector('[name="action"]')?.value || 'default';
    const cfg = AJAX_ACTION_LABELS[actionKey] || AJAX_ACTION_LABELS.default;

    if (form.dataset.syPrompt) {
        promptSchoolYear(form, cfg);
        return;
    }

    Swal.fire({
        title: form.dataset.confirmTitle || cfg.title,
        text: form.dataset.confirmText || '',
        icon: form.dataset.confirmIcon || cfg.icon,
        showCancelButton: true,
        confirmButtonText: cfg.confirmText,
        cancelButtonText: 'Cancel',
        confirmButtonColor: cfg.danger ? '#dc2626' : '#800000',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed) ajaxFormSubmit(form);
    });
});

/* ── School-year prompt for forms marked [data-sy-prompt] ────
   The confirm dialog doubles as the year question: two 4-digit boxes with a
   fixed dash between them ([2025]–[2026]). Filling the first box pre-fills
   the second with the next year and jumps to it. The chosen year is written
   into the form's hidden input named by data-sy-prompt before submitting. */
function promptSchoolYear(form, cfg) {
    const target = form.querySelector('[name="' + form.dataset.syPrompt + '"]');

    // A Bootstrap modal traps focus inside itself, which would pull every
    // click/keystroke back out of the SweetAlert boxes — so step out of the
    // modal while asking, and bring it back (file still chosen) on Cancel.
    const modalEl = form.closest('.modal');
    const modal   = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
    if (modal) modal.hide();

    const box = 'class="swal2-input sy-box" inputmode="numeric" maxlength="4" placeholder="YYYY" autocomplete="off"'
              + ' style="width:6.5rem;margin:0;text-align:center;font-size:1.25rem;letter-spacing:.1em;"';

    Swal.fire({
        title: form.dataset.syPromptTitle || 'What school year is this data for?',
        html: '<div style="display:flex;align-items:center;justify-content:center;gap:.6rem;margin-top:.5rem;">'
            + '<input id="syStart" ' + box + '>'
            + '<span style="font-size:1.6rem;font-weight:600;">&ndash;</span>'
            + '<input id="syEnd" ' + box + '>'
            + '</div>'
            + (form.dataset.confirmText ? '<p class="text-muted small mt-3 mb-0">' + form.dataset.confirmText + '</p>' : ''),
        icon: form.dataset.confirmIcon || cfg.icon,
        showCancelButton: true,
        confirmButtonText: cfg.confirmText,
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#800000',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        focusConfirm: false,
        didOpen: () => {
            const start = document.getElementById('syStart');
            const end   = document.getElementById('syEnd');
            [start, end].forEach(input => input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(0, 4);
            }));
            start.addEventListener('input', () => {
                if (start.value.length === 4) {
                    end.value = String(parseInt(start.value, 10) + 1);
                    end.focus();
                    end.select();
                }
            });
            end.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && end.value === '') start.focus();
            });
            start.focus();
            // Bootstrap may hand focus back to the modal's trigger button once
            // its hide transition ends — reclaim it for the year box.
            if (modalEl) modalEl.addEventListener('hidden.bs.modal', () => {
                if (Swal.isVisible() && !end.contains(document.activeElement)) start.focus();
            }, { once: true });
        },
        preConfirm: () => {
            const year = document.getElementById('syStart').value + '-' + document.getElementById('syEnd').value;
            if (!isValidSchoolYear(year)) {
                Swal.showValidationMessage('Enter consecutive years, e.g. 2025 – 2026.');
                return false;
            }
            return year;
        },
    }).then(result => {
        if (!result.isConfirmed) {
            if (modal) modal.show();
            return;
        }
        target.value = result.value;
        ajaxFormSubmit(form);
    });
}

/* ── Required / optional field markers ──────────────────────
   Every .form-label gets a red * when its field is required, or a muted
   "(optional)" when it isn't — derived from the field's own `required`
   attribute, so forms never need hand-written markers. A select with no
   blank option always has a value, so it counts as required. Re-run on
   AJAX page swaps and whenever a modal opens (some forms toggle `required`
   on the fly). A label can opt out with data-no-req-mark. */
const REQ_MARK_SKIP_TYPES = ['hidden', 'checkbox', 'radio', 'button', 'submit', 'reset'];

function reqMarkFields(label) {
    if (label.htmlFor) {
        const target = document.getElementById(label.htmlFor);
        return target ? [target] : [];
    }
    return Array.from(label.parentElement.querySelectorAll('input, select, textarea')).filter(f =>
        !REQ_MARK_SKIP_TYPES.includes((f.type || '').toLowerCase())
        && !f.disabled
        && !(f.readOnly && !f.required) // display-only value
    );
}

function reqMarkIsRequired(field) {
    if (field.required) return true;
    if (field.tagName === 'SELECT' && !field.multiple) {
        return field.options.length > 0 && !Array.from(field.options).some(o => o.value === '');
    }
    return false;
}

function applyRequiredMarkers(scope) {
    (scope || document).querySelectorAll('label.form-label').forEach(label => {
        if (label.hasAttribute('data-no-req-mark')) return;
        label.querySelectorAll('.req-mark').forEach(el => el.remove());

        const fields = reqMarkFields(label);
        if (!fields.length) return;
        if (/\(optional\)/i.test(label.textContent)) return; // already annotated by hand

        const mark = document.createElement('span');
        if (fields.some(reqMarkIsRequired)) {
            mark.className = 'req-mark req-mark-required';
            mark.textContent = ' *';
            mark.title = 'Required';
        } else {
            mark.className = 'req-mark req-mark-optional';
            mark.textContent = ' (optional)';
        }
        label.appendChild(mark);
    });
}

document.addEventListener('DOMContentLoaded', () => applyRequiredMarkers());
document.addEventListener('show.bs.modal', e => applyRequiredMarkers(e.target));

/* ── Safety net for stuck modal backdrops ────────────────────
   If a modal ever ends up shown twice (a double click, a page script
   re-running after AJAX navigation…), closing it can leave an orphan
   .modal-backdrop behind that greys out and blocks the whole page. Once
   the last open modal is gone, clear any leftover backdrop and body lock. */
// A modal mid-opening (e.g. Tasks swaps one modal for another on close) has
// no .show class yet — mark it so its fresh backdrop isn't swept away.
document.addEventListener('show.bs.modal', e => { e.target.dataset.opening = '1'; });
document.addEventListener('shown.bs.modal', e => { delete e.target.dataset.opening; });
document.addEventListener('hidden.bs.modal', function (e) {
    delete e.target.dataset.opening;
    setTimeout(() => {
        if (document.querySelector('.modal.show, .modal[data-opening]')) return;
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }, 50);
});

/* ── Person card (names rendered through personLink()) ──────
   Delegated on document so names on AJAX-loaded pages and in JS-built
   lists (e.g. the task detail modal) work too. Details are fetched on
   click from /people/{id}. */
const ROLE_LABELS = { admin: 'Admin', teacher: 'Teacher', adas: 'ADAS' };

/** JS twin of the personLink() PHP helper, for lists built client-side. */
function personLinkHtml(id, name) {
    const a = document.createElement(id ? 'a' : 'span');
    a.textContent = name || '';
    if (id) {
        a.href = '#';
        a.className = 'person-link';
        a.dataset.personId = id;
    }
    return a.outerHTML;
}

/** JS twin of submissionTimingBadge(): "On time" / "Late · 2d 3h" pill. */
function timingBadgeHtml(late, lateBy) {
    const span = document.createElement('span');
    span.className = 'timing-badge ' + (late ? 'timing-late' : 'timing-ontime');
    span.innerHTML = late ? '<i class="bi bi-alarm-fill me-1"></i>' : '<i class="bi bi-check-circle-fill me-1"></i>';
    span.append(late ? 'Late · ' + (lateBy || '') : 'On time');
    return span.outerHTML;
}

/** Annotate button (reviewers) + "Marked up" link for one submitted file in a JS-built list. */
function submissionFileExtrasHtml(fileId, annotated, canAnnotate) {
    const base = '<?= base_url('task-submissions/') ?>' + encodeURIComponent(fileId);
    let html = '';
    if (canAnnotate) {
        html += '<a class="btn btn-sm btn-outline-maroon" href="' + base + '/annotate" target="_blank" rel="noopener" title="Annotate (draw / write notes)"><i class="bi bi-pencil-square"></i></a>';
    }
    if (annotated) {
        html += '<a class="timing-badge annot-badge text-decoration-none align-self-center" href="' + base + '/annotated" target="_blank" rel="noopener" title="Open the marked-up copy"><i class="bi bi-pencil-fill me-1"></i>Marked up</a>';
    }
    return html;
}

function personCardChips(items, emptyText) {
    if (!items.length) {
        return '<span class="small text-muted">' + emptyText + '</span>';
    }
    return items.map(item => {
        const chip = document.createElement('span');
        chip.className = 'person-chip';
        chip.textContent = item;
        return chip.outerHTML;
    }).join('');
}

function showPersonCard(id) {
    const modalEl = document.getElementById('personInfoModal');
    const $ = sel => modalEl.querySelector(sel);

    $('#personInfoLoading').classList.remove('d-none');
    $('#personInfoBody').classList.add('d-none');
    $('#personInfoError').classList.add('d-none');
    $('#personInfoChat').classList.add('d-none');
    bootstrap.Modal.getOrCreateInstance(modalEl).show();

    fetch('<?= base_url('people') ?>/' + encodeURIComponent(id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success') throw new Error(data.message || 'Could not load this profile.');
            const p = data.person;

            const avatar = $('#personInfoAvatar');
            avatar.innerHTML = '';
            if (p.photo) {
                const img = document.createElement('img');
                img.src = p.photo;
                img.alt = '';
                avatar.appendChild(img);
            } else {
                avatar.textContent = (p.name || '?').charAt(0).toUpperCase();
            }

            $('#personInfoName').textContent = p.name;
            $('#personInfoRole').textContent = [ROLE_LABELS[p.role] || p.role, p.position].filter(Boolean).join(' · ');

            const email = $('#personInfoEmail');
            email.textContent = p.email || 'No email on record';
            email.href = p.email ? 'mailto:' + p.email : '#';

            const acNo = $('#personInfoAcNo');
            acNo.textContent = p.acNo ? 'AC-' + p.acNo : 'Not linked yet';
            acNo.classList.toggle('text-muted', !p.acNo);

            $('#personInfoAdvisoryWrap').classList.toggle('d-none', !p.advisory);
            $('#personInfoAdvisory').textContent = p.advisory || '';

            const isTeacher = p.role === 'teacher';
            $('#personInfoTeaching').classList.toggle('d-none', !isTeacher);
            if (isTeacher) {
                $('#personInfoSubjects').innerHTML = personCardChips(p.subjects, 'No subject load on record.');
                $('#personInfoSections').innerHTML = personCardChips(p.sections, 'No sections on record.');
            }

            const chat = $('#personInfoChat');
            chat.href = p.chatUrl;
            chat.classList.toggle('d-none', p.isSelf);

            $('#personInfoLoading').classList.add('d-none');
            $('#personInfoBody').classList.remove('d-none');
        })
        .catch(err => {
            $('#personInfoLoading').classList.add('d-none');
            const box = $('#personInfoError');
            box.textContent = err.message || 'Could not load this profile.';
            box.classList.remove('d-none');
        });
}

document.addEventListener('click', function (e) {
    const link = e.target.closest('.person-link[data-person-id]');
    if (link) {
        e.preventDefault(); // also tells the AJAX-nav click handler below to ignore it
        showPersonCard(link.dataset.personId);
        return;
    }
    // Leaving for the chat page: close the card first so its backdrop
    // doesn't linger over the AJAX-swapped page.
    if (e.target.closest('#personInfoChat')) {
        bootstrap.Modal.getInstance(document.getElementById('personInfoModal'))?.hide();
    }
});

/* ── Logout confirmation ─────────────────────────────────── */
function confirmLogout(e, link) {
    e.preventDefault();
    Swal.fire({
        title: 'Log out?',
        text: 'You’ll need to sign in again to continue.',
        icon: 'question',
        iconHtml: '<i class="bi bi-box-arrow-right"></i>',
        showCancelButton: true,
        confirmButtonText: 'Yes, log out',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        buttonsStyling: false,
        // Styled in app.css (.logout-swal*)
        customClass: {
            popup: 'logout-swal',
            icon: 'logout-swal-icon',
            title: 'logout-swal-title',
            htmlContainer: 'logout-swal-text',
            actions: 'logout-swal-actions',
            confirmButton: 'logout-swal-confirm',
            cancelButton: 'logout-swal-cancel',
        },
    }).then(result => {
        if (result.isConfirmed) window.location.href = link.href;
    });
    return false;
}

/* ── Live search (debounced auto-submit, no need to press Enter) ── */
function initLiveSearch(inputId, formId, delay = 500) {
    const input = document.getElementById(inputId);
    const form  = document.getElementById(formId);
    if (!input || !form) return;
    let timer;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const query = new URLSearchParams(new FormData(form)).toString();
            loadPage(form.getAttribute('action') + (query ? '?' + query : ''), {
                quiet: true,
                // The swap replaces this input with a fresh copy: hand it back
                // the focus, the caret, and anything typed while the request
                // was in flight (which then schedules its own search).
                afterSwap() {
                    const fresh = document.getElementById(inputId);
                    if (!fresh || fresh === input) return;
                    const typedMeanwhile = fresh.value !== input.value;
                    fresh.value = input.value;
                    fresh.focus();
                    fresh.setSelectionRange(input.selectionStart, input.selectionEnd);
                    if (typedMeanwhile) fresh.dispatchEvent(new Event('input'));
                },
            });
        }, delay);
    });
}

/* ── Instant client-side table filter (no server round-trip, filters rows
   already on the page as you type) ── */
function initInstantFilter(inputId, tableId, options = {}) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    const tbody = table?.tBodies[0];
    if (!input || !tbody) return;

    const rows = Array.from(tbody.querySelectorAll(':scope > tr'));
    const columnCount = table.querySelectorAll('thead tr:last-child th').length || 1;

    let emptyRow = null;
    function getEmptyRow() {
        if (!emptyRow) {
            emptyRow = document.createElement('tr');
            emptyRow.className = 'instant-filter-empty-row';
            const td = document.createElement('td');
            td.colSpan = columnCount;
            td.className = 'text-center text-muted py-4';
            td.textContent = options.emptyText || 'No matching results.';
            emptyRow.appendChild(td);
        }
        return emptyRow;
    }

    function applyFilter() {
        const term = input.value.trim().toLowerCase();
        let visibleCount = 0;

        rows.forEach(row => {
            const matches = term === '' || row.textContent.toLowerCase().includes(term);
            row.classList.toggle('d-none', !matches);
            if (matches) visibleCount++;
        });

        const empty = getEmptyRow();
        if (visibleCount === 0 && rows.length > 0) {
            if (!empty.isConnected) tbody.appendChild(empty);
        } else if (empty.isConnected) {
            empty.remove();
        }

        if (options.counterId) {
            const counter = document.getElementById(options.counterId);
            if (counter) {
                counter.textContent = options.counterLabel ? options.counterLabel(visibleCount) : visibleCount;
            }
        }
    }

    input.addEventListener('input', applyFilter);
}

/* ── Maroon date picker (replaces native <input type=date>) ── */
function initMaroonDatePicker(root) {
    const display     = root.querySelector('.maroon-dp-display');
    const hidden       = root.querySelector('input[type="hidden"]');
    const monthLabel   = root.querySelector('.maroon-dp-month-label');
    const grid         = root.querySelector('.maroon-dp-grid');
    const monthNames   = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    let minDate  = root.dataset.min ? new Date(root.dataset.min + 'T00:00:00') : null;
    let view     = hidden.value ? new Date(hidden.value + 'T00:00:00') : new Date();
    let selected = hidden.value ? new Date(hidden.value + 'T00:00:00') : null;

    function pad(n) { return String(n).padStart(2, '0'); }
    function fmt(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function fmtDisplay(d) { return monthNames[d.getMonth()].slice(0, 3) + ' ' + d.getDate() + ', ' + d.getFullYear(); }
    function sameDay(a, b) {
        return a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
    }

    function render() {
        monthLabel.textContent = monthNames[view.getMonth()] + ' ' + view.getFullYear();
        const firstDay    = new Date(view.getFullYear(), view.getMonth(), 1).getDay();
        const daysInMonth = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();
        const today       = new Date();
        let html = '';
        for (let i = 0; i < firstDay; i++) html += '<span class="is-empty">.</span>';
        for (let d = 1; d <= daysInMonth; d++) {
            const cellDate = new Date(view.getFullYear(), view.getMonth(), d);
            const disabled = minDate && cellDate < minDate;
            const classes  = [];
            if (sameDay(cellDate, today)) classes.push('is-today');
            if (sameDay(cellDate, selected)) classes.push('is-selected');
            html += '<button type="button" class="' + classes.join(' ') + '" ' + (disabled ? 'disabled' : '')
                + ' data-date="' + fmt(cellDate) + '">' + d + '</button>';
        }
        grid.innerHTML = html;
    }

    function selectDate(value, { autosubmit = true } = {}) {
        hidden.value = value;
        selected = value ? new Date(value + 'T00:00:00') : null;
        view = selected || new Date();
        display.value = selected ? fmtDisplay(selected) : '';
        root.classList.remove('open');
        render();
        hidden.dispatchEvent(new Event('change', { bubbles: true }));
        if (autosubmit && root.hasAttribute('data-autosubmit')) {
            root.closest('form')?.requestSubmit();
        }
    }

    if (selected) display.value = fmtDisplay(selected);
    render();

    display.addEventListener('click', (e) => {
        e.stopPropagation();
        root.classList.toggle('open');
    });
    root.querySelectorAll('.maroon-dp-nav').forEach(btn => {
        btn.addEventListener('click', () => {
            view = new Date(view.getFullYear(), view.getMonth() + parseInt(btn.dataset.dir, 10), 1);
            render();
        });
    });
    grid.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-date]');
        if (!btn || btn.disabled) return;
        selectDate(btn.dataset.date);
    });
    document.addEventListener('click', (e) => {
        if (!root.contains(e.target)) root.classList.remove('open');
    });

    /* Exposed so pages can reset/preset the picker (e.g. reopening a "New" modal). */
    root.maroonDpSetValue = (value) => selectDate(value, { autosubmit: false });
    root.maroonDpSetMin = (value) => { minDate = value ? new Date(value + 'T00:00:00') : null; render(); };
}
document.querySelectorAll('.maroon-dp').forEach(initMaroonDatePicker);

/* ── Maroon select (replaces native <select> with a custom rounded,
   maroon-highlighted dropdown) ── */
function initMaroonSelect(root) {
    // Guards against double-init: some pages (e.g. Performance Analytics'
    // grade filter) explicitly re-run this after their own partial AJAX
    // refresh, on top of the page-wide pass this already got at load time.
    if (root.dataset.maroonSelectInit) return;
    root.dataset.maroonSelectInit = '1';

    const select  = root.querySelector('select');
    const display = root.querySelector('.maroon-select-display');
    const label   = root.querySelector('.maroon-select-label');
    const panel   = root.querySelector('.maroon-select-panel');
    if (!select || !display || !panel) return;

    select.classList.add('maroon-select-native');

    // Moved to <body> (see the CSS comment on .maroon-select-panel for why)
    // and positioned in the viewport via getBoundingClientRect(), so no
    // ancestor's stacking context or overflow can ever clip it or swallow
    // clicks meant for it. Tagged with its wrapper so a later cleanup pass
    // can tell whether this panel is still owned by a live widget (see
    // removeOrphanedMaroonSelectPanels()) instead of removing all of them.
    panel._maroonSelectRoot = root;
    document.body.appendChild(panel);

    function renderOptions() {
        panel.innerHTML = '';
        [...select.options].forEach((opt, i) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'maroon-select-option' + (i === select.selectedIndex ? ' is-selected' : '');
            item.textContent = opt.textContent;
            item.disabled = opt.disabled;
            item.addEventListener('click', () => {
                if (select.selectedIndex !== i) {
                    select.selectedIndex = i;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }
                closePanel();
                sync();
            });
            panel.appendChild(item);
        });
    }

    function sync() {
        const opt = select.options[select.selectedIndex];
        label.textContent = opt ? opt.textContent : '';
        renderOptions();
    }

    function positionPanel() {
        const r = display.getBoundingClientRect();
        panel.style.top = (r.bottom + 6) + 'px';
        panel.style.left = r.left + 'px';
        panel.style.minWidth = r.width + 'px';
    }

    function openPanel() {
        positionPanel();
        panel.style.display = 'block';
        root.classList.add('open');
    }

    function closePanel() {
        panel.style.display = 'none';
        root.classList.remove('open');
    }

    display.addEventListener('click', (e) => {
        e.stopPropagation();
        if (select.disabled) return;
        panel.style.display === 'block' ? closePanel() : openPanel();
    });
    document.addEventListener('click', (e) => {
        if (!root.contains(e.target) && !panel.contains(e.target)) closePanel();
    });
    // A "fixed" panel doesn't track the trigger button while the page
    // scrolls underneath it, so just close it instead of drifting out of
    // place; a resize can shift the button too, so reposition for that.
    // Scrolling the panel's own option list (a long one, e.g. the Users
    // department filter) must not count — the capture listener sees it too.
    window.addEventListener('scroll', (e) => {
        if (panel.style.display === 'block' && !panel.contains(e.target)) closePanel();
    }, true);
    window.addEventListener('resize', () => { if (panel.style.display === 'block') positionPanel(); });
    select.addEventListener('change', sync);

    sync();

    /* Exposed in case a page ever needs to force a re-render (e.g. after
       toggling which options are disabled). */
    root.maroonSelectSync = sync;
}
document.querySelectorAll('.maroon-select').forEach(initMaroonSelect);

/* ── AJAX page navigation (no full reload / no white flash) ──
   Intercepts internal links + GET filter forms, fetches the target
   page, swaps #main-content in place, and re-runs that page's own
   script. History (back/forward) is kept in sync via pushState. ── */
const ajaxProgressBar = document.getElementById('ajax-progress');
let ajaxNavToken = 0;

function startAjaxProgress() {
    if (!ajaxProgressBar) return;
    ajaxProgressBar.style.transition = 'none';
    ajaxProgressBar.style.width = '0%';
    void ajaxProgressBar.offsetWidth;
    ajaxProgressBar.style.transition = 'width .3s ease, opacity .25s ease';
    ajaxProgressBar.classList.add('active');
    ajaxProgressBar.style.width = '70%';
}
/* Full-screen motion overlay layers on top of the thin bar above, but only
   for loads slow enough to notice — delayed so a fast swap never flickers it. */
let pageLoadingShowTimer = null;
function showPageLoadingOverlay() {
    clearTimeout(pageLoadingShowTimer);
    pageLoadingShowTimer = setTimeout(() => {
        document.getElementById('page-loading-overlay')?.classList.add('active');
    }, 150);
}
function hidePageLoadingOverlay() {
    clearTimeout(pageLoadingShowTimer);
    document.getElementById('page-loading-overlay')?.classList.remove('active');
}

function finishAjaxProgress() {
    if (!ajaxProgressBar) return;
    ajaxProgressBar.style.width = '100%';
    setTimeout(() => {
        ajaxProgressBar.classList.remove('active');
        ajaxProgressBar.style.width = '0%';
    }, 250);
}

function reinitPageWidgets(scope) {
    scope.querySelectorAll('.maroon-dp').forEach(initMaroonDatePicker);
    scope.querySelectorAll('.maroon-select').forEach(initMaroonSelect);
    scope.querySelectorAll('[data-counter]').forEach(el => animateCounter(el, parseInt(el.dataset.counter, 10)));
    applyRequiredMarkers(scope);
}

/*
 * Re-runs a page's inline script after it has already run once before in
 * this browser session. Top-level `let`/`const` would throw "already been
 * declared" the second time a page is (re)visited via AJAX navigation
 * (they share one global lexical scope across every injected <script>), so
 * we downgrade top-level declarations to `var`, which safely re-assigns
 * instead of erroring. Functions/vars declared this way stay reachable
 * from inline onclick="..." handlers exactly like a normal <script> would.
 */
function runPageScript(code, { replayReady = true } = {}) {
    if (!code.trim()) return;

    const safeCode = code.replace(/(^|[;{\n]\s*)(let|const)(\s+)/g, '$1var$3');
    const script = document.createElement('script');
    script.textContent = safeCode;
    document.body.appendChild(script);
    script.remove();

    // Some pages wire things up inside DOMContentLoaded; that event only
    // fires once natively, so replay it manually for re-injected scripts.
    // (Not on the initial full load: the native event is still to come.)
    if (replayReady) {
        document.dispatchEvent(new Event('DOMContentLoaded'));
        window.dispatchEvent(new Event('DOMContentLoaded'));
    }
}

/* The page's $extraScript lives in an inert <template id="page-extra-script">. */
function pageExtraScriptCode(doc) {
    const holder = doc.getElementById('page-extra-script');
    const script = holder && (holder.content || holder).querySelector('script');
    return script ? script.textContent : '';
}

function updateActiveNavLinks(pathname) {
    document.querySelectorAll('#sidebar .nav-link').forEach(link => {
        link.classList.toggle('active', link.pathname === pathname);
    });
}

function isAjaxNavExempt(url) {
    // "template" (singular) covers file-download endpoints like
    // enrollment-kpis/template and performance/mps/template — not the
    // plural /templates list page, which is a normal AJAX-navigable view.
    // "annotate"/"annotated" are the full-screen annotator and its saved PDF.
    return /\/(download|logout|login|template)(\/|$|\?)/.test(url.pathname) || /\/(file|preview|annotate|annotated)(\/|$|\?)/.test(url.pathname);
}

/*
 * Any Bootstrap modal open at the time of a content swap needs to be torn
 * down explicitly: bootstrap.Modal appends its .modal-backdrop straight to
 * <body> (outside #main-content) and locks scrolling via a class + inline
 * style on <body>. If the modal's own element gets wiped out from under it
 * by an innerHTML swap, that backdrop/lock is orphaned forever, leaving the
 * whole page gray and unclickable.
 */
function closeAnyOpenModals() {
    document.querySelectorAll('.modal.show').forEach(el => {
        bootstrap.Modal.getInstance(el)?.dispose();
    });
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
}

/*
 * initMaroonSelect() (see above) detaches each dropdown's panel to <body>
 * so it can't be clipped by an ancestor's stacking context — which means an
 * innerHTML swap (full page nav, or a page's own partial AJAX refresh) can
 * leave panels behind, orphaned, if their wrapper was inside the replaced
 * subtree. Only removes panels whose wrapper is actually gone — a partial
 * refresh (e.g. Performance Analytics' grade filter) replaces just one
 * widget's markup, and the other widgets' panels are still very much in use.
 */
function removeOrphanedMaroonSelectPanels() {
    document.querySelectorAll('body > .maroon-select-panel').forEach(el => {
        if (!el._maroonSelectRoot || !document.contains(el._maroonSelectRoot)) {
            el.remove();
        }
    });
}

// quiet: an in-place refresh (live search) — no overlay, no entry animation,
// no scroll jump, and the URL is replaced instead of pushed. afterSwap runs
// once the new content and its page script are in place.
function loadPage(url, { push = true, scroll = true, quiet = false, afterSwap = null } = {}) {
    const target = new URL(url, window.location.href);
    if (target.origin !== window.location.origin || isAjaxNavExempt(target)) {
        window.location.href = target.href;
        return;
    }

    const token = ++ajaxNavToken;
    const main = document.getElementById('main-content');
    startAjaxProgress();
    if (!quiet) showPageLoadingOverlay();

    fetch(target.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(res => {
            if (!res.ok) throw new Error('Failed to load page');
            return res.text().then(html => ({ html, finalUrl: res.url }));
        })
        .then(({ html, finalUrl }) => {
            if (token !== ajaxNavToken || !main) return;

            const doc = new DOMParser().parseFromString(html, 'text/html');
            const newMain = doc.getElementById('main-content');
            if (!newMain) {
                // Not an app page (e.g. a file download that slipped past
                // isAjaxNavExempt) — clear the loading state before falling
                // back, or it's stuck on screen forever since this tab never
                // actually navigates away for a Content-Disposition response.
                finishAjaxProgress();
                hidePageLoadingOverlay();
                window.location.href = finalUrl;
                return;
            }

            closeAnyOpenModals();
            Object.values(Chart.instances).forEach(c => { if (main.contains(c.canvas)) c.destroy(); });
            main.innerHTML = newMain.innerHTML;
            // Only after the swap: this is what actually detaches the old
            // wrappers from the document, which is what tells the cleanup
            // which body-level panels are truly orphaned vs. still in use.
            removeOrphanedMaroonSelectPanels();
            if (!quiet) {
                main.classList.remove('animate-in');
                void main.offsetWidth;
                main.classList.add('animate-in');
            }

            if (doc.title) document.title = doc.title;

            const finalPath = new URL(finalUrl, window.location.origin).pathname;
            updateActiveNavLinks(finalPath);

            runPageScript(pageExtraScriptCode(doc));
            reinitPageWidgets(main);

            if (quiet) history.replaceState({ ajaxNav: true }, '', finalUrl);
            else if (push) history.pushState({ ajaxNav: true }, '', finalUrl);
            if (target.hash) {
                const hashTarget = document.getElementById(target.hash.slice(1));
                if (hashTarget) hashTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else if (scroll && !quiet) {
                window.scrollTo({ top: 0, behavior: 'auto' });
            }
            closeSidebar();
            finishAjaxProgress();
            hidePageLoadingOverlay();
            if (afterSwap) afterSwap();
        })
        .catch(err => {
            console.error('AJAX navigation failed, falling back to a full page load:', err);
            hidePageLoadingOverlay();
            window.location.href = target.href;
        });
}

document.addEventListener('click', function (e) {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

    const link = e.target.closest('a[href]');
    if (!link || link.target === '_blank' || link.hasAttribute('download') || link.dataset.noAjax !== undefined) return;

    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) return;
    if (link.hasAttribute('onclick')) return; // has its own navigation handling (e.g. logout confirm)

    let url;
    try { url = new URL(href, window.location.href); } catch (err) { return; }
    if (url.origin !== window.location.origin) return;

    e.preventDefault();
    if (url.pathname === window.location.pathname && url.search === window.location.search) {
        if (url.hash) {
            const hashTarget = document.getElementById(url.hash.slice(1));
            if (hashTarget) hashTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        return;
    }
    loadPage(url.href);
});

document.addEventListener('submit', function (e) {
    const form = e.target;
    if (form.classList.contains('ajax-form') || form.id === 'sendForm') return;
    if ((form.getAttribute('method') || 'get').toLowerCase() !== 'get') return;
    if (form.target === '_blank' || form.dataset.noAjax !== undefined) return;

    e.preventDefault();
    const params = new URLSearchParams(new FormData(form));
    if (e.submitter && e.submitter.name) params.set(e.submitter.name, e.submitter.value);
    const query = params.toString();
    loadPage(form.getAttribute('action') + (query ? '?' + query : ''));
});

window.addEventListener('popstate', () => loadPage(window.location.href, { push: false }));

/* ── School year inputs (.sy-input) ──────────────────────────
   Digits only, dash auto-inserted (YYYY-YYYY), second year = first + 1.
   Delegated on document so it also covers AJAX-loaded pages. A form with a
   hidden year field marks it [data-sy-hidden] to have it checked too. The
   server re-validates every one of these. */
const SY_MESSAGE = 'School year must be YYYY-YYYY with consecutive years (e.g. 2026-2027). Letters are not allowed.';

function formatSchoolYear(raw) {
    const digits = String(raw).replace(/\D/g, '').slice(0, 8);
    return digits.length > 4 ? digits.slice(0, 4) + '-' + digits.slice(4) : digits;
}

function isValidSchoolYear(v) {
    const m = /^(\d{4})-(\d{4})$/.exec(v);
    return !!m && parseInt(m[2], 10) === parseInt(m[1], 10) + 1;
}

function validateSchoolYearInput(input) {
    const ok = isValidSchoolYear(input.value);
    input.setCustomValidity(ok ? '' : SY_MESSAGE);
    input.classList.toggle('is-invalid', !ok && input.value !== '');
    return ok;
}

document.addEventListener('keydown', function (e) {
    if (!e.target.classList || !e.target.classList.contains('sy-input')) return;
    if (e.ctrlKey || e.metaKey || e.altKey || e.key.length !== 1) return; // shortcuts, arrows, backspace…
    if (!/[0-9]/.test(e.key)) e.preventDefault(); // letters/symbols; the dash is auto-inserted
}, true);

document.addEventListener('input', function (e) {
    const input = e.target;
    if (!input.classList || !input.classList.contains('sy-input')) return;
    const formatted = formatSchoolYear(input.value);
    if (formatted !== input.value) input.value = formatted;
    validateSchoolYearInput(input);
}, true);

// Capture phase: runs before the ajax-form / AJAX-nav submit handlers.
document.addEventListener('submit', function (e) {
    const form = e.target;
    const hidden = form.querySelector('input[data-sy-hidden]');
    if (hidden && !isValidSchoolYear(hidden.value)) {
        e.preventDefault();
        e.stopImmediatePropagation();
        showToast(SY_MESSAGE, 'danger');
        return;
    }
    const bad = Array.from(form.querySelectorAll('.sy-input')).filter(i => !validateSchoolYearInput(i));
    if (bad.length) {
        e.preventDefault();
        e.stopImmediatePropagation();
        bad[0].reportValidity();
        bad[0].focus();
    }
}, true);

/* ── Chart.js global defaults ─────────────────────────────── */
function applyChartDefaults() {
    const style   = getComputedStyle(document.documentElement);
    const cssVar  = (name) => style.getPropertyValue(name).trim();

    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.font.size   = 12;
    Chart.defaults.color       = cssVar('--muted');
    Chart.defaults.plugins.tooltip.backgroundColor = cssVar('--card');
    Chart.defaults.plugins.tooltip.titleColor = cssVar('--text');
    Chart.defaults.plugins.tooltip.bodyColor  = cssVar('--text-secondary');
    Chart.defaults.plugins.tooltip.borderColor = cssVar('--border');
    Chart.defaults.plugins.tooltip.borderWidth = 1;
    Chart.defaults.plugins.tooltip.cornerRadius = 10;
    Chart.defaults.plugins.tooltip.padding = 10;
}
applyChartDefaults();

function chartGridColor() {
    return getComputedStyle(document.documentElement).getPropertyValue('--border').trim();
}

// Theme-aware series colors (dark maroon in light mode, soft gold in dark
// mode) — sourced from --chart-rgb / --chart-2 in app.css.
function chartColor(alpha = 1) {
    const rgb = getComputedStyle(document.documentElement).getPropertyValue('--chart-rgb').trim();
    return 'rgba(' + rgb + ',' + alpha + ')';
}

function chartColorAlt() {
    return getComputedStyle(document.documentElement).getPropertyValue('--chart-2').trim();
}

/* ── Click a chart to expand it ──────────────────────────────
   Any Chart.js chart on any page: a click opens a large copy in
   #chartExpandModal, titled after the card it sits in. The copy gets its
   own data arrays (so the two charts never share state) and a legend
   whenever there's more than one series to tell apart. */
let expandedChart = null;

/* The numbers behind a chart as a table: one row per label, one column per
   series. Optional hints on the chart's <canvas>:
     data-label-header  first column's heading (default "Category")
     data-unit          suffix for values, e.g. "%"
     data-table-total   "1" adds a Total column / row (only for counts)
   Doughnut / pie charts get a Share column and a Total row automatically. */
function chartDataTableHtml(chart, canvas) {
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const labels   = chart.data.labels || [];
    const datasets = chart.data.datasets || [];
    const unit     = canvas.dataset.unit || '';
    const isPie    = ['doughnut', 'pie'].includes(chart.config.type);
    const withTotal = isPie || canvas.dataset.tableTotal === '1';
    const fmt = v => (v === null || v === undefined || Number.isNaN(Number(v)))
        ? '—'
        : Number(v).toLocaleString('en-US', { maximumFractionDigits: 2 }) + unit;
    const sum = arr => arr.reduce((a, v) => a + (Number(v) || 0), 0);
    if (!labels.length || !datasets.length) return '';

    // A placeholder slice (e.g. "No documents") isn't real data.
    if (isPie && labels.length === 1 && /^no /i.test(labels[0])) {
        return '<p class="text-muted small text-center mb-0 py-2">No data to show yet.</p>';
    }

    let head = '<th>' + esc(canvas.dataset.labelHeader || 'Category') + '</th>';
    if (isPie) {
        head += '<th class="text-end">Count</th><th class="text-end">Share</th>';
    } else {
        datasets.forEach(d => { head += '<th class="text-end">' + esc(d.label || 'Value') + '</th>'; });
        if (withTotal && datasets.length > 1) head += '<th class="text-end">Total</th>';
    }

    const pieTotal = isPie ? sum(datasets[0].data) : 0;
    const rows = labels.map((label, i) => {
        let cells = '<td>' + esc(label) + '</td>';
        if (isPie) {
            const v = Number(datasets[0].data[i]) || 0;
            cells += '<td class="text-end">' + fmt(v) + '</td><td class="text-end">' + (pieTotal ? (v / pieTotal * 100).toFixed(1) : '0') + '%</td>';
        } else {
            datasets.forEach(d => { cells += '<td class="text-end">' + fmt(d.data[i]) + '</td>'; });
            if (withTotal && datasets.length > 1) cells += '<td class="text-end fw-semibold">' + fmt(sum(datasets.map(d => d.data[i]))) + '</td>';
        }
        return '<tr>' + cells + '</tr>';
    }).join('');

    let foot = '';
    if (isPie) {
        foot = '<tr><td>Total</td><td class="text-end">' + fmt(pieTotal) + '</td><td class="text-end">100%</td></tr>';
    } else if (withTotal) {
        foot = '<tr><td>Total</td>' + datasets.map(d => '<td class="text-end">' + fmt(sum(d.data)) + '</td>').join('')
            + (datasets.length > 1 ? '<td class="text-end">' + fmt(sum(datasets.map(d => sum(d.data)))) + '</td>' : '') + '</tr>';
    }

    return '<table class="table table-sm mb-0"><thead><tr>' + head + '</tr></thead><tbody>' + rows + '</tbody>'
        + (foot ? '<tfoot>' + foot + '</tfoot>' : '') + '</table>';
}

function openChartModal(canvas) {
    const src = Chart.getChart(canvas);
    if (!src) return;

    const card  = canvas.closest('.card');
    const head  = card && card.querySelector('.dash-card-head > span, .card-header .fw-semibold, .card-header');
    const title = (head ? head.textContent : '').replace(/\s+/g, ' ').trim() || 'Chart';
    const modalEl = document.getElementById('chartExpandModal');
    modalEl.querySelector('#chartExpandTitle span').textContent = title;
    modalEl.dataset.fileName = title.replace(/[^\w\- ]+/g, '').trim().replace(/\s+/g, '-').toLowerCase() || 'chart';

    document.getElementById('chartExpandTable').innerHTML = chartDataTableHtml(src, canvas);

    // Where the numbers come from: the chart's data-source attribute.
    const sourceBox = document.getElementById('chartExpandSource');
    const source    = (canvas.dataset.source || '').trim();
    sourceBox.querySelector('span').textContent = source;
    sourceBox.classList.toggle('d-none', source === '');

    const type     = src.config.type;
    const multi    = src.data.datasets.length > 1 || type === 'doughnut' || type === 'pie';
    const baseOpts = src.config.options || {};
    modalEl._chartConfig = {
        type: type,
        data: {
            labels: [...(src.data.labels || [])],
            // Bars capped thin for a small card would look like hairlines full-size.
            datasets: src.data.datasets.map(d => ({ ...d, data: [...d.data], ...(d.maxBarThickness ? { maxBarThickness: d.maxBarThickness * 2 } : {}) })),
        },
        options: {
            ...baseOpts,
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 400 },
            plugins: {
                ...(baseOpts.plugins || {}),
                legend: { display: multi, position: 'bottom', labels: { boxWidth: 12, boxHeight: 12, padding: 16 } },
            },
        },
    };
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
}
(function initChartExpand() {
    const modalEl = document.getElementById('chartExpandModal');
    if (!modalEl) return;

    // Build the copy only once the modal is visible, so it sizes to the full width.
    modalEl.addEventListener('shown.bs.modal', () => {
        if (expandedChart) expandedChart.destroy();
        expandedChart = new Chart(document.getElementById('chartExpandCanvas'), modalEl._chartConfig);
    });
    modalEl.addEventListener('hidden.bs.modal', () => {
        if (expandedChart) { expandedChart.destroy(); expandedChart = null; }
    });
    document.getElementById('chartExpandDownload').addEventListener('click', () => {
        if (!expandedChart) return;
        const a = document.createElement('a');
        a.href = expandedChart.toBase64Image('image/png', 1);
        a.download = (modalEl.dataset.fileName || 'chart') + '.png';
        a.click();
    });

    // Delegated, so charts added later by AJAX navigation work too. Capture
    // phase + preventDefault: a chart inside a card link (e.g. the teacher
    // dashboard's Task Status card) must expand, not navigate away.
    document.addEventListener('click', e => {
        const canvas = e.target.closest('canvas');
        if (!canvas || canvas.id === 'chartExpandCanvas' || !Chart.getChart(canvas)) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        openChartModal(canvas);
    }, true);
})();
</script>

<?php if (isset($extraScript)): ?>
<!-- Inert <template> so the browser doesn't run it natively: a native run
     leaves top-level const/let as global bindings, and the var-rewritten
     re-run after AJAX navigation back to this page would then throw
     "already been declared" (charts silently missing). Always go through
     runPageScript so every run uses the same var declarations. -->
<template id="page-extra-script"><?= $extraScript ?></template>
<script>runPageScript(pageExtraScriptCode(document), { replayReady: false });</script>
<?php endif; ?>
</body>
</html>

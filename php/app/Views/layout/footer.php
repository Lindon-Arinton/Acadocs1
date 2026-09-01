</main><!-- /#main-content -->

<?php if (hasRole('admin')): ?>
<a href="<?= base_url('announcements') ?>#postAnnouncementCard" class="fab-announcement" title="New Announcement">
  <i class="bi bi-megaphone-fill"></i>
</a>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

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

function ajaxFormSubmit(form) {
    const formData = new FormData(form);

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
        .then(data => {
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
        })
        .catch(() => {
            Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server. Please check your connection and try again.' });
        });
}

document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form.classList.contains('ajax-form')) return;
    e.preventDefault();

    const actionKey = form.dataset.confirmAction || form.querySelector('[name="action"]')?.value || 'default';
    const cfg = AJAX_ACTION_LABELS[actionKey] || AJAX_ACTION_LABELS.default;

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

/* ── Logout confirmation ─────────────────────────────────── */
function confirmLogout(e, link) {
    e.preventDefault();
    Swal.fire({
        title: 'Log out?',
        text: 'You will need to sign in again to continue.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, log out',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#800000',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
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
        timer = setTimeout(() => form.requestSubmit(), delay);
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
    window.addEventListener('scroll', () => { if (panel.style.display === 'block') closePanel(); }, true);
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
function runPageScript(code) {
    const safeCode = code.replace(/(^|[;{\n]\s*)(let|const)(\s+)/g, '$1var$3');
    const script = document.createElement('script');
    script.textContent = safeCode;
    document.body.appendChild(script);
    script.remove();

    // Some pages wire things up inside DOMContentLoaded; that event only
    // fires once natively, so replay it manually for re-injected scripts.
    document.dispatchEvent(new Event('DOMContentLoaded'));
    window.dispatchEvent(new Event('DOMContentLoaded'));
}

function updateActiveNavLinks(pathname) {
    document.querySelectorAll('#sidebar .nav-link').forEach(link => {
        link.classList.toggle('active', link.pathname === pathname);
    });
}

function isAjaxNavExempt(url) {
    return /\/(download|logout|login)(\/|$|\?)/.test(url.pathname) || /\/(file|preview)(\/|$|\?)/.test(url.pathname);
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

function loadPage(url, { push = true, scroll = true } = {}) {
    const target = new URL(url, window.location.href);
    if (target.origin !== window.location.origin || isAjaxNavExempt(target)) {
        window.location.href = target.href;
        return;
    }

    const token = ++ajaxNavToken;
    const main = document.getElementById('main-content');
    startAjaxProgress();

    fetch(target.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(res => {
            if (!res.ok) throw new Error('Failed to load page');
            return res.text().then(html => ({ html, finalUrl: res.url }));
        })
        .then(({ html, finalUrl }) => {
            if (token !== ajaxNavToken || !main) return;

            const doc = new DOMParser().parseFromString(html, 'text/html');
            const newMain = doc.getElementById('main-content');
            if (!newMain) { window.location.href = finalUrl; return; }

            closeAnyOpenModals();
            main.innerHTML = newMain.innerHTML;
            // Only after the swap: this is what actually detaches the old
            // wrappers from the document, which is what tells the cleanup
            // which body-level panels are truly orphaned vs. still in use.
            removeOrphanedMaroonSelectPanels();
            main.classList.remove('animate-in');
            void main.offsetWidth;
            main.classList.add('animate-in');

            if (doc.title) document.title = doc.title;

            const finalPath = new URL(finalUrl, window.location.origin).pathname;
            updateActiveNavLinks(finalPath);

            const newScript = doc.querySelector('#page-extra-script script');
            if (newScript && newScript.textContent.trim()) {
                runPageScript(newScript.textContent);
            }
            reinitPageWidgets(main);

            if (push) history.pushState({ ajaxNav: true }, '', finalUrl);
            if (target.hash) {
                const hashTarget = document.getElementById(target.hash.slice(1));
                if (hashTarget) hashTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else if (scroll) {
                window.scrollTo({ top: 0, behavior: 'auto' });
            }
            closeSidebar();
            finishAjaxProgress();
        })
        .catch(err => {
            console.error('AJAX navigation failed, falling back to a full page load:', err);
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

/* ── Chart.js global defaults ─────────────────────────────── */
(function () {
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
})();

function chartGridColor() {
    return getComputedStyle(document.documentElement).getPropertyValue('--border').trim();
}
</script>

<?php if (isset($extraScript)): ?>
<div id="page-extra-script" hidden><?= $extraScript ?></div>
<?php endif; ?>
</body>
</html>

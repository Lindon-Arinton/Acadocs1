</main><!-- /#main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script>
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

/* ── Sidebar sections accordion ──────────────────────────── */
function toggleSection(btn) {
    const items = btn?.nextElementSibling;
    if (!items) return;

    const isOpen = btn.classList.contains('open');
    btn.classList.toggle('open', !isOpen);
    items.classList.toggle('open', !isOpen);
}

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
                        window.location.href = data.redirect;
                    } else {
                        window.location.reload();
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

/* ── Chart.js global defaults ─────────────────────────────── */
Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
Chart.defaults.font.size   = 12;
Chart.defaults.color       = '#6b7280';
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(255,255,255,.98)';
Chart.defaults.plugins.tooltip.titleColor = '#111';
Chart.defaults.plugins.tooltip.bodyColor  = '#374151';
Chart.defaults.plugins.tooltip.borderColor = '#e5e7eb';
Chart.defaults.plugins.tooltip.borderWidth = 1;
Chart.defaults.plugins.tooltip.cornerRadius = 10;
Chart.defaults.plugins.tooltip.padding = 10;
</script>

<?php if (isset($extraScript)) echo $extraScript; ?>
</body>
</html>

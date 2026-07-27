<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — ACADOCS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<link rel="icon" type="image/png" href="<?= base_url('assets/img/logo-icon.png') ?>">
</head>
<body>
<div class="login-wrapper">
  <div class="login-shell">

    <!-- Brand / welcome panel -->
    <div class="login-brand-panel">
      <div class="login-brand-decor"></div>
      <div class="login-brand-content">
        <div class="login-brand-icon"><img src="<?= base_url('assets/img/logo-icon.png') ?>" alt="ACADOCS"></div>
        <h2>Hello, Welcome</h2>
        <p>ACADOCS keeps your school's documents, performance records, and day-to-day operations organized in one place.</p>

        <div class="login-clock-widget">
          <div class="login-clock" id="liveClock">--:--:--</div>
          <div class="login-date" id="liveDate">Loading date…</div>
        </div>

        <div class="mini-calendar">
          <div class="mini-calendar-head" id="calMonthLabel"></div>
          <div class="mini-calendar-dow">
            <span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span>
          </div>
          <div class="mini-calendar-grid" id="calGrid"></div>
        </div>
      </div>
    </div>

    <!-- Form panel -->
    <div class="login-form-panel">
      <h3 class="login-form-title">Sign In</h3>
      <p class="login-form-sub">Enter your credentials to access your dashboard</p>

      <?php if ($error ?? ''): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
        <span style="font-size:.82rem"><?= e($error) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" action="<?= base_url('login') ?>" autocomplete="on">
        <div class="login-input-group">
          <input type="email" name="email" value="<?= e($email ?? '') ?>"
                 placeholder="email@school.edu" autocomplete="username" required>
          <span class="login-input-icon"><i class="bi bi-envelope"></i></span>
        </div>

        <div class="login-input-group">
          <input type="password" name="password" id="pwdField" placeholder="••••••••" autocomplete="current-password" required>
          <button type="button" class="login-input-icon" onclick="togglePwd()">
            <i class="bi bi-eye" id="pwdIcon"></i>
          </button>
        </div>

        <button type="submit" class="login-submit-btn mt-2">
          <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePwd() {
    const f = document.getElementById('pwdField');
    const i = document.getElementById('pwdIcon');
    const show = f.type === 'password';
    f.type = show ? 'text' : 'password';
    i.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
}

/* ── Live clock ───────────────────────────────────────────── */
function updateClock() {
    const now = new Date();
    document.getElementById('liveClock').textContent = now.toLocaleTimeString('en-US', {
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true,
    });
    document.getElementById('liveDate').textContent = now.toLocaleDateString('en-US', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    });
}
updateClock();
setInterval(updateClock, 1000);

/* ── Mini calendar (current month, today highlighted) ────────── */
function buildMiniCalendar() {
    const now   = new Date();
    const year  = now.getFullYear();
    const month = now.getMonth();
    const today = now.getDate();

    const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    document.getElementById('calMonthLabel').textContent = monthNames[month] + ' ' + year;

    const firstDay     = new Date(year, month, 1).getDay();
    const daysInMonth  = new Date(year, month + 1, 0).getDate();
    const grid         = document.getElementById('calGrid');

    let html = '';
    for (let i = 0; i < firstDay; i++) {
        html += '<span class="is-empty">.</span>';
    }
    for (let d = 1; d <= daysInMonth; d++) {
        html += '<span class="' + (d === today ? 'is-today' : '') + '">' + d + '</span>';
    }
    grid.innerHTML = html;
}
buildMiniCalendar();
</script>
</body>
</html>

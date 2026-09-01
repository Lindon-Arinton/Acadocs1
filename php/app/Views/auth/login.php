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
<div class="login-wrapper login-wrapper-light">
  <div class="login-shell login-shell-split">

    <!-- Form panel (left) -->
    <div class="login-form-panel login-form-panel-left">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-2">
          <img src="<?= base_url('assets/img/logo-icon.png') ?>" alt="ACADOCS" style="width:34px;height:34px;object-fit:contain;">
          <span class="fw-bold" style="font-size:1.05rem;color:var(--text);">ACADOCS</span>
        </div>
        <span class="text-muted" id="liveClock" style="font-size:.78rem;font-variant-numeric:tabular-nums;">--:--:--</span>
      </div>

      <h3 class="login-form-title">Welcome back</h3>
      <p class="login-form-sub">Log in to manage your school's documents and records.</p>

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

    <!-- Illustration panel (right) -->
    <div class="login-illustration-panel">
      <svg id="loginIllustration" class="login-illustration-svg" viewBox="0 0 400 420" fill="none" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <path id="li-sparkle" d="M0,-11 C1.2,-3.5 3.5,-1.2 11,0 C3.5,1.2 1.2,3.5 0,11 C-1.2,3.5 -3.5,1.2 -11,0 C-3.5,-1.2 -1.2,-3.5 0,-11 Z"/>
          <path id="li-leaf" d="M0,-22 C11,-16 11,12 0,22 C-11,12 -11,-16 0,-22 Z"/>

          <linearGradient id="li-g-book1" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#7a0000"/><stop offset="1" stop-color="#3f0000"/>
          </linearGradient>
          <linearGradient id="li-g-book2" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#9c1c1c"/><stop offset="1" stop-color="#6b0000"/>
          </linearGradient>
          <linearGradient id="li-g-book3" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#e2600f"/><stop offset="1" stop-color="#a8330a"/>
          </linearGradient>
          <linearGradient id="li-g-book4" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#c02669"/><stop offset="1" stop-color="#84124a"/>
          </linearGradient>
          <linearGradient id="li-g-screen" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#fff5f5"/><stop offset="1" stop-color="#ffe4e9"/>
          </linearGradient>
          <radialGradient id="li-g-glow" cx="50%" cy="45%" r="55%">
            <stop offset="0" stop-color="#fff0f0" stop-opacity=".9"/>
            <stop offset="1" stop-color="#fff0f0" stop-opacity="0"/>
          </radialGradient>

          <filter id="li-blur" x="-60%" y="-60%" width="220%" height="220%">
            <feGaussianBlur stdDeviation="16"/>
          </filter>
          <filter id="li-shadow" x="-60%" y="-60%" width="220%" height="220%">
            <feDropShadow dx="0" dy="6" stdDeviation="6" flood-color="#800000" flood-opacity=".22"/>
          </filter>
          <filter id="li-shadow-soft" x="-60%" y="-60%" width="220%" height="220%">
            <feDropShadow dx="0" dy="3" stdDeviation="3" flood-color="#800000" flood-opacity=".16"/>
          </filter>
        </defs>

        <!-- Layer 1: ambient glow + soft-blurred blobs (barely-there drift, moves least on parallax) -->
        <g id="li-layer-back">
          <circle cx="200" cy="200" r="170" fill="url(#li-g-glow)"/>
          <circle class="li-drift" filter="url(#li-blur)" cx="80" cy="90" r="70" fill="#800000" opacity=".14"/>
          <circle class="li-drift li-drift-slow" filter="url(#li-blur)" cx="330" cy="320" r="90" fill="#c2410c" opacity=".12"/>
          <circle class="li-drift" filter="url(#li-blur)" cx="340" cy="70" r="40" fill="#9d174d" opacity=".14"/>
        </g>

        <!-- Layer 2: the grounded scene (books/cap/laptop/plant) — moves a little on parallax -->
        <g id="li-layer-mid">
          <ellipse cx="195" cy="358" rx="120" ry="14" fill="#800000" opacity=".1"/>

          <g class="li-enter" style="animation-delay:.05s;">
            <g class="li-sway" style="transform-origin:60px 330px;">
              <path d="M60,330 L48,270 A16,16 0 0 1 72,270 Z" fill="#7c2d12"/>
              <use href="#li-leaf" transform="translate(48,255) rotate(-18) scale(1.05)" fill="#15803d"/>
              <use href="#li-leaf" transform="translate(70,258) rotate(20) scale(.9)" fill="#22c55e"/>
              <use href="#li-leaf" transform="translate(58,240) rotate(0) scale(1.15)" fill="#16a34a"/>
            </g>
          </g>

          <g class="li-enter" style="animation-delay:.15s;" filter="url(#li-shadow-soft)">
            <rect x="120" y="330" width="150" height="24" rx="6" fill="url(#li-g-book1)" transform="rotate(-2 195 342)"/>
          </g>
          <g class="li-enter" style="animation-delay:.25s;" filter="url(#li-shadow-soft)">
            <rect x="115" y="308" width="150" height="24" rx="6" fill="url(#li-g-book2)" transform="rotate(2 190 320)"/>
          </g>
          <g class="li-enter" style="animation-delay:.35s;" filter="url(#li-shadow-soft)">
            <rect x="122" y="286" width="150" height="24" rx="6" fill="url(#li-g-book3)" transform="rotate(-3 197 298)"/>
          </g>
          <g class="li-enter" style="animation-delay:.45s;" filter="url(#li-shadow-soft)">
            <rect x="118" y="264" width="150" height="24" rx="6" fill="url(#li-g-book4)" transform="rotate(1.5 193 276)"/>
          </g>

          <!-- Graduation cap: bouncy entrance, then a gentle idle bob -->
          <g class="li-enter li-enter-bounce" style="animation-delay:.6s;" filter="url(#li-shadow)">
            <g class="li-float" style="transform-origin:196px 240px;animation-delay:1.2s;">
              <rect x="171" y="248" width="50" height="14" rx="4" fill="#2b0000"/>
              <rect x="156" y="221" width="80" height="80" rx="6" fill="#3a0a0a" transform="rotate(45 196 261)"/>
              <circle cx="196" cy="248" r="5" fill="#c2410c"/>
              <path d="M196,248 C220,258 222,278 214,292" stroke="#c2410c" stroke-width="2.5" fill="none" stroke-linecap="round"/>
              <circle cx="214" cy="294" r="4.5" fill="#c2410c"/>
            </g>
          </g>

          <!-- Laptop -->
          <g class="li-enter" style="animation-delay:.75s;" filter="url(#li-shadow)">
            <g class="li-float" style="transform-origin:300px 300px;animation-delay:1.35s;">
              <rect x="255" y="234" width="90" height="62" rx="6" fill="url(#li-g-screen)" stroke="#e5e7eb" stroke-width="2"/>
              <rect x="264" y="243" width="72" height="8" rx="2" fill="#ffb8c4"/>
              <rect x="264" y="256" width="52" height="6" rx="2" fill="#f1f5f9"/>
              <rect x="264" y="266" width="60" height="6" rx="2" fill="#f1f5f9"/>
              <rect x="264" y="276" width="40" height="6" rx="2" fill="#f1f5f9"/>
              <circle class="li-blink" cx="331" cy="279" r="3" fill="#22c55e"/>
              <path d="M246,296 L354,296 L344,312 L256,312 Z" fill="#800000"/>
            </g>
          </g>
        </g>

        <!-- Layer 3: floating extras — moves the most on parallax, for real depth -->
        <g id="li-layer-front">
          <g class="li-enter" style="animation-delay:.9s;" filter="url(#li-shadow-soft)">
            <g class="li-float" style="transform-origin:96px 150px;animation-delay:1.5s;">
              <rect x="70" y="122" width="52" height="66" rx="6" fill="#ffffff" stroke="#e5e7eb" stroke-width="2" transform="rotate(-8 96 155)"/>
              <g transform="rotate(-8 96 155)">
                <rect x="80" y="138" width="32" height="5" rx="2" fill="#ffd6de"/>
                <rect x="80" y="149" width="26" height="5" rx="2" fill="#f1f5f9"/>
                <rect x="80" y="160" width="30" height="5" rx="2" fill="#f1f5f9"/>
                <rect x="80" y="171" width="20" height="5" rx="2" fill="#f1f5f9"/>
              </g>
            </g>
          </g>

          <g class="li-enter" style="animation-delay:1.05s;" filter="url(#li-shadow-soft)">
            <g class="li-float" style="transform-origin:322px 150px;animation-delay:1.65s;">
              <g transform="rotate(35 322 150)">
                <rect x="312" y="118" width="16" height="64" rx="4" fill="#c2410c"/>
                <path d="M312,118 L328,118 L320,102 Z" fill="#fbbf24"/>
                <rect x="312" y="170" width="16" height="12" fill="#f5d0c5"/>
                <rect x="312" y="178" width="16" height="6" fill="#2b0000"/>
              </g>
            </g>
          </g>

          <g class="li-enter" style="animation-delay:1.2s;">
            <use href="#li-sparkle" class="li-twinkle" transform="translate(140,90) scale(1.3)" fill="#c2410c" style="animation-delay:1.8s;"/>
          </g>
          <g class="li-enter" style="animation-delay:1.3s;">
            <use href="#li-sparkle" class="li-twinkle" transform="translate(300,180) scale(1)" fill="#9d174d" style="animation-delay:1.9s;"/>
          </g>
          <g class="li-enter" style="animation-delay:1.4s;">
            <use href="#li-sparkle" class="li-twinkle" transform="translate(250,70) scale(.8)" fill="#800000" style="animation-delay:2s;"/>
          </g>
          <g class="li-enter" style="animation-delay:1.5s;">
            <use href="#li-sparkle" class="li-twinkle" transform="translate(90,220) scale(.9)" fill="#c2410c" style="animation-delay:2.1s;"/>
          </g>

          <g class="li-enter" style="animation-delay:1.15s;">
            <circle class="li-float" style="transform-origin:210px 110px;animation-delay:1.75s;" cx="210" cy="110" r="5" fill="#fbbf24"/>
          </g>
          <g class="li-enter" style="animation-delay:1.25s;">
            <circle class="li-float" style="transform-origin:355px 240px;animation-delay:1.85s;" cx="355" cy="240" r="4" fill="#22c55e"/>
          </g>
          <g class="li-enter" style="animation-delay:1.35s;">
            <circle class="li-float" style="transform-origin:60px 190px;animation-delay:1.95s;" cx="60" cy="190" r="4" fill="#800000"/>
          </g>
        </g>
      </svg>

      <div class="text-center mt-2">
        <div class="fw-bold" style="color:var(--primary);font-size:1.05rem;">Manage your school, effortlessly</div>
        <p class="text-muted mb-0" style="font-size:.82rem;max-width:280px;margin:0 auto;">
          Documents, performance records, and day-to-day operations — all organized in one place.
        </p>
      </div>
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

/* ── Live clock (compact, top of the form panel) ─────────────── */
function updateClock() {
    document.getElementById('liveClock').textContent = new Date().toLocaleTimeString('en-US', {
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true,
    });
}
updateClock();
setInterval(updateClock, 1000);

/* ── Layered cursor parallax — each depth layer shifts by a different
   amount, so the scene reads as genuinely three-dimensional instead of
   the whole illustration tilting as one rigid card. ── */
const illoPanel = document.querySelector('.login-illustration-panel');
const illoLayers = [
    { el: document.getElementById('li-layer-back'), amount: 4 },
    { el: document.getElementById('li-layer-mid'), amount: 9 },
    { el: document.getElementById('li-layer-front'), amount: 16 },
];
illoPanel?.addEventListener('mousemove', (e) => {
    const rect = illoPanel.getBoundingClientRect();
    const px = (e.clientX - rect.left) / rect.width - 0.5;
    const py = (e.clientY - rect.top) / rect.height - 0.5;
    illoLayers.forEach(({ el, amount }) => {
        if (el) el.style.transform = 'translate(' + (px * amount) + 'px,' + (py * amount) + 'px)';
    });
});
illoPanel?.addEventListener('mouseleave', () => {
    illoLayers.forEach(({ el }) => { if (el) el.style.transform = ''; });
});
</script>
</body>
</html>

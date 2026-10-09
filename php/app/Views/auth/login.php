<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — ACADOCS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Dancing+Script:wght@600&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<link rel="icon" type="image/png" href="<?= base_url('assets/img/logo-icon.png') ?>">
</head>
<body>
<div class="login-wrapper login-wrapper-photo">
  <!-- Full-page campus photo backdrop (Ken Burns drift) -->
  <div class="login-bg" aria-hidden="true">
    <div class="login-bg-photo"></div>
    <div class="login-bg-overlay"></div>
  </div>

<div class="auth-split">

  <!-- ── Left: sign-in form ─────────────────────────────── -->
  <section class="auth-left">
    <div class="auth-wave auth-wave-tr" aria-hidden="true">
      <svg viewBox="0 0 100 100" preserveAspectRatio="none">
        <path d="M28,0 C40,32 68,46 100,38 L100,0 Z" fill="#f3e4df"/>
        <path d="M58,0 C66,20 82,26 100,22 L100,0 Z" fill="#ecd5ce"/>
      </svg>
    </div>
    <div class="auth-wave auth-wave-bl" aria-hidden="true">
      <svg viewBox="0 0 100 100" preserveAspectRatio="none">
        <defs>
          <linearGradient id="aw-maroon" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#8a1a2c"/><stop offset="1" stop-color="#4f0812"/>
          </linearGradient>
        </defs>
        <path d="M0,30 C24,48 46,92 78,100 L0,100 Z" fill="#efdcd7"/>
        <path d="M0,58 C18,66 34,92 56,100 L0,100 Z" fill="url(#aw-maroon)"/>
        <path d="M0,46 C22,56 40,90 66,100" fill="none" stroke="#c98f98" stroke-width=".6" vector-effect="non-scaling-stroke"/>
      </svg>
    </div>

    <header class="auth-topbar ls-item" style="--i:0;">
      <div class="auth-brand">
        <img src="<?= base_url('assets/img/logo-icon.png') ?>" alt="ACADOCS">
        <div>
          <div class="d-flex align-items-center gap-2">
            <span class="auth-brand-name">ACADOCS</span>
            <button type="button" class="btn btn-sm btn-link p-0" style="line-height:1;font-size:.95rem;"
                    title="About the developer" data-bs-toggle="modal" data-bs-target="#aboutDeveloperModal">
              <i class="bi bi-info-circle"></i>
            </button>
          </div>
          <div class="auth-brand-sub">School Documents Management System</div>
        </div>
      </div>
      <div class="auth-date">
        <i class="bi bi-calendar3"></i>
        <div>
          <div id="liveDate"></div>
          <div id="liveClock">--:--:--</div>
        </div>
      </div>
    </header>

    <div class="auth-form-wrap">
      <div class="auth-accent ls-item" style="--i:1;"></div>
      <h1 class="auth-title ls-item" style="--i:2;">Welcome back,<span>ACADOCS User</span></h1>
      <p class="auth-sub ls-item" style="--i:3;">Log in to manage your school's documents and records.</p>

      <?php if ($success = session()->getFlashdata('success')): ?>
      <div class="alert alert-success d-flex align-items-center gap-2 mb-3 ls-item" style="--i:4;">
        <i class="bi bi-check-circle-fill flex-shrink-0"></i>
        <span style="font-size:.82rem"><?= e($success) ?></span>
      </div>
      <?php endif; ?>

      <?php if ($error ?? ''): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 mb-3 login-shake">
        <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
        <span style="font-size:.82rem"><?= e($error) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" action="<?= base_url('login') ?>" autocomplete="on" id="loginForm">
        <div class="auth-field ls-item" style="--i:5;">
          <i class="bi bi-envelope auth-field-icon"></i>
          <input type="email" name="email" value="<?= e($email ?? '') ?>"
                 placeholder="email@school.edu" autocomplete="username" aria-label="Email" required>
        </div>

        <div class="auth-field ls-item" style="--i:6;">
          <i class="bi bi-lock auth-field-icon"></i>
          <input type="password" name="password" id="pwdField" placeholder="••••••••"
                 autocomplete="current-password" aria-label="Password" required>
          <button type="button" class="auth-field-toggle" onclick="togglePwd()" aria-label="Show password">
            <i class="bi bi-eye" id="pwdIcon"></i>
          </button>
        </div>

        <div class="auth-forgot ls-item" style="--i:7;">
          <a href="<?= base_url('forgot-password') ?>">Forgot password?</a>
        </div>

        <div class="ls-item" style="--i:8;">
          <button type="submit" class="login-submit-btn auth-submit" id="loginSubmit">
            <span class="btn-label"><i class="bi bi-box-arrow-in-right me-2"></i>Sign In</span>
            <span class="btn-spinner" aria-hidden="true"></span>
          </button>
        </div>
      </form>

      <div class="auth-features ls-item" style="--i:10;">
        <div class="auth-feature">
          <i class="bi bi-shield-check"></i>
          <div><strong>Secure</strong><small>Your data, our priority</small></div>
        </div>
        <div class="auth-feature">
          <i class="bi bi-file-earmark-text"></i>
          <div><strong>Efficient</strong><small>Streamlined processes</small></div>
        </div>
        <div class="auth-feature">
          <i class="bi bi-mortarboard"></i>
          <div><strong>For a Better</strong><small>Educational Community</small></div>
        </div>
      </div>
    </div>
  </section>

  <!-- ── Right: campus photo scene ──────────────────────── -->
  <section class="auth-right" aria-hidden="true">
    <!-- The photo stays put (depth 0); only the books and cards in front drift with the cursor. -->
    <div class="auth-par" data-depth="0">
      <div class="auth-photo"></div>
    </div>
    <div class="auth-photo-tint"></div>

    <div class="auth-wave auth-wave-r-tl">
      <svg viewBox="0 0 100 100" preserveAspectRatio="none">
        <defs>
          <linearGradient id="aw-r1" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#5a0b18"/><stop offset="1" stop-color="#8a1a2c"/>
          </linearGradient>
        </defs>
        <path d="M0,0 L48,0 C32,10 14,30 0,62 Z" fill="url(#aw-r1)" opacity=".92"/>
        <path d="M58,0 C38,14 16,36 0,74" fill="none" stroke="rgba(255,190,190,.35)" stroke-width="1" vector-effect="non-scaling-stroke"/>
      </svg>
    </div>

    <div class="auth-tagline">
      <span class="auth-tagline-text">Better Records.<br>Brighter Futures.</span>
      <svg viewBox="0 0 120 14" preserveAspectRatio="none">
        <path d="M2,10 C30,4 70,2 118,6" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
    </div>

    <div class="auth-desk"></div>

    <!-- Glass icon cards linked by flowing dotted lines -->
    <div class="auth-par" data-depth="22">
      <div class="auth-orbit">
        <svg class="auth-orbit-lines" viewBox="0 0 440 220">
          <path d="M-20,190 C10,120 40,82 80,70"/>
          <path d="M80,70 C140,52 170,90 210,120"/>
          <path d="M210,120 C270,160 320,120 350,80"/>
          <path d="M350,80 C380,40 420,26 470,30"/>
          <circle cx="138" cy="66" r="3" style="animation-delay:.4s;"/>
          <circle cx="285" cy="140" r="3" style="animation-delay:1.1s;"/>
          <circle cx="410" cy="34" r="2.5" style="animation-delay:1.7s;"/>
        </svg>
        <div class="auth-card" style="left:80px;top:70px;animation-delay:2.1s;">
          <div class="auth-card-inner" style="--r:-6deg;animation-delay:2.8s;"><i class="bi bi-file-earmark-text"></i></div>
        </div>
        <div class="auth-card" style="left:210px;top:120px;animation-delay:2.25s;">
          <div class="auth-card-inner" style="--r:4deg;animation-delay:3.4s;"><i class="bi bi-folder2-open"></i></div>
        </div>
        <div class="auth-card" style="left:350px;top:80px;animation-delay:2.4s;">
          <div class="auth-card-inner" style="--r:-3deg;animation-delay:4s;"><i class="bi bi-person-badge"></i></div>
        </div>
      </div>
    </div>

    <!-- Book stack + pen cup -->
    <div class="auth-par" data-depth="10">
      <svg class="auth-books" viewBox="0 0 380 215">
        <defs>
          <linearGradient id="ab-cover" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#9a2335"/><stop offset=".5" stop-color="#7a1424"/><stop offset="1" stop-color="#520a16"/>
          </linearGradient>
          <linearGradient id="ab-cup" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#1c1013"/><stop offset=".45" stop-color="#3a2328"/><stop offset="1" stop-color="#140a0c"/>
          </linearGradient>
          <filter id="ab-shadow" x="-20%" y="-20%" width="140%" height="160%">
            <feDropShadow dx="0" dy="6" stdDeviation="6" flood-color="#100003" flood-opacity=".55"/>
          </filter>
        </defs>

        <ellipse cx="170" cy="204" rx="190" ry="10" fill="#0d0003" opacity=".55"/>

        <!-- Pen cup -->
        <g class="auth-book" style="animation-delay:2.05s;" filter="url(#ab-shadow)">
          <line x1="318" y1="118" x2="300" y2="58" stroke="#1d2433" stroke-width="6" stroke-linecap="round"/>
          <line x1="330" y1="116" x2="334" y2="46" stroke="#8b1d2c" stroke-width="6" stroke-linecap="round"/>
          <line x1="342" y1="118" x2="362" y2="66" stroke="#2b2b30" stroke-width="5" stroke-linecap="round"/>
          <line x1="334" y1="46" x2="334" y2="40" stroke="#d9b36c" stroke-width="3" stroke-linecap="round"/>
          <rect x="304" y="112" width="56" height="88" rx="7" fill="url(#ab-cup)"/>
          <ellipse cx="332" cy="113" rx="28" ry="5" fill="#2c1a1e" stroke="#4a3035" stroke-width="1.5"/>
          <rect x="314" y="122" width="4" height="70" rx="2" fill="rgba(255,255,255,.08)"/>
        </g>

        <?php
        // Bottom to top: [x, y, width, label, icon, entrance delay, tilt]
        $books = [
            [0,  152, 292, 'PROGRESS',  'chart', '1.6s',  0],
            [14, 106, 278, 'DOCUMENTS', 'doc',   '1.75s', .8],
            [6,  60,  284, 'EDUCATION', 'cap',   '1.9s', -1.4],
        ];
        foreach ($books as [$bx, $by, $bw, $label, $icon, $delay, $tilt]):
            $bh = 44;
            $cx = $bx + 16 + ($bw - 16) / 2;
        ?>
        <g class="auth-book" style="animation-delay:<?= $delay ?>;" filter="url(#ab-shadow)">
          <g transform="rotate(<?= $tilt ?> <?= $cx ?> <?= $by + $bh / 2 ?>)">
            <rect x="<?= $bx ?>" y="<?= $by + 3 ?>" width="<?= $bw - 4 ?>" height="<?= $bh - 6 ?>" rx="4" fill="#f1e4d2"/>
            <?php for ($l = 9; $l < $bh - 6; $l += 5): ?>
            <line x1="<?= $bx + 2 ?>" y1="<?= $by + $l ?>" x2="<?= $bx + 16 ?>" y2="<?= $by + $l ?>" stroke="#d6c2a8" stroke-width=".8"/>
            <?php endfor; ?>
            <rect x="<?= $bx + 16 ?>" y="<?= $by ?>" width="<?= $bw - 16 ?>" height="<?= $bh ?>" rx="5" fill="url(#ab-cover)"/>
            <rect x="<?= $bx + 16 ?>" y="<?= $by ?>" width="<?= $bw - 16 ?>" height="4" rx="2" fill="rgba(255,255,255,.12)"/>
            <line x1="<?= $bx + 30 ?>" y1="<?= $by + 7 ?>" x2="<?= $bx + $bw - 12 ?>" y2="<?= $by + 7 ?>" stroke="#d9b36c" stroke-width=".9" opacity=".7"/>
            <line x1="<?= $bx + 30 ?>" y1="<?= $by + $bh - 7 ?>" x2="<?= $bx + $bw - 12 ?>" y2="<?= $by + $bh - 7 ?>" stroke="#d9b36c" stroke-width=".9" opacity=".7"/>
            <g transform="translate(<?= $cx - 62 ?> <?= $by + $bh / 2 ?>)" fill="#e2bd7f">
              <?php if ($icon === 'cap'): ?>
              <path d="M0,-3 L9,-7 L18,-3 L9,1 Z"/><path d="M4,-1 L4,4 C7,6 11,6 14,4 L14,-1 L9,1 Z"/>
              <?php elseif ($icon === 'doc'): ?>
              <path d="M3,-8 L12,-8 L16,-4 L16,8 L3,8 Z" fill="none" stroke="#e2bd7f" stroke-width="1.4"/>
              <line x1="6" y1="-2" x2="13" y2="-2" stroke="#e2bd7f" stroke-width="1.2"/><line x1="6" y1="2" x2="13" y2="2" stroke="#e2bd7f" stroke-width="1.2"/>
              <?php else: ?>
              <rect x="2" y="1" width="3.5" height="7"/><rect x="7.5" y="-3" width="3.5" height="11"/><rect x="13" y="-7" width="3.5" height="15"/>
              <?php endif; ?>
            </g>
            <text x="<?= $cx + 12 ?>" y="<?= $by + $bh / 2 + 4.5 ?>" text-anchor="middle" fill="#e2bd7f"
                  font-family="Georgia, 'Times New Roman', serif" font-size="13" letter-spacing="2"><?= $label ?></text>
          </g>
        </g>
        <?php endforeach; ?>
      </svg>
    </div>

    <div class="auth-wave auth-wave-r-br">
      <svg viewBox="0 0 100 100" preserveAspectRatio="none">
        <path d="M100,40 C84,66 66,90 40,100 L100,100 Z" fill="#4f0812" opacity=".85"/>
        <path d="M100,62 C90,80 78,94 62,100 L100,100 Z" fill="#7a1222"/>
      </svg>
    </div>

    <div class="auth-headline">
      <h2>Manage your school, effortlessly</h2>
      <p>Documents, performance records, and day-to-day operations — all organized in one place.</p>
    </div>
  </section>
</div>
</div>

<!-- About Developer Modal -->
<?php
$appDevelopers = [
    ['name' => 'Niel Francis Benedict Betita', 'role' => 'Main Developer',            'email' => 'betitaniel9@gmail.com'],
    ['name' => 'Lindon Arinton',                'role' => 'System Analyst/Developer', 'email' => 'donarinton@gmail.com'],
    ['name' => 'Margarette Perez',              'role' => 'Librarian/Developer',      'email' => 'margaretteperez73@gmail.com'],
];
?>
<div class="modal fade" id="aboutDeveloperModal" tabindex="-1" aria-labelledby="aboutDeveloperTitle">
  <div class="modal-dialog modal-dialog-centered dev-modal-dialog">
    <div class="modal-content dev-modal">
      <div class="dev-modal-head">
        <span class="dev-modal-badge"><i class="bi bi-code-slash"></i></span>
        <div class="dev-modal-heading">
          <h5 class="dev-modal-title" id="aboutDeveloperTitle">About the Developers</h5>
          <p class="dev-modal-sub">Meet the team behind ACADOCS</p>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="dev-modal-body">
        <?php foreach ($appDevelopers as $dev): ?>
        <div class="dev-row">
          <div class="dev-avatar"><i class="bi bi-person-fill"></i></div>
          <div class="min-w-0">
            <div class="dev-name"><?= e($dev['name']) ?></div>
            <span class="dev-role"><?= e($dev['role']) ?></span>
            <a href="mailto:<?= e($dev['email']) ?>" class="dev-email"><i class="bi bi-envelope"></i><?= e($dev['email']) ?></a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="dev-modal-foot">
        <i class="bi bi-mortarboard-fill"></i>
        <div>
          <div>ACADOCS — built for Matabungkay National High School</div>
          <div class="dev-modal-stack">CodeIgniter 4 · Bootstrap 5 · MySQL</div>
        </div>
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

/* ── Live clock + date (top-right of the form panel) ─────── */
function updateClock() {
    const now = new Date();
    const date = now.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    const time = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    document.getElementById('liveDate').textContent  = date;
    document.getElementById('liveClock').textContent = time;
}
updateClock();
setInterval(updateClock, 1000);

/* ── Submit: swap the button label for a spinner while the request is in flight ── */
document.getElementById('loginForm')?.addEventListener('submit', () => {
    document.getElementById('loginSubmit')?.classList.add('is-loading');
});
/* Coming back via the browser's back button restores the page from cache —
   clear the spinner so the button isn't stuck. */
window.addEventListener('pageshow', () => {
    document.getElementById('loginSubmit')?.classList.remove('is-loading');
});

/* ── Layered cursor parallax on the photo scene — photo, books and the
   floating cards each shift by a different amount for depth. ── */
const scene = document.querySelector('.auth-right');
const sceneLayers = scene ? [...scene.querySelectorAll('.auth-par')] : [];
scene?.addEventListener('mousemove', (e) => {
    const rect = scene.getBoundingClientRect();
    const px = (e.clientX - rect.left) / rect.width - 0.5;
    const py = (e.clientY - rect.top) / rect.height - 0.5;
    sceneLayers.forEach((el) => {
        const d = +el.dataset.depth || 0;
        el.style.transform = 'translate(' + (px * -d) + 'px,' + (py * -d) + 'px)';
    });
});
scene?.addEventListener('mouseleave', () => {
    sceneLayers.forEach((el) => { el.style.transform = ''; });
});
</script>
</body>
</html>

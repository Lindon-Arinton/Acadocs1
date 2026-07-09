<?php
require_once __DIR__ . '/../config.php';
if (currentUser()) { header('Location: ' . BASE_URL . 'dashboard'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($email && $password) {
        try {
            $stmt = getDB()->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                unset($user['password']);
                $_SESSION['user'] = $user;
                header('Location: ' . BASE_URL . ($user['role'] === 'teacher' ? 'teacher-dashboard' : 'dashboard'));
                exit;
            }
            $error = 'Invalid email or password. Please try again.';
        } catch (Exception $e) {
            $error = 'Connection error. Please try again.';
        }
    } else {
        $error = 'Please enter your email and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — ACADOCS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css">
</head>
<body>
<div class="login-wrapper">
  <div class="login-card" style="background:#fff;">

    <!-- Gradient header -->
    <div class="p-5 text-center text-white"
         style="background:linear-gradient(135deg,#9d174d 0%,#800000 50%,#c2410c 100%);position:relative;overflow:hidden;">
      <div style="position:absolute;top:-50px;right:-50px;width:160px;height:160px;background:rgba(255,255,255,.1);border-radius:50%;"></div>
      <div style="position:absolute;bottom:-40px;left:-40px;width:120px;height:120px;background:rgba(255,255,255,.08);border-radius:50%;"></div>
      <div style="position:relative;">
        <div style="width:64px;height:64px;background:rgba(255,255,255,.2);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
          <i class="bi bi-mortarboard-fill" style="font-size:1.8rem;color:#fff;"></i>
        </div>
        <h4 class="fw-bold mb-1">ACADOCS</h4>
        <p class="mb-0" style="opacity:.8;font-size:.88rem;">Academic Document Management System</p>
      </div>
    </div>

    <!-- Form -->
    <div class="p-4">
      <h6 class="fw-bold text-center mb-4" style="color:#374151;">Sign in to your account</h6>

      <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
        <span style="font-size:.82rem"><?= e($error) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Email Address</label>
          <div class="input-group">
            <span class="input-group-text" style="background:#f9fafb;border-right:0;">
              <i class="bi bi-envelope text-muted"></i>
            </span>
            <input type="email" name="email" class="form-control" style="border-left:0;"
                   value="<?= e($_POST['email'] ?? 'principal@school.edu') ?>"
                   placeholder="email@school.edu" required>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text" style="background:#f9fafb;border-right:0;">
              <i class="bi bi-lock text-muted"></i>
            </span>
            <input type="password" name="password" id="pwdField" class="form-control" style="border-left:0;border-right:0;"
                   value="admin123" placeholder="••••••••" required>
            <button type="button" class="input-group-text" style="background:#f9fafb;cursor:pointer;" onclick="togglePwd()">
              <i class="bi bi-eye" id="pwdIcon"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
          <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>
      </form>

      <!-- Quick credentials -->
      <div class="mt-4 p-3 rounded-3" style="background:#f9fafb;border:1px solid #e5e7eb;">
        <p class="fw-semibold mb-2 text-muted" style="font-size:.75rem;">
          <i class="bi bi-info-circle me-1"></i>Demo Credentials
        </p>
        <div class="row g-2">
          <?php foreach ([
            ['Admin',      'principal@school.edu',  'admin123'],
            ['Teacher',    'maria.santos@school.edu','teacher123'],
            ['Secretary',  'secretary@school.edu',   'sec123'],
            ['Canteen',    'canteen@school.edu',      'canteen123'],
            ['Disbursing', 'disbursing@school.edu',  'disb123'],
            ['ADAS',       'adas@school.edu',         'adas123'],
          ] as [$role,$email,$pass]): ?>
          <div class="col-6">
            <button type="button"
                    class="btn btn-outline-secondary btn-sm w-100 text-start py-1 px-2"
                    style="font-size:.7rem;"
                    onclick="fillCreds('<?= $email ?>','<?= $pass ?>')">
              <strong class="d-block"><?= $role ?></strong>
              <span class="text-muted" style="font-size:.65rem;"><?= $email ?></span>
            </button>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function fillCreds(email, pass) {
    document.querySelector('input[name=email]').value = email;
    document.getElementById('pwdField').value = pass;
}
function togglePwd() {
    const f = document.getElementById('pwdField');
    const i = document.getElementById('pwdIcon');
    const show = f.type === 'password';
    f.type = show ? 'text' : 'password';
    i.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
}
</script>
</body>
</html>

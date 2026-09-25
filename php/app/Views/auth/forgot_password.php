<?php $authTitle = 'Forgot Password'; include __DIR__ . '/_auth_head.php'; ?>

      <h3 class="login-form-title">Forgot password?</h3>
      <p class="login-form-sub">Enter your account email and we'll send you a 6-digit code to reset your password.</p>

      <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
        <span style="font-size:.82rem"><?= e($error) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" action="<?= base_url('forgot-password') ?>">
        <div class="login-input-group">
          <input type="email" name="email" value="<?= e($email) ?>" placeholder="email@school.edu" autocomplete="username" required autofocus>
          <span class="login-input-icon"><i class="bi bi-envelope"></i></span>
        </div>

        <button type="submit" class="login-submit-btn mt-2">
          <i class="bi bi-send me-2"></i>Send Reset Code
        </button>
      </form>

      <p class="text-center mt-4 mb-0" style="font-size:.85rem;">
        <a href="<?= base_url('login') ?>" class="text-decoration-none" style="color:var(--primary);"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a>
      </p>
    </div>
  </div>
</div>
</body>
</html>

<?php $authTitle = 'Reset Password'; include __DIR__ . '/_auth_head.php'; ?>

      <h3 class="login-form-title">Reset password</h3>
      <p class="login-form-sub">Enter the code sent to <strong><?= e($email) ?></strong> and choose a new password.</p>

      <?php if ($info && ! $error): ?>
      <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-envelope-check-fill flex-shrink-0"></i>
        <span style="font-size:.82rem"><?= e($info) ?></span>
      </div>
      <?php endif; ?>

      <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
        <span style="font-size:.82rem"><?= e($error) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" action="<?= base_url('reset-password') ?>" autocomplete="off">
        <div class="login-input-group">
          <input type="text" name="code" inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="6-digit code"
                 autocomplete="one-time-code" required autofocus style="letter-spacing:.3em;">
          <span class="login-input-icon"><i class="bi bi-shield-lock"></i></span>
        </div>

        <div class="login-input-group">
          <input type="password" name="password" id="newPwd" placeholder="New password (min. 6 characters)" minlength="6" autocomplete="new-password" required>
          <button type="button" class="login-input-icon" onclick="toggleNewPwd()"><i class="bi bi-eye" id="newPwdIcon"></i></button>
        </div>

        <div class="login-input-group">
          <input type="password" name="confirm_password" id="confirmPwd" placeholder="Confirm new password" minlength="6" autocomplete="new-password" required>
          <span class="login-input-icon"><i class="bi bi-check2-circle"></i></span>
        </div>

        <button type="submit" class="login-submit-btn mt-2">
          <i class="bi bi-key me-2"></i>Reset Password
        </button>
      </form>

      <div class="d-flex justify-content-between mt-4" style="font-size:.85rem;">
        <a href="<?= base_url('login') ?>" class="text-decoration-none" style="color:var(--primary);"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a>
        <a href="<?= base_url('forgot-password?email=' . urlencode($email)) ?>" class="text-decoration-none" style="color:var(--primary);">Send a new code</a>
      </div>
    </div>
  </div>
</div>
<script>
function toggleNewPwd() {
    const show = document.getElementById('newPwd').type === 'password';
    document.getElementById('newPwd').type = document.getElementById('confirmPwd').type = show ? 'text' : 'password';
    document.getElementById('newPwdIcon').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
}
</script>
</body>
</html>

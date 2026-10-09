<?php $authTitle = 'Reset Password'; include __DIR__ . '/_auth_head.php'; ?>

      <h3 class="login-form-title">Reset password</h3>
      <?php if ($verified): ?>
      <p class="login-form-sub">Code verified for <strong><?= e($email) ?></strong>. Choose your new password.</p>
      <?php else: ?>
      <p class="login-form-sub">Enter the 6-digit code sent to <strong><?= e($email) ?></strong>.</p>
      <?php endif; ?>

      <?php if ($info && ! $error && ! $verified): ?>
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

      <?php if ($verified): ?>
      <!-- Step 2 done: the code checked out; the new password goes in the modal. -->
      <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-patch-check-fill flex-shrink-0"></i>
        <span style="font-size:.82rem">Code verified.</span>
      </div>
      <button type="button" class="login-submit-btn" data-bs-toggle="modal" data-bs-target="#newPasswordModal">
        <i class="bi bi-key me-2"></i>Set New Password
      </button>
      <?php else: ?>
      <form method="POST" action="<?= base_url('reset-password') ?>" autocomplete="off">
        <input type="hidden" name="step" value="verify">
        <div class="login-input-group">
          <input type="text" name="code" inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="6-digit code"
                 autocomplete="one-time-code" required autofocus style="letter-spacing:.3em;">
          <span class="login-input-icon"><i class="bi bi-shield-lock"></i></span>
        </div>

        <button type="submit" class="login-submit-btn mt-2">
          <i class="bi bi-shield-check me-2"></i>Verify Code
        </button>
      </form>
      <?php endif; ?>

      <div class="d-flex justify-content-between mt-4" style="font-size:.85rem;">
        <a href="<?= base_url('login') ?>" class="text-decoration-none" style="color:var(--primary);"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a>
        <a href="<?= base_url('forgot-password?email=' . urlencode($email)) ?>" class="text-decoration-none" style="color:var(--primary);">Send a new code</a>
      </div>
    </div>
  </div>
</div>

<?php if ($verified): ?>
<!-- Step 3: new password — only rendered once the code is verified (the server re-checks on submit). -->
<div class="modal fade" id="newPasswordModal" tabindex="-1" aria-labelledby="newPasswordTitle" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered" style="max-width:26rem;">
    <div class="modal-content border-0" style="border-radius:16px;">
      <div class="modal-body p-4">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <h5 class="fw-bold mb-1" id="newPasswordTitle" style="color:var(--text);">Set a new password</h5>
            <p class="text-muted mb-0" style="font-size:.85rem;">For <strong><?= e($email) ?></strong></p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <?php if ($passwordError): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 my-3">
          <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
          <span style="font-size:.82rem"><?= e($passwordError) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= base_url('reset-password') ?>" autocomplete="off" class="mt-3">
          <input type="hidden" name="step" value="password">
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
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleNewPwd() {
    const show = document.getElementById('newPwd').type === 'password';
    document.getElementById('newPwd').type = document.getElementById('confirmPwd').type = show ? 'text' : 'password';
    document.getElementById('newPwdIcon').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
}

// Code verified: open the new-password step straight away.
const newPasswordModal = document.getElementById('newPasswordModal');
if (newPasswordModal) {
    newPasswordModal.addEventListener('shown.bs.modal', () => document.getElementById('newPwd').focus());
    new bootstrap.Modal(newPasswordModal).show();
}
</script>
</body>
</html>

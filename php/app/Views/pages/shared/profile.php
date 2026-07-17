<?php
$initials = implode('', array_map(fn ($w) => strtoupper($w[0]), array_slice(explode(' ', $profile['name'] ?? 'U'), 0, 2)));
$photoUrl = ! empty($profile['photo']) ? base_url('uploads/avatars/' . $profile['photo']) : null;

include APPPATH . 'Views/layout/header.php';
?>

<div class="page-header">
  <h4><i class="bi bi-person-circle me-2"></i>My Profile</h4>
  <p>Manage your photo and account credentials</p>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-check-circle-fill"></i><?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<div class="row g-4">
  <!-- Photo -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body text-center py-4">
        <div class="position-relative d-inline-block mb-3">
          <?php if ($photoUrl): ?>
          <img src="<?= e($photoUrl) ?>" alt="Profile photo"
               style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid #fff;box-shadow:0 0 0 3px var(--primary);">
          <?php else: ?>
          <div style="width:120px;height:120px;border-radius:50%;background:var(--primary);color:#fff;font-size:2.2rem;font-weight:700;display:flex;align-items:center;justify-content:center;border:3px solid #fff;box-shadow:0 0 0 3px var(--primary);margin:0 auto;">
            <?= e($initials) ?>
          </div>
          <?php endif; ?>

          <label for="photoInput" class="btn btn-primary btn-sm rounded-circle position-absolute"
                 style="width:36px;height:36px;padding:0;display:flex;align-items:center;justify-content:center;bottom:0;right:0;cursor:pointer;"
                 title="Change photo">
            <i class="bi bi-camera-fill"></i>
          </label>
        </div>

        <h6 class="fw-bold mb-0"><?= e($profile['name']) ?></h6>
        <span class="text-muted" style="font-size:.8rem;"><?= e(ucfirst($profile['role'])) ?></span>

        <form method="POST" action="<?= base_url('profile') ?>" class="ajax-form d-none" enctype="multipart/form-data"
              data-confirm-title="Update your profile photo?" id="photoForm">
          <input type="hidden" name="action" value="upload_photo">
          <input type="file" name="photo" id="photoInput" accept=".jpg,.jpeg,.png,.gif,.webp"
                 onchange="document.getElementById('photoForm').requestSubmit()">
        </form>

        <?php if ($photoUrl): ?>
        <form method="POST" action="<?= base_url('profile') ?>" class="ajax-form mt-3"
              data-confirm-title="Remove your profile photo?">
          <input type="hidden" name="action" value="remove_photo">
          <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-trash me-1"></i>Remove Photo</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Account info + password -->
  <div class="col-lg-8">
    <div class="card mb-4">
      <div class="card-header bg-white py-3">
        <span class="fw-semibold"><i class="bi bi-person-badge me-2 text-muted"></i>Account Information</span>
      </div>
      <div class="card-body">
        <form method="POST" action="<?= base_url('profile') ?>" class="ajax-form"
              data-confirm-title="Save changes to your account?">
          <input type="hidden" name="action" value="update_info">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Full Name</label>
              <input type="text" name="name" class="form-control" value="<?= e($profile['name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email Address</label>
              <input type="email" name="email" class="form-control" value="<?= e($profile['email']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Role</label>
              <input type="text" class="form-control" value="<?= e(ucfirst($profile['role'])) ?>" disabled>
            </div>
          </div>
          <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header bg-white py-3">
        <span class="fw-semibold"><i class="bi bi-shield-lock me-2 text-muted"></i>Change Password</span>
      </div>
      <div class="card-body">
        <form method="POST" action="<?= base_url('profile') ?>" class="ajax-form"
              data-confirm-title="Change your password?" data-confirm-icon="warning">
          <input type="hidden" name="action" value="change_password">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Current Password</label>
              <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-control" minlength="6" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-control" minlength="6" required>
            </div>
          </div>
          <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-key me-1"></i>Update Password</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include APPPATH . 'Views/layout/footer.php'; ?>

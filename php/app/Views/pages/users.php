<?php include APPPATH . 'Views/layout/header.php'; ?>

<div class="page-header">
  <h4><i class="bi bi-person-gear me-2"></i>User Management</h4>
  <p>Manage system accounts and role-based access control</p>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-check-circle-fill"></i><?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Search + Add -->
<div class="card mb-4">
  <div class="card-body py-3">
    <div class="d-flex gap-3 flex-wrap align-items-center">
      <form class="d-flex gap-2" style="flex:1;max-width:380px;">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search teachers by name or email..."
                 class="form-control border-start-0 ps-0">
        </div>
        <button class="btn btn-outline-secondary btn-sm">Search</button>
      </form>
      <button class="btn btn-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-person-plus me-1"></i>Add User
      </button>
    </div>
  </div>
</div>

<!-- User table -->
<div class="card">
  <div class="card-header">
    <div class="card-title">System Users</div>
    <div class="card-description"><?= count($users) ?> user<?= count($users)!==1?'s':'' ?> found</div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th class="text-center">Actions</th></tr>
        </thead>
        <tbody>
          <?php
          $roleCfg = [
            'admin'      => ['#fff0f0','#800000'],
            'teacher'    => ['#dbeafe','#1e40af'],
            'secretary'  => ['#eff6ff','#3730a3'],
            'canteen'    => ['#fef9c3','#713f12'],
            'disbursing' => ['#d1fae5','#065f46'],
            'adas'       => ['#f3f4f6','#374151'],
          ];
          foreach ($users as $i => $u):
            [$rbg,$rtc] = $roleCfg[$u['role']] ?? ['#f3f4f6','#374151'];
            $isMe = $u['id'] == currentUser()['id'];
          ?>
          <tr>
            <td class="text-muted"><?= $i+1 ?></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div style="width:30px;height:30px;border-radius:50%;background:<?= $rbg ?>;color:<?= $rtc ?>;font-size:.7rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <?= strtoupper(substr($u['name'],0,1)) ?>
                </div>
                <span class="fw-semibold"><?= e($u['name']) ?></span>
                <?php if ($isMe): ?>
                <span class="badge badge-secondary" style="font-size:.62rem;">You</span>
                <?php endif; ?>
              </div>
            </td>
            <td class="text-muted"><?= e($u['email']) ?></td>
            <td>
              <span class="badge" style="background:<?= $rbg ?>;color:<?= $rtc ?>;border:1px solid <?= $rtc ?>33;">
                <?= e(ucfirst($u['role'])) ?>
              </span>
            </td>
            <td class="text-muted" style="font-size:.78rem"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
            <td class="text-center">
              <button class="btn btn-ghost btn-sm"
                      onclick="resetPw(<?= $u['id'] ?>, '<?= e(addslashes($u['name'])) ?>')"
                      title="Reset Password">
                <i class="bi bi-key"></i>
              </button>
              <?php if (!$isMe): ?>
              <form method="POST" action="<?= base_url('users') ?>" class="d-inline ajax-form"
                    data-confirm-title="Delete <?= e(addslashes($u['name'])) ?>?"
                    data-confirm-text="This will permanently remove this user account.">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button class="btn btn-ghost btn-sm text-danger" title="Delete user">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add New User</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('users') ?>" class="ajax-form"
            data-confirm-text="A new account will be created and the user will be able to sign in immediately.">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Full Name</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label">Email Address</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label">Role</label>
              <select name="role" class="form-select">
                <option value="teacher">Teacher</option>
                <option value="secretary">Secretary</option>
                <option value="canteen">Canteen</option>
                <option value="disbursing">Disbursing</option>
                <option value="adas">ADAS</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required minlength="6">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPwModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-key me-2"></i>Reset Password</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('users') ?>" class="ajax-form"
            data-confirm-text="The user will need to use this new password to sign in.">
        <input type="hidden" name="action" value="reset_pw">
        <input type="hidden" name="id" id="resetUserId">
        <div class="modal-body">
          <p class="text-muted mb-3" style="font-size:.82rem;">
            Reset password for <strong id="resetUserName"></strong>
          </p>
          <label class="form-label">New Password</label>
          <input type="password" name="new_password" class="form-control" required minlength="6">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Reset Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extraScript = "<script>
function resetPw(id, name) {
    document.getElementById('resetUserId').value = id;
    document.getElementById('resetUserName').textContent = name;
    new bootstrap.Modal(document.getElementById('resetPwModal')).show();
}
</script>";
include APPPATH . 'Views/layout/footer.php';
?>

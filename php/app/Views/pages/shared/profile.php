<?php
$initials = implode('', array_map(fn ($w) => strtoupper($w[0]), array_slice(explode(' ', $profile['name'] ?? 'U'), 0, 2)));
$photoUrl = (! empty($profile['photo']) && is_file(FCPATH . 'uploads/avatars/' . $profile['photo']))
    ? base_url('uploads/avatars/' . $profile['photo'])
    : null;

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
               style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid var(--card);box-shadow:0 0 0 3px var(--primary);">
          <?php else: ?>
          <div style="width:120px;height:120px;border-radius:50%;background:var(--primary);color:#fff;font-size:2.2rem;font-weight:700;display:flex;align-items:center;justify-content:center;border:3px solid var(--card);box-shadow:0 0 0 3px var(--primary);margin:0 auto;">
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

  <?php if ($subjectLoad): ?>
  <!-- Subject load per term (teachers) -->
  <div class="col-12" id="subject-load">
    <div class="card">
      <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center gap-2">
        <span class="fw-semibold me-auto"><i class="bi bi-journal-bookmark me-2 text-muted"></i>Subject Load</span>
        <form method="GET" action="<?= base_url('profile') ?>" class="d-flex flex-wrap gap-2 align-items-center" id="subjectTermForm">
          <input type="text" name="sy" value="<?= e($subjectLoad['year']) ?>" list="subjectYearOptions"
                 class="form-control form-control-sm" style="width:120px;" pattern="\d{4}-\d{4}" title="e.g. 2026-2027"
                 onchange="this.form.requestSubmit()">
          <datalist id="subjectYearOptions">
            <?php foreach ($subjectLoad['years'] as $y): ?><option value="<?= e($y) ?>"><?php endforeach; ?>
          </datalist>
          <div class="maroon-select maroon-select-sm" style="width:auto;">
            <select name="term" class="maroon-select-native" onchange="this.form.requestSubmit()">
              <?php foreach ($subjectLoad['terms'] as $t): ?>
              <option value="<?= $t ?>" <?= $t === $subjectLoad['term'] ? 'selected' : '' ?>>Term <?= $t ?></option>
              <?php endforeach; ?>
            </select>
            <button type="button" class="maroon-select-display"><span class="maroon-select-label"></span><span class="maroon-select-caret"></span></button>
            <div class="maroon-select-panel"></div>
          </div>
        </form>
      </div>
      <div class="card-body">
        <p class="text-muted small mb-3">
          Your subjects change every term — list the ones you handle in
          <strong>Term <?= (int) $subjectLoad['term'] ?>, SY <?= e($subjectLoad['year']) ?></strong>.
          These decide which subjects and sections you enter MPS scores for.
          <?php if ($subjectLoad['source'] !== 'saved' && $subjectLoad['source'] !== 'none'): ?>
          <br><i class="bi bi-info-circle me-1"></i>Not saved for this term yet — pre-filled from <?= e($subjectLoad['source']) ?>. Edit what changed, then save.
          <?php endif; ?>
        </p>

        <form method="POST" action="<?= base_url('profile') ?>" class="ajax-form"
              data-confirm-title="Save your subject load for Term <?= (int) $subjectLoad['term'] ?>, SY <?= e($subjectLoad['year']) ?>?">
          <input type="hidden" name="action" value="save_subjects">
          <input type="hidden" name="school_year" value="<?= e($subjectLoad['year']) ?>">
          <input type="hidden" name="term" value="<?= (int) $subjectLoad['term'] ?>">

          <datalist id="subjectNameOptions">
            <?php foreach ($subjectLoad['subjects'] as $s): ?><option value="<?= e($s) ?>"><?php endforeach; ?>
          </datalist>

          <div id="subjectLoadList" class="d-flex flex-column gap-2"></div>
          <p class="text-muted small mb-0 d-none" id="subjectLoadEmpty">No subjects yet — add one for each section you teach.</p>

          <div class="d-flex flex-wrap gap-2 mt-3">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addLoadRow()">
              <i class="bi bi-plus-lg me-1"></i>Add Subject
            </button>
            <button type="submit" class="btn btn-primary btn-sm ms-auto"><i class="bi bi-check-lg me-1"></i>Save Subject Load</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php
if ($subjectLoad) {
    $initialRows = array_map(static fn ($r) => [
        'subject' => $r['subject'],
        'grade'   => $r['grade_level'] ?? '',
        'section' => $r['section'] ?? '',
    ], $subjectLoad['rows']);

    $extraScript = '<script>
const LOAD_GRADE_LEVELS = ' . json_encode($subjectLoad['gradeLevels']) . ';
let loadRowSeq = 0;

function loadEscape(v) {
    return String(v).replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/</g, "&lt;");
}

function addLoadRow(subject, grade, section) {
    const idx = loadRowSeq++;
    const gradeOptions = LOAD_GRADE_LEVELS.map(function (g) {
        return "<option value=\"" + g + "\"" + (g === grade ? " selected" : "") + ">" + g + "</option>";
    }).join("");

    document.getElementById("subjectLoadList").insertAdjacentHTML("beforeend",
        "<div class=\"d-flex flex-wrap gap-2 align-items-center load-row\">" +
          "<input type=\"text\" class=\"form-control form-control-sm\" style=\"flex:1 1 140px;\" list=\"subjectNameOptions\" placeholder=\"Subject (e.g. Science)\" required " +
            "name=\"subjects[" + idx + "][subject]\" value=\"" + loadEscape(subject || "") + "\">" +
          "<select class=\"form-select form-select-sm\" style=\"flex:1 1 120px;\" required name=\"subjects[" + idx + "][grade]\">" +
            "<option value=\"\">Grade</option>" + gradeOptions +
          "</select>" +
          "<input type=\"text\" class=\"form-control form-control-sm\" style=\"flex:1 1 140px;\" placeholder=\"Section (e.g. Matatag)\" required " +
            "name=\"subjects[" + idx + "][section]\" value=\"" + loadEscape(section || "") + "\">" +
          "<button type=\"button\" class=\"btn btn-ghost btn-sm text-danger\" title=\"Remove\" onclick=\"this.closest(\'.load-row\').remove(); syncLoadEmpty();\">" +
            "<i class=\"bi bi-x-lg\"></i></button>" +
        "</div>");
    syncLoadEmpty();
}

function syncLoadEmpty() {
    document.getElementById("subjectLoadEmpty").classList.toggle("d-none", document.getElementById("subjectLoadList").children.length > 0);
}

' . json_encode($initialRows) . '.forEach(function (r) { addLoadRow(r.subject, r.grade, r.section); });
syncLoadEmpty();
</script>';
}

include APPPATH . 'Views/layout/footer.php';
?>

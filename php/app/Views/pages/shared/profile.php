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
            <div class="col-md-6">
              <label class="form-label">Biometric No. (AC-No)</label>
              <input type="text" class="form-control" value="<?= ! empty($profile['ac_no']) ? 'AC-' . e($profile['ac_no']) : 'Not linked yet' ?>" disabled
                     title="Set by the principal or ADAS in User Management">
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
                 inputmode="numeric" maxlength="9" autocomplete="off" required placeholder="e.g. 2026-2027"
                 class="form-control form-control-sm sy-input" style="width:120px;" pattern="\d{4}-\d{4}" title="YYYY-YYYY, e.g. 2026-2027"
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
          Your subjects in <strong>Term <?= (int) $subjectLoad['term'] ?>, SY <?= e($subjectLoad['year']) ?></strong> —
          they decide which subjects and sections you enter MPS scores for.
          To add, change or remove one, fill out a request; it applies once the principal / ADAS approves it.
          <?php if ($subjectLoad['source'] !== 'saved' && $subjectLoad['source'] !== 'none'): ?>
          <br><i class="bi bi-info-circle me-1"></i>Not set for this term yet — showing <?= e($subjectLoad['source']) ?>. An approved request saves it for this term.
          <?php endif; ?>
        </p>

        <?php
          $loadLine     = static fn (?array $r) => e(\App\Models\SubjectLoadRequestModel::label($r));
          $pendingLabel = ['change' => 'Change requested', 'remove' => 'Removal requested'];
          $rowJson      = static fn (array $r) => e(json_encode(['subject' => $r['subject'], 'grade_level' => $r['grade_level'] ?? '', 'section' => $r['section'] ?? '']));
        ?>
        <div class="table-responsive mb-3">
          <table class="table table-sm align-middle mb-0 subject-load-table">
            <thead><tr><th>Subject</th><th>Grade Level</th><th>Section</th><th class="text-end">Request</th></tr></thead>
            <tbody>
              <?php foreach ($subjectLoad['rows'] as $r): ?>
              <tr>
                <td class="fw-semibold"><?= e($r['subject']) ?></td>
                <td><?= e($r['grade_level'] ?? '—') ?></td>
                <td><?= e($r['section'] ?? '—') ?></td>
                <td class="text-end text-nowrap">
                  <?php if ($r['pending']): ?>
                  <span class="status-pill badge-pending"><?= $pendingLabel[$r['pending']] ?? 'Requested' ?></span>
                  <?php else: ?>
                  <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openSubjectRequest('change', <?= $rowJson($r) ?>)"><i class="bi bi-pencil me-1"></i>Change</button>
                  <button type="button" class="btn btn-sm btn-outline-danger" onclick="openSubjectRequest('remove', <?= $rowJson($r) ?>)"><i class="bi bi-trash me-1"></i>Remove</button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (! $subjectLoad['rows']): ?>
              <tr><td colspan="4" class="text-center text-muted py-3">No subjects yet — request one for each section you teach.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <button type="button" class="btn btn-primary btn-sm" onclick="openSubjectRequest('add', null)">
          <i class="bi bi-plus-lg me-1"></i>Request to Add Subject
        </button>

        <?php if ($subjectLoad['requests']): ?>
        <h6 class="fw-semibold small text-muted text-uppercase mt-4 mb-2" style="letter-spacing:.04em;">My requests for this term</h6>
        <div class="d-flex flex-column gap-2">
          <?php foreach ($subjectLoad['requests'] as $q): $c = $q['change']; ?>
          <div class="subject-request-item status-<?= strtolower($q['status']) ?>">
            <div class="d-flex flex-wrap align-items-start gap-2">
              <span class="badge badge-change-<?= e($c['action']) ?>"><?= e(\App\Models\SubjectLoadRequestModel::ACTIONS[$c['action']]) ?></span>
              <div class="flex-grow-1 min-w-0 small">
                <?php if ($c['action'] === 'change'): ?>
                <?= $loadLine($c['from']) ?> <i class="bi bi-arrow-right mx-1"></i> <strong><?= $loadLine($c['to']) ?></strong>
                <?php else: ?>
                <strong><?= $loadLine($c['action'] === 'add' ? $c['to'] : $c['from']) ?></strong>
                <?php endif; ?>
                <div class="text-muted">Reason: <?= e($q['reason'] ?? '') ?></div>
                <?php if ($q['status'] !== 'Pending'): ?>
                <div class="<?= $q['status'] === 'Approved' ? 'text-success' : 'text-danger' ?>">
                  <?= e($q['status']) ?><?= $q['reviewer_name'] ? ' by ' . e($q['reviewer_name']) : '' ?> on <?= date('M d, Y', strtotime($q['reviewed_at'])) ?><?= $q['review_note'] ? ' — ' . e($q['review_note']) : '' ?>
                </div>
                <?php endif; ?>
              </div>
              <?php if ($q['status'] === 'Pending'): ?>
              <span class="status-pill badge-pending">Waiting for approval</span>
              <form method="POST" action="<?= base_url('profile') ?>" class="ajax-form"
                    data-confirm-title="Cancel this request?" data-confirm-text="Your subject load stays as it is.">
                <input type="hidden" name="action" value="cancel_subject_request">
                <input type="hidden" name="request_id" value="<?= (int) $q['id'] ?>">
                <button class="btn btn-sm btn-outline-secondary">Cancel</button>
              </form>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Subject request form: add / change / remove one subject (a reason is required) -->
  <div class="modal fade" id="subjectRequestModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="<?= base_url('profile') ?>" class="ajax-form" id="subjectRequestForm">
          <div class="modal-header" style="background:var(--maroon);color:#fff;">
            <h6 class="modal-title fw-bold"><i class="bi bi-journal-plus me-2"></i><span id="subjectRequestTitle">Request</span></h6>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="request_subject">
            <input type="hidden" name="kind" id="srKind">
            <input type="hidden" name="school_year" value="<?= e($subjectLoad['year']) ?>">
            <input type="hidden" name="term" value="<?= (int) $subjectLoad['term'] ?>">
            <input type="hidden" name="from_subject" id="srFromSubject">
            <input type="hidden" name="from_grade" id="srFromGrade">
            <input type="hidden" name="from_section" id="srFromSection">

            <p class="small text-muted mb-3">For <strong>Term <?= (int) $subjectLoad['term'] ?>, SY <?= e($subjectLoad['year']) ?></strong>. The principal / ADAS will review it.</p>

            <div class="mb-3 d-none" id="srFromBox">
              <label class="form-label small fw-semibold mb-1" id="srFromLabel">Current subject</label>
              <div class="form-control form-control-sm bg-light" id="srFromText"></div>
            </div>

            <div id="srToBox">
              <div class="row g-2 mb-3">
                <div class="col-12">
                  <label class="form-label small fw-semibold mb-1" for="srToSubject" id="srToLabel">Subject</label>
                  <input type="text" class="form-control form-control-sm" name="to_subject" id="srToSubject" list="subjectNameOptions" maxlength="100" placeholder="e.g. Science">
                </div>
                <div class="col-6">
                  <label class="form-label small fw-semibold mb-1" for="srToGrade">Grade Level</label>
                  <select class="form-select form-select-sm" name="to_grade" id="srToGrade">
                    <option value="">Choose…</option>
                    <?php foreach ($subjectLoad['gradeLevels'] as $g): ?><option value="<?= e($g) ?>"><?= e($g) ?></option><?php endforeach; ?>
                  </select>
                </div>
                <div class="col-6">
                  <label class="form-label small fw-semibold mb-1" for="srToSection">Section</label>
                  <input type="text" class="form-control form-control-sm" name="to_section" id="srToSection" list="sectionOptions" maxlength="100" placeholder="e.g. Matatag">
                </div>
              </div>
            </div>
            <datalist id="subjectNameOptions"><?php foreach ($subjectLoad['subjects'] as $s): ?><option value="<?= e($s) ?>"><?php endforeach; ?></datalist>
            <datalist id="sectionOptions"></datalist>

            <label class="form-label small fw-semibold mb-1" for="srReason">Reason</label>
            <textarea class="form-control form-control-sm" name="reason" id="srReason" rows="3" maxlength="1000" required
                      placeholder="e.g. I was assigned this class at the start of the term."></textarea>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Send Request</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php
if ($subjectLoad) {
    $extraScript = '<script>
// Subject request form (Profile -> Subject Load): one modal for add / change / remove.
window.SUBJECT_SECTIONS = ' . json_encode($subjectLoad['sections']) . ';

window.openSubjectRequest = function (kind, row) {
    var titles = { add: "Request to Add a Subject", change: "Request to Change a Subject", remove: "Request to Remove a Subject" };
    var f = document.getElementById("subjectRequestForm");
    f.reset();
    document.getElementById("subjectRequestTitle").textContent = titles[kind];
    document.getElementById("srKind").value = kind;
    document.getElementById("srFromSubject").value = row ? row.subject : "";
    document.getElementById("srFromGrade").value = row ? row.grade_level : "";
    document.getElementById("srFromSection").value = row ? row.section : "";

    var fromBox = document.getElementById("srFromBox");
    fromBox.classList.toggle("d-none", kind === "add");
    document.getElementById("srFromLabel").textContent = kind === "remove" ? "Subject to remove" : "Current subject";
    document.getElementById("srFromText").textContent = row ? [row.subject, row.grade_level, row.section].join(" · ") : "";

    // Add / Change fill out the new subject; Remove only needs a reason.
    var needsTo = kind !== "remove";
    document.getElementById("srToBox").classList.toggle("d-none", !needsTo);
    document.getElementById("srToLabel").textContent = kind === "change" ? "New subject" : "Subject";
    ["srToSubject", "srToGrade", "srToSection"].forEach(function (id) { document.getElementById(id).required = needsTo; });
    if (kind === "change" && row) {
        document.getElementById("srToSubject").value = row.subject;
        document.getElementById("srToGrade").value = row.grade_level;
        document.getElementById("srToSection").value = row.section;
    }
    fillSectionOptions();
    bootstrap.Modal.getOrCreateInstance(document.getElementById("subjectRequestModal")).show();
};

// Section suggestions follow the chosen grade level.
function fillSectionOptions() {
    var grade = document.getElementById("srToGrade").value;
    var list = (window.SUBJECT_SECTIONS[grade] || []);
    document.getElementById("sectionOptions").innerHTML = list.map(function (s) {
        return "<option value=\"" + String(s).replace(/&/g, "&amp;").replace(/\"/g, "&quot;").replace(/</g, "&lt;") + "\">";
    }).join("");
}
document.getElementById("srToGrade")?.addEventListener("change", fillSectionOptions);
</script>';
}

include APPPATH . 'Views/layout/footer.php';
?>

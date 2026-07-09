<?php
require_once __DIR__ . '/../config.php';
requireLogin();
define('PAGE_TITLE', 'DepEd Documents');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasRole('admin','adas')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $db->prepare("INSERT INTO deped_documents (document_type,description,due_date,status,completion_rate,prepared_by,last_updated) VALUES (?,?,?,?,?,?,?)")
           ->execute([$_POST['document_type'],$_POST['description'],$_POST['due_date'],
                      $_POST['status'],(int)$_POST['completion_rate'],currentUser()['name'],date('Y-m-d')]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Document added.'];
    } elseif ($action === 'update') {
        $rate = (int)$_POST['completion_rate'];
        $status = $_POST['status'];
        if ($rate >= 100) $status = 'Completed';
        $db->prepare("UPDATE deped_documents SET status=?,completion_rate=?,last_updated=? WHERE id=?")
           ->execute([$status,$rate,date('Y-m-d'),(int)$_POST['id']]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Document progress updated.'];
    }
    header('Location: ' . BASE_URL . 'deped-documents'); exit;
}

$docs  = $db->query("SELECT * FROM deped_documents ORDER BY due_date ASC")->fetchAll();
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

$statusCount = ['Completed'=>0,'In Progress'=>0,'Pending'=>0];
foreach ($docs as $d) { if (isset($statusCount[$d['status']])) $statusCount[$d['status']]++; }

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3" style="position:relative;z-index:1">
    <div>
      <h4><i class="bi bi-clipboard2-check me-2"></i>DepEd Required Documents</h4>
      <p>Track completion of all DepEd-mandated forms and reports</p>
    </div>
    <?php if (hasRole('admin','adas')): ?>
    <button class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);"
            data-bs-toggle="modal" data-bs-target="#addDocModal">
      <i class="bi bi-plus-lg me-1"></i>Add Document
    </button>
    <?php endif; ?>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-check-circle-fill"></i><?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Summary cards -->
<div class="row g-4 mb-4">
  <?php foreach ([
    ['Completed',   'bi-check-circle-fill', '#d1fae5','#065f46', $statusCount['Completed']],
    ['In Progress', 'bi-hourglass-split',   '#fef9c3','#713f12', $statusCount['In Progress']],
    ['Pending',     'bi-clock',             '#fee2e2','#991b1b', $statusCount['Pending']],
    ['Total',       'bi-files',             '#dbeafe','#1e40af', count($docs)],
  ] as [$lbl,$ico,$bg,$tc,$cnt]): ?>
  <div class="col-6 col-xl-3">
    <div class="card">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="kpi-icon" style="background:<?= $bg ?>">
          <i class="bi <?= $ico ?>" style="color:<?= $tc ?>;font-size:1.1rem;"></i>
        </div>
        <div>
          <div style="font-size:1.6rem;font-weight:700;color:<?= $tc ?>"><?= $cnt ?></div>
          <div class="text-muted" style="font-size:.78rem"><?= $lbl ?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Document cards grid -->
<div class="row g-4">
  <?php foreach ($docs as $d):
    $overdue = $d['status'] !== 'Completed' && strtotime($d['due_date']) < time();
    $cfg = [
      'Completed'   => ['#d1fae5','#065f46','#a7f3d0','success'],
      'In Progress' => ['#fef9c3','#713f12','#fde68a','warning'],
      'Pending'     => ['#fee2e2','#991b1b','#fecaca','danger'],
    ][$d['status']] ?? ['#f9fafb','#374151','#e5e7eb','secondary'];
    [$bg,$tc,$bc,$bs] = $cfg;
    $pct = (int)$d['completion_rate'];
  ?>
  <div class="col-md-6 col-xl-4">
    <div class="card h-100 <?= $overdue ? 'border-danger' : '' ?>" style="transition:.2s;">
      <div class="card-body">
        <!-- Status + overdue -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="badge" style="background:<?= $bg ?>;color:<?= $tc ?>;border:1px solid <?= $bc ?>;">
            <?= e($d['status']) ?>
          </span>
          <?php if ($overdue): ?>
          <span class="badge" style="background:#fee2e2;color:#991b1b;border:1px solid #fecaca;">
            <i class="bi bi-exclamation-triangle me-1"></i>Overdue
          </span>
          <?php endif; ?>
        </div>

        <!-- Title & desc -->
        <h6 class="fw-bold mb-1" style="font-size:.88rem"><?= e($d['document_type']) ?></h6>
        <p class="text-muted mb-3" style="font-size:.78rem"><?= e($d['description']) ?></p>

        <!-- Progress -->
        <div class="d-flex justify-content-between mb-1" style="font-size:.75rem">
          <span class="text-muted">Completion</span>
          <span class="fw-semibold" style="color:<?= $tc ?>"><?= $pct ?>%</span>
        </div>
        <div class="progress mb-3" style="height:8px;">
          <div class="progress-bar <?= $pct >= 100 ? 'success' : ($pct >= 50 ? '' : 'warning') ?>"
               style="width:<?= $pct ?>%;background:<?= $tc ?>!important;"></div>
        </div>

        <!-- Meta -->
        <div class="d-flex justify-content-between" style="font-size:.72rem;color:#9ca3af">
          <span><i class="bi bi-person me-1"></i><?= e($d['prepared_by']) ?></span>
          <span><i class="bi bi-calendar-event me-1"></i>Due: <?= date('M d, Y', strtotime($d['due_date'])) ?></span>
        </div>
      </div>

      <?php if (hasRole('admin','adas')): ?>
      <div class="card-body pt-0">
        <button class="btn btn-outline-secondary btn-sm w-100"
                onclick="updateDoc(<?= htmlspecialchars(json_encode($d),ENT_QUOTES) ?>)">
          <i class="bi bi-pencil me-1"></i>Update Progress
        </button>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addDocModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add DepEd Document</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Document Type</label>
              <input type="text" name="document_type" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <input type="text" name="description" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label">Due Date</label>
              <input type="date" name="due_date" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option>Pending</option><option>In Progress</option><option>Completed</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">Completion %</label>
              <input type="number" name="completion_rate" class="form-control" value="0" min="0" max="100">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Document</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Update Modal -->
<div class="modal fade" id="updateModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-pencil me-2"></i>Update Progress</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="updId">
        <div class="modal-body">
          <p class="fw-semibold mb-3" id="updTitle" style="font-size:.85rem"></p>
          <div class="mb-3">
            <label class="form-label">Completion %</label>
            <input type="range" name="completion_rate" id="updRange" class="form-range" min="0" max="100"
                   oninput="document.getElementById('updPct').textContent=this.value+'%'">
            <div class="text-center fw-bold" id="updPct" style="color:var(--primary)">0%</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" id="updStatus" class="form-select">
              <option>Pending</option><option>In Progress</option><option>Completed</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extraScript = "<script>
function updateDoc(d) {
    document.getElementById('updId').value = d.id;
    document.getElementById('updTitle').textContent = d.document_type;
    const range = document.getElementById('updRange');
    range.value = d.completion_rate;
    document.getElementById('updPct').textContent = d.completion_rate + '%';
    document.getElementById('updStatus').value = d.status;
    new bootstrap.Modal(document.getElementById('updateModal')).show();
}
</script>";
include __DIR__ . '/../includes/footer.php';
?>

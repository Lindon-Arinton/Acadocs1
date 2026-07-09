<?php
require_once __DIR__ . '/../config.php';
requireLogin();
define('PAGE_TITLE', 'Document Links');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasRole('admin','secretary')) {
    if ($_POST['action'] === 'add') {
        $db->prepare("INSERT INTO document_links (category,title,description,url,added_by,date_added,access_level) VALUES (?,?,?,?,?,?,?)")
           ->execute([$_POST['category'],$_POST['title'],$_POST['description'],$_POST['url'],
                      currentUser()['name'],date('Y-m-d'),$_POST['access_level']]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Link added.'];
    } elseif ($_POST['action'] === 'delete') {
        $db->prepare("DELETE FROM document_links WHERE id=?")->execute([(int)$_POST['id']]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Link removed.'];
    }
    header('Location: ' . BASE_URL . 'document-links'); exit;
}

$cat    = $_GET['category'] ?? 'all';
$where  = $cat !== 'all' ? "WHERE category=?" : "";
$params = $cat !== 'all' ? [$cat] : [];
$stmt   = $db->prepare("SELECT * FROM document_links $where ORDER BY date_added DESC");
$stmt->execute($params);
$links  = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

$catCfg = [
    'Forms'          => ['#fff0f0','#800000','bi-file-earmark-text-fill'],
    'Questionnaires' => ['#eff6ff','#1e40af','bi-card-checklist'],
    'Templates'      => ['#f0fdf4','#065f46','bi-file-earmark-code-fill'],
    'Guidelines'     => ['#fffbeb','#713f12','bi-book-fill'],
];

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3" style="position:relative;z-index:1">
    <div>
      <h4><i class="bi bi-link-45deg me-2"></i>Document Links</h4>
      <p>Quick access to forms, templates, questionnaires &amp; DepEd guidelines</p>
    </div>
    <?php if (hasRole('admin','secretary')): ?>
    <button class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);"
            data-bs-toggle="modal" data-bs-target="#addLinkModal">
      <i class="bi bi-plus-lg me-1"></i>Add Link
    </button>
    <?php endif; ?>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-check-circle-fill"></i><?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Category filter (tab pills) -->
<div class="tab-pills mb-4">
  <?php foreach (['all'=>'All','Forms'=>'Forms','Questionnaires'=>'Questionnaires','Templates'=>'Templates','Guidelines'=>'Guidelines'] as $v=>$l): ?>
  <button class="tab-pill <?= $cat===$v?'active':'' ?>" onclick="window.location='?category=<?= $v ?>'">
    <?= $l ?>
  </button>
  <?php endforeach; ?>
</div>

<!-- Link cards grid -->
<div class="row g-4">
  <?php foreach ($links as $link):
    [$bg,$tc,$icon] = $catCfg[$link['category']] ?? ['#f9fafb','#374151','bi-link'];
  ?>
  <div class="col-md-6 col-xl-4">
    <div class="doclink-card">
      <div class="d-flex gap-3 align-items-start">
        <div style="width:40px;height:40px;border-radius:10px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i class="bi <?= $icon ?>" style="color:<?= $tc ?>;font-size:1.1rem;"></i>
        </div>
        <div class="flex-grow-1">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <span class="badge mb-1" style="background:<?= $bg ?>;color:<?= $tc ?>;border:1px solid <?= $tc ?>22;font-size:.68rem;">
              <?= e($link['category']) ?>
            </span>
            <?php if (hasRole('admin','secretary')): ?>
            <form method="POST" onsubmit="return confirm('Remove this link?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $link['id'] ?>">
              <button class="btn btn-ghost btn-sm text-danger py-0 px-1"><i class="bi bi-x-lg"></i></button>
            </form>
            <?php endif; ?>
          </div>
          <h6 class="fw-bold mb-1" style="font-size:.88rem"><?= e($link['title']) ?></h6>
          <p class="text-muted mb-3" style="font-size:.78rem;line-height:1.5;"><?= e($link['description']) ?></p>
          <a href="<?= e($link['url']) ?>" target="_blank" rel="noopener"
             class="btn btn-sm w-100" style="border:1.5px solid <?= $tc ?>;color:<?= $tc ?>;background:transparent;">
            <i class="bi bi-box-arrow-up-right me-1"></i>Open Link
          </a>
        </div>
      </div>
      <div class="d-flex justify-content-between mt-3 pt-2 border-top" style="font-size:.7rem;color:#9ca3af;">
        <span><i class="bi bi-person me-1"></i><?= e($link['added_by']) ?></span>
        <span><?= date('M d, Y', strtotime($link['date_added'])) ?></span>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($links)): ?>
  <div class="col-12">
    <div class="card"><div class="card-body text-center py-5">
      <i class="bi bi-link-45deg fs-1 d-block mb-3 text-muted"></i>
      <p class="text-muted mb-0">No links found in this category.</p>
    </div></div>
  </div>
  <?php endif; ?>
</div>

<!-- Add Link Modal -->
<div class="modal fade" id="addLinkModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Document Link</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label">Category</label>
              <select name="category" class="form-select">
                <option>Forms</option><option>Questionnaires</option>
                <option>Templates</option><option>Guidelines</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">Access Level</label>
              <select name="access_level" class="form-select">
                <option>All Users</option><option>Teachers</option><option>Admin</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Title</label>
              <input type="text" name="title" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">URL</label>
              <input type="url" name="url" class="form-control" required placeholder="https://...">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Link</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

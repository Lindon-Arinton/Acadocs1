<?php include APPPATH . 'Views/layout/header.php'; ?>

<!-- Page Header -->
<div class="page-header">
  <h4><i class="bi bi-cash-stack me-2"></i>Financial Transparency Reports</h4>
  <p>Real-time tracking of canteen operations and school fund disbursements</p>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-check-circle-fill"></i><?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Tab Switcher (React-style TabsList) -->
<div class="mb-4">
  <div class="tab-pills" id="finTabPills">
    <button class="tab-pill active" onclick="switchTab('canteen', this)">
      <i class="bi bi-shop me-2"></i>Canteen Reports
    </button>
    <button class="tab-pill" onclick="switchTab('funds', this)">
      <i class="bi bi-bank me-2"></i>School Funds
    </button>
  </div>
</div>

<!-- ── CANTEEN TAB ─────────────────────────────────────────── -->
<div id="tab-canteen">
  <!-- 4 summary cards -->
  <div class="row g-4 mb-4">
    <?php foreach ([
      ['Total Revenue',      '₱'.number_format($canteenTotals['revenue'],2),      'bi-trending-up',    '#560000'],
      ['Total Expenses',     '₱'.number_format($canteenTotals['expenses'],2),     'bi-trending-down',  '#800000'],
      ['Net Income',         '₱'.number_format($canteenTotals['netIncome'],2),    'bi-wallet2',        '#560000'],
      ['Total Transactions', number_format($canteenTotals['transactions']),        'bi-receipt',        '#374151'],
    ] as [$lbl,$val,$ico,$tc]): ?>
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="card-description mb-2"><?= $lbl ?></div>
          <div class="d-flex align-items-center justify-content-between">
            <div style="font-size:1.5rem;font-weight:700;color:<?= $tc ?>"><?= $val ?></div>
            <i class="bi <?= $ico ?>" style="font-size:1.2rem;color:<?= $tc ?>"></i>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Canteen table card -->
  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <div>
        <div class="card-title">Canteen Financial Records</div>
        <div class="card-description">Weekly sales and expenses tracking</div>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="exportTable('canteen-table','canteen_records')">
          <i class="bi bi-download me-1"></i>Export
        </button>
        <?php if (hasRole('admin','canteen')): ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCanteenModal">
          <i class="bi bi-plus-lg me-1"></i>Add Record
        </button>
        <?php endif; ?>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table mb-0" id="canteen-table">
          <thead>
            <tr>
              <th>Date</th><th>Description</th>
              <th class="text-right">Revenue</th>
              <th class="text-right">Expenses</th>
              <th class="text-right">Net Income</th>
              <th class="text-right">Transactions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_reverse($canteenRecords) as $r): ?>
            <tr>
              <td><?= date('M d, Y', strtotime($r['date'])) ?></td>
              <td><?= e($r['description']) ?></td>
              <td class="text-right" style="color:#560000">₱<?= number_format($r['revenue'],2) ?></td>
              <td class="text-right" style="color:#800000">₱<?= number_format($r['expenses'],2) ?></td>
              <td class="text-right fw-semibold">₱<?= number_format($r['net_income'],2) ?></td>
              <td class="text-right"><?= number_format($r['transaction_count']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- ── SCHOOL FUNDS TAB ────────────────────────────────────── -->
<div id="tab-funds" style="display:none;">
  <!-- Current balance (big card like React) -->
  <div class="balance-card mb-4">
    <div class="balance-label">Current Balance</div>
    <div class="balance-value">₱<?= number_format($currentBalance, 2) ?></div>
  </div>

  <!-- Funds table card -->
  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <div>
        <div class="card-title">School Fund Disbursements</div>
        <div class="card-description">Transparent tracking of all fund allocations and expenses</div>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="exportTable('fund-table','school_funds')">
          <i class="bi bi-download me-1"></i>Export
        </button>
        <?php if (hasRole('admin','disbursing')): ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFundModal">
          <i class="bi bi-plus-lg me-1"></i>Add Disbursement
        </button>
        <?php endif; ?>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table mb-0" id="fund-table">
          <thead>
            <tr>
              <th>Date</th><th>Category</th><th>Description</th>
              <th>Particulars</th>
              <th class="text-right">Amount</th>
              <th class="text-right">Balance</th>
              <th>Prepared By</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_reverse($fundRecords) as $r): ?>
            <tr>
              <td><?= date('M d, Y', strtotime($r['date'])) ?></td>
              <td><span class="badge badge-outline"><?= e($r['category']) ?></span></td>
              <td><?= e($r['description']) ?></td>
              <td class="text-muted truncate" style="max-width:180px"><?= e($r['particulars']) ?></td>
              <td class="text-right" style="color:#800000">₱<?= number_format(abs($r['amount']),2) ?></td>
              <td class="text-right fw-semibold">₱<?= number_format($r['balance'],2) ?></td>
              <td class="text-muted"><?= e($r['prepared_by']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- ── Add Canteen Modal ───────────────────────────────────── -->
<div class="modal fade" id="addCanteenModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Canteen Record</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('financial-reports') ?>">
        <input type="hidden" name="type" value="canteen">
        <div class="modal-body">
          <p class="text-muted mb-3" style="font-size:.8rem;">Record daily sales and expenses</p>
          <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <input type="text" name="description" class="form-control" value="Daily Sales - Breakfast &amp; Snacks" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Revenue (₱)</label>
            <input type="number" name="revenue" class="form-control" step="0.01" placeholder="8500.00" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Expenses (₱)</label>
            <input type="number" name="expenses" class="form-control" step="0.01" placeholder="3200.00" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Transaction Count</label>
            <input type="number" name="transaction_count" class="form-control" placeholder="150" value="0">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Record</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── Add Fund Disbursement Modal ────────────────────────── -->
<div class="modal fade" id="addFundModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Fund Disbursement</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('financial-reports') ?>">
        <input type="hidden" name="type" value="fund">
        <div class="modal-body">
          <p class="text-muted mb-3" style="font-size:.8rem;">Record fund allocation or expense</p>
          <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category" class="form-select" required>
              <option>MOOE</option><option>Capital Outlay</option>
              <option>Maintenance</option><option>Utilities</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <input type="text" name="description" class="form-control" placeholder="Office supplies" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Particulars</label>
            <input type="text" name="particulars" class="form-control" placeholder="Bond paper, ink, pens">
          </div>
          <div class="mb-3">
            <label class="form-label">Amount (₱)</label>
            <input type="number" name="amount" class="form-control" step="0.01" placeholder="15420.00" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Disbursement</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extraScript = '<script>
function switchTab(id, btn) {
    document.querySelectorAll("[id^=tab-]").forEach(el => el.style.display = "none");
    document.getElementById("tab-" + id).style.display = "block";
    document.querySelectorAll(".tab-pill").forEach(b => b.classList.remove("active"));
    btn.classList.add("active");
}

function exportTable(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows  = [...table.querySelectorAll("tr")].map(r =>
        [...r.querySelectorAll("th,td")].map(c => JSON.stringify(c.innerText.trim())).join(",")
    );
    const blob = new Blob([rows.join("\n")], { type: "text/csv" });
    const a = Object.assign(document.createElement("a"), { href: URL.createObjectURL(blob), download: filename + ".csv" });
    a.click();
}
</script>';
include APPPATH . 'Views/layout/footer.php';
?>

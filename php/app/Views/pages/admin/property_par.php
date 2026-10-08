<?php
/**
 * Printable Property Acknowledgment Receipt (standard PAR layout).
 * Unit, Property Number and Amount are left blank to be filled in by hand
 * — the inventory doesn't track them.
 */
$statusNote = match ($par['status']) {
    'Approved' => 'Acknowledged electronically by ' . ($issuedTo['name'] ?? 'the end user') . ' on ' . date('F d, Y h:i A', strtotime($par['responded_at'])),
    'Returned' => 'Returned by ' . ($issuedTo['name'] ?? 'the end user') . ' on ' . date('F d, Y', strtotime($par['responded_at'])) . ($par['remarks'] ? ' — ' . $par['remarks'] : ''),
    default    => 'Awaiting acknowledgment by ' . ($issuedTo['name'] ?? 'the end user'),
};
$minRows = 12;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($par['par_no']) ?> — Property Acknowledgment Receipt</title>
<link rel="icon" type="image/png" href="<?= base_url('assets/img/logo-icon.png') ?>">
<style>
  :root { --ink: #111; --line: #222; --muted: #555; --maroon: #800000; }
  * { box-sizing: border-box; }
  body { margin: 0; background: #e9e9ec; color: var(--ink); font: 13px/1.4 Arial, Helvetica, sans-serif; }
  .toolbar {
    position: sticky; top: 0; z-index: 2;
    display: flex; gap: .5rem; justify-content: center; align-items: center; flex-wrap: wrap;
    padding: .75rem 16px; background: #fff; border-bottom: 1px solid #ddd;
  }
  .toolbar button {
    border: 0; border-radius: 8px; padding: .5rem 1rem; font: 600 13px Arial, sans-serif; cursor: pointer;
    background: var(--maroon); color: #fff;
  }
  .toolbar .ghost { background: #f1f1f3; color: #333; }
  .status { font-size: 12px; padding: .3rem .7rem; border-radius: 999px; font-weight: 700; }
  .status-Pending  { background: #fef9c3; color: #713f12; }
  .status-Approved { background: #d1fae5; color: #065f46; }
  .status-Returned { background: #fee2e2; color: #991b1b; }

  .sheet {
    width: 210mm; max-width: calc(100% - 32px); min-height: 297mm;
    margin: 24px auto; padding: 16mm 14mm; background: #fff;
    box-shadow: 0 6px 30px rgba(0,0,0,.12);
  }
  .appendix { text-align: right; font-style: italic; font-size: 11px; color: var(--muted); }
  .head { text-align: center; margin: 6px 0 14px; }
  .head img { width: 58px; height: 58px; object-fit: contain; }
  .head .rep { font-size: 11px; }
  .head h1 { font-size: 16px; letter-spacing: .04em; margin: 10px 0 0; }
  .meta { display: grid; grid-template-columns: 1fr auto; gap: 4px 24px; margin-bottom: 8px; }
  .meta span { display: inline-block; min-width: 180px; border-bottom: 1px solid var(--line); padding: 0 4px; font-weight: 700; }

  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid var(--line); padding: 5px 6px; vertical-align: top; }
  th { font-size: 12px; background: #f3f3f3; }
  td.c { text-align: center; }
  .desc small { display: block; color: var(--muted); font-size: 11px; }
  tr.blank td { height: 24px; }

  .sign { display: grid; grid-template-columns: 1fr 1fr; border: 1px solid var(--line); border-top: 0; }
  .sign > div { padding: 8px 10px 12px; }
  .sign > div + div { border-left: 1px solid var(--line); }
  .sign .label { font-size: 12px; }
  .sign .name { margin-top: 34px; text-align: center; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid var(--line); }
  .sign .cap { text-align: center; font-size: 11px; color: var(--muted); }
  .sign .line { margin-top: 10px; text-align: center; border-bottom: 1px solid var(--line); min-height: 18px; }
  .note { margin-top: 10px; font-size: 11px; color: var(--muted); }

  @page { size: A4; margin: 12mm; }
  @media print {
    body { background: #fff; }
    .toolbar { display: none; }
    .sheet { width: auto; max-width: none; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
  }
  @media (max-width: 600px) {
    .sheet { padding: 16px; }
    .meta { grid-template-columns: 1fr; }
    .table-wrap { overflow-x: auto; }
  }
</style>
</head>
<body>
<div class="toolbar">
  <span class="status status-<?= e($par['status']) ?>"><?= e($par['status']) ?></span>
  <button type="button" onclick="window.print()">Print / Save as PDF</button>
  <button type="button" class="ghost" onclick="window.close()">Close</button>
</div>

<div class="sheet">
  <div class="appendix">Appendix 71</div>
  <div class="head">
    <img src="<?= base_url('assets/img/logo-icon.png') ?>" alt="">
    <div class="rep">Republic of the Philippines<br>Department of Education</div>
    <h1>PROPERTY ACKNOWLEDGMENT RECEIPT</h1>
  </div>

  <div class="meta">
    <div>Entity Name: <span>Matabungkay National High School</span></div>
    <div>PAR No.: <span><?= e($par['par_no']) ?></span></div>
    <div>Fund Cluster: <span>&nbsp;</span></div>
    <div>Date: <span><?= date('F d, Y', strtotime($par['created_at'])) ?></span></div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:9%">Quantity</th>
          <th style="width:8%">Unit</th>
          <th>Description</th>
          <th style="width:15%">Property Number</th>
          <th style="width:13%">Date Acquired</th>
          <th style="width:11%">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
          <td class="c"><?= (int) $item['quantity'] ?></td>
          <td></td>
          <td class="desc">
            <?= e($item['item_name']) ?>
            <small><?= e($item['grade']) ?> – <?= e($item['section']) ?> · <?= e($item['condition_status']) ?> · <?= e($item['acquisition']) ?></small>
          </td>
          <td></td>
          <td class="c"><?= $item['date_acquired'] ? date('m/d/Y', strtotime($item['date_acquired'])) : '' ?></td>
          <td></td>
        </tr>
        <?php endforeach; ?>
        <?php for ($i = count($items); $i < $minRows; $i++): ?>
        <tr class="blank"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        <?php endfor; ?>
      </tbody>
    </table>
  </div>

  <div class="sign">
    <div>
      <div class="label">Received by:</div>
      <div class="name"><?= e($issuedTo['name'] ?? '') ?></div>
      <div class="cap">Signature over Printed Name of End User</div>
      <div class="line"><?= e($issuedTo['position'] ?? 'Teacher') ?></div>
      <div class="cap">Position/Office</div>
      <div class="line"><?= $par['status'] === 'Approved' ? date('m/d/Y', strtotime($par['responded_at'])) : '' ?></div>
      <div class="cap">Date</div>
    </div>
    <div>
      <div class="label">Issued by:</div>
      <div class="name"><?= e($issuedBy['name'] ?? '') ?></div>
      <div class="cap">Signature over Printed Name of Supply and/or Property Custodian</div>
      <div class="line"><?= e($issuedBy['position'] ?? 'Administrative Assistant (ADAS)') ?></div>
      <div class="cap">Position/Office</div>
      <div class="line"><?= date('m/d/Y', strtotime($par['created_at'])) ?></div>
      <div class="cap">Date</div>
    </div>
  </div>

  <div class="note">Status: <?= e($statusNote) ?>. Generated by ACADOCS.</div>
</div>
</body>
</html>

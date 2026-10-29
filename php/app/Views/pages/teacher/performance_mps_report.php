<?php
/**
 * Printable MPS report a teacher submits for validation — one row per class
 * handled (grade · subject · section) with each test period's MPS, the
 * average and its DepEd descriptor, then signature blocks for the teacher,
 * the validating coordinator and the school head.
 */
$fmt = static fn (?float $v) => $v === null ? '—' : number_format($v, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MPS Validation Report — Term <?= (int) $term ?>, SY <?= e($year) ?></title>
<link rel="icon" type="image/png" href="<?= asset_url('assets/img/logo-icon.png') ?>">
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
  .toolbar .warn { font-size: 12px; padding: .3rem .7rem; border-radius: 999px; font-weight: 700; background: #fef9c3; color: #713f12; }

  .sheet {
    width: 210mm; max-width: calc(100% - 32px); min-height: 297mm;
    margin: 24px auto; padding: 16mm 14mm; background: #fff;
    box-shadow: 0 6px 30px rgba(0,0,0,.12);
  }
  .head { text-align: center; margin: 6px 0 14px; }
  .head img { width: 58px; height: 58px; object-fit: contain; }
  .head .rep { font-size: 11px; }
  .head .school { font-weight: 700; margin-top: 2px; }
  .head h1 { font-size: 16px; letter-spacing: .04em; margin: 10px 0 0; }
  .head .sub { font-size: 12px; color: var(--muted); }
  .meta { display: grid; grid-template-columns: 1fr auto; gap: 4px 24px; margin-bottom: 12px; }
  .meta span { display: inline-block; min-width: 160px; border-bottom: 1px solid var(--line); padding: 0 4px; font-weight: 700; }

  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid var(--line); padding: 5px 6px; vertical-align: middle; }
  th { font-size: 12px; background: #f3f3f3; }
  td.c { text-align: center; font-variant-numeric: tabular-nums; }
  tr.grade td { background: #fafafa; font-weight: 700; text-transform: uppercase; }
  td.missing { color: #b45309; }
  .low { font-weight: 700; }

  .summary { display: grid; grid-template-columns: repeat(4, 1fr); border: 1px solid var(--line); border-top: 0; }
  .summary > div { padding: 6px 8px; text-align: center; }
  .summary > div + div { border-left: 1px solid var(--line); }
  .summary .k { font-size: 11px; color: var(--muted); }
  .summary .v { font-size: 15px; font-weight: 700; }
  .legend { margin-top: 6px; font-size: 11px; color: var(--muted); }

  .sign { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0 24px; margin-top: 28px; }
  .sign .label { font-size: 12px; }
  .sign .name { margin-top: 34px; text-align: center; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid var(--line); min-height: 18px; }
  .sign .cap { text-align: center; font-size: 11px; color: var(--muted); }
  .sign .line { margin-top: 12px; border-bottom: 1px solid var(--line); min-height: 18px; }
  .remarks { margin-top: 18px; }
  .remarks .line { border-bottom: 1px solid var(--line); height: 22px; }
  .note { margin-top: 14px; font-size: 11px; color: var(--muted); }

  @page { size: A4; margin: 12mm; }
  @media print {
    body { background: #fff; }
    .toolbar { display: none; }
    .sheet { width: auto; max-width: none; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
    tr { break-inside: avoid; }
  }
  @media (max-width: 600px) {
    .sheet { padding: 16px; }
    .meta, .sign { grid-template-columns: 1fr; }
    .summary { grid-template-columns: 1fr 1fr; }
    .table-wrap { overflow-x: auto; }
  }
</style>
</head>
<body>
<div class="toolbar">
  <?php if ($incomplete > 0): ?>
  <span class="warn"><?= (int) $incomplete ?> class<?= $incomplete === 1 ? '' : 'es' ?> with missing scores</span>
  <?php endif; ?>
  <button type="button" onclick="window.print()">Print / Save as PDF</button>
  <button type="button" class="ghost" onclick="window.close()">Close</button>
</div>

<div class="sheet">
  <div class="head">
    <img src="<?= asset_url('assets/img/deped-seal.png') ?>" alt="DepEd seal">
    <div class="rep">Republic of the Philippines<br>Department of Education</div>
    <div class="school">Matabungkay National High School</div>
    <h1>MEAN PERCENTAGE SCORE (MPS) REPORT</h1>
    <div class="sub">For Validation</div>
  </div>

  <div class="meta">
    <div>Teacher: <span><?= e($teacherName) ?></span></div>
    <div>School Year: <span><?= e($year) ?></span></div>
    <div>Employee ID: <span><?= e($employeeId) ?: '&nbsp;' ?></span></div>
    <div>Term: <span><?= (int) $term ?></span></div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Subject</th>
          <th>Section</th>
          <?php foreach ($periods as $label): ?>
          <th style="width:13%"><?= e($label) ?></th>
          <?php endforeach; ?>
          <th style="width:10%">Average</th>
          <th style="width:13%">Descriptor</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($byGrade as $grade => $rows): ?>
        <tr class="grade"><td colspan="<?= count($periods) + 4 ?>"><?= e($grade) ?></td></tr>
        <?php foreach ($rows as $row): ?>
        <?php $desc = mpsDescriptor($row['avg']); ?>
        <tr>
          <td><?= e($row['subject']) ?></td>
          <td><?= e($row['section'] ?? '—') ?></td>
          <?php foreach (array_keys($periods) as $key): ?>
          <td class="c<?= $row[$key] === null ? ' missing' : '' ?>"><?= $fmt($row[$key]) ?></td>
          <?php endforeach; ?>
          <td class="c<?= $row['avg'] !== null && $row['avg'] < 75 ? ' low' : '' ?>"><?= $fmt($row['avg']) ?></td>
          <td class="c"><?= e($desc['label'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="summary">
    <div><div class="k">Classes</div><div class="v"><?= (int) $classCount ?></div></div>
    <div><div class="k">Overall Average</div><div class="v"><?= $overall === null ? '—' : $fmt($overall) . '%' ?></div></div>
    <div><div class="k">Below Mastery (&lt; 75)</div><div class="v"><?= (int) $belowMastery ?></div></div>
    <div><div class="k">Incomplete</div><div class="v"><?= (int) $incomplete ?></div></div>
  </div>
  <div class="legend">
    Descriptors:
    <?php foreach (MPS_DESCRIPTORS as $i => [$min, $label, $range]): ?><?= $i ? ' · ' : '' ?><?= e($range) ?> <?= e($label) ?><?php endforeach; ?>.
    Average is the mean of the test periods entered; “—” means no score on record.
  </div>

  <div class="remarks">
    <div class="label">Remarks:</div>
    <div class="line"></div>
    <div class="line"></div>
  </div>

  <div class="sign">
    <div>
      <div class="label">Prepared by:</div>
      <div class="name"><?= e($teacherName) ?></div>
      <div class="cap">Subject Teacher</div>
      <div class="line"></div>
      <div class="cap">Date</div>
    </div>
    <div>
      <div class="label">Validated by:</div>
      <div class="name"></div>
      <div class="cap">Subject Coordinator / Department Head</div>
      <div class="line"></div>
      <div class="cap">Date</div>
    </div>
    <div>
      <div class="label">Noted by:</div>
      <div class="name"></div>
      <div class="cap">School Head</div>
      <div class="line"></div>
      <div class="cap">Date</div>
    </div>
  </div>

  <div class="note">Generated by ACADOCS on <?= date('F d, Y h:i A') ?>.</div>
</div>
</body>
</html>

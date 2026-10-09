<?php
/**
 * Full-screen annotator for a submitted task file (see TaskDownload::annotate()).
 * Renders the file with PDF.js, keeps marks as vectors per page (so undo and
 * zoom are lossless), and on Save flattens them onto the original with
 * pdf-lib and uploads the result as the file's "annotated" copy.
 *
 * @var array  $file
 * @var ?string $kind  'pdf' | 'image' | null (can't be annotated on this server)
 */
$sourceUrl = base_url('task-submissions/' . $file['id'] . '/annotation-source') . ($fresh ? '?original=1' : '');
$saveUrl   = base_url('task-submissions/' . $file['id'] . '/annotate');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Annotate — <?= e($file['file_name']) ?> — ACADOCS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<link rel="icon" type="image/png" href="<?= base_url('assets/img/logo-icon.png') ?>">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function () {
    var saved = localStorage.getItem('theme');
    if (saved === 'dark') document.documentElement.setAttribute('data-bs-theme', 'dark');
})();
</script>
<style>
  body { margin: 0; background: #3a3a40; font-family: 'Inter', system-ui, sans-serif; }
  .ann-bar {
    position: sticky; top: 0; z-index: 10; display: flex; flex-wrap: wrap; align-items: center; gap: .5rem;
    padding: .55rem .9rem; background: var(--card); border-bottom: 1px solid var(--border);
    box-shadow: 0 2px 10px rgba(0,0,0,.15);
  }
  .ann-title { min-width: 0; flex: 1 1 220px; }
  .ann-title .name { font-weight: 700; font-size: .9rem; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .ann-title .sub  { font-size: .72rem; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .ann-group { display: flex; align-items: center; gap: .25rem; padding: 0 .4rem; border-left: 1px solid var(--border); }
  .ann-group:first-of-type { border-left: 0; }
  .ann-tool {
    width: 34px; height: 34px; border-radius: 8px; border: 1px solid transparent; background: transparent;
    color: var(--text-secondary); display: inline-flex; align-items: center; justify-content: center; font-size: 1rem;
  }
  .ann-tool:hover:not(:disabled) { background: var(--surface-hover); }
  .ann-tool.active { background: var(--highlight-bg); color: var(--highlight-tx); border-color: var(--primary); }
  .ann-tool:disabled { opacity: .35; }
  .ann-swatch { width: 22px; height: 22px; border-radius: 50%; border: 2px solid var(--card); box-shadow: 0 0 0 1px var(--border); cursor: pointer; padding: 0; }
  .ann-swatch.active { box-shadow: 0 0 0 2px var(--primary); }
  .ann-pages { display: flex; flex-direction: column; align-items: center; gap: 18px; padding: 22px 12px 60px; }
  .ann-page { position: relative; background: #fff; box-shadow: 0 4px 18px rgba(0,0,0,.35); }
  .ann-page canvas { position: absolute; left: 0; top: 0; }
  .ann-page canvas.overlay { touch-action: none; cursor: crosshair; }
  .ann-page.text-mode canvas.overlay { cursor: text; }
  .ann-msg { color: #e5e7eb; text-align: center; padding: 4rem 1rem; }
</style>
</head>
<body>

<div class="ann-bar">
  <div class="ann-title">
    <div class="name"><i class="bi bi-pencil-square me-1"></i><?= e($file['file_name']) ?></div>
    <div class="sub"><?= e($taskTitle) ?><?= $submitterName !== '' ? ' · ' . e($submitterName) : '' ?></div>
  </div>

  <?php if ($kind !== null): ?>
  <div class="ann-group" role="group" aria-label="Tool">
    <button type="button" class="ann-tool active" data-tool="pen" title="Pen (P)"><i class="bi bi-pen"></i></button>
    <button type="button" class="ann-tool" data-tool="highlighter" title="Highlighter (H)"><i class="bi bi-highlighter"></i></button>
    <button type="button" class="ann-tool" data-tool="text" title="Text (T)"><i class="bi bi-fonts"></i></button>
  </div>
  <div class="ann-group" role="group" aria-label="Color">
    <?php foreach (['#dc2626' => 'Red', '#2563eb' => 'Blue', '#16a34a' => 'Green', '#111827' => 'Black', '#facc15' => 'Yellow'] as $hex => $label): ?>
    <button type="button" class="ann-swatch<?= $hex === '#dc2626' ? ' active' : '' ?>" data-color="<?= $hex ?>" title="<?= $label ?>" style="background:<?= $hex ?>;"></button>
    <?php endforeach; ?>
  </div>
  <div class="ann-group" role="group" aria-label="Size">
    <button type="button" class="ann-tool" data-size="2" title="Thin"><i class="bi bi-circle-fill" style="font-size:.35rem;"></i></button>
    <button type="button" class="ann-tool active" data-size="4" title="Medium"><i class="bi bi-circle-fill" style="font-size:.6rem;"></i></button>
    <button type="button" class="ann-tool" data-size="8" title="Thick"><i class="bi bi-circle-fill" style="font-size:.9rem;"></i></button>
  </div>
  <div class="ann-group" role="group" aria-label="History">
    <button type="button" class="ann-tool" id="undoBtn" title="Undo (Ctrl+Z)" disabled><i class="bi bi-arrow-counterclockwise"></i></button>
    <button type="button" class="ann-tool" id="redoBtn" title="Redo (Ctrl+Y)" disabled><i class="bi bi-arrow-clockwise"></i></button>
    <button type="button" class="ann-tool" id="clearBtn" title="Clear my new marks"><i class="bi bi-eraser"></i></button>
  </div>
  <div class="ann-group" role="group" aria-label="Zoom">
    <button type="button" class="ann-tool" id="zoomOutBtn" title="Zoom out"><i class="bi bi-zoom-out"></i></button>
    <span id="zoomLabel" class="small text-muted" style="min-width:3rem;text-align:center;">100%</span>
    <button type="button" class="ann-tool" id="zoomInBtn" title="Zoom in"><i class="bi bi-zoom-in"></i></button>
  </div>
  <div class="ann-group">
    <?php if ($hasAnnotation && ! $fresh): ?>
    <a href="?fresh=1" class="btn btn-sm btn-outline-secondary" title="Discard earlier saved marks and start again from the original file"
       onclick="return confirm('Start over from the original file? Earlier saved marks will be replaced when you save.');">
      <i class="bi bi-file-earmark me-1"></i>Original
    </a>
    <?php endif; ?>
    <button type="button" class="btn btn-sm btn-primary" id="saveBtn" disabled><i class="bi bi-save me-1"></i>Save</button>
  </div>
  <?php endif; ?>
  <div class="ann-group">
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="closeAnnotator()"><i class="bi bi-x-lg me-1"></i>Close</button>
  </div>
</div>

<div class="ann-pages" id="pages">
  <?php if ($kind === null): ?>
  <div class="ann-msg">
    <i class="bi bi-file-earmark-x fs-1 d-block mb-2"></i>
    This file type can't be annotated on this server.<br>
    PDFs and images can always be annotated; Word/Excel/PowerPoint files need LibreOffice installed to convert them.
  </div>
  <?php else: ?>
  <div class="ann-msg" id="loadingMsg"><span class="spinner-border spinner-border-sm me-2"></span>Loading file…</div>
  <?php endif; ?>
</div>

<?php if ($kind !== null): ?>
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
<script>
const KIND       = <?= json_encode($kind) ?>;           // 'pdf' | 'image'
const IMAGE_TYPE = <?= json_encode($imageType) ?>;      // 'png' | 'jpg' (image kind only)
const SOURCE_URL = <?= json_encode($sourceUrl) ?>;
const SAVE_URL   = <?= json_encode($saveUrl) ?>;
const TEXT_SIZE  = 18; // css px at 100% zoom

pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';

const state = {
    tool: 'pen', color: '#dc2626', size: 4,
    zoom: 1, baseWidth: 0,       // baseWidth: css width of a page at 100% (fit to screen)
    sourceBytes: null, pdf: null, image: null,
    pages: [],                   // { el, base, overlay, cssW, cssH, marks: [], render(fn) }
    history: [], redo: [],       // { page, mark }
    dirty: false, drawing: null,
};

/* ── Loading ─────────────────────────────────────────────── */
fetch(SOURCE_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(res => { if (!res.ok) throw new Error('Could not load the file (' + res.status + ').'); return res.arrayBuffer(); })
    .then(async bytes => {
        state.sourceBytes = bytes;
        if (KIND === 'pdf') {
            state.pdf = await pdfjsLib.getDocument({ data: new Uint8Array(bytes.slice(0)) }).promise;
            const first = await state.pdf.getPage(1);
            state.baseWidth = fitWidth(first.getViewport({ scale: 1 }).width);
            for (let i = 1; i <= state.pdf.numPages; i++) addPage(await state.pdf.getPage(i));
        } else {
            state.image = await loadImage(URL.createObjectURL(new Blob([bytes])));
            state.baseWidth = fitWidth(state.image.naturalWidth);
            addPage(null);
        }
        document.getElementById('loadingMsg').remove();
        await renderAll();
        document.getElementById('saveBtn').disabled = false;
    })
    .catch(err => {
        document.getElementById('loadingMsg').innerHTML = '<i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>' + escapeHtml(err.message || 'Could not open this file.');
    });

function fitWidth(naturalWidth) {
    return Math.min(naturalWidth * 1.5, document.getElementById('pages').clientWidth - 24, 1000);
}

function loadImage(src) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = () => reject(new Error('Could not open this image.'));
        img.src = src;
    });
}

function addPage(pdfPage) {
    const el = document.createElement('div');
    el.className = 'ann-page';
    const base = document.createElement('canvas');
    const overlay = document.createElement('canvas');
    overlay.className = 'overlay';
    el.append(base, overlay);
    document.getElementById('pages').appendChild(el);

    const page = { el, base, overlay, pdfPage, marks: [], cssW: 0, cssH: 0 };
    state.pages.push(page);
    bindDrawing(page);
}

async function renderAll() {
    const dpr = window.devicePixelRatio || 1;
    const width = state.baseWidth * state.zoom;
    document.getElementById('zoomLabel').textContent = Math.round(state.zoom * 100) + '%';

    for (const page of state.pages) {
        let naturalW, naturalH;
        if (page.pdfPage) {
            const vp1 = page.pdfPage.getViewport({ scale: 1 });
            naturalW = vp1.width; naturalH = vp1.height;
        } else {
            naturalW = state.image.naturalWidth; naturalH = state.image.naturalHeight;
        }
        const scale = width / naturalW;
        page.cssW = width;
        page.cssH = naturalH * scale;

        page.el.style.width = page.cssW + 'px';
        page.el.style.height = page.cssH + 'px';
        for (const c of [page.base, page.overlay]) {
            c.width = Math.round(page.cssW * dpr);
            c.height = Math.round(page.cssH * dpr);
            c.style.width = page.cssW + 'px';
            c.style.height = page.cssH + 'px';
        }

        if (page.pdfPage) {
            await page.pdfPage.render({
                canvasContext: page.base.getContext('2d'),
                viewport: page.pdfPage.getViewport({ scale: scale * dpr }),
            }).promise;
        } else {
            page.base.getContext('2d').drawImage(state.image, 0, 0, page.base.width, page.base.height);
        }
        redraw(page);
    }
}

/* ── Marks: stored normalized to the page (0..1) so zoom is lossless ── */
function drawMarks(ctx, marks, W, H) {
    ctx.clearRect(0, 0, W, H);
    for (const m of marks) {
        ctx.save();
        if (m.type === 'text') {
            ctx.fillStyle = m.color;
            ctx.font = '600 ' + (m.size * W) + 'px Inter, Arial, sans-serif';
            ctx.textBaseline = 'top';
            m.text.split('\n').forEach((line, i) => ctx.fillText(line, m.x * W, m.y * H + i * m.size * W * 1.25));
        } else {
            ctx.strokeStyle = m.color;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.lineWidth = m.size * W;
            if (m.tool === 'highlighter') ctx.globalAlpha = 0.35;
            ctx.beginPath();
            m.points.forEach(([x, y], i) => i ? ctx.lineTo(x * W, y * H) : ctx.moveTo(x * W, y * H));
            if (m.points.length === 1) ctx.lineTo(m.points[0][0] * W + 0.01, m.points[0][1] * H);
            ctx.stroke();
        }
        ctx.restore();
    }
}

function redraw(page) {
    const marks = state.drawing && state.drawing.page === page ? page.marks.concat([state.drawing.mark]) : page.marks;
    drawMarks(page.overlay.getContext('2d'), marks, page.overlay.width, page.overlay.height);
}

function pointOn(page, e) {
    const r = page.overlay.getBoundingClientRect();
    return [Math.min(Math.max((e.clientX - r.left) / r.width, 0), 1), Math.min(Math.max((e.clientY - r.top) / r.height, 0), 1)];
}

function bindDrawing(page) {
    const ov = page.overlay;

    ov.addEventListener('pointerdown', e => {
        if (e.button !== 0) return;
        const [x, y] = pointOn(page, e);

        if (state.tool === 'text') {
            Swal.fire({
                title: 'Add a note', input: 'textarea', inputPlaceholder: 'Type your comment…',
                showCancelButton: true, confirmButtonText: 'Add', confirmButtonColor: '#800000',
            }).then(r => {
                const text = (r.value || '').trim();
                if (r.isConfirmed && text) commitMark(page, { type: 'text', x, y, text, color: state.color, size: TEXT_SIZE / page.cssW * (state.size / 4) });
            });
            return;
        }

        ov.setPointerCapture(e.pointerId);
        const widthPx = state.tool === 'highlighter' ? state.size * 4 : state.size;
        state.drawing = { page, mark: { type: 'stroke', tool: state.tool, color: state.color, size: widthPx / page.cssW, points: [[x, y]] } };
        redraw(page);
    });

    ov.addEventListener('pointermove', e => {
        if (!state.drawing || state.drawing.page !== page) return;
        state.drawing.mark.points.push(pointOn(page, e));
        redraw(page);
    });

    const finish = () => {
        if (!state.drawing || state.drawing.page !== page) return;
        const mark = state.drawing.mark;
        state.drawing = null;
        commitMark(page, mark);
    };
    ov.addEventListener('pointerup', finish);
    ov.addEventListener('pointercancel', finish);
}

function commitMark(page, mark) {
    page.marks.push(mark);
    state.history.push({ page, mark });
    state.redo = [];
    markDirty(true);
    redraw(page);
}

function markDirty(dirty) {
    state.dirty = dirty;
    document.getElementById('undoBtn').disabled = state.history.length === 0;
    document.getElementById('redoBtn').disabled = state.redo.length === 0;
}

function undo() {
    const last = state.history.pop();
    if (!last) return;
    last.page.marks.splice(last.page.marks.lastIndexOf(last.mark), 1);
    state.redo.push(last);
    markDirty(true);
    redraw(last.page);
}

function redo() {
    const next = state.redo.pop();
    if (!next) return;
    next.page.marks.push(next.mark);
    state.history.push(next);
    markDirty(true);
    redraw(next.page);
}

/* ── Toolbar ─────────────────────────────────────────────── */
function setTool(tool) {
    state.tool = tool;
    document.querySelectorAll('[data-tool]').forEach(b => b.classList.toggle('active', b.dataset.tool === tool));
    state.pages.forEach(p => p.el.classList.toggle('text-mode', tool === 'text'));
}
document.querySelectorAll('[data-tool]').forEach(b => b.addEventListener('click', () => setTool(b.dataset.tool)));
document.querySelectorAll('[data-color]').forEach(b => b.addEventListener('click', () => {
    state.color = b.dataset.color;
    document.querySelectorAll('[data-color]').forEach(s => s.classList.toggle('active', s === b));
}));
document.querySelectorAll('[data-size]').forEach(b => b.addEventListener('click', () => {
    state.size = parseInt(b.dataset.size, 10);
    document.querySelectorAll('[data-size]').forEach(s => s.classList.toggle('active', s === b));
}));
document.getElementById('undoBtn').addEventListener('click', undo);
document.getElementById('redoBtn').addEventListener('click', redo);
document.getElementById('clearBtn').addEventListener('click', () => {
    if (!state.history.length) return;
    Swal.fire({
        title: 'Clear your new marks?', text: 'Marks saved earlier are part of the file and stay.',
        icon: 'warning', showCancelButton: true, confirmButtonText: 'Clear', confirmButtonColor: '#dc2626',
    }).then(r => {
        if (!r.isConfirmed) return;
        state.pages.forEach(p => { p.marks = []; redraw(p); });
        state.history = []; state.redo = [];
        markDirty(false);
    });
});
document.getElementById('zoomInBtn').addEventListener('click', () => { state.zoom = Math.min(state.zoom + 0.25, 3); renderAll(); });
document.getElementById('zoomOutBtn').addEventListener('click', () => { state.zoom = Math.max(state.zoom - 0.25, 0.5); renderAll(); });
document.getElementById('saveBtn').addEventListener('click', save);

document.addEventListener('keydown', e => {
    if (e.target.closest('.swal2-container')) return;
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') { e.preventDefault(); e.shiftKey ? redo() : undo(); }
    else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'y') { e.preventDefault(); redo(); }
    else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') { e.preventDefault(); save(); }
    else if (!e.ctrlKey && !e.metaKey && !e.altKey) {
        const tool = { p: 'pen', h: 'highlighter', t: 'text' }[e.key.toLowerCase()];
        if (tool) setTool(tool);
    }
});

window.addEventListener('beforeunload', e => {
    if (state.dirty) { e.preventDefault(); e.returnValue = ''; }
});

/* ── Save: flatten marks onto the original and upload ───── */
function marksImage(page, outW, outH, rotation) {
    // Draw in the page's *unrotated* space: PDF.js shows pages rotated, but
    // pdf-lib draws in raw page coordinates.
    const c = document.createElement('canvas');
    c.width = outW; c.height = outH;
    const ctx = c.getContext('2d');
    const turned = rotation === 90 || rotation === 270;
    const viewW = turned ? outH : outW, viewH = turned ? outW : outH;
    if (rotation === 90)  { ctx.translate(0, outH); ctx.rotate(-Math.PI / 2); }
    if (rotation === 180) { ctx.translate(outW, outH); ctx.rotate(Math.PI); }
    if (rotation === 270) { ctx.translate(outW, 0); ctx.rotate(Math.PI / 2); }
    // drawMarks clears with clearRect in the transformed space — fine on a fresh canvas.
    drawMarks(ctx, page.marks, viewW, viewH);
    return c.toDataURL('image/png');
}

async function save() {
    const btn = document.getElementById('saveBtn');
    if (btn.disabled) return;
    if (!state.history.length && !state.pages.some(p => p.marks.length)) {
        Swal.fire({ icon: 'info', title: 'Nothing to save', text: 'Draw or add a note first.', confirmButtonColor: '#800000' });
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';

    try {
        const { PDFDocument } = PDFLib;
        let doc;

        if (KIND === 'pdf') {
            doc = await PDFDocument.load(state.sourceBytes.slice(0), { ignoreEncryption: true });
        } else {
            doc = await PDFDocument.create();
            const img = IMAGE_TYPE === 'png' ? await doc.embedPng(state.sourceBytes.slice(0)) : await doc.embedJpg(state.sourceBytes.slice(0));
            const p = doc.addPage([img.width, img.height]);
            p.drawImage(img, { x: 0, y: 0, width: img.width, height: img.height });
        }

        const pdfPages = doc.getPages();
        for (let i = 0; i < state.pages.length; i++) {
            const page = state.pages[i];
            if (!page.marks.length || !pdfPages[i]) continue;

            const target = pdfPages[i];
            const box = target.getCropBox();
            const rotation = ((target.getRotation().angle % 360) + 360) % 360;
            const scale = Math.min(2.5, 2500 / Math.max(box.width, box.height)); // crisp but bounded
            const png = await doc.embedPng(marksImage(page, Math.round(box.width * scale), Math.round(box.height * scale), rotation));
            target.drawImage(png, { x: box.x, y: box.y, width: box.width, height: box.height });
        }

        const form = new FormData();
        form.append('annotated', new Blob([await doc.save()], { type: 'application/pdf' }), 'annotated.pdf');
        const res = await fetch(SAVE_URL, { method: 'POST', body: form, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json().catch(() => ({ status: 'error', message: 'Unexpected server response.' }));
        if (data.status !== 'success') throw new Error(data.message || 'Could not save.');

        markDirty(false);
        Swal.fire({ icon: 'success', title: 'Saved', text: data.message, confirmButtonColor: '#800000' });
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Could not save', text: err.message || 'Something went wrong.', confirmButtonColor: '#800000' });
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save me-1"></i>Save';
    }
}

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}
</script>
<?php endif; ?>
<script>
function closeAnnotator() {
    // Opened in its own tab from the review screens — close it; if the
    // browser won't (tab not script-opened), go back instead.
    window.close();
    setTimeout(() => history.length > 1 ? history.back() : (location.href = <?= json_encode(base_url('tasks')) ?>), 150);
}
</script>
</body>
</html>

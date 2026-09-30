/*
 * Renders an Office file inside the sandboxed (opaque-origin) preview page
 * built by App\Libraries\FilePreview, so markup produced from an uploaded
 * document can never touch the app's session or DOM. That page embeds the
 * file as `window.PREVIEW_FILE = {ext, data}` (base64) before loading this
 * script. Rendering libraries are loaded only when a file that needs them is
 * opened:
 *   - .docx                          -> docx-preview (keeps headers/letterheads and page layout)
 *   - .pptx .xlsx .odt .ods .odp .csv -> officeparser, rendered to its HTML output
 */
(function () {
    'use strict';

    var CDN = {
        jszip:        'https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js',
        docxPreview:  'https://cdn.jsdelivr.net/npm/docx-preview@0.3.5/dist/docx-preview.min.js',
        officeParser: 'https://cdn.jsdelivr.net/npm/officeparser@8.1.0/dist/officeparser.browser.slim.iife.js'
    };

    var OFFICEPARSER_EXT = ['pptx', 'xlsx', 'odt', 'ods', 'odp', 'csv'];

    // The sandbox (no allow-same-origin) makes window.localStorage throw, which
    // breaks officeparser's spreadsheet sheet tabs/column resizing. Give it a
    // throwaway in-memory store instead.
    try {
        window.localStorage.length;
    } catch (e) {
        var store = {};
        Object.defineProperty(window, 'localStorage', {
            configurable: true,
            value: {
                getItem: function (k) { return Object.prototype.hasOwnProperty.call(store, k) ? store[k] : null; },
                setItem: function (k, v) { store[k] = String(v); },
                removeItem: function (k) { delete store[k]; },
                clear: function () { store = {}; },
                key: function (i) { return Object.keys(store)[i] || null; },
                get length() { return Object.keys(store).length; }
            }
        });
    }

    var style = document.createElement('style');
    style.textContent = 'html,body{margin:0;background:#f3f4f6;font-family:"Inter",system-ui,sans-serif;}'
        + '.docx-wrapper{background:#f3f4f6 !important;padding:1rem !important;}'
        + '#previewMsg{color:#6b7280;text-align:center;padding:3rem 1rem;font-size:.9rem;}';
    document.head.appendChild(style);

    var msg = document.getElementById('previewMsg');
    if (!msg) {
        msg = document.createElement('div');
        msg.id = 'previewMsg';
        document.body.appendChild(msg);
    }
    msg.textContent = 'Loading preview…';

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = function () { reject(new Error('Could not load ' + src)); };
            document.head.appendChild(s);
        });
    }

    function renderDocx(buffer) {
        return loadScript(CDN.jszip)
            .then(function () { return loadScript(CDN.docxPreview); })
            .then(function () {
                var container = document.createElement('div');
                document.body.appendChild(container);
                return window.docx.renderAsync(buffer, container, null, { inWrapper: true });
            })
            .then(function () { msg.style.display = 'none'; });
    }

    function renderWithOfficeParser(ext, buffer) {
        return loadScript(CDN.officeParser)
            .then(function () {
                return window.officeParser.parseOffice(new Uint8Array(buffer), { fileType: ext, extractAttachments: true });
            })
            .then(function (ast) { return ast.to('html'); })
            .then(function (result) {
                // officeparser emits a complete standalone document (its own
                // styles, plus Chart.js for any charts), so it replaces this page.
                document.open();
                document.write(result.value);
                document.close();
            });
    }

    function render(ext, buffer) {
        if (ext === 'docx') return renderDocx(buffer);
        if (OFFICEPARSER_EXT.indexOf(ext) !== -1) return renderWithOfficeParser(ext, buffer);
        return Promise.reject(new Error('Unsupported type: ' + ext));
    }

    function fail() {
        msg.textContent = "Preview isn't available for this file. Download it to view the contents.";
        msg.style.display = '';
    }

    function base64ToBuffer(b64) {
        var bin = atob(b64);
        var bytes = new Uint8Array(bin.length);
        for (var i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
        return bytes.buffer;
    }

    var file = window.PREVIEW_FILE;

    if (!file) {
        fail();
        return;
    }

    render(file.ext, base64ToBuffer(file.data)).catch(fail);
})();

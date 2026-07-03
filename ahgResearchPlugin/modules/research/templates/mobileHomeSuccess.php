<?php decorate_with('layout_1col') ?>
<?php
$researcher = sfOutputEscaper::unescape($researcher);
$recentItems = sfOutputEscaper::unescape($recentItems ?? []);
$nonce = sfConfig::get('csp_nonce', '');
$nonceAttr = $nonce ? preg_replace('/^nonce=/', 'nonce="', $nonce) . '"' : '';
$syncUrl = url_for(['module' => 'research', 'action' => 'offlineSync']);
$dataUrl = url_for(['module' => 'research', 'action' => 'offlineData']);
?>
<?php slot('head') ?>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0d6efd">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="AtoM Heratio">
<?php end_slot() ?>

<div class="container-fluid mobile-research">
    <div class="d-flex justify-content-between align-items-center my-3">
        <div>
            <h1 class="h4 mb-0">Work Offline</h1>
            <small class="text-muted">Hi, <?php echo htmlspecialchars($researcher->first_name ?? 'researcher'); ?> — your collected records travel with you.</small>
        </div>
        <span class="badge" id="online-badge">Online</span>
    </div>

    <?php if ($msg = $sf_user->getFlash('success')): ?>
        <div class="alert alert-success py-2"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <div class="alert alert-info py-2 small">
        <i class="fas fa-shield-halved me-1"></i>Only records you are permitted to see are made available offline.
        Add notes, sources, suggestions and files below — they save on this device and sync when you reconnect (or use <strong>Save for sync</strong>).
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-box-archive me-1"></i>My collected records</strong>
            <span class="badge bg-secondary" id="rec-count">…</span>
        </div>
        <div class="card-body">
            <input type="search" class="form-control mb-2" id="rec-search" placeholder="Search title or identifier">
            <div id="rec-list" class="list-group" style="max-height:50vh;overflow-y:auto"></div>
            <div id="rec-empty" class="text-muted small py-2" style="display:none">No records collected yet — add records to a Collection, Favourites folder or Project, then reopen this page online to cache them.</div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong><i class="fas fa-pen me-1"></i>Quick journal entry</strong></div>
        <div class="card-body">
            <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'mobileHome']); ?>" id="quick-journal-form">
                <input type="hidden" name="quick_journal" value="1">
                <textarea name="content" rows="2" class="form-control mb-2" placeholder="What did you find today?"></textarea>
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i> Save</button>
            </form>
        </div>
    </div>
</div>

<!-- Record detail + capture (offcanvas) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="rec-detail" style="width:min(560px,100%)">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="rec-detail-title">Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body" id="rec-detail-body"></div>
</div>

<!-- Sticky sync bar -->
<div class="offline-syncbar" id="syncbar">
    <span class="grow small">Your offline work is saved on this device.</span>
    <span class="badge bg-light text-dark" id="sync-count">0 to sync</span>
    <button type="button" class="btn btn-sm btn-success" id="sync-save"><i class="fas fa-cloud-arrow-up me-1"></i>Save for sync</button>
</div>

<style <?php echo $nonceAttr; ?>>
    .mobile-research .btn { font-size: 0.95rem; }
    #online-badge.bg-success { background:#198754 !important; color:#fff; }
    #online-badge.bg-danger  { background:#dc3545 !important; color:#fff; }
    .offline-syncbar { position:sticky; bottom:0; left:0; right:0; display:flex; align-items:center; gap:.6rem;
        background:#1f2a37; color:#fff; padding:.55rem .9rem; z-index:1030; }
    .offline-syncbar .grow { flex:1; opacity:.9; }
    .cap-tabs { display:flex; gap:.3rem; flex-wrap:wrap; margin:.4rem 0; }
    .cap-tabs button { border:1px solid #ccd; background:#fff; padding:.25rem .6rem; border-radius:1rem; font-size:.8rem; }
    .cap-tabs button.on { background:#0d6efd; color:#fff; border-color:#0d6efd; }
    .cap-pane { display:none; } .cap-pane.on { display:block; }
    .cap-entry { background:#f6f8fa; border:1px solid #e2e6ea; border-radius:.25rem; padding:.35rem .5rem; margin-bottom:.3rem; font-size:.82rem; display:flex; justify-content:space-between; gap:.5rem; }
    .cap-entry .x { color:#a33; cursor:pointer; font-weight:700; }
</style>

<script <?php echo $nonceAttr; ?>>
(function () {
    var SYNC_URL = <?php echo json_encode($syncUrl); ?>;
    var DATA_URL = <?php echo json_encode($dataUrl); ?>;
    var QUEUE_KEY = 'heratio_offline_queue_v1';
    var RECORDS_KEY = 'heratio_offline_records_v1';

    function setBadge(online) {
        var el = document.getElementById('online-badge');
        if (!el) return;
        el.textContent = online ? 'Online' : 'Offline';
        el.className = 'badge ' + (online ? 'bg-success' : 'bg-danger');
    }
    function esc(s) { return String(s == null ? '' : s).replace(/[<>&"]/g, function (c) { return ({ '<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;' })[c]; }); }

    function readQueue() { try { return JSON.parse(localStorage.getItem(QUEUE_KEY) || '[]'); } catch (e) { return []; } }
    function writeQueue(q) { localStorage.setItem(QUEUE_KEY, JSON.stringify(q)); updateSyncCount(); }
    function pushQueue(entry) { var q = readQueue(); entry.created_at = new Date().toISOString(); q.push(entry); writeQueue(q); }

    function updateSyncCount() {
        var n = readQueue().length;
        var el = document.getElementById('sync-count');
        if (el) el.textContent = n + (n === 1 ? ' to sync' : ' to sync');
    }

    function flushQueue(cb) {
        var q = readQueue();
        if (!q.length) { if (cb) cb(true, 0); return; }
        fetch(SYNC_URL, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ queue: q })
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data && data.ok) { writeQueue([]); if (cb) cb(true, data.applied || q.length); }
            else if (cb) cb(false, 0);
        }).catch(function () { if (cb) cb(false, 0); });
    }

    // ---- Records cache + list ----
    var records = [];
    function readRecords() { try { return JSON.parse(localStorage.getItem(RECORDS_KEY) || '[]'); } catch (e) { return []; } }
    function renderList(filter) {
        var f = (filter || '').toLowerCase();
        var list = document.getElementById('rec-list');
        var empty = document.getElementById('rec-empty');
        document.getElementById('rec-count').textContent = records.length;
        if (!records.length) { list.innerHTML = ''; empty.style.display = ''; return; }
        empty.style.display = 'none';
        var shown = records.filter(function (r) {
            if (!f) return true;
            return (String(r.title || '').toLowerCase().indexOf(f) !== -1) || (String(r.identifier || '').toLowerCase().indexOf(f) !== -1);
        });
        list.innerHTML = shown.map(function (r) {
            return '<a href="#" class="list-group-item list-group-item-action" data-id="' + r.id + '">'
                + esc(r.title || 'Untitled')
                + (r.identifier ? '<div class="small text-muted">' + esc(r.identifier) + '</div>' : '')
                + '</a>';
        }).join('') || '<div class="text-muted small py-2">No matches.</div>';
    }
    function refreshRecords() {
        fetch(DATA_URL, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d && d.success && Array.isArray(d.records)) {
                    records = d.records;
                    localStorage.setItem(RECORDS_KEY, JSON.stringify(records));
                    renderList(document.getElementById('rec-search').value);
                }
            }).catch(function () { /* offline — keep cached */ });
    }

    // ---- Record detail + capture ----
    var oc = null;
    function openRecord(id) {
        var r = records.filter(function (x) { return String(x.id) === String(id); })[0];
        if (!r) return;
        document.getElementById('rec-detail-title').textContent = r.title || 'Untitled';
        var fields = [['Identifier', r.identifier], ['Scope and content', r.scope_and_content], ['Extent and medium', r.extent_and_medium], ['Archival history', r.archival_history], ['Access conditions', r.access_conditions]];
        var dl = fields.filter(function (p) { return p[1]; }).map(function (p) { return '<dt class="small text-muted">' + esc(p[0]) + '</dt><dd>' + esc(p[1]) + '</dd>'; }).join('');
        var existing = (r.notes || []).map(function (n) { return '<div class="cap-entry"><span><em>' + esc(n.type) + '</em>: ' + esc(n.content) + '</span></div>'; }).join('');
        document.getElementById('rec-detail-body').innerHTML =
            (r.thumbnail ? '<img src="' + esc(r.thumbnail) + '" class="img-fluid mb-2 rounded" alt="">' : '')
            + '<dl>' + dl + '</dl>'
            + (existing ? '<div class="mb-2"><strong class="small">Your existing notes</strong>' + existing + '</div>' : '')
            + capturePanel(r);
        wireCapture(r);
        oc = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('rec-detail'));
        oc.show();
    }
    function capturePanel(r) {
        return '<hr><strong class="small">Add offline work</strong>'
            + '<div class="cap-tabs">'
            + '<button data-t="note" class="on" type="button">Note</button>'
            + '<button data-t="src" type="button">Source</button>'
            + '<button data-t="sug" type="button">Suggestion</button>'
            + '<button data-t="file" type="button">File</button>'
            + '</div>'
            + '<div class="cap-pane on" data-p="note"><textarea id="cap-note" class="form-control mb-1" rows="3" placeholder="Your note on this record"></textarea><button class="btn btn-sm btn-primary" id="cap-note-add" type="button">Add note</button></div>'
            + '<div class="cap-pane" data-p="src"><input id="cap-src-title" class="form-control mb-1" placeholder="Source title"><input id="cap-src-author" class="form-control mb-1" placeholder="Author"><input id="cap-src-year" class="form-control mb-1" placeholder="Year"><input id="cap-src-url" class="form-control mb-1" placeholder="URL / reference"><button class="btn btn-sm btn-primary" id="cap-src-add" type="button">Add source</button></div>'
            + '<div class="cap-pane" data-p="sug"><input id="cap-sug-field" class="form-control mb-1" placeholder="Field (e.g. Title, Dates)"><textarea id="cap-sug-text" class="form-control mb-1" rows="2" placeholder="Suggested correction / addition"></textarea><button class="btn btn-sm btn-primary" id="cap-sug-add" type="button">Add suggestion</button><div class="small text-muted mt-1">Suggestions are reviewed by a curator before anything changes.</div></div>'
            + '<div class="cap-pane" data-p="file"><input type="file" id="cap-file" class="form-control mb-1"><div class="small text-muted">Small files only (max 5 MB); embedded in your sync.</div></div>'
            + '<div id="cap-flash" class="small text-success mt-2"></div>';
    }
    function wireCapture(r) {
        var body = document.getElementById('rec-detail-body');
        body.querySelectorAll('.cap-tabs button').forEach(function (b) {
            b.addEventListener('click', function () {
                body.querySelectorAll('.cap-tabs button').forEach(function (x) { x.classList.remove('on'); });
                body.querySelectorAll('.cap-pane').forEach(function (x) { x.classList.remove('on'); });
                b.classList.add('on');
                var p = body.querySelector('.cap-pane[data-p="' + b.dataset.t + '"]'); if (p) p.classList.add('on');
            });
        });
        function flash(msg) { var f = body.querySelector('#cap-flash'); if (f) { f.textContent = msg; setTimeout(function () { f.textContent = ''; }, 1500); } }
        body.querySelector('#cap-note-add').addEventListener('click', function () {
            var v = body.querySelector('#cap-note').value.trim(); if (!v) return;
            pushQueue({ kind: 'annotation', object_id: r.id, content: v, annotation_type: 'note' });
            body.querySelector('#cap-note').value = ''; flash('Note queued');
        });
        body.querySelector('#cap-src-add').addEventListener('click', function () {
            var t = body.querySelector('#cap-src-title').value.trim(); if (!t) return;
            pushQueue({ kind: 'source', object_id: r.id, title: t, author: body.querySelector('#cap-src-author').value.trim(), year: body.querySelector('#cap-src-year').value.trim(), url: body.querySelector('#cap-src-url').value.trim() });
            body.querySelector('#cap-src-title').value = ''; body.querySelector('#cap-src-author').value = ''; body.querySelector('#cap-src-year').value = ''; body.querySelector('#cap-src-url').value = ''; flash('Source queued');
        });
        body.querySelector('#cap-sug-add').addEventListener('click', function () {
            var f = body.querySelector('#cap-sug-field').value.trim(), s = body.querySelector('#cap-sug-text').value.trim();
            if (!f || !s) return;
            pushQueue({ kind: 'metadata_suggestion', object_id: r.id, field: f, suggestion: s });
            body.querySelector('#cap-sug-field').value = ''; body.querySelector('#cap-sug-text').value = ''; flash('Suggestion queued');
        });
        body.querySelector('#cap-file').addEventListener('change', function () {
            var file = this.files && this.files[0]; if (!file) return;
            if (file.size > 5 * 1024 * 1024) { flash('File exceeds 5 MB'); this.value = ''; return; }
            var reader = new FileReader();
            reader.onload = function () {
                pushQueue({ kind: 'file', object_id: r.id, name: file.name, type: file.type, size: file.size, data: reader.result });
                flash('File queued');
            };
            reader.readAsDataURL(file);
            this.value = '';
        });
    }

    // ---- Wiring ----
    document.getElementById('rec-list').addEventListener('click', function (e) {
        var a = e.target.closest('a[data-id]'); if (!a) return; e.preventDefault(); openRecord(a.dataset.id);
    });
    document.getElementById('rec-search').addEventListener('input', function () { renderList(this.value); });
    document.getElementById('sync-save').addEventListener('click', function () {
        var btn = this; btn.disabled = true;
        flushQueue(function (ok, applied) {
            btn.disabled = false;
            alert(ok ? ('Synced ' + applied + ' change(s) to Heratio.') : 'Could not sync — you may be offline. Your work is safe and will retry.');
        });
    });

    // Quick journal: queue when offline
    var form = document.getElementById('quick-journal-form');
    if (form) {
        form.addEventListener('submit', function (ev) {
            if (!navigator.onLine) {
                ev.preventDefault();
                var content = form.querySelector('[name="content"]').value || '';
                if (!content.trim()) return;
                pushQueue({ kind: 'journal_entry', content: content });
                form.querySelector('[name="content"]').value = '';
                alert('Saved offline. Will sync when reconnected.');
            }
        });
    }

    window.addEventListener('online',  function () { setBadge(true); flushQueue(); refreshRecords(); });
    window.addEventListener('offline', function () { setBadge(false); });
    setBadge(navigator.onLine);

    // Init: render from cache immediately (works offline), refresh from network if online.
    records = readRecords();
    renderList('');
    updateSyncCount();
    if (navigator.onLine) { flushQueue(); refreshRecords(); }

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(function () { /* ignore */ });
    }
})();
</script>

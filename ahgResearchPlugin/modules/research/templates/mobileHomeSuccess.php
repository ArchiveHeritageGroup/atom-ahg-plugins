<?php decorate_with('layout_1col') ?>
<?php
$researcher = sfOutputEscaper::unescape($researcher);
$recentItems = sfOutputEscaper::unescape($recentItems ?? []);
$nonce = sfConfig::get('csp_nonce', '');
$nonceAttr = $nonce ? preg_replace('/^nonce=/', 'nonce="', $nonce) . '"' : '';
$syncUrl = url_for(['module' => 'research', 'action' => 'offlineSync']);
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
            <h1 class="h4 mb-0">Hi, <?php echo htmlspecialchars($researcher->first_name ?? 'researcher'); ?></h1>
            <small class="text-muted"><?php echo htmlspecialchars($researcher->email ?? ''); ?></small>
        </div>
        <span class="badge" id="online-badge">Online</span>
    </div>

    <?php if ($msg = $sf_user->getFlash('success')): ?>
        <div class="alert alert-success py-2"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <div class="row g-2 mb-3">
        <div class="col-6"><a class="btn btn-primary w-100 py-3" href="<?php echo url_for(['module' => 'research', 'action' => 'crossFondsQuery']); ?>"><i class="fas fa-search"></i><br><small>Search</small></a></div>
        <div class="col-6"><a class="btn btn-success w-100 py-3" href="<?php echo url_for(['module' => 'research', 'action' => 'notebooks']); ?>"><i class="fas fa-book"></i><br><small>Notebooks</small></a></div>
        <div class="col-6"><a class="btn btn-info w-100 py-3 text-white" href="<?php echo url_for(['module' => 'research', 'action' => 'bibliographies']); ?>"><i class="fas fa-list"></i><br><small>Bibliographies</small></a></div>
        <div class="col-6"><a class="btn btn-warning w-100 py-3" href="<?php echo url_for(['module' => 'research', 'action' => 'journal']); ?>"><i class="fas fa-pen"></i><br><small>Journal</small></a></div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong><i class="fas fa-pen me-1"></i>Quick journal entry</strong></div>
        <div class="card-body">
            <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'mobileHome']); ?>" id="quick-journal-form">
                <input type="hidden" name="quick_journal" value="1">
                <textarea name="content" rows="3" class="form-control mb-2" placeholder="What did you find today?"></textarea>
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i> Save</button>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong><i class="fas fa-list me-1"></i>Recent reading list</strong></div>
        <ul class="list-group list-group-flush">
            <?php if (empty($recentItems)): ?>
                <li class="list-group-item text-muted small">No items in your collections yet.</li>
            <?php else: ?>
                <?php foreach (array_slice($recentItems, 0, 20) as $item): ?>
                    <li class="list-group-item">
                        <?php if (!empty($item->slug)): ?>
                            <a href="/index.php/<?php echo htmlspecialchars($item->slug); ?>"><?php echo htmlspecialchars($item->title ?: 'Untitled'); ?></a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($item->title ?: 'Untitled'); ?>
                        <?php endif; ?>
                        <div class="small text-muted"><?php echo htmlspecialchars($item->collection_name); ?></div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>

<style <?php echo $nonceAttr; ?>>
    .mobile-research .btn { font-size: 0.95rem; }
    #online-badge.bg-success { background:#198754 !important; color:#fff; }
    #online-badge.bg-danger  { background:#dc3545 !important; color:#fff; }
</style>

<script <?php echo $nonceAttr; ?>>
(function () {
    var SYNC_URL = <?php echo json_encode($syncUrl); ?>;
    var QUEUE_KEY = 'heratio_offline_queue_v1';

    function setBadge(online) {
        var el = document.getElementById('online-badge');
        if (!el) return;
        el.textContent = online ? 'Online' : 'Offline';
        el.className = 'badge ' + (online ? 'bg-success' : 'bg-danger');
    }

    function readQueue() {
        try { return JSON.parse(localStorage.getItem(QUEUE_KEY) || '[]'); } catch (e) { return []; }
    }
    function writeQueue(q) { localStorage.setItem(QUEUE_KEY, JSON.stringify(q)); }

    function flushQueue() {
        var q = readQueue();
        if (!q.length) return;
        fetch(SYNC_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ queue: q })
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data && data.ok) writeQueue([]);
        }).catch(function () {});
    }

    // Intercept the quick journal form when offline
    var form = document.getElementById('quick-journal-form');
    if (form) {
        form.addEventListener('submit', function (ev) {
            if (!navigator.onLine) {
                ev.preventDefault();
                var content = form.querySelector('[name="content"]').value || '';
                if (!content.trim()) return;
                var q = readQueue();
                q.push({ kind: 'journal_entry', content: content, created_at: new Date().toISOString() });
                writeQueue(q);
                form.querySelector('[name="content"]').value = '';
                alert('Saved offline. Will sync when reconnected.');
            }
        });
    }

    window.addEventListener('online',  function () { setBadge(true);  flushQueue(); });
    window.addEventListener('offline', function () { setBadge(false); });
    setBadge(navigator.onLine);

    // Try flushing on page load too
    if (navigator.onLine) flushQueue();

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(function () { /* ignore */ });
    }
})();
</script>

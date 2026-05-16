<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$project = sfOutputEscaper::unescape($project);
$nonce = sfConfig::get('csp_nonce', '');
$nonceAttr = $nonce ? preg_replace('/^nonce=/', 'nonce="', $nonce) . '"' : '';
$joinUrl    = url_for(['module' => 'research', 'action' => 'collabJoin',    'projectId' => $project->id]);
$pollUrl    = url_for(['module' => 'research', 'action' => 'collabPoll',    'projectId' => $project->id]);
$commentUrl = url_for(['module' => 'research', 'action' => 'collabComment', 'projectId' => $project->id]);
$resolveUrlTpl = '/index.php/research/projects/' . (int) $project->id . '/realtime/comment/COMMENT_ID/resolve';
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">Live collaboration</li>
    </ol>
</nav>

<h1 class="h2 mb-3"><i class="fas fa-users text-primary me-2"></i>Live collaboration</h1>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-circle me-2"></i>Online now</h5></div>
            <ul class="list-group list-group-flush" id="presence-list">
                <li class="list-group-item text-muted small">Connecting…</li>
            </ul>
        </div>
    </div>

    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-comments me-2"></i>Project comments</h5></div>
            <div class="card-body" id="comment-list" style="max-height:520px; overflow-y:auto;">
                <p class="text-muted small">Loading…</p>
            </div>
            <div class="card-footer">
                <div class="input-group">
                    <textarea id="new-comment" rows="2" class="form-control" placeholder="Add a comment to the project thread…"></textarea>
                    <button id="send-comment" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

<style <?php echo $nonceAttr; ?>>
    .presence-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; }
    .comment-card { padding: 8px 10px; margin-bottom: 8px; border-left: 3px solid #0d6efd; background: #f8f9fa; border-radius: 4px; }
    .comment-card.resolved { opacity: 0.55; border-left-color: #6c757d; }
</style>

<script <?php echo $nonceAttr; ?>>
(function() {
    var joinUrl = <?php echo json_encode($joinUrl); ?>;
    var pollUrl = <?php echo json_encode($pollUrl); ?>;
    var commentUrl = <?php echo json_encode($commentUrl); ?>;
    var resolveUrlTpl = <?php echo json_encode($resolveUrlTpl); ?>;

    var cursor = 0;
    var commentMap = {};

    function fetchJson(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: JSON.stringify(body || {})
        }).then(function(r) { return r.json(); });
    }

    function renderPresence(list) {
        var el = document.getElementById('presence-list');
        if (!list.length) { el.innerHTML = '<li class="list-group-item text-muted small">No collaborators online.</li>'; return; }
        el.innerHTML = list.map(function(p) {
            return '<li class="list-group-item d-flex align-items-center">'
                 + '<span class="presence-dot" style="background:' + p.color + '"></span>'
                 + '<span>' + escapeHtml(p.name) + '</span></li>';
        }).join('');
    }

    function renderComments(comments) {
        comments.forEach(function(c) {
            if (commentMap[c.id]) return;
            commentMap[c.id] = c;
            var card = document.createElement('div');
            card.className = 'comment-card' + (c.is_resolved ? ' resolved' : '');
            card.id = 'comment-' + c.id;
            card.innerHTML =
                '<div class="d-flex justify-content-between align-items-center">'
                + '<strong>' + escapeHtml(c.author_name) + '</strong>'
                + '<small class="text-muted">' + escapeHtml(c.created_at) + '</small>'
                + '</div>'
                + '<div class="mt-1">' + escapeHtml(c.body).replace(/\n/g, '<br>') + '</div>'
                + (c.is_resolved ? '' : '<button class="btn btn-sm btn-link p-0 mt-1 resolve-btn" data-cid="' + c.id + '">Resolve</button>');
            document.getElementById('comment-list').appendChild(card);
        });
        document.getElementById('comment-list').scrollTop = document.getElementById('comment-list').scrollHeight;
    }

    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, function(c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
        });
    }

    function poll() {
        fetchJson(pollUrl, { since: cursor, cursor_target: window.location.pathname }).then(function(data) {
            if (data && data.ok) {
                renderPresence(data.presence || []);
                renderComments(data.comments || []);
                cursor = data.cursor || cursor;
                if (document.getElementById('comment-list').firstElementChild && document.getElementById('comment-list').firstElementChild.tagName === 'P') {
                    document.getElementById('comment-list').firstElementChild.remove();
                }
            }
        }).catch(function() {});
    }

    document.getElementById('send-comment').addEventListener('click', function() {
        var ta = document.getElementById('new-comment');
        var body = (ta.value || '').trim();
        if (!body) return;
        ta.disabled = true;
        fetchJson(commentUrl, { body: body }).then(function(r) {
            if (r && r.ok) { ta.value = ''; poll(); }
        }).finally(function() { ta.disabled = false; });
    });

    document.addEventListener('click', function(ev) {
        if (ev.target && ev.target.classList.contains('resolve-btn')) {
            var cid = ev.target.getAttribute('data-cid');
            fetchJson(resolveUrlTpl.replace('COMMENT_ID', cid), {}).then(function() {
                var card = document.getElementById('comment-' + cid);
                if (card) card.classList.add('resolved');
                if (ev.target.parentNode) ev.target.remove();
            });
        }
    });

    fetchJson(joinUrl, { cursor_target: window.location.pathname }).then(function() { poll(); });
    setInterval(poll, 3000);
})();
</script>

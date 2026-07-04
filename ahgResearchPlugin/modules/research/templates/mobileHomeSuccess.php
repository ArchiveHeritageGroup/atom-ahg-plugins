<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => 'offline', 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$researcher = sfOutputEscaper::unescape($researcher);
$collections = sfOutputEscaper::unescape($collections ?? []);
$projects = sfOutputEscaper::unescape($projects ?? []);
$folders = sfOutputEscaper::unescape($folders ?? []);
$buildUrl = url_for(['module' => 'research', 'action' => 'buildOfflinePackage']);
$syncUrl = url_for(['module' => 'research', 'action' => 'syncUpload']);
$hasAny = count($collections) + count($projects) + count($folders) > 0;
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active"><?php echo __('Work Offline'); ?></li>
    </ol>
</nav>

<h1 class="h2"><i class="fas fa-laptop me-2"></i><?php echo __('Work Offline'); ?></h1>

<?php if ($msg = $sf_user->getFlash('success')): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>
<?php if ($msg = $sf_user->getFlash('error')): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- STEP 1: take offline -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-primary text-white"><i class="fas fa-download me-1"></i><?php echo __('1. Take records offline'); ?></div>
            <div class="card-body">
                <p class="text-muted small">
                    <?php echo __('Choose what to take with you, then download a self-contained package. It opens in any web browser with no internet or login — add notes, sources, suggestions and files, then bring them back below. Only records you are permitted to see are included.'); ?>
                </p>

                <?php if (!$hasAny): ?>
                    <div class="alert alert-info small">
                        <?php echo __('You have no collections, projects or favourites folders yet — use the search below to add individual records.'); ?>
                    </div>
                <?php endif; ?>
                    <form method="post" action="<?php echo $buildUrl; ?>">
                        <?php
                        // Grouped like the left nav: a bold section header + its items.
                        $groups = [
                            ['label' => __('Collections'), 'icon' => 'fa-layer-group', 'field' => 'collection_ids', 'items' => $collections, 'name' => 'name'],
                            ['label' => __('Projects'), 'icon' => 'fa-project-diagram', 'field' => 'project_ids', 'items' => $projects, 'name' => 'title'],
                            ['label' => __('Favourites folders'), 'icon' => 'fa-star', 'field' => 'folder_ids', 'items' => $folders, 'name' => 'name'],
                        ];
                        ?>
                        <?php foreach ($groups as $g): ?>
                            <?php if (!empty($g['items'])): ?>
                                <div class="list-group mb-3">
                                    <span class="list-group-item bg-light fw-bold text-uppercase small">
                                        <i class="fas <?php echo $g['icon']; ?> me-1"></i><?php echo $g['label']; ?>
                                    </span>
                                    <?php foreach ($g['items'] as $it): $nm = $g['name']; ?>
                                        <label class="list-group-item d-flex align-items-center">
                                            <input class="form-check-input me-2 mt-0" type="checkbox" name="<?php echo $g['field']; ?>[]" value="<?php echo (int) $it->id; ?>">
                                            <span class="flex-grow-1"><?php echo htmlspecialchars($it->$nm); ?></span>
                                            <span class="badge bg-secondary rounded-pill"><?php echo (int) $it->item_count; ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <div class="list-group mb-3">
                            <span class="list-group-item bg-light fw-bold text-uppercase small">
                                <i class="fas fa-magnifying-glass me-1"></i><?php echo __('Search &amp; add records'); ?>
                            </span>
                            <div class="list-group-item">
                                <input type="search" id="rec-search" class="form-control form-control-sm" autocomplete="off" placeholder="<?php echo __('Find a record by title or identifier'); ?>">
                                <div id="rec-results" class="list-group list-group-flush mt-2"></div>
                            </div>
                            <div id="rec-selected"></div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="include_notes" value="1" id="incnotes" checked>
                            <label class="form-check-label" for="incnotes"><?php echo __('Include my existing notes/annotations on those records'); ?></label>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-download me-1"></i><?php echo __('Download offline package'); ?></button>
                    </form>

                    <?php $n = sfConfig::get('csp_nonce', ''); $nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>
                    <script <?php echo $nonceAttr; ?>>
                    (function () {
                        var searchUrl = <?php echo json_encode(url_for(['module' => 'research', 'action' => 'offlineSearch'])); ?>;
                        var box = document.getElementById('rec-search');
                        var results = document.getElementById('rec-results');
                        var selected = document.getElementById('rec-selected');
                        var picked = {};
                        var timer = null;
                        function esc(s) { return String(s == null ? '' : s).replace(/[<>&"]/g, function (c) { return ({ '<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;' })[c]; }); }
                        function renderResults(recs) {
                            results.innerHTML = recs.map(function (r) {
                                if (picked[r.id]) return '';
                                return '<button type="button" class="list-group-item list-group-item-action py-1 rec-add" data-id="' + r.id + '" data-title="' + esc(r.title) + '">'
                                    + '<i class="fas fa-plus text-success me-1"></i>' + esc(r.title)
                                    + (r.identifier ? ' <span class="text-muted small">' + esc(r.identifier) + '</span>' : '') + '</button>';
                            }).join('') || '<div class="text-muted small py-1">No matches.</div>';
                        }
                        function renderSelected() {
                            var ids = Object.keys(picked);
                            if (!ids.length) { selected.innerHTML = ''; return; }
                            selected.innerHTML = '<span class="list-group-item bg-light fw-bold text-uppercase small"><i class="fas fa-check me-1"></i>Records added by search (' + ids.length + ')</span>'
                                + ids.map(function (id) {
                                    return '<label class="list-group-item d-flex align-items-center">'
                                        + '<input type="hidden" name="record_ids[]" value="' + id + '">'
                                        + '<span class="flex-grow-1">' + esc(picked[id]) + '</span>'
                                        + '<span class="rec-del text-danger" data-id="' + id + '" style="cursor:pointer;font-weight:700">&times;</span></label>';
                                }).join('');
                        }
                        box.addEventListener('input', function () {
                            clearTimeout(timer);
                            var q = this.value.trim();
                            if (q.length < 2) { results.innerHTML = ''; return; }
                            timer = setTimeout(function () {
                                fetch(searchUrl + '?q=' + encodeURIComponent(q), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                    .then(function (r) { return r.json(); })
                                    .then(function (d) { if (d && d.success) renderResults(d.records || []); })
                                    .catch(function () {});
                            }, 250);
                        });
                        results.addEventListener('click', function (e) {
                            var b = e.target.closest('.rec-add'); if (!b) return;
                            picked[b.dataset.id] = b.dataset.title; b.remove(); renderSelected();
                        });
                        selected.addEventListener('click', function (e) {
                            var x = e.target.closest('.rec-del'); if (!x) return;
                            delete picked[x.dataset.id]; renderSelected();
                        });
                    })();
                    </script>
            </div>
        </div>
    </div>

    <!-- STEP 2: sync back -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-success text-white"><i class="fas fa-cloud-arrow-up me-1"></i><?php echo __('2. Bring your work back'); ?></div>
            <div class="card-body">
                <p class="text-muted small">
                    <?php echo __('Finished working offline? In the package, click "Save for sync" to download a researcher-sync.json file, then upload it here. Your notes and sources are added to your research, files are attached, and metadata suggestions go to a curator for review.'); ?>
                </p>
                <form method="post" action="<?php echo $syncUrl; ?>" enctype="multipart/form-data" id="sync-form">
                    <input type="file" name="sync_file" id="sync-file" accept=".json,application/json" class="d-none">
                    <button type="button" class="btn btn-success" id="sync-pick"><i class="fas fa-cloud-arrow-up me-1"></i><?php echo __('Choose researcher-sync.json &amp; sync'); ?></button>
                    <span id="sync-chosen" class="ms-2 small text-muted"></span>
                    <div class="form-text mt-1"><?php echo __('Pick the <strong>researcher-sync.json</strong> file you downloaded; it uploads and syncs automatically.'); ?></div>
                </form>
                <?php $n2 = sfConfig::get('csp_nonce', ''); $nonceAttr2 = $n2 ? preg_replace('/^nonce=/', 'nonce="', $n2) . '"' : ''; ?>
                <script <?php echo $nonceAttr2; ?>>
                (function () {
                    var pick = document.getElementById('sync-pick');
                    var inp = document.getElementById('sync-file');
                    var form = document.getElementById('sync-form');
                    var chosen = document.getElementById('sync-chosen');
                    if (!pick || !inp) return;
                    pick.addEventListener('click', function () { inp.click(); });
                    inp.addEventListener('change', function () {
                        if (inp.files && inp.files.length) {
                            chosen.textContent = inp.files[0].name + ' — syncing…';
                            pick.disabled = true;
                            form.submit();
                        }
                    });
                })();
                </script>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><i class="fas fa-circle-info me-1"></i><?php echo __('How it works'); ?></div>
            <div class="card-body small text-muted">
                <ol class="mb-0 ps-3">
                    <li><?php echo __('Pick collections / projects / favourites and download the package.'); ?></li>
                    <li><?php echo __('Open index.html in any browser — no internet needed.'); ?></li>
                    <li><?php echo __('Add notes, sources, suggestions, files to records.'); ?></li>
                    <li><?php echo __('Click "Save for sync" → get researcher-sync.json.'); ?></li>
                    <li><?php echo __('Upload it here to bring everything back.'); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

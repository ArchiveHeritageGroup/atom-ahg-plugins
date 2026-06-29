<?php
$session = $sf_data->getRaw('session');
$files = $sf_data->getRaw('files') ?? [];
$spEnabled = file_exists(sfConfig::get('sf_plugins_dir') . '/ahgSharePointPlugin');
$spTenants = $sf_data->getRaw('sp_tenants') ?? [];
$spDrives = $sf_data->getRaw('sp_drives') ?? [];
?>

<h1><?php echo __('Upload Files') ?></h1>

<?php echo get_partial('default/breadcrumb', [
    'objects' => [
        ['title' => __('Admin'), 'url' => url_for(['module' => 'admin', 'action' => 'index'])],
        ['title' => __('Ingestion Manager'), 'url' => url_for(['module' => 'ingest', 'action' => 'index'])],
        ['title' => esc_entities($session->title ?: __('Session #' . $session->id))],
        ['title' => __('Upload')]
    ]
]) ?>

<!-- Wizard Progress -->
<div class="mb-4">
    <div class="d-flex justify-content-between text-center">
        <div class="flex-fill"><span class="badge bg-success rounded-pill">1</span><br><small class="text-muted"><?php echo __('Configure') ?></small></div>
        <div class="flex-fill"><span class="badge bg-primary rounded-pill">2</span><br><small class="fw-bold"><?php echo __('Upload') ?></small></div>
        <div class="flex-fill"><span class="badge bg-secondary rounded-pill">3</span><br><small class="text-muted"><?php echo __('Map') ?></small></div>
        <div class="flex-fill"><span class="badge bg-secondary rounded-pill">4</span><br><small class="text-muted"><?php echo __('Validate') ?></small></div>
        <div class="flex-fill"><span class="badge bg-secondary rounded-pill">5</span><br><small class="text-muted"><?php echo __('Preview') ?></small></div>
        <div class="flex-fill"><span class="badge bg-secondary rounded-pill">6</span><br><small class="text-muted"><?php echo __('Commit') ?></small></div>
    </div>
    <div class="progress mt-2" style="height: 4px;">
        <div class="progress-bar" style="width: 25%"></div>
    </div>
</div>

<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo $sf_user->getFlash('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif ?>

<div class="row">
    <div class="col-md-8">

        <ul class="nav nav-tabs mb-3" id="ingestSourceTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-fileupload-tab" data-bs-toggle="tab" data-bs-target="#tab-fileupload" type="button" role="tab">
                    <i class="fas fa-cloud-upload-alt me-1"></i><?php echo __('File / Directory') ?>
                </button>
            </li>
            <?php if ($spEnabled): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-sharepoint-tab" data-bs-toggle="tab" data-bs-target="#tab-sharepoint" type="button" role="tab">
                    <i class="fab fa-microsoft me-1"></i><?php echo __('From SharePoint') ?>
                </button>
            </li>
            <?php endif ?>
        </ul>

        <div class="tab-content" id="ingestSourceTabsContent">

            <div class="tab-pane fade show active" id="tab-fileupload" role="tabpanel">
                <form method="post" enctype="multipart/form-data"
                      action="<?php echo url_for(['module' => 'ingest', 'action' => 'upload', 'id' => $session->id]) ?>">

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-cloud-upload-alt me-2"></i><?php echo __('Upload File') ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="ingest_file" class="form-label"><?php echo __('Select CSV, ZIP, or EAD file') ?></label>
                                <div id="drop-zone" class="border border-2 border-dashed rounded p-5 text-center mb-3">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <p class="mb-1"><?php echo __('Drag and drop file here, or click to browse') ?></p>
                                    <small class="text-muted"><?php echo __('Supported: CSV, ZIP (with CSV + digital objects), EAD XML') ?></small>
                                    <input type="file" class="form-control mt-3" id="ingest_file" name="ingest_file"
                                           accept=".csv,.zip,.xml,.ead">
                                </div>
                                <div id="file-info" class="alert alert-info" style="display:none;"></div>
                            </div>

                            <hr>

                            <div class="mb-3">
                                <label for="ingest_folder" class="form-label"><?php echo __('Or upload an entire local folder') ?></label>
                                <input type="file" class="form-control" id="ingest_folder" name="ingest_folder[]"
                                       webkitdirectory directory multiple>
                                <small class="text-muted"><?php echo __('Pick a folder on your computer — every file inside is uploaded as a batch of digital objects (no CSV required).') ?></small>
                                <div id="folder-info" class="alert alert-info mt-2" style="display:none;"></div>
                            </div>

                            <hr>

                            <div class="mb-3">
                                <label for="directory_path" class="form-label"><?php echo __('Or enter a server directory path') ?></label>
                                <input type="text" class="form-control" id="directory_path" name="directory_path"
                                       placeholder="<?php echo __('/path/to/files/on/server') ?>">
                                <small class="text-muted"><?php echo __('For large batches, point to a directory on the server instead of uploading') ?></small>
                                <?php if ($sf_user->isAdministrator()): ?>
                                <div class="mt-2">
                                    <button type="submit" class="btn btn-outline-info btn-sm" formnovalidate
                                            formaction="<?php echo url_for(['module' => 'ingest', 'action' => 'setWatchFolder', 'id' => $session->id]) ?>">
                                        <i class="fas fa-binoculars me-1"></i><?php echo __('Set as watched folder') ?>
                                    </button>
                                    <small class="text-muted d-block mt-1"><?php echo __('Auto-ingest new files dropped into this folder, using this session\'s settings as the template (requires the ingest:watch cron).') ?></small>
                                </div>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'configure', 'id' => $session->id]) ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back') ?>
                        </a>
                        <button type="submit" class="btn btn-primary" id="btn-upload">
                            <?php echo __('Upload & Continue') ?> <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($spEnabled): ?>
            <div class="tab-pane fade" id="tab-sharepoint" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fab fa-microsoft me-2"></i><?php echo __('Import from SharePoint') ?></h5>
                    </div>
                    <div class="card-body">

                        <?php if (empty($spTenants)): ?>
                            <div class="alert alert-warning mb-0">
                                <?php echo __('No SharePoint tenants are configured. Configure at') ?>
                                <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'tenants']) ?>">/admin/sharepoint/tenants</a>.
                            </div>
                        <?php else: ?>

                        <form method="post" id="sp-import-form"
                              action="<?php echo url_for(['module' => 'ingest', 'action' => 'importFromSharePoint', 'id' => $session->id]) ?>">

                            <div class="mb-3">
                                <label for="sp_tenant" class="form-label"><?php echo __('SharePoint tenant') ?></label>
                                <select class="form-select" id="sp_tenant" name="sp_tenant_id">
                                    <?php foreach ($spTenants as $t): ?>
                                        <option value="<?php echo (int) $t->id ?>"><?php echo esc_entities($t->name) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="sp_site" class="form-label"><?php echo __('Site') ?></label>
                                <select class="form-select" id="sp_site"><option value="">— <?php echo __('Loading sites...') ?> —</option></select>
                            </div>

                            <div class="mb-3">
                                <label for="sp_drive" class="form-label"><?php echo __('Drive (document library)') ?></label>
                                <select class="form-select" id="sp_drive" disabled><option value="">— <?php echo __('Select a site first') ?> —</option></select>
                                <input type="hidden" name="sp_drive_id" id="sp_drive_id_hidden">
                                <input type="hidden" name="sp_drive_name" id="sp_drive_name_hidden">
                                <input type="hidden" name="sp_site_id" id="sp_site_id_hidden">
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><?php echo __('Items') ?></label>
                                <div id="sp-tree" class="border rounded p-3" style="max-height: 360px; overflow-y: auto;">
                                    <small class="text-muted"><?php echo __('Select a drive to browse files') ?></small>
                                </div>
                                <small class="text-muted"><?php echo __('Tick the files to import; folders expand on click.') ?></small>
                            </div>

                            <div id="sp-selected-count" class="mb-3"></div>

                            <div class="d-flex justify-content-between">
                                <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'configure', 'id' => $session->id]) ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i><?php echo __('Back') ?>
                                </a>
                                <button type="submit" class="btn btn-primary" id="sp-import-btn" disabled>
                                    <?php echo __('Import selected & continue') ?> <i class="fas fa-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </form>

                        <?php endif ?>
                    </div>
                </div>
            </div>
            <?php endif ?>

        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Session Info') ?></h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li><strong><?php echo __('Sector') ?>:</strong> <?php echo ucfirst($session->sector) ?></li>
                    <li><strong><?php echo __('Standard') ?>:</strong> <?php echo strtoupper($session->standard) ?></li>
                    <li><strong><?php echo __('Placement') ?>:</strong> <?php echo ucfirst(str_replace('_', ' ', $session->parent_placement)) ?></li>
                </ul>
            </div>
        </div>

        <?php if (!empty($files)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-file me-2"></i><?php echo __('Uploaded Files') ?></h5>
            </div>
            <div class="card-body">
                <?php foreach ($files as $f): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <i class="fas fa-file-<?php echo $f->file_type === 'csv' ? 'csv' : ($f->file_type === 'zip' ? 'archive' : ($f->file_type === 'sharepoint' ? 'cloud' : 'code')) ?> me-1"></i>
                            <small><?php echo esc_entities($f->original_name) ?></small>
                        </div>
                        <small class="text-muted"><?php echo $f->row_count ? $f->row_count . ' rows' : '' ?></small>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
        <?php endif ?>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-download me-2"></i><?php echo __('CSV Templates') ?></h5>
            </div>
            <div class="card-body">
                <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'downloadTemplate', 'sector' => $session->sector]) ?>"
                   class="btn btn-outline-secondary btn-sm w-100">
                    <i class="fas fa-download me-1"></i><?php echo __('Download Template for') ?> <?php echo ucfirst($session->sector) ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var dropZone = document.getElementById('drop-zone');
    var fileInput = document.getElementById('ingest_file');
    var fileInfo = document.getElementById('file-info');

    if (dropZone) {
        ['dragenter', 'dragover'].forEach(function(ev) {
            dropZone.addEventListener(ev, function(e) {
                e.preventDefault();
                dropZone.classList.add('border-primary', 'bg-light');
            });
        });
        ['dragleave', 'drop'].forEach(function(ev) {
            dropZone.addEventListener(ev, function(e) {
                e.preventDefault();
                dropZone.classList.remove('border-primary', 'bg-light');
            });
        });
        dropZone.addEventListener('drop', function(e) {
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                showFileInfo(e.dataTransfer.files[0]);
            }
        });
        dropZone.addEventListener('click', function() { fileInput.click(); });
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) showFileInfo(this.files[0]);
        });
    }

    function showFileInfo(file) {
        var size = (file.size / 1024 / 1024).toFixed(2);
        fileInfo.style.display = '';
        fileInfo.innerHTML = '<strong>' + file.name + '</strong> (' + size + ' MB)';
    }

    // Local folder picker — summarise the selection (file count + total size)
    var folderInput = document.getElementById('ingest_folder');
    var folderInfo = document.getElementById('folder-info');
    if (folderInput && folderInfo) {
        folderInput.addEventListener('change', function() {
            var n = this.files.length;
            if (!n) { folderInfo.style.display = 'none'; return; }
            var total = 0;
            for (var i = 0; i < n; i++) { total += this.files[i].size; }
            var mb = (total / 1024 / 1024).toFixed(2);
            folderInfo.style.display = '';
            folderInfo.innerHTML = '<strong>' + n + '</strong> ' +
                (n === 1 ? 'file' : 'files') + ' selected (' + mb + ' MB total)';
        });
    }

    // ── SharePoint picker ─────────────────────────────────────────
    var browseUrl = '<?php echo url_for(['module' => 'ingest', 'action' => 'browseSharePoint', 'id' => $session->id]) ?>';
    var spTenant = document.getElementById('sp_tenant');
    var spSite = document.getElementById('sp_site');
    var spDrive = document.getElementById('sp_drive');
    var spDriveIdHidden = document.getElementById('sp_drive_id_hidden');
    var spDriveNameHidden = document.getElementById('sp_drive_name_hidden');
    var spSiteIdHidden = document.getElementById('sp_site_id_hidden');
    var spTree = document.getElementById('sp-tree');
    var spImportBtn = document.getElementById('sp-import-btn');
    var spSelectedCount = document.getElementById('sp-selected-count');
    var spForm = document.getElementById('sp-import-form');
    if (!spTenant) return;

    function fetchSP(params) {
        var qs = Object.keys(params).map(function (k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
        }).join('&');
        return fetch(browseUrl + (browseUrl.indexOf('?') === -1 ? '?' : '&') + qs, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        });
    }

    function loadSites() {
        spSite.innerHTML = '<option value="">— Loading sites... —</option>';
        fetchSP({ op: 'sites', tenant_id: spTenant.value }).then(function (json) {
            spSite.innerHTML = '<option value="">— Select site —</option>';
            (json.sites || []).forEach(function (s) {
                var o = document.createElement('option');
                o.value = s.id;
                o.dataset.name = s.displayName;
                o.textContent = s.displayName;
                spSite.appendChild(o);
            });
        }).catch(function (e) {
            spSite.innerHTML = '<option value="">' + e.message + '</option>';
        });
    }

    function loadDrives() {
        if (!spSite.value) { spDrive.disabled = true; return; }
        spDrive.disabled = true;
        spDrive.innerHTML = '<option value="">— Loading drives... —</option>';
        fetchSP({ op: 'drives', tenant_id: spTenant.value, site_id: spSite.value }).then(function (json) {
            spDrive.innerHTML = '<option value="">— Select drive —</option>';
            (json.drives || []).forEach(function (d) {
                var o = document.createElement('option');
                o.value = d.id;
                o.dataset.name = d.name;
                o.textContent = d.name + ' (' + (d.driveType || 'documentLibrary') + ')';
                spDrive.appendChild(o);
            });
            spDrive.disabled = false;
        });
    }

    function renderChildren(parentEl, items) {
        parentEl.innerHTML = '';
        if (!items.length) {
            parentEl.innerHTML = '<small class="text-muted">— ' + 'empty' + ' —</small>';
            return;
        }
        var ul = document.createElement('ul');
        ul.style.listStyle = 'none';
        ul.style.paddingLeft = '1rem';
        items.forEach(function (it) {
            var li = document.createElement('li');
            li.style.marginBottom = '4px';
            if (it.isFolder) {
                var caret = document.createElement('span');
                caret.textContent = '▶';
                caret.style.cursor = 'pointer';
                caret.style.marginRight = '6px';
                var label = document.createElement('span');
                label.innerHTML = '<i class="fas fa-folder text-warning me-1"></i><strong>' + escapeHtml(it.name) + '</strong>';
                var childWrap = document.createElement('div');
                childWrap.style.display = 'none';
                childWrap.style.paddingLeft = '12px';
                caret.addEventListener('click', function () {
                    if (childWrap.style.display === 'none') {
                        if (!childWrap.dataset.loaded) {
                            childWrap.innerHTML = '<small class="text-muted">loading...</small>';
                            fetchSP({ op: 'children', tenant_id: spTenant.value, drive_id: spDrive.value, item_id: it.id }).then(function (json) {
                                renderChildren(childWrap, json.items || []);
                                childWrap.dataset.loaded = '1';
                            });
                        }
                        childWrap.style.display = '';
                        caret.textContent = '▼';
                    } else {
                        childWrap.style.display = 'none';
                        caret.textContent = '▶';
                    }
                });
                li.appendChild(caret);
                li.appendChild(label);
                li.appendChild(childWrap);
            } else {
                var cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.name = 'sp_item_ids[]';
                cb.value = it.id;
                cb.className = 'sp-item-cb me-2';
                cb.dataset.name = it.name;
                cb.dataset.size = it.size;
                cb.dataset.etag = it.etag || '';
                var lbl = document.createElement('label');
                lbl.innerHTML = '<i class="fas fa-file text-secondary me-1"></i>' + escapeHtml(it.name) +
                    ' <small class="text-muted">(' + formatBytes(it.size) + ')</small>';
                li.appendChild(cb);
                li.appendChild(lbl);
            }
            ul.appendChild(li);
        });
        parentEl.appendChild(ul);
    }

    function escapeHtml(s) { return String(s).replace(/[&<>"']/g, function (c) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]; }); }
    function formatBytes(n) { if (!n) return '0 B'; var u = ['B','KB','MB','GB']; var i = Math.floor(Math.log(n)/Math.log(1024)); return (n/Math.pow(1024,i)).toFixed(1) + ' ' + u[i]; }

    spTenant.addEventListener('change', loadSites);
    spSite.addEventListener('change', function () {
        var opt = spSite.options[spSite.selectedIndex];
        spSiteIdHidden.value = spSite.value;
        loadDrives();
    });
    spDrive.addEventListener('change', function () {
        if (!spDrive.value) return;
        spDriveIdHidden.value = spDrive.value;
        spDriveNameHidden.value = spDrive.options[spDrive.selectedIndex].dataset.name || '';
        spTree.innerHTML = '<small class="text-muted">loading...</small>';
        fetchSP({ op: 'children', tenant_id: spTenant.value, drive_id: spDrive.value, item_id: 'root' }).then(function (json) {
            renderChildren(spTree, json.items || []);
        });
    });
    spTree.addEventListener('change', function () {
        var n = spTree.querySelectorAll('input.sp-item-cb:checked').length;
        spSelectedCount.textContent = n + ' file(s) selected';
        spImportBtn.disabled = (n === 0);
    });

    loadSites();
});
</script>

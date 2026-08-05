<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1>
    <i class="bi bi-archive text-primary me-2"></i>
    <?php echo $package ? __('Edit Package') : __('Create Package'); ?>
</h1>
<?php end_slot() ?>

<?php slot('content') ?>

<?php if ($sf_user->hasFlash('notice')): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?php echo $sf_user->getFlash('notice'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($sf_user->hasFlash('error')): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?php echo $sf_user->getFlash('error'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-<?php echo $package ? 'pencil' : 'plus-lg'; ?> me-2"></i>
                <?php echo $package ? __('Package Details') : __('New Package'); ?>
            </div>
            <div class="card-body">
                <form method="post" action="">
<input type="hidden" name="_ahg_csrf_token" value="<?php echo htmlspecialchars(class_exists('\AtomFramework\Services\CsrfService') ? \AtomFramework\Services\CsrfService::generateToken() : '', ENT_QUOTES); ?>">
                    <input type="hidden" name="form_action" value="<?php echo $package ? 'update' : 'create'; ?>">

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Package Name'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               value="<?php echo htmlspecialchars($package->name ?? ''); ?>"
                               placeholder="<?php echo __('e.g., Annual Reports 2024 SIP'); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Description'); ?></label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="<?php echo __('Brief description of package contents'); ?>"><?php echo htmlspecialchars($package->description ?? ''); ?></textarea>
                    </div>

                    <?php if (!$package): ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Package Type'); ?> <span class="text-danger">*</span></label>
                            <select name="package_type" class="form-select" required>
                                <option value=""><?php echo __('Select type...'); ?></option>
                                <option value="sip">SIP - Submission Information Package</option>
                                <option value="aip">AIP - Archival Information Package</option>
                                <option value="dip">DIP - Dissemination Information Package</option>
                            </select>
                            <div class="form-text"><?php echo __('SIP for ingest, AIP for storage, DIP for access'); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Package Format'); ?></label>
                            <select name="package_format" class="form-select">
                                <option value="bagit" selected>BagIt (Recommended)</option>
                                <option value="zip">ZIP Archive</option>
                                <option value="tar">TAR Archive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Checksum Algorithm'); ?></label>
                        <select name="manifest_algorithm" class="form-select">
                            <option value="sha256" selected>SHA-256 (Recommended)</option>
                            <option value="sha512">SHA-512</option>
                            <option value="sha1">SHA-1</option>
                            <option value="md5">MD5</option>
                        </select>
                    </div>
                    <?php else: ?>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><?php echo __('Package Type'); ?></label>
                            <input type="text" class="form-control" disabled value="<?php echo strtoupper($package->package_type); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php echo __('Status'); ?></label>
                            <input type="text" class="form-control" disabled value="<?php echo ucfirst($package->status); ?>">
                        </div>
                    </div>
                    <?php endif; ?>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Originator'); ?></label>
                        <input type="text" name="originator" class="form-control"
                               value="<?php echo htmlspecialchars($package->originator ?? ''); ?>"
                               placeholder="<?php echo __('Organization creating this package'); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Submission Agreement'); ?></label>
                        <input type="text" name="submission_agreement" class="form-control"
                               value="<?php echo htmlspecialchars($package->submission_agreement ?? ''); ?>"
                               placeholder="<?php echo __('Reference to submission agreement'); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Retention Period'); ?></label>
                        <input type="text" name="retention_period" class="form-control"
                               value="<?php echo htmlspecialchars($package->retention_period ?? ''); ?>"
                               placeholder="<?php echo __('e.g., Permanent, 10 years, etc.'); ?>">
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Parent Package'); ?></label>
                        <select name="parent_package_id" class="form-select">
                            <option value=""><?php echo __('- None (top-level package) -'); ?></option>
                            <?php foreach ($allPackages as $p): ?>
                            <option value="<?php echo (int) $p->id; ?>" <?php echo ($package && $package->parent_package_id == $p->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p->name).' ('.strtoupper($p->package_type).' / '.ucfirst($p->status).')'; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text"><?php echo __('Nest this package under a parent - e.g. a SIP or DIP under its AIP.'); ?></div>
                    </div>

                    <div class="mb-3 position-relative">
                        <label class="form-label"><?php echo __('Linked Collection / Description'); ?></label>
                        <input type="hidden" name="information_object_id" id="collectionId" value="<?php echo (int) ($package->information_object_id ?? 0) ?: ''; ?>">
                        <div class="input-group">
                            <input type="text" id="collectionSearch" class="form-control" autocomplete="off"
                                   value="<?php echo htmlspecialchars($linkedDescription ?? ''); ?>"
                                   placeholder="<?php echo __('Search archival descriptions by title or reference...'); ?>">
                            <button type="button" class="btn btn-outline-secondary" id="collectionClear" title="<?php echo __('Clear'); ?>">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div id="collectionResults" class="list-group position-absolute w-100 shadow-sm" style="z-index:1000;"></div>
                        <div class="form-text"><?php echo __('The archival collection this package represents (its child descriptions).'); ?></div>
                    </div>

                    <?php if ($package): ?>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="build_on_save" id="buildOnSave" value="1">
                        <label class="form-check-label" for="buildOnSave">
                            <?php echo __('Build &amp; export on save'); ?>
                            <span class="text-muted d-block small"><?php echo __('Package and export immediately so the download is ready after saving (requires at least one object).'); ?></span>
                        </label>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between">
                        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'packages']); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i><?php echo __('Cancel'); ?>
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i><?php echo $package ? __('Save Changes') : __('Create Package'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($package && 'draft' === $package->status): ?>
        <!-- Add Objects Section -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-file-earmark-plus me-2"></i><?php echo __('Package Objects'); ?></span>
                <span class="badge bg-primary"><?php echo count($objects); ?> objects</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Add Digital Object'); ?></label>
                    <div class="input-group position-relative">
                        <input type="text" id="objectSearchInput" class="form-control" autocomplete="off" placeholder="<?php echo __('Search by title or file name...'); ?>">
                        <input type="hidden" id="objectIdInput" value="">
                        <button type="button" class="btn btn-outline-primary" onclick="addObject()">
                            <i class="bi bi-plus-lg me-1"></i><?php echo __('Add'); ?>
                        </button>
                        <div id="objectSearchResults" class="list-group position-absolute w-100 shadow-sm" style="z-index:1050; top:100%; max-height:320px; overflow-y:auto;"></div>
                    </div>
                    <div class="form-text"><?php echo __('Search a description by title or file name and pick it - or type a numeric digital object ID directly.'); ?></div>
                </div>

                <div class="mb-3 border-top pt-3">
                    <label class="form-label"><?php echo __('Add all objects from a collection'); ?></label>
                    <div class="input-group position-relative">
                        <input type="text" id="collImportSearch" class="form-control" autocomplete="off" placeholder="<?php echo __('Search a collection/description by title or reference...'); ?>">
                        <input type="hidden" id="collImportId" value="">
                        <button type="button" class="btn btn-outline-primary" onclick="addCollectionObjects()">
                            <i class="bi bi-collection me-1"></i><?php echo __('Add all'); ?>
                        </button>
                        <div id="collImportResults" class="list-group position-absolute w-100 shadow-sm" style="z-index:1050; top:100%; max-height:320px; overflow-y:auto;"></div>
                    </div>
                    <div class="form-text"><?php echo __('Pulls every master digital object under the chosen description and its child records into this package.'); ?></div>
                </div>

                <?php if (!empty($objects)): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th><?php echo __('File'); ?></th>
                                <th><?php echo __('Format'); ?></th>
                                <th><?php echo __('Size'); ?></th>
                                <th class="text-end"><?php echo __('Actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($objects as $obj): ?>
                            <tr id="obj-row-<?php echo $obj->digital_object_id; ?>">
                                <td>
                                    <?php echo htmlspecialchars($obj->file_name); ?>
                                    <br>
                                    <small class="text-muted"><?php echo $obj->information_object_title ?? 'No title'; ?></small>
                                </td>
                                <td>
                                    <?php if ($obj->puid): ?>
                                    <span class="badge bg-info"><?php echo $obj->puid; ?></span>
                                    <?php endif; ?>
                                    <small class="text-muted d-block"><?php echo $obj->mime_type ?? 'Unknown'; ?></small>
                                </td>
                                <td><?php echo $obj->file_size ? formatBytes($obj->file_size) : '-'; ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeObject(<?php echo $obj->digital_object_id; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center text-muted py-3">
                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                    <?php echo __('No objects added yet'); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-4">
        <?php if ($package): ?>
        <!-- Package Info -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i><?php echo __('Package Info'); ?>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4"><?php echo __('UUID'); ?></dt>
                    <dd class="col-sm-8"><code class="small"><?php echo $package->uuid; ?></code></dd>

                    <dt class="col-sm-4"><?php echo __('Format'); ?></dt>
                    <dd class="col-sm-8"><?php echo ucfirst($package->package_format); ?></dd>

                    <dt class="col-sm-4"><?php echo __('Algorithm'); ?></dt>
                    <dd class="col-sm-8"><?php echo strtoupper($package->manifest_algorithm); ?></dd>

                    <dt class="col-sm-4"><?php echo __('Objects'); ?></dt>
                    <dd class="col-sm-8"><?php echo number_format($package->object_count); ?></dd>

                    <dt class="col-sm-4"><?php echo __('Size'); ?></dt>
                    <dd class="col-sm-8"><?php echo $package->total_size ? formatBytes($package->total_size) : '-'; ?></dd>

                    <?php if ($package->package_checksum): ?>
                    <dt class="col-sm-4"><?php echo __('Checksum'); ?></dt>
                    <dd class="col-sm-8"><code class="small"><?php echo substr($package->package_checksum, 0, 16); ?>...</code></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <!-- Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-lightning me-2"></i><?php echo __('Actions'); ?>
            </div>
            <div class="card-body">
                <?php if ($package->object_count > 0): ?>
                <button type="button" class="btn btn-success w-100 mb-2" onclick="buildExportPackage()">
                    <i class="bi bi-lightning-charge me-1"></i><?php echo __('Build &amp; Export'); ?>
                </button>
                <div class="form-text mb-2"><?php echo __('One step: builds and exports so the download is ready.'); ?></div>
                <hr>
                <?php endif; ?>

                <?php if ('draft' === $package->status && $package->object_count > 0): ?>
                <button type="button" class="btn btn-outline-success w-100 mb-2" onclick="buildPackage()">
                    <i class="bi bi-hammer me-1"></i><?php echo __('Build Package'); ?>
                </button>
                <?php endif; ?>

                <?php if ('complete' === $package->status): ?>
                <button type="button" class="btn btn-primary w-100 mb-2" onclick="validatePackage()">
                    <i class="bi bi-check-circle me-1"></i><?php echo __('Validate Package'); ?>
                </button>
                <?php endif; ?>

                <?php if (in_array($package->status, ['complete', 'validated'])): ?>
                <button type="button" class="btn btn-info w-100 mb-2" onclick="exportPackage()">
                    <i class="bi bi-box-arrow-up me-1"></i><?php echo __('Export Package'); ?>
                </button>
                <?php endif; ?>

                <?php if ($package->export_path): ?>
                <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'packageDownload', 'id' => $package->id]); ?>" class="btn btn-outline-success w-100 mb-2">
                    <i class="bi bi-download me-1"></i><?php echo __('Download Export'); ?>
                </a>
                <?php endif; ?>

                <?php if ('sip' === $package->package_type && in_array($package->status, ['validated', 'exported'])): ?>
                <hr>
                <button type="button" class="btn btn-outline-primary w-100" onclick="convertPackage('aip')">
                    <i class="bi bi-arrow-right-circle me-1"></i><?php echo __('Convert to AIP'); ?>
                </button>
                <?php endif; ?>

                <?php if ('aip' === $package->package_type && in_array($package->status, ['validated', 'exported'])): ?>
                <hr>
                <button type="button" class="btn btn-outline-warning w-100" onclick="convertPackage('dip')">
                    <i class="bi bi-arrow-right-circle me-1"></i><?php echo __('Create DIP'); ?>
                </button>
                <?php endif; ?>

                <?php if ('draft' === $package->status): ?>
                <hr>
                <button type="button" class="btn btn-outline-danger w-100" onclick="deletePackage()">
                    <i class="bi bi-trash me-1"></i><?php echo __('Delete Package'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Events -->
        <?php if (!empty($events)): ?>
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history me-2"></i><?php echo __('Recent Events'); ?>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach (array_slice($sf_data->getRaw('events'), 0, 5) as $event): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <span class="badge bg-<?php echo 'success' === $event->event_outcome ? 'success' : ('failure' === $event->event_outcome ? 'danger' : 'secondary'); ?>">
                            <?php echo $event->event_type; ?>
                        </span>
                        <small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($event->event_datetime)); ?></small>
                    </div>
                    <?php if ($event->event_detail): ?>
                    <small class="text-muted d-block mt-1"><?php echo htmlspecialchars(substr($event->event_detail, 0, 50)); ?></small>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <!-- Help Card for New Package -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-question-circle me-2"></i><?php echo __('OAIS Package Types'); ?>
            </div>
            <div class="card-body">
                <h6 class="text-info"><i class="bi bi-box-arrow-in-right me-1"></i>SIP - Submission</h6>
                <p class="small text-muted mb-3">Package used to submit content to the archive. Contains the digital objects and metadata.</p>

                <h6 class="text-success"><i class="bi bi-safe me-1"></i>AIP - Archival</h6>
                <p class="small text-muted mb-3">Package stored in the archive for long-term preservation. Created from a validated SIP.</p>

                <h6 class="text-warning"><i class="bi bi-box-arrow-right me-1"></i>DIP - Dissemination</h6>
                <p class="small text-muted mb-0">Package created for user access. Derived from an AIP with access-optimized formats.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
function formatBytes($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' bytes';
}
?>

<?php if ($package): ?>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
const packageId = <?php echo $package->id; ?>;

function addObject() {
    let objectId = document.getElementById('objectIdInput').value;
    const raw = document.getElementById('objectSearchInput').value.trim();
    // Fallback: allow typing a bare numeric ID without selecting from the lookup.
    if (!objectId && /^\d+$/.test(raw)) { objectId = raw; }
    if (!objectId) {
        alert('Search for and select a digital object, or type its numeric ID');
        return;
    }

    fetch('<?php echo url_for(['module' => 'preservation', 'action' => 'apiPackageAddObject']); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `package_id=${packageId}&object_id=${objectId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    });
}

// Digital-object lookup: search by description title or file name, show File + Format.
(function () {
    var input = document.getElementById('objectSearchInput');
    var idField = document.getElementById('objectIdInput');
    var box = document.getElementById('objectSearchResults');
    if (!input || !box) { return; }
    var timer = null;
    function esc(s) { var d = document.createElement('div'); d.textContent = (s == null ? '' : s); return d.innerHTML; }
    input.addEventListener('input', function () {
        idField.value = '';               // typing invalidates any prior selection
        clearTimeout(timer);
        var q = input.value.trim();
        if (q.length < 2) { box.innerHTML = ''; return; }
        timer = setTimeout(function () {
            fetch('<?php echo url_for(['module' => 'preservation', 'action' => 'apiSearchObjects']); ?>?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    box.innerHTML = '';
                    var results = (d && d.results) || [];
                    if (!results.length) { return; }
                    results.forEach(function (it) {
                        var a = document.createElement('a');
                        a.href = '#';
                        a.className = 'list-group-item list-group-item-action py-1 small';
                        a.innerHTML = '<strong>#' + it.id + '</strong> ' + esc(it.title) +
                            ' <span class="text-muted">- ' + esc(it.filename) + ' [' + esc(it.format) + ']</span>';
                        a.addEventListener('click', function (e) {
                            e.preventDefault();
                            idField.value = it.id;
                            input.value = '#' + it.id + '  ' + it.title;
                            box.innerHTML = '';
                        });
                        box.appendChild(a);
                    });
                })
                .catch(function () { box.innerHTML = ''; });
        }, 300);
    });
    document.addEventListener('click', function (e) {
        if (!input.parentNode.contains(e.target)) { box.innerHTML = ''; }
    });
})();

function removeObject(objectId) {
    if (!confirm('Remove this object from the package?')) return;

    fetch('<?php echo url_for(['module' => 'preservation', 'action' => 'apiPackageRemoveObject']); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `package_id=${packageId}&object_id=${objectId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('obj-row-' + objectId).remove();
        } else {
            alert('Error: ' + data.error);
        }
    });
}

function buildPackage() {
    if (!confirm('Build the BagIt package? This will copy all files.')) return;

    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Building...';

    fetch('<?php echo url_for(['module' => 'preservation', 'action' => 'apiPackageBuild']); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `package_id=${packageId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Package built successfully!\nFiles: ' + data.files + '\nSize: ' + formatBytes(data.size));
            location.reload();
        } else {
            alert('Error: ' + data.error);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-hammer me-1"></i>Build Package';
        }
    });
}

function validatePackage() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Validating...';

    fetch('<?php echo url_for(['module' => 'preservation', 'action' => 'apiPackageValidate']); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `package_id=${packageId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.valid) {
            alert('Validation passed! ' + data.validated_files + ' files verified.');
            location.reload();
        } else {
            alert('Validation failed!\n\n' + data.errors.join('\n'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Validate Package';
        }
    });
}

function exportPackage() {
    const format = prompt('Export format (zip, tar, tar.gz):', 'zip');
    if (!format) return;

    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Exporting...';

    fetch('<?php echo url_for(['module' => 'preservation', 'action' => 'apiPackageExport']); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `package_id=${packageId}&format=${format}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Export completed!\nFormat: ' + data.format + '\nSize: ' + formatBytes(data.size));
            location.reload();
        } else {
            alert('Error: ' + data.error);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-box-arrow-up me-1"></i>Export Package';
        }
    });
}

function convertPackage(targetType) {
    const typeName = targetType === 'aip' ? 'AIP' : 'DIP';
    if (!confirm(`Convert this package to ${typeName}?`)) return;

    fetch('<?php echo url_for(['module' => 'preservation', 'action' => 'apiPackageConvert']); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `package_id=${packageId}&target_type=${targetType}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(`${typeName} created successfully!`);
            window.location.href = '<?php echo url_for(['module' => 'preservation', 'action' => 'packageEdit']); ?>?id=' + data.new_package_id;
        } else {
            alert('Error: ' + data.error);
        }
    });
}

function deletePackage() {
    if (!confirm('Are you sure you want to delete this package? This cannot be undone.')) return;

    fetch('<?php echo url_for(['module' => 'preservation', 'action' => 'apiPackageDelete']); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `package_id=${packageId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = '<?php echo url_for(['module' => 'preservation', 'action' => 'packages']); ?>';
        } else {
            alert('Error: ' + data.error);
        }
    });
}

// One-click build + export: packages (if needed) then exports, landing with a
// ready download. Also re-exports a package whose archive was cleaned off disk.
function buildExportPackage() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Building &amp; exporting...';

    fetch('<?php echo url_for(['module' => 'preservation', 'action' => 'apiPackageBuildExport']); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `package_id=${packageId}&format=zip`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Package built and exported.\nSize: ' + formatBytes(data.size) + '\nThe download is now available.');
            location.reload();
        } else {
            alert('Error: ' + data.error);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-lightning-charge me-1"></i>Build & Export';
        }
    })
    .catch(e => {
        alert('Error: ' + e);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-lightning-charge me-1"></i>Build & Export';
    });
}

// Collection (archival description) lookup for the Linked Collection field.
(function () {
    var input = document.getElementById('collectionSearch');
    var hidden = document.getElementById('collectionId');
    var results = document.getElementById('collectionResults');
    var clearBtn = document.getElementById('collectionClear');
    if (!input || !hidden || !results) return;
    var timer = null;

    function hideResults() { results.innerHTML = ''; }

    input.addEventListener('input', function () {
        // Typing a new query invalidates any previously chosen collection.
        hidden.value = '';
        var q = input.value.trim();
        clearTimeout(timer);
        if (q.length < 2) { hideResults(); return; }
        timer = setTimeout(function () {
            fetch('<?php echo url_for(['module' => 'preservation', 'action' => 'apiSearchDescriptions']); ?>?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    results.innerHTML = '';
                    (data.results || []).forEach(function (row) {
                        var a = document.createElement('a');
                        a.href = '#';
                        a.className = 'list-group-item list-group-item-action';
                        var meta = [row.reference, row.level].filter(Boolean).join(' - ');
                        a.innerHTML = '<span>' + (row.title || '(untitled)') + '</span>' +
                            (meta ? '<small class="text-muted d-block">' + meta + '</small>' : '');
                        a.addEventListener('click', function (e) {
                            e.preventDefault();
                            hidden.value = row.id;
                            input.value = row.title || ('#' + row.id);
                            hideResults();
                        });
                        results.appendChild(a);
                    });
                });
        }, 250);
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            hidden.value = '';
            input.value = '';
            hideResults();
            input.focus();
        });
    }

    document.addEventListener('click', function (e) {
        if (!results.contains(e.target) && e.target !== input) hideResults();
    });
})();

// Add-all-from-collection: autocomplete on descriptions, then bulk-add every
// master digital object under the chosen collection.
(function () {
    var input = document.getElementById('collImportSearch');
    var hidden = document.getElementById('collImportId');
    var results = document.getElementById('collImportResults');
    if (!input || !hidden || !results) return;
    var timer = null;

    function hide() { results.innerHTML = ''; }

    input.addEventListener('input', function () {
        hidden.value = '';
        var q = input.value.trim();
        clearTimeout(timer);
        if (q.length < 2) { hide(); return; }
        timer = setTimeout(function () {
            fetch('<?php echo url_for(['module' => 'preservation', 'action' => 'apiSearchDescriptions']); ?>?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    results.innerHTML = '';
                    (data.results || []).forEach(function (row) {
                        var a = document.createElement('a');
                        a.href = '#';
                        a.className = 'list-group-item list-group-item-action';
                        var meta = [row.reference, row.level].filter(Boolean).join(' - ');
                        a.innerHTML = '<span>' + (row.title || '(untitled)') + '</span>' +
                            (meta ? '<small class="text-muted d-block">' + meta + '</small>' : '');
                        a.addEventListener('click', function (e) {
                            e.preventDefault();
                            hidden.value = row.id;
                            input.value = row.title || ('#' + row.id);
                            hide();
                        });
                        results.appendChild(a);
                    });
                });
        }, 250);
    });

    document.addEventListener('click', function (e) {
        if (!results.contains(e.target) && e.target !== input) hide();
    });
})();

function addCollectionObjects() {
    var ioId = document.getElementById('collImportId').value;
    if (!ioId) {
        alert('Pick a collection/description from the search list first.');
        return;
    }
    var btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Adding...';

    fetch('<?php echo url_for(['module' => 'preservation', 'action' => 'apiPackageAddCollection']); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `package_id=${packageId}&information_object_id=${encodeURIComponent(ioId)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (data.added > 0) {
                alert('Added ' + data.added + ' object(s) from the collection' +
                      (data.skipped > 0 ? ' (' + data.skipped + ' already in the package)' : '') + '.');
                location.reload();
            } else {
                alert(data.error || 'No new objects to add - everything under that collection is already in the package.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-collection me-1"></i>Add all';
            }
        } else {
            alert('Error: ' + (data.error || 'unknown'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-collection me-1"></i>Add all';
        }
    })
    .catch(e => {
        alert('Error: ' + e);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-collection me-1"></i>Add all';
    });
}

function formatBytes(bytes) {
    if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return bytes + ' bytes';
}
</script>
<?php endif; ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
// Attach the CSRF token to this page's non-GET fetches. The framework rejects any
// mutating request without it, and these templates issue their calls through plain
// fetch() with no token - so every save, build and delete returned
// {"error":"CSRF token validation failed"}. Wrapping once is safer than editing each
// call site, and it leaves GETs untouched.
(function () {
    if (window.__ahgCsrfFetchWrapped) { return; }
    window.__ahgCsrfFetchWrapped = true;

    var token = '<?php echo htmlspecialchars(class_exists('\AtomFramework\Services\CsrfService') ? \AtomFramework\Services\CsrfService::generateToken() : '', ENT_QUOTES); ?>';
    if (!token) { return; }

    var original = window.fetch;
    window.fetch = function (input, init) {
        init = init || {};
        var method = (init.method || (typeof input === 'object' && input && input.method) || 'GET').toUpperCase();
        var url = (typeof input === 'string') ? input : (input && input.url) || '';
        var sameOrigin = !/^https?:\/\//i.test(url) || 0 === url.indexOf(window.location.origin);

        if ('GET' !== method && 'HEAD' !== method && sameOrigin) {
            var headers = new Headers(init.headers || (typeof input === 'object' && input ? input.headers : undefined) || {});
            if (!headers.has('X-CSRF-TOKEN')) { headers.set('X-CSRF-TOKEN', token); }
            init.headers = headers;
        }
        return original.call(this, input, init);
    };
})();
</script>
<?php end_slot() ?>

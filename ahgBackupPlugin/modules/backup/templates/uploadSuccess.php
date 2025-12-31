<?php
$maxUploadSize = $sf_data->getRaw('maxUploadSize') ?? 0;
$pendingUploads = $sf_data->getRaw('pendingUploads') ?? [];

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>

<?php echo get_component('default', 'updateCheck') ?>

<h1><?php echo __('Upload Backup') ?></h1>

<?php echo get_partial('default/breadcrumb', [
    'objects' => [
        ['title' => __('Admin'), 'url' => url_for(['module' => 'admin', 'action' => 'index'])],
        ['title' => __('Backup & Restore'), 'url' => url_for(['module' => 'backup', 'action' => 'index'])],
        ['title' => __('Upload')]
    ]
]) ?>

<div class="row">
    <div class="col-lg-8">
        <!-- Upload Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-cloud-upload-alt me-2"></i><?php echo __('Upload Backup File') ?></h5>
            </div>
            <div class="card-body">
                <form id="upload-form" enctype="multipart/form-data">
                    <!-- Upload Type Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold"><?php echo __('Upload Type') ?></label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check border rounded p-3 h-100">
                                    <input class="form-check-input" type="radio" name="upload_type" id="type-full" value="full" checked>
                                    <label class="form-check-label w-100" for="type-full">
                                        <strong><i class="fas fa-archive me-1 text-primary"></i><?php echo __('Full Backup') ?></strong>
                                        <p class="text-muted small mb-0 mt-1"><?php echo __('Upload a complete backup archive (.tar.gz) containing database, uploads, plugins, etc.') ?></p>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check border rounded p-3 h-100">
                                    <input class="form-check-input" type="radio" name="upload_type" id="type-db" value="db_only">
                                    <label class="form-check-label w-100" for="type-db">
                                        <strong><i class="fas fa-database me-1 text-success"></i><?php echo __('Database Only') ?></strong>
                                        <p class="text-muted small mb-0 mt-1"><?php echo __('Upload just a database dump file (.sql or .sql.gz)') ?></p>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Drop Zone -->
                    <div class="mb-4">
                        <label class="form-label fw-bold"><?php echo __('Backup File') ?></label>
                        <div id="drop-zone" class="border border-2 border-dashed rounded p-5 text-center bg-light">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <p class="mb-2"><?php echo __('Drag and drop your backup file here') ?></p>
                            <p class="text-muted small mb-3"><?php echo __('or') ?></p>
                            <label class="btn btn-outline-primary">
                                <i class="fas fa-folder-open me-1"></i><?php echo __('Browse Files') ?>
                                <input type="file" name="backup_file" id="backup-file" class="d-none" accept=".tar.gz,.gz,.sql,.sql.gz,.zip">
                            </label>
                            <p class="text-muted small mt-3 mb-0">
                                <?php echo __('Max file size:') ?> <strong><?php echo formatBytes($maxUploadSize) ?></strong>
                                <br><?php echo __('Allowed types:') ?> .tar.gz, .sql, .sql.gz
                            </p>
                        </div>
                        <div id="file-info" class="d-none mt-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-file-archive text-primary me-2"></i>
                                    <strong id="file-name"></strong>
                                    <span class="text-muted ms-2" id="file-size"></span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="btn-clear-file">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Progress -->
                    <div id="upload-progress" class="d-none mb-4">
                        <label class="form-label"><?php echo __('Upload Progress') ?></label>
                        <div class="progress" style="height: 25px;">
                            <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%">0%</div>
                        </div>
                        <small class="text-muted" id="upload-status"></small>
                    </div>

                    <!-- Submit -->
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo url_for(['module' => 'backup', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back') ?>
                        </a>
                        <button type="submit" class="btn btn-primary" id="btn-upload" disabled>
                            <i class="fas fa-upload me-1"></i><?php echo __('Upload Backup') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Pending Uploads -->
        <?php if (!empty($pendingUploads)): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-clock me-2"></i><?php echo __('Pending Uploads') ?></h5>
                <span class="badge bg-warning text-dark"><?php echo count($pendingUploads) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?php echo __('File') ?></th>
                                <th><?php echo __('Type') ?></th>
                                <th><?php echo __('Components') ?></th>
                                <th><?php echo __('Uploaded') ?></th>
                                <th class="text-end"><?php echo __('Actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingUploads as $upload): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-file-archive text-primary me-1"></i>
                                        <?php echo esc_entities($upload['filename'] ?? $upload['id']) ?>
                                        <br><small class="text-muted"><?php echo formatBytes($upload['size'] ?? 0) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo ($upload['type'] ?? '') === 'db_only' ? 'success' : 'primary' ?>">
                                            <?php echo ($upload['type'] ?? '') === 'db_only' ? __('DB Only') : __('Full') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php $components = $upload['components'] ?? []; ?>
                                        <?php if (!empty($components['database'])): ?><span class="badge bg-success me-1" title="Database"><i class="fas fa-database"></i></span><?php endif; ?>
                                        <?php if (!empty($components['uploads'])): ?><span class="badge bg-warning text-dark me-1" title="Uploads"><i class="fas fa-images"></i></span><?php endif; ?>
                                        <?php if (!empty($components['plugins'])): ?><span class="badge bg-info me-1" title="Plugins"><i class="fas fa-puzzle-piece"></i></span><?php endif; ?>
                                        <?php if (!empty($components['framework'])): ?><span class="badge bg-secondary me-1" title="Framework"><i class="fas fa-code"></i></span><?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo esc_entities($upload['uploaded_at'] ?? '') ?></small>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo url_for(['module' => 'backup', 'action' => 'restoreUpload', 'id' => $upload['id']]) ?>" class="btn btn-success" title="<?php echo __('Restore') ?>">
                                                <i class="fas fa-undo me-1"></i><?php echo __('Restore') ?>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger btn-delete-upload" data-id="<?php echo esc_entities($upload['id']) ?>" title="<?php echo __('Delete') ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <!-- Help Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Upload Help') ?></h5>
            </div>
            <div class="card-body">
                <h6><i class="fas fa-archive text-primary me-1"></i><?php echo __('Full Backup') ?></h6>
                <p class="small text-muted"><?php echo __('Use this for complete system migrations or disaster recovery. Upload a .tar.gz file created by this backup system.') ?></p>
                
                <h6><i class="fas fa-database text-success me-1"></i><?php echo __('Database Only') ?></h6>
                <p class="small text-muted mb-0"><?php echo __('Use this to restore just the database. Accepts .sql or .sql.gz files from mysqldump or this system.') ?></p>
                
                <hr>
                
                <h6><i class="fas fa-exclamation-triangle text-warning me-1"></i><?php echo __('Important') ?></h6>
                <ul class="small text-muted mb-0">
                    <li><?php echo __('Restoring will overwrite existing data') ?></li>
                    <li><?php echo __('Create a backup before restoring') ?></li>
                    <li><?php echo __('Large uploads may take several minutes') ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
#drop-zone {
    transition: all 0.3s ease;
    cursor: pointer;
}
#drop-zone.drag-over {
    border-color: var(--bs-primary) !important;
    background-color: rgba(13, 110, 253, 0.1) !important;
}
#drop-zone:hover {
    border-color: var(--bs-primary) !important;
}
</style>

<script <?php echo __(sfConfig::get('csp_nonce', '')); ?>>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('backup-file');
    const fileInfo = document.getElementById('file-info');
    const fileName = document.getElementById('file-name');
    const fileSize = document.getElementById('file-size');
    const btnUpload = document.getElementById('btn-upload');
    const btnClear = document.getElementById('btn-clear-file');
    const form = document.getElementById('upload-form');
    const progressDiv = document.getElementById('upload-progress');
    const progressBar = document.getElementById('progress-bar');
    const uploadStatus = document.getElementById('upload-status');
    const maxSize = <?php echo $maxUploadSize ?>;

    // Drag and drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(event => {
        dropZone.addEventListener(event, e => {
            e.preventDefault();
            e.stopPropagation();
        });
    });

    ['dragenter', 'dragover'].forEach(event => {
        dropZone.addEventListener(event, () => dropZone.classList.add('drag-over'));
    });

    ['dragleave', 'drop'].forEach(event => {
        dropZone.addEventListener(event, () => dropZone.classList.remove('drag-over'));
    });

    dropZone.addEventListener('drop', e => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    });

    dropZone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleFile(this.files[0]);
        }
    });

    btnClear.addEventListener('click', clearFile);

    function handleFile(file) {
        if (file.size > maxSize) {
            alert('<?php echo __('File too large. Maximum size is') ?> ' + formatBytes(maxSize));
            return;
        }

        const ext = file.name.split('.').pop().toLowerCase();
        const validExts = ['gz', 'sql', 'zip'];
        if (!validExts.includes(ext) && !file.name.endsWith('.tar.gz') && !file.name.endsWith('.sql.gz')) {
            alert('<?php echo __('Invalid file type') ?>');
            return;
        }

        // Update UI
        fileName.textContent = file.name;
        fileSize.textContent = formatBytes(file.size);
        fileInfo.classList.remove('d-none');
        dropZone.classList.add('d-none');
        btnUpload.disabled = false;

        // Auto-detect type
        if (file.name.endsWith('.sql') || file.name.endsWith('.sql.gz')) {
            document.getElementById('type-db').checked = true;
        } else {
            document.getElementById('type-full').checked = true;
        }
    }

    function clearFile() {
        fileInput.value = '';
        fileInfo.classList.add('d-none');
        dropZone.classList.remove('d-none');
        btnUpload.disabled = true;
    }

    function formatBytes(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let i = 0;
        while (bytes >= 1024 && i < units.length - 1) {
            bytes /= 1024;
            i++;
        }
        return bytes.toFixed(2) + ' ' + units[i];
    }

    // Form submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!fileInput.files.length) {
            alert('<?php echo __('Please select a file') ?>');
            return;
        }

        const formData = new FormData();
        formData.append('backup_file', fileInput.files[0]);
        formData.append('upload_type', document.querySelector('input[name="upload_type"]:checked').value);

        progressDiv.classList.remove('d-none');
        btnUpload.disabled = true;
        uploadStatus.textContent = '<?php echo __('Uploading...') ?>';

        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const pct = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = pct + '%';
                progressBar.textContent = pct + '%';
                uploadStatus.textContent = formatBytes(e.loaded) + ' / ' + formatBytes(e.total);
            }
        });

        xhr.addEventListener('load', function() {
            try {
                const response = JSON.parse(xhr.responseText);
                if (xhr.status === 200 && response.status === 'success') {
                    progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                    progressBar.classList.add('bg-success');
                    uploadStatus.innerHTML = '<span class="text-success"><i class="fas fa-check"></i> ' + response.message + '</span>';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                    progressBar.classList.add('bg-danger');
                    uploadStatus.innerHTML = '<span class="text-danger"><i class="fas fa-times"></i> ' + (response.error || 'Upload failed') + '</span>';
                    btnUpload.disabled = false;
                }
            } catch (e) {
                progressBar.classList.add('bg-danger');
                uploadStatus.innerHTML = '<span class="text-danger">Error parsing response</span>';
                btnUpload.disabled = false;
            }
        });

        xhr.addEventListener('error', function() {
            progressBar.classList.add('bg-danger');
            uploadStatus.innerHTML = '<span class="text-danger">Network error</span>';
            btnUpload.disabled = false;
        });

        xhr.open('POST', '<?php echo url_for(['module' => 'backup', 'action' => 'doUpload']) ?>');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(formData);
    });

    // Delete pending uploads
    document.querySelectorAll('.btn-delete-upload').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('<?php echo __('Delete this pending upload?') ?>')) return;
            
            const id = this.dataset.id;
            const row = this.closest('tr');
            
            fetch('<?php echo url_for(['module' => 'backup', 'action' => 'deleteUpload']) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'id=' + encodeURIComponent(id)
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    row.remove();
                } else {
                    alert(data.error || 'Delete failed');
                }
            });
        });
    });
});
</script>

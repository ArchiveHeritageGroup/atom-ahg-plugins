<?php
$title = 'Merge Images to PDF';
if ($informationObject) {
    $title .= ' - ' . ($informationObject->title ?? $informationObject->slug ?? '');
}
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-layer-group text-primary me-2"></i>
                        Merge Images to PDF
                    </h2>
                    <?php if ($informationObject): ?>
                    <p class="text-muted mb-0">
                        <i class="fas fa-link me-1"></i>
                        Attaching to: <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $informationObject->slug]); ?>"><?php echo $informationObject->title ?? $informationObject->slug; ?></a>
                    </p>
                    <?php endif; ?>
                </div>
                <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'browse']); ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-tasks me-1"></i>View Jobs
                </a>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-pdf me-2"></i>Create Multi-Page PDF</h5>
                </div>

                <div class="card-body">
                    <div id="tpmAlert" class="alert" style="display: none;"></div>

                    <input type="hidden" id="tpmInformationObjectId" value="<?php echo $informationObjectId ?? ''; ?>">
                    <input type="hidden" id="tpmJobId" value="">

                    <div class="mb-4 pb-4 border-bottom">
                        <h6 class="text-uppercase text-muted mb-3"><span class="badge bg-primary me-2">1</span>Output Settings</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="tpmJobName" class="form-label">Document Name</label>
                                <input type="text" class="form-control" id="tpmJobName" value="Merged Document <?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="tpmPdfStandard" class="form-label">PDF Standard</label>
                                <select id="tpmPdfStandard" class="form-select">
                                    <option value="pdfa-2b" selected>PDF/A-2b (Archival)</option>
                                    <option value="pdfa-1b">PDF/A-1b</option>
                                    <option value="pdfa-3b">PDF/A-3b</option>
                                    <option value="pdf">Standard PDF</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="tpmDpi" class="form-label">DPI</label>
                                <select id="tpmDpi" class="form-select">
                                    <option value="150">150</option>
                                    <option value="300" selected>300</option>
                                    <option value="400">400</option>
                                    <option value="600">600</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="tpmQuality" class="form-label">Quality</label>
                                <select id="tpmQuality" class="form-select">
                                    <option value="70">70% - Smaller</option>
                                    <option value="85" selected>85% - Balanced</option>
                                    <option value="95">95% - High</option>
                                    <option value="100">100% - Maximum</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 pb-4 border-bottom">
                        <h6 class="text-uppercase text-muted mb-3"><span class="badge bg-primary me-2">2</span>Upload Images</h6>
                        <div id="tpmDropZone" class="border border-2 border-dashed rounded p-5 text-center bg-light upload-zone">
                            <i class="fas fa-cloud-upload-alt fa-4x text-muted mb-3"></i>
                            <h5>Drag and drop images here</h5>
                            <p class="text-muted mb-3">or click to browse files</p>
                            <p class="text-muted small mb-0"><i class="fas fa-info-circle me-1"></i>Supported: TIFF, JPEG, PNG, BMP, GIF</p>
                            <input type="file" id="tpmFileInput" class="d-none" multiple accept=".tif,.tiff,.jpg,.jpeg,.png,.bmp,.gif">
                        </div>
                        <div id="tpmProgressContainer" class="mt-3" style="display: none;">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">Uploading...</small>
                                <small id="tpmProgressText" class="text-muted"></small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div id="tpmProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-uppercase text-muted mb-0">
                                <span class="badge bg-primary me-2">3</span>Review &amp; Reorder
                                <span id="tpmFileCount" class="badge bg-secondary ms-2">0</span>
                            </h6>
                            <small class="text-muted"><i class="fas fa-arrows-alt me-1"></i>Drag to reorder</small>
                        </div>
                        <div id="tpmFileList" class="border rounded bg-white" style="min-height: 100px; max-height: 400px; overflow-y: auto;">
                            <div class="text-muted text-center py-5"><i class="fas fa-images fa-2x mb-2 d-block"></i>No files uploaded yet</div>
                        </div>
                    </div>

                    <?php if ($informationObjectId): ?>
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="tpmAttachToRecord" checked>
                            <label class="form-check-label" for="tpmAttachToRecord">
                                <i class="fas fa-paperclip me-1"></i>Attach PDF to record as digital object
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <?php if ($informationObject): ?>
                            <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $informationObject->slug]); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Record
                            </a>
                            <?php else: ?>
                            <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back
                            </a>
                            <?php endif; ?>
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline-danger me-2" id="tpmClearBtn" style="display: none;">
                                <i class="fas fa-trash me-1"></i>Clear All
                            </button>
                            <button type="button" class="btn btn-primary btn-lg" id="tpmCreateBtn" disabled>
                                <i class="fas fa-file-pdf me-1"></i>Create PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($pendingJobs->count() > 0): ?>
            <div class="card mt-4">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-history me-2"></i>Your Recent Jobs</h6></div>
                <div class="list-group list-group-flush">
                    <?php foreach ($pendingJobs as $pJob): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo htmlspecialchars($pJob->job_name); ?></strong><br>
                            <small class="text-muted"><?php echo $pJob->file_count; ?> files • <?php echo $pJob->created_at; ?></small>
                        </div>
                        <span class="badge bg-<?php echo $pJob->status === 'pending' ? 'warning' : ($pJob->status === 'processing' ? 'info' : ($pJob->status === 'completed' ? 'success' : 'danger')); ?>">
                            <?php echo ucfirst($pJob->status); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.upload-zone { cursor: pointer; transition: all 0.3s ease; min-height: 200px; }
.upload-zone:hover, .upload-zone.drag-over { border-color: #0d6efd !important; background-color: #e8f4ff !important; }
.tpm-file-item { transition: background-color 0.2s; cursor: grab; }
.tpm-file-item:hover { background-color: #f8f9fa; }
.sortable-ghost { opacity: 0.4; background-color: #cfe2ff !important; }
</style>

<script src="/plugins/ahgCorePlugin/web/js/vendor/sortable.min.js"></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
    'use strict';
    let currentJob = null, uploadedFiles = [], sortable = null;

    document.addEventListener('DOMContentLoaded', function() {
        bindEvents();
        createJob();
    });

    function bindEvents() {
        document.getElementById('tpmFileInput')?.addEventListener('change', e => { processFiles(Array.from(e.target.files)); e.target.value = ''; });
        const dz = document.getElementById('tpmDropZone');
        dz?.addEventListener('click', () => document.getElementById('tpmFileInput')?.click());
        dz?.addEventListener('dragover', e => { e.preventDefault(); e.currentTarget.classList.add('drag-over'); });
        dz?.addEventListener('dragleave', e => { e.preventDefault(); e.currentTarget.classList.remove('drag-over'); });
        dz?.addEventListener('drop', e => { e.preventDefault(); e.currentTarget.classList.remove('drag-over'); processFiles(Array.from(e.dataTransfer.files)); });
        document.getElementById('tpmCreateBtn')?.addEventListener('click', createPdf);
        document.getElementById('tpmClearBtn')?.addEventListener('click', clearAll);
    }

    async function createJob() {
        try {
            const response = await fetch('<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'create']); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    job_name: document.getElementById('tpmJobName')?.value || 'Merged PDF',
                    information_object_id: document.getElementById('tpmInformationObjectId')?.value || '',
                    pdf_standard: document.getElementById('tpmPdfStandard')?.value || 'pdfa-2b',
                    dpi: document.getElementById('tpmDpi')?.value || '300',
                    compression_quality: document.getElementById('tpmQuality')?.value || '85',
                    attach_to_record: document.getElementById('tpmAttachToRecord')?.checked ? '1' : '0'
                })
            });
            const data = await response.json();
            if (data.success) { currentJob = data.job_id; document.getElementById('tpmJobId').value = currentJob; }
            else showAlert('Failed to initialize: ' + data.error, 'danger');
        } catch (e) { showAlert('Failed to initialize: ' + e.message, 'danger'); }
        initSortable();
    }

    function initSortable() {
        const fl = document.getElementById('tpmFileList');
        if (sortable) sortable.destroy();
        sortable = new Sortable(fl, { animation: 150, ghostClass: 'sortable-ghost', handle: '.drag-handle', onEnd: handleReorder });
    }

    async function processFiles(files) {
        if (!currentJob) { showAlert('Job not initialized.', 'danger'); return; }
        const validExts = ['tif', 'tiff', 'jpg', 'jpeg', 'png', 'bmp', 'gif'];
        const validFiles = files.filter(f => validExts.includes(f.name.split('.').pop().toLowerCase()));
        if (!validFiles.length) { showAlert('No valid images.', 'warning'); return; }

        showProgress(true);
        let uploaded = 0;
        for (const file of validFiles) {
            updateProgress(uploaded, validFiles.length);
            try {
                const fd = new FormData();
                fd.append('file', file);
                fd.append('job_id', currentJob);
                const res = await fetch('<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'upload']); ?>', { method: 'POST', body: fd });
                const result = await res.json();
                if (result.success && result.results) {
                    result.results.forEach(r => {
                        if (r.success) uploadedFiles.push({ id: r.file_id, name: r.filename, size: r.size || file.size, width: r.width, height: r.height });
                    });
                }
            } catch (e) { console.error(e); }
            uploaded++;
        }
        updateProgress(validFiles.length, validFiles.length);
        setTimeout(() => showProgress(false), 500);
        updateFileList();
        updateButtons();
    }

    function updateFileList() {
        const c = document.getElementById('tpmFileList');
        document.getElementById('tpmFileCount').textContent = uploadedFiles.length;
        if (!uploadedFiles.length) { c.innerHTML = '<div class="text-muted text-center py-5"><i class="fas fa-images fa-2x mb-2 d-block"></i>No files uploaded yet</div>'; return; }
        c.innerHTML = uploadedFiles.map((f, i) => `
            <div class="tpm-file-item d-flex align-items-center p-3 border-bottom" data-file-id="${f.id}">
                <div class="drag-handle me-3 text-muted"><i class="fas fa-grip-vertical fa-lg"></i></div>
                <span class="badge bg-primary rounded-pill fs-6 me-3">${i + 1}</span>
                <i class="fas fa-file-image fa-2x text-secondary me-3"></i>
                <div class="flex-grow-1">
                    <div class="fw-semibold">${escapeHtml(f.name)}</div>
                    <small class="text-muted">${formatSize(f.size)}${f.width ? ` • ${f.width}×${f.height}px` : ''}</small>
                </div>
                <button class="btn btn-sm btn-outline-danger" onclick="window.removeFile(${f.id})"><i class="fas fa-times"></i></button>
            </div>
        `).join('');
    }

    async function handleReorder(evt) {
        const m = uploadedFiles.splice(evt.oldIndex, 1)[0];
        uploadedFiles.splice(evt.newIndex, 0, m);
        try {
            await fetch('<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'reorder']); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ job_id: currentJob, 'file_order[]': uploadedFiles.map(f => f.id) })
            });
        } catch (e) {}
        updateFileList();
    }

    window.removeFile = async function(id) {
        if (!confirm('Remove this file?')) return;
        try {
            await fetch('<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'removeFile']); ?>', {
                method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ file_id: id })
            });
            uploadedFiles = uploadedFiles.filter(f => f.id !== id);
            updateFileList();
            updateButtons();
        } catch (e) { showAlert('Failed to remove file', 'danger'); }
    };

    async function createPdf() {
        if (!currentJob || !uploadedFiles.length) { showAlert('Upload files first.', 'warning'); return; }
        const btn = document.getElementById('tpmCreateBtn'), orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processing...';
        btn.disabled = true;
        try {
            const res = await fetch('<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'process']); ?>', {
                method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ job_id: currentJob })
            });
            const result = await res.json();
            if (result.success) {
                showAlert("<i class=\"fas fa-check-circle me-2\"></i><strong>Job queued!</strong> Redirecting...", "success"); setTimeout(() => window.location.href = "/index.php/tiff-pdf-merge/jobs", 2000);
                uploadedFiles = [];
                updateFileList();
                updateButtons();
            } else {
                showAlert(result.error, 'danger');
                btn.innerHTML = orig;
                btn.disabled = false;
            }
        } catch (e) {
            showAlert(e.message, 'danger');
            btn.innerHTML = orig;
            btn.disabled = false;
        }
    }

    async function clearAll() {
        if (!confirm('Clear all files?')) return;
        if (currentJob) {
            try { await fetch('<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'delete']); ?>', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ job_id: currentJob }) }); } catch (e) {}
        }
        uploadedFiles = [];
        updateFileList();
        updateButtons();
        hideAlert();
        createJob();
    }

    function updateButtons() {
        document.getElementById('tpmCreateBtn').disabled = !uploadedFiles.length;
        document.getElementById('tpmClearBtn').style.display = uploadedFiles.length ? 'inline-block' : 'none';
    }

    function showProgress(s) { document.getElementById('tpmProgressContainer').style.display = s ? 'block' : 'none'; }
    function updateProgress(c, t) { document.getElementById('tpmProgressBar').style.width = (t ? Math.round(c/t*100) : 0) + '%'; document.getElementById('tpmProgressText').textContent = c + ' of ' + t; }
    function showAlert(m, t) { const a = document.getElementById('tpmAlert'); a.className = 'alert alert-' + t; a.innerHTML = m; a.style.display = 'block'; }
    function hideAlert() { document.getElementById('tpmAlert').style.display = 'none'; }
    function formatSize(b) { if (!b) return ''; const k = 1024, s = ['B','KB','MB','GB'], i = Math.floor(Math.log(b)/Math.log(k)); return (b/Math.pow(k,i)).toFixed(1) + ' ' + s[i]; }
    function escapeHtml(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
})();
</script>

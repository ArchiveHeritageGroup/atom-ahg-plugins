<?php decorate_with('layout_2col.php') ?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card mb-3">
        <div class="card-header bg-info text-white py-2">
            <h6 class="mb-0"><i class="fas fa-brain me-1"></i><?php echo __('Model Training') ?></h6>
        </div>
        <div class="card-body py-2 small">
            <p class="text-muted mb-2"><?php echo __('Upload labeled training data and train the damage detection model to improve accuracy for your collections.') ?></p>
            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'index']) ?>" class="btn btn-sm btn-outline-secondary w-100 mb-2">
                <i class="fas fa-cog me-1"></i><?php echo __('Back to Settings') ?>
            </a>
            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'browse']) ?>" class="btn btn-sm btn-outline-primary w-100">
                <i class="fas fa-list me-1"></i><?php echo __('Browse Assessments') ?>
            </a>
        </div>
    </div>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<h1 class="h3 mb-0"><i class="fas fa-brain me-2"></i><?php echo __('Model Training') ?></h1>
<p class="text-muted small mb-3"><?php echo __('Upload labeled images and train the damage detection model') ?></p>
<?php end_slot() ?>

<?php slot('content') ?>

<!-- Row 1: Model Info + Training Status -->
<div class="row mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-cube me-1"></i><?php echo __('Model Info') ?></h6></div>
            <div class="card-body" id="modelInfoBody">
                <div class="text-center text-muted small py-3">
                    <i class="fas fa-spinner fa-spin me-1"></i><?php echo __('Loading model info...') ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-tasks me-1"></i><?php echo __('Training Status') ?></h6></div>
            <div class="card-body" id="trainingStatusBody">
                <div class="text-center text-muted small py-3">
                    <i class="fas fa-spinner fa-spin me-1"></i><?php echo __('Loading status...') ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Upload Training Data -->
<div class="card mb-3">
    <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-upload me-1"></i><?php echo __('Upload Training Data') ?></h6></div>
    <div class="card-body">
        <div class="alert alert-info small py-2 mb-3">
            <i class="fas fa-info-circle me-1"></i>
            <?php echo __('Expected format: ZIP file containing an <code>images/</code> directory (JPG/PNG files) and an <code>annotations/</code> directory (JSON files with damage type and bounding box coordinates).') ?>
        </div>
        <div id="dropZone" class="border border-2 border-dashed rounded p-4 text-center mb-3" style="cursor:pointer;border-color:#adb5bd !important">
            <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2 d-block"></i>
            <p class="text-muted mb-1"><?php echo __('Drag and drop a ZIP file here, or click to browse') ?></p>
            <input type="file" id="trainingFile" accept=".zip" style="display:none">
        </div>
        <div id="uploadProgress" style="display:none">
            <div class="progress mb-2" style="height:6px">
                <div class="progress-bar progress-bar-striped progress-bar-animated" id="uploadProgressBar" style="width:0%"></div>
            </div>
            <p class="text-center small text-muted" id="uploadProgressText"><?php echo __('Uploading...') ?></p>
        </div>
        <div id="uploadResult" style="display:none"></div>
    </div>
</div>

<!-- Row 3: Available Datasets -->
<div class="card mb-3">
    <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-database me-1"></i><?php echo __('Available Datasets') ?></h6></div>
    <div class="card-body p-0" id="datasetsBody">
        <div class="p-3 text-center text-muted small">
            <i class="fas fa-spinner fa-spin me-1"></i><?php echo __('Loading datasets...') ?>
        </div>
    </div>
</div>

<!-- Row 4: Training Configuration -->
<div class="card mb-3">
    <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-sliders-h me-1"></i><?php echo __('Training Configuration') ?></h6></div>
    <div class="card-body">
        <div class="row mb-3">
            <label class="col-sm-3 col-form-label col-form-label-sm"><?php echo __('Epochs') ?></label>
            <div class="col-sm-9">
                <input type="number" class="form-control form-control-sm" id="trainEpochs" value="100" min="1" max="1000">
                <div class="form-text"><?php echo __('Number of training iterations (higher = longer but potentially more accurate)') ?></div>
            </div>
        </div>
        <div class="row mb-3">
            <label class="col-sm-3 col-form-label col-form-label-sm"><?php echo __('Batch Size') ?></label>
            <div class="col-sm-9">
                <input type="number" class="form-control form-control-sm" id="trainBatchSize" value="16" min="1" max="128">
                <div class="form-text"><?php echo __('Images per training batch (reduce if running out of GPU memory)') ?></div>
            </div>
        </div>
        <div class="row mb-3">
            <label class="col-sm-3 col-form-label col-form-label-sm"><?php echo __('Image Size') ?></label>
            <div class="col-sm-9">
                <select class="form-select form-select-sm" id="trainImageSize">
                    <option value="320">320px</option>
                    <option value="416">416px</option>
                    <option value="512">512px</option>
                    <option value="640" selected>640px (default)</option>
                    <option value="800">800px</option>
                    <option value="1024">1024px</option>
                </select>
                <div class="form-text"><?php echo __('Input image resolution for training (larger = more detail but slower)') ?></div>
            </div>
        </div>
        <button type="button" class="btn btn-primary" id="startTrainingBtn" disabled>
            <i class="fas fa-play me-1"></i><?php echo __('Start Training') ?>
        </button>
        <span class="small text-muted ms-2" id="trainHint"><?php echo __('Select a dataset from the table above to enable training.') ?></span>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
var selectedDatasetId = null;

// --- Load Model Info ---
function loadModelInfo() {
    fetch('<?php echo url_for(['module' => 'aiCondition', 'action' => 'apiTrainingModelInfo']) ?>')
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var el = document.getElementById('modelInfoBody');
        if (!data.success) {
            el.innerHTML = '<div class="alert alert-warning py-1 small mb-0"><i class="fas fa-exclamation-triangle me-1"></i>' + esc(data.error || 'Could not load model info') + '</div>';
            return;
        }
        var d = data.data || {};
        var statusBadge = d.loaded
            ? '<span class="badge bg-success"><?php echo __('Loaded') ?></span>'
            : '<span class="badge bg-warning text-dark"><?php echo __('Not Found') ?></span>';

        el.innerHTML = '<table class="table table-sm table-borderless mb-0 small">'
            + '<tr><td class="text-muted"><?php echo __('Status') ?></td><td>' + statusBadge + '</td></tr>'
            + '<tr><td class="text-muted"><?php echo __('File Size') ?></td><td>' + esc(d.file_size || '--') + '</td></tr>'
            + '<tr><td class="text-muted"><?php echo __('Last Modified') ?></td><td>' + esc(d.last_modified || '--') + '</td></tr>'
            + '<tr><td class="text-muted"><?php echo __('Damage Classes') ?></td><td><span class="badge bg-secondary">15</span></td></tr>'
            + '</table>';
    })
    .catch(function() {
        document.getElementById('modelInfoBody').innerHTML = '<div class="alert alert-danger py-1 small mb-0"><i class="fas fa-times me-1"></i><?php echo __('Network error') ?></div>';
    });
}

// --- Load Training Status ---
function loadTrainingStatus() {
    fetch('<?php echo url_for(['module' => 'aiCondition', 'action' => 'apiTrainingStatus']) ?>')
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var el = document.getElementById('trainingStatusBody');
        if (!data.success) {
            el.innerHTML = '<div class="alert alert-warning py-1 small mb-0"><i class="fas fa-exclamation-triangle me-1"></i>' + esc(data.error || 'Could not load status') + '</div>';
            return;
        }
        var d = data.data || {};
        var statusColors = {idle:'secondary',preparing:'info',training:'primary',completed:'success',failed:'danger'};
        var status = d.status || 'idle';
        var html = '<table class="table table-sm table-borderless mb-0 small">';
        html += '<tr><td class="text-muted"><?php echo __('Status') ?></td><td><span class="badge bg-' + (statusColors[status] || 'secondary') + '">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span></td></tr>';

        if (status === 'training' && d.current_epoch != null && d.total_epochs) {
            var pct = Math.round((d.current_epoch / d.total_epochs) * 100);
            html += '<tr><td class="text-muted"><?php echo __('Progress') ?></td><td>'
                + '<div class="progress" style="height:6px"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:' + pct + '%"></div></div>'
                + '<span class="small">' + d.current_epoch + ' / ' + d.total_epochs + ' <?php echo __('epochs') ?></span>'
                + '</td></tr>';
        }

        if (status === 'completed' && d.metrics) {
            var m = d.metrics;
            html += '<tr><td class="text-muted">mAP</td><td><strong>' + (m.mAP != null ? parseFloat(m.mAP).toFixed(3) : '--') + '</strong></td></tr>';
            html += '<tr><td class="text-muted"><?php echo __('Precision') ?></td><td>' + (m.precision != null ? parseFloat(m.precision).toFixed(3) : '--') + '</td></tr>';
            html += '<tr><td class="text-muted"><?php echo __('Recall') ?></td><td>' + (m.recall != null ? parseFloat(m.recall).toFixed(3) : '--') + '</td></tr>';
        }

        if (d.started_at) {
            html += '<tr><td class="text-muted"><?php echo __('Started') ?></td><td>' + esc(d.started_at) + '</td></tr>';
        }
        if (d.completed_at) {
            html += '<tr><td class="text-muted"><?php echo __('Completed') ?></td><td>' + esc(d.completed_at) + '</td></tr>';
        }

        html += '</table>';
        el.innerHTML = html;

        // Auto-refresh while training
        if (status === 'training' || status === 'preparing') {
            setTimeout(loadTrainingStatus, 5000);
        }
    })
    .catch(function() {
        document.getElementById('trainingStatusBody').innerHTML = '<div class="alert alert-danger py-1 small mb-0"><i class="fas fa-times me-1"></i><?php echo __('Network error') ?></div>';
    });
}

// --- Drag-and-drop upload ---
var dropZone = document.getElementById('dropZone');
var trainingFile = document.getElementById('trainingFile');

dropZone.addEventListener('click', function() {
    trainingFile.click();
});

dropZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('border-primary');
});

dropZone.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.classList.remove('border-primary');
});

dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('border-primary');
    if (e.dataTransfer.files.length) {
        trainingFile.files = e.dataTransfer.files;
        uploadTrainingData(e.dataTransfer.files[0]);
    }
});

trainingFile.addEventListener('change', function() {
    if (this.files[0]) {
        uploadTrainingData(this.files[0]);
    }
});

function uploadTrainingData(file) {
    if (!file.name.toLowerCase().endsWith('.zip')) {
        alert('<?php echo __('Please upload a ZIP file.') ?>');
        return;
    }

    var formData = new FormData();
    formData.append('training_file', file);

    document.getElementById('uploadProgress').style.display = '';
    document.getElementById('uploadResult').style.display = 'none';
    document.getElementById('uploadProgressBar').style.width = '0%';
    document.getElementById('uploadProgressText').textContent = '<?php echo __('Uploading...') ?>';

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '<?php echo url_for(['module' => 'aiCondition', 'action' => 'apiTrainingUpload']) ?>');

    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            var pct = Math.round((e.loaded / e.total) * 100);
            document.getElementById('uploadProgressBar').style.width = pct + '%';
            document.getElementById('uploadProgressText').textContent = pct + '% <?php echo __('uploaded') ?>';
        }
    });

    xhr.addEventListener('load', function() {
        document.getElementById('uploadProgressBar').style.width = '100%';
        var resultEl = document.getElementById('uploadResult');
        resultEl.style.display = '';

        try {
            var data = JSON.parse(xhr.responseText);
            if (data.success) {
                var ds = data.data || {};
                resultEl.innerHTML = '<div class="alert alert-success py-2 small">'
                    + '<i class="fas fa-check me-1"></i><?php echo __('Dataset uploaded successfully.') ?>'
                    + (ds.images != null ? ' <?php echo __('Images') ?>: <strong>' + ds.images + '</strong>,' : '')
                    + (ds.annotations != null ? ' <?php echo __('Annotations') ?>: <strong>' + ds.annotations + '</strong>' : '')
                    + '</div>';
                document.getElementById('uploadProgress').style.display = 'none';
                loadDatasets();
            } else {
                resultEl.innerHTML = '<div class="alert alert-danger py-2 small"><i class="fas fa-times me-1"></i>' + esc(data.error || 'Upload failed') + '</div>';
            }
        } catch (e) {
            resultEl.innerHTML = '<div class="alert alert-danger py-2 small"><i class="fas fa-times me-1"></i><?php echo __('Invalid response from server') ?></div>';
        }
    });

    xhr.addEventListener('error', function() {
        document.getElementById('uploadProgressText').textContent = '<?php echo __('Upload failed') ?>';
        var resultEl = document.getElementById('uploadResult');
        resultEl.style.display = '';
        resultEl.innerHTML = '<div class="alert alert-danger py-2 small"><i class="fas fa-times me-1"></i><?php echo __('Network error during upload') ?></div>';
    });

    xhr.send(formData);
}

// --- Load Datasets ---
function loadDatasets() {
    fetch('<?php echo url_for(['module' => 'aiCondition', 'action' => 'apiTrainingDatasets']) ?>')
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var el = document.getElementById('datasetsBody');
        if (!data.success || !data.data || !data.data.length) {
            el.innerHTML = '<div class="p-3 text-center text-muted small"><i class="fas fa-info-circle me-1"></i><?php echo __('No training datasets available. Upload a ZIP file above.') ?></div>';
            return;
        }

        var html = '<div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">';
        html += '<thead class="table-light"><tr>'
            + '<th><?php echo __('Dataset ID') ?></th>'
            + '<th class="text-center"><?php echo __('Images') ?></th>'
            + '<th class="text-center"><?php echo __('Annotations') ?></th>'
            + '<th><?php echo __('Created') ?></th>'
            + '<th class="text-end"><?php echo __('Actions') ?></th>'
            + '</tr></thead><tbody>';

        data.data.forEach(function(ds) {
            var isSelected = selectedDatasetId === ds.id;
            html += '<tr' + (isSelected ? ' class="table-primary"' : '') + '>'
                + '<td><code class="small">' + esc(String(ds.id)) + '</code></td>'
                + '<td class="text-center"><span class="badge bg-secondary">' + (ds.images || 0) + '</span></td>'
                + '<td class="text-center"><span class="badge bg-secondary">' + (ds.annotations || 0) + '</span></td>'
                + '<td class="small">' + esc(ds.created_at || '--') + '</td>'
                + '<td class="text-end">'
                + '<button type="button" class="btn btn-sm ' + (isSelected ? 'btn-primary' : 'btn-outline-primary') + ' me-1" onclick="selectDataset(' + ds.id + ')">'
                + '<i class="fas fa-check me-1"></i>' + (isSelected ? '<?php echo __('Selected') ?>' : '<?php echo __('Use for Training') ?>')
                + '</button>'
                + '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteDataset(' + ds.id + ')">'
                + '<i class="fas fa-trash"></i>'
                + '</button>'
                + '</td></tr>';
        });

        html += '</tbody></table></div>';
        el.innerHTML = html;
    })
    .catch(function() {
        document.getElementById('datasetsBody').innerHTML = '<div class="p-3 text-center text-danger small"><i class="fas fa-times me-1"></i><?php echo __('Failed to load datasets') ?></div>';
    });
}

function selectDataset(id) {
    selectedDatasetId = id;
    document.getElementById('startTrainingBtn').disabled = false;
    document.getElementById('trainHint').textContent = '<?php echo __('Dataset') ?> #' + id + ' <?php echo __('selected.') ?>';
    loadDatasets();
}

function deleteDataset(id) {
    if (!confirm('<?php echo __('Delete this dataset? This cannot be undone.') ?>')) return;
    fetch('<?php echo url_for(['module' => 'aiCondition', 'action' => 'apiTrainingDatasets']) ?>?dataset_id=' + id, {method: 'DELETE'})
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            if (selectedDatasetId === id) {
                selectedDatasetId = null;
                document.getElementById('startTrainingBtn').disabled = true;
                document.getElementById('trainHint').textContent = '<?php echo __('Select a dataset from the table above to enable training.') ?>';
            }
            loadDatasets();
        } else {
            alert(data.error || '<?php echo __('Failed to delete dataset') ?>');
        }
    });
}

// --- Start Training ---
document.getElementById('startTrainingBtn').addEventListener('click', function() {
    if (!selectedDatasetId) {
        alert('<?php echo __('Please select a dataset first.') ?>');
        return;
    }

    var params = 'dataset_id=' + selectedDatasetId
        + '&epochs=' + document.getElementById('trainEpochs').value
        + '&batch_size=' + document.getElementById('trainBatchSize').value
        + '&image_size=' + document.getElementById('trainImageSize').value;

    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i><?php echo __('Starting...') ?>';

    var btn = this;
    fetch('<?php echo url_for(['module' => 'aiCondition', 'action' => 'apiTrainingStart']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: params
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-play me-1"></i><?php echo __('Start Training') ?>';

        if (data.success) {
            loadTrainingStatus();
            loadModelInfo();
        } else {
            alert(data.error || '<?php echo __('Failed to start training') ?>');
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-play me-1"></i><?php echo __('Start Training') ?>';
        alert('<?php echo __('Network error') ?>');
    });
});

// --- Escape helper ---
function esc(str) {
    var d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// --- Initial load ---
loadModelInfo();
loadTrainingStatus();
loadDatasets();
</script>
<?php end_slot() ?>

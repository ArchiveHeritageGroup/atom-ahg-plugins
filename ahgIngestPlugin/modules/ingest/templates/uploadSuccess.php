<?php
$session = $sf_data->getRaw('session');
$files = $sf_data->getRaw('files') ?? [];
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
                        <label for="directory_path" class="form-label"><?php echo __('Or enter a server directory path') ?></label>
                        <input type="text" class="form-control" id="directory_path" name="directory_path"
                               placeholder="<?php echo __('/path/to/files/on/server') ?>">
                        <small class="text-muted"><?php echo __('For large batches, point to a directory on the server instead of uploading') ?></small>
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
                            <i class="fas fa-file-<?php echo $f->file_type === 'csv' ? 'csv' : ($f->file_type === 'zip' ? 'archive' : 'code') ?> me-1"></i>
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

    dropZone.addEventListener('click', function() {
        fileInput.click();
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            showFileInfo(this.files[0]);
        }
    });

    function showFileInfo(file) {
        var size = (file.size / 1024 / 1024).toFixed(2);
        fileInfo.style.display = '';
        fileInfo.innerHTML = '<strong>' + file.name + '</strong> (' + size + ' MB)';
    }
});
</script>

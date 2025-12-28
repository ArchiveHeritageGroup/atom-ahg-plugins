<?php
$upload = $sf_data->getRaw('upload') ?? [];
$uploadId = $sf_data->getRaw('uploadId');
$components = $upload['components'] ?? [];
?>

<?php echo get_component('default', 'updateCheck') ?>

<h1><?php echo __('Restore from Upload') ?></h1>

<?php echo get_partial('default/breadcrumb', [
    'objects' => [
        ['title' => __('Admin'), 'url' => url_for(['module' => 'admin', 'action' => 'index'])],
        ['title' => __('Backup & Restore'), 'url' => url_for(['module' => 'backup', 'action' => 'index'])],
        ['title' => __('Upload'), 'url' => url_for(['module' => 'backup', 'action' => 'upload'])],
        ['title' => __('Restore')]
    ]
]) ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i><?php echo __('Restore Uploaded Backup') ?></h5>
            </div>
            <div class="card-body">
                <!-- Upload Info -->
                <div class="alert alert-info">
                    <div class="row">
                        <div class="col-sm-6">
                            <strong><?php echo __('File') ?>:</strong> <?php echo esc_entities($upload['filename'] ?? $uploadId) ?>
                        </div>
                        <div class="col-sm-6">
                            <strong><?php echo __('Uploaded') ?>:</strong> <?php echo esc_entities($upload['uploaded_at'] ?? 'Unknown') ?>
                        </div>
                    </div>
                </div>

                <!-- Warning -->
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong><?php echo __('Warning:') ?></strong> <?php echo __('Restoring will overwrite existing data. This action cannot be undone. Make sure you have a current backup before proceeding.') ?>
                </div>

                <!-- Component Selection -->
                <h6 class="mb-3"><?php echo __('Select components to restore:') ?></h6>
                
                <form id="restore-form">
                    <input type="hidden" name="id" value="<?php echo esc_entities($uploadId) ?>">
                    
                    <?php if (!empty($components['database'])): ?>
                    <div class="form-check mb-3 p-3 border rounded">
                        <input class="form-check-input" type="checkbox" id="restore-database" name="restore_database" value="1" checked>
                        <label class="form-check-label" for="restore-database">
                            <i class="fas fa-database text-success me-2"></i>
                            <strong><?php echo __('Database') ?></strong>
                            <p class="text-muted small mb-0 ms-4"><?php echo __('Restore all database tables and data') ?></p>
                        </label>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($components['uploads'])): ?>
                    <div class="form-check mb-3 p-3 border rounded">
                        <input class="form-check-input" type="checkbox" id="restore-uploads" name="restore_uploads" value="1">
                        <label class="form-check-label" for="restore-uploads">
                            <i class="fas fa-images text-warning me-2"></i>
                            <strong><?php echo __('Uploads') ?></strong>
                            <p class="text-muted small mb-0 ms-4"><?php echo __('Restore digital objects and uploaded files') ?></p>
                        </label>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($components['plugins'])): ?>
                    <div class="form-check mb-3 p-3 border rounded">
                        <input class="form-check-input" type="checkbox" id="restore-plugins" name="restore_plugins" value="1">
                        <label class="form-check-label" for="restore-plugins">
                            <i class="fas fa-puzzle-piece text-info me-2"></i>
                            <strong><?php echo __('Custom Plugins') ?></strong>
                            <p class="text-muted small mb-0 ms-4"><?php echo __('Restore custom plugin files') ?></p>
                        </label>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($components['framework'])): ?>
                    <div class="form-check mb-3 p-3 border rounded">
                        <input class="form-check-input" type="checkbox" id="restore-framework" name="restore_framework" value="1">
                        <label class="form-check-label" for="restore-framework">
                            <i class="fas fa-code text-secondary me-2"></i>
                            <strong><?php echo __('AHG Framework') ?></strong>
                            <p class="text-muted small mb-0 ms-4"><?php echo __('Restore atom-framework files') ?></p>
                        </label>
                    </div>
                    <?php endif; ?>

                    <!-- Progress -->
                    <div id="restore-progress" class="d-none mb-4">
                        <div class="progress" style="height: 25px;">
                            <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" style="width: 100%">
                                <?php echo __('Restoring...') ?>
                            </div>
                        </div>
                        <small class="text-muted" id="restore-status"><?php echo __('Please wait, this may take several minutes...') ?></small>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?php echo url_for(['module' => 'backup', 'action' => 'upload']) ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i><?php echo __('Cancel') ?>
                        </a>
                        <button type="submit" class="btn btn-danger" id="btn-restore">
                            <i class="fas fa-undo me-1"></i><?php echo __('Start Restore') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('restore-form');
    const btnRestore = document.getElementById('btn-restore');
    const progressDiv = document.getElementById('restore-progress');
    const progressBar = document.getElementById('progress-bar');
    const restoreStatus = document.getElementById('restore-status');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!confirm('<?php echo __('Are you sure you want to restore? This will overwrite existing data!') ?>')) {
            return;
        }

        const formData = new FormData(form);
        
        progressDiv.classList.remove('d-none');
        btnRestore.disabled = true;
        document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.disabled = true);

        fetch('<?php echo url_for(['module' => 'backup', 'action' => 'doRestoreUpload']) ?>', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: new URLSearchParams(formData)
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped', 'bg-warning');
                progressBar.classList.add('bg-success');
                progressBar.textContent = '<?php echo __('Complete!') ?>';
                restoreStatus.innerHTML = '<span class="text-success"><i class="fas fa-check"></i> ' + data.message + '</span>';
                
                setTimeout(() => {
                    window.location.href = '<?php echo url_for(['module' => 'backup', 'action' => 'index']) ?>';
                }, 2000);
            } else {
                progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped', 'bg-warning');
                progressBar.classList.add('bg-danger');
                progressBar.textContent = '<?php echo __('Failed') ?>';
                restoreStatus.innerHTML = '<span class="text-danger"><i class="fas fa-times"></i> ' + (data.error || 'Restore failed') + '</span>';
                btnRestore.disabled = false;
                document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.disabled = false);
            }
        })
        .catch(e => {
            progressBar.classList.add('bg-danger');
            restoreStatus.innerHTML = '<span class="text-danger">Error: ' + e.message + '</span>';
            btnRestore.disabled = false;
        });
    });
});
</script>

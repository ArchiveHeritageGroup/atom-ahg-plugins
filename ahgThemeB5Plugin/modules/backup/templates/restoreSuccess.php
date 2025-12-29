<?php
/**
 * Backup restore template - AtoM 2.10 style with Bootstrap 5
 * 
 * Location: /usr/share/nginx/archive/plugins/ahgThemeB5Plugin/modules/backup/templates/restoreSuccess.php
 */

$backupId = $sf_data->getRaw('backupId');
$backup = $sf_data->getRaw('backup');
?>

<?php echo get_component('default', 'updateCheck') ?>

<h1><?php echo __('Restore Backup') ?></h1>

<?php echo get_partial('default/breadcrumb', [
    'objects' => [
        ['title' => __('Admin'), 'url' => url_for(['module' => 'admin', 'action' => 'index'])],
        ['title' => __('Backup'), 'url' => url_for(['module' => 'backup', 'action' => 'index'])],
        ['title' => __('Restore')]
    ]
]) ?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-history me-2"></i>
            <?php echo __('Backup Details') ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th><?php echo __('Backup ID') ?></th>
                        <td><code><?php echo esc_entities($backupId) ?></code></td>
                    </tr>
                    <tr>
                        <th><?php echo __('Created') ?></th>
                        <td><?php echo esc_entities($backup['started_at'] ?? $backup['created_at'] ?? 'Unknown') ?></td>
                    </tr>
                    <tr>
                        <th><?php echo __('Status') ?></th>
                        <td>
                            <?php if (($backup['status'] ?? '') === 'completed'): ?>
                                <span class="badge bg-success"><?php echo __('Completed') ?></span>
                            <?php else: ?>
                                <span class="badge bg-warning"><?php echo esc_entities($backup['status'] ?? 'Unknown') ?></span>
                            <?php endif ?>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6><?php echo __('Components') ?></h6>
                <ul class="list-unstyled">
                    <?php
                    $components = $backup['components'] ?? [];
                    $componentLabels = [
                        'database' => __('Database'),
                        'uploads' => __('Uploads'),
                        'plugins' => __('Plugins'),
                        'framework' => __('Framework'),
                    ];
                    foreach ($componentLabels as $key => $label):
                        $included = isset($components[$key]) && $components[$key];
                    ?>
                    <li>
                        <?php if ($included): ?>
                            <i class="fas fa-check-circle text-success me-2"></i>
                        <?php else: ?>
                            <i class="fas fa-times-circle text-muted me-2"></i>
                        <?php endif ?>
                        <?php echo $label ?>
                        <?php if ($included && isset($components[$key]['size'])): ?>
                            <small class="text-muted">(<?php echo format_filesize($components[$key]['size']) ?>)</small>
                        <?php endif ?>
                    </li>
                    <?php endforeach ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong><?php echo __('Warning') ?>:</strong>
    <?php echo __('Restoring a backup will overwrite current data. This action cannot be undone. Make sure you have a current backup before proceeding.') ?>
</div>

<form id="restore-form" class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-undo me-2"></i>
            <?php echo __('Restore Options') ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="restore_database" name="restore_database" 
                           <?php echo isset($components['database']) && $components['database'] ? 'checked' : 'disabled' ?>>
                    <label class="form-check-label" for="restore_database">
                        <i class="fas fa-database me-1"></i>
                        <?php echo __('Restore Database') ?>
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="restore_uploads" name="restore_uploads"
                           <?php echo isset($components['uploads']) && $components['uploads'] ? 'checked' : 'disabled' ?>>
                    <label class="form-check-label" for="restore_uploads">
                        <i class="fas fa-folder me-1"></i>
                        <?php echo __('Restore Uploads') ?>
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="restore_plugins" name="restore_plugins"
                           <?php echo isset($components['plugins']) && $components['plugins'] ? '' : 'disabled' ?>>
                    <label class="form-check-label" for="restore_plugins">
                        <i class="fas fa-puzzle-piece me-1"></i>
                        <?php echo __('Restore Plugins') ?>
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="restore_framework" name="restore_framework"
                           <?php echo isset($components['framework']) && $components['framework'] ? '' : 'disabled' ?>>
                    <label class="form-check-label" for="restore_framework">
                        <i class="fas fa-cogs me-1"></i>
                        <?php echo __('Restore Framework') ?>
                    </label>
                </div>
            </div>
        </div>
        
        <hr>
        
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="confirm_restore" name="confirm_restore" required>
            <label class="form-check-label" for="confirm_restore">
                <strong><?php echo __('I understand that this will overwrite current data and cannot be undone') ?></strong>
            </label>
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <a href="<?php echo url_for(['module' => 'backup', 'action' => 'index']) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                <?php echo __('Cancel') ?>
            </a>
            <button type="submit" id="btn-restore" class="btn btn-danger" disabled>
                <i class="fas fa-undo me-1"></i>
                <?php echo __('Restore Backup') ?>
            </button>
        </div>
    </div>
</form>

<div id="restore-progress" class="card mt-4" style="display: none;">
    <div class="card-body text-center py-5">
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden"><?php echo __('Restoring...') ?></span>
        </div>
        <h5><?php echo __('Restoring backup...') ?></h5>
        <p class="text-muted"><?php echo __('Please do not close this page') ?></p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('restore-form');
    const confirmCheckbox = document.getElementById('confirm_restore');
    const restoreBtn = document.getElementById('btn-restore');
    const progressDiv = document.getElementById('restore-progress');
    
    // Enable button only when confirmed
    confirmCheckbox.addEventListener('change', function() {
        restoreBtn.disabled = !this.checked;
    });
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!confirm('<?php echo __('Are you sure you want to restore this backup? This action cannot be undone.') ?>')) {
            return;
        }
        
        // Show progress
        form.style.display = 'none';
        progressDiv.style.display = 'block';
        
        // Collect options
        const options = {
            id: '<?php echo esc_entities($backupId) ?>',
            restore_database: document.getElementById('restore_database').checked ? 1 : 0,
            restore_uploads: document.getElementById('restore_uploads').checked ? 1 : 0,
            restore_plugins: document.getElementById('restore_plugins').checked ? 1 : 0,
            restore_framework: document.getElementById('restore_framework').checked ? 1 : 0
        };
        
        fetch('<?php echo url_for(['module' => 'backup', 'action' => 'doRestore']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams(options)
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('<?php echo __('Restore failed') ?>: ' + data.error);
                form.style.display = 'block';
                progressDiv.style.display = 'none';
            } else {
                alert('<?php echo __('Restore completed successfully!') ?>');
                window.location.href = '<?php echo url_for(['module' => 'backup', 'action' => 'index']) ?>';
            }
        })
        .catch(error => {
            alert('<?php echo __('Error') ?>: ' + error);
            form.style.display = 'block';
            progressDiv.style.display = 'none';
        });
    });
});
</script>
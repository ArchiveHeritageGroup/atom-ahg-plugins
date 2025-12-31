<?php
/**
 * Backup index template - AtoM 2.10 style with Bootstrap 5
 */

// Get data from action
$backups = $sf_data->getRaw('backups') ?? [];
$schedules = $sf_data->getRaw('schedules') ?? [];
$backupService = $sf_data->getRaw('backupService');

// Get settings service for DB info display
$settingsService = new \AtomExtensions\Services\BackupSettingsService();
$dbConfig = $settingsService->getDbConfigFromFile();
$settings = $settingsService->all();
?>

<?php echo get_component('default', 'updateCheck') ?>

<h1><?php echo __('Backup & Restore') ?></h1>

<?php echo get_partial('default/breadcrumb', [
    'objects' => [
        ['title' => __('Admin'), 'url' => url_for(['module' => 'admin', 'action' => 'index'])],
        ['title' => __('Backup & Restore')]
    ]
]) ?>

<!-- Action buttons -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?php echo url_for(['module' => 'backup', 'action' => 'settings']) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-cog me-1"></i><?php echo __('Settings') ?>
        </a>
    </div>
    <div>
        <button type="button" class="btn btn-primary" id="btn-create-backup">
            <i class="fas fa-plus me-1"></i><?php echo __('Create Backup') ?>
        </button>
    </div>
</div>

<div class="row">
    <!-- Left Column: Info Cards -->
    <div class="col-md-4">
        <!-- Database Info -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-database me-2"></i><?php echo __('Database Info') ?></h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li><strong><?php echo __('Host') ?>:</strong> <?php echo esc_entities($dbConfig['db_host'] ?? 'localhost') ?></li>
                    <li><strong><?php echo __('Database') ?>:</strong> <?php echo esc_entities($dbConfig['db_name'] ?? 'archive') ?></li>
                    <li><strong><?php echo __('User') ?>:</strong> <?php echo esc_entities($dbConfig['db_user'] ?? 'root') ?></li>
                    <li><strong><?php echo __('Port') ?>:</strong> <?php echo esc_entities($dbConfig['db_port'] ?? 3306) ?></li>
                </ul>
            </div>
        </div>

        <!-- Storage Info -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-folder me-2"></i><?php echo __('Storage') ?></h5>
            </div>
            <div class="card-body">
                <?php
                $backupPath = $settings['backup_path'] ?? '/var/backups/atom';
                $totalSize = 0;
                foreach ($backups as $backup) {
                    $totalSize += $backup['size'] ?? 0;
                }
                ?>
                <ul class="list-unstyled mb-0">
                    <li><strong><?php echo __('Path') ?>:</strong> <code><?php echo esc_entities($backupPath) ?></code></li>
                    <li><strong><?php echo __('Backups') ?>:</strong> <?php echo count($backups) ?></li>
                    <li><strong><?php echo __('Total Size') ?>:</strong> <?php echo $backupService ? $backupService->formatSize($totalSize) : round($totalSize / 1024 / 1024, 2) . ' MB' ?></li>
                    <li><strong><?php echo __('Max Backups') ?>:</strong> <?php echo esc_entities($settings['max_backups'] ?? 30) ?></li>
                    <li><strong><?php echo __('Retention') ?>:</strong> <?php echo esc_entities($settings['retention_days'] ?? 90) ?> <?php echo __('days') ?></li>
                </ul>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i><?php echo __('Quick Actions') ?></h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?php echo url_for(['module' => 'backup', 'action' => 'settings']) ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-cog me-2"></i><?php echo __('Backup Settings') ?>
                </a>
                <a href="#" class="list-group-item list-group-item-action" id="btn-backup-db-only">
                    <i class="fas fa-database me-2"></i><?php echo __('Backup Database Only') ?>
                </a>
                <a href="#" class="list-group-item list-group-item-action" id="btn-backup-full">
                    <i class="fas fa-archive me-2"></i><?php echo __('Full Backup') ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Right Column: Backup List -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i><?php echo __('Backup History') ?></h5>
                <span class="badge bg-secondary"><?php echo count($backups) ?> <?php echo __('backups') ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($backups)): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p><?php echo __('No backups found') ?></p>
                    <button type="button" class="btn btn-primary" id="btn-first-backup">
                        <i class="fas fa-plus me-1"></i><?php echo __('Create First Backup') ?>
                    </button>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><?php echo __('Backup ID') ?></th>
                                <th><?php echo __('Date') ?></th>
                                <th><?php echo __('Size') ?></th>
                                <th><?php echo __('Components') ?></th>
                                <th><?php echo __('Status') ?></th>
                                <th class="text-end"><?php echo __('Actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td>
                                    <code><?php echo esc_entities(substr($backup['id'], 0, 20)) ?></code>
                                </td>
                                <td><?php echo esc_entities($backup['created_at'] ?? $backup['started_at'] ?? '') ?></td>
                                <td>
                                    <?php 
                                    $size = $backup['size'] ?? 0;
                                    echo $backupService ? $backupService->formatSize($size) : round($size / 1024 / 1024, 2) . ' MB';
                                    ?>
                                </td>
                                <td>
                                    <?php $components = $backup['components'] ?? []; ?>
                                    <?php if (!empty($components['database'])): ?>
                                        <span class="badge bg-info" title="<?php echo __('Database') ?>"><i class="fas fa-database"></i></span>
                                    <?php endif ?>
                                    <?php if (!empty($components['uploads'])): ?>
                                        <span class="badge bg-success" title="<?php echo __('Uploads') ?>"><i class="fas fa-folder"></i></span>
                                    <?php endif ?>
                                    <?php if (!empty($components['plugins'])): ?>
                                        <span class="badge bg-warning" title="<?php echo __('Plugins') ?>"><i class="fas fa-puzzle-piece"></i></span>
                                    <?php endif ?>
                                    <?php if (!empty($components['framework'])): ?>
                                        <span class="badge bg-secondary" title="<?php echo __('Framework') ?>"><i class="fas fa-cogs"></i></span>
                                    <?php endif ?>
                                </td>
                                <td>
                                    <?php $status = $backup['status'] ?? 'unknown'; ?>
                                    <?php if ($status === 'completed'): ?>
                                        <span class="badge bg-success"><?php echo __('Completed') ?></span>
                                    <?php elseif ($status === 'in_progress'): ?>
                                        <span class="badge bg-warning"><?php echo __('In Progress') ?></span>
                                    <?php elseif ($status === 'failed'): ?>
                                        <span class="badge bg-danger"><?php echo __('Failed') ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo esc_entities($status) ?></span>
                                    <?php endif ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo url_for(['module' => 'backup', 'action' => 'download', 'id' => $backup['id']]) ?>" 
                                           class="btn btn-outline-primary" title="<?php echo __('Download') ?>">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <a href="<?php echo url_for(['module' => 'backup', 'action' => 'restore', 'id' => $backup['id']]) ?>" 
                                           class="btn btn-outline-warning" title="<?php echo __('Restore') ?>">
                                            <i class="fas fa-undo"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger btn-delete-backup" 
                                                data-id="<?php echo esc_entities($backup['id']) ?>" title="<?php echo __('Delete') ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Backup Modal -->
<div class="modal fade" id="createBackupModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-archive me-2"></i><?php echo __('Create Backup') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3"><?php echo __('Select a backup type:') ?></p>
                
                <!-- Presets -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4 col-6">
                        <div class="card h-100 preset-card" data-preset="db">
                            <div class="card-body text-center py-3">
                                <i class="bi bi-database fs-2 mb-2 text-primary"></i>
                                <h6 class="mb-1"><?php echo __('Database Only') ?></h6>
                                <small class="text-muted"><?php echo __('MySQL dump only') ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6">
                        <div class="card h-100 preset-card" data-preset="atom_base">
                            <div class="card-body text-center py-3">
                                <i class="bi bi-box fs-2 mb-2 text-primary"></i>
                                <h6 class="mb-1"><?php echo __('AtoM Base') ?></h6>
                                <small class="text-muted"><?php echo __('DB + core files') ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6">
                        <div class="card h-100 preset-card" data-preset="content">
                            <div class="card-body text-center py-3">
                                <i class="bi bi-images fs-2 mb-2 text-primary"></i>
                                <h6 class="mb-1"><?php echo __('Content') ?></h6>
                                <small class="text-muted"><?php echo __('DB + digital objects') ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6">
                        <div class="card h-100 preset-card selected" data-preset="ahg">
                            <div class="card-body text-center py-3">
                                <i class="bi bi-puzzle fs-2 mb-2 text-primary"></i>
                                <h6 class="mb-1"><?php echo __('AHG Extensions') ?></h6>
                                <small class="text-muted"><?php echo __('DB + Framework + Plugins') ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6">
                        <div class="card h-100 preset-card" data-preset="full">
                            <div class="card-body text-center py-3">
                                <i class="bi bi-archive fs-2 mb-2 text-primary"></i>
                                <h6 class="mb-1"><?php echo __('Full Backup') ?></h6>
                                <small class="text-muted"><?php echo __('Everything') ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <input type="hidden" id="backup-preset" value="ahg">
                
                <!-- Custom Options -->
                <div class="collapse" id="customOptions">
                    <hr>
                    <h6 class="mb-3"><?php echo __('Custom Options') ?></h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input class="form-check-input custom-opt" type="checkbox" id="opt-database" checked>
                                <label class="form-check-label" for="opt-database"><?php echo __('Database') ?></label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input custom-opt" type="checkbox" id="opt-digital-objects">
                                <label class="form-check-label" for="opt-digital-objects"><?php echo __('Digital Objects (uploads/r)') ?></label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input custom-opt" type="checkbox" id="opt-uploads">
                                <label class="form-check-label" for="opt-uploads"><?php echo __('All Uploads') ?></label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input class="form-check-input custom-opt" type="checkbox" id="opt-atom-base">
                                <label class="form-check-label" for="opt-atom-base"><?php echo __('AtoM Base') ?></label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input custom-opt" type="checkbox" id="opt-plugins" checked>
                                <label class="form-check-label" for="opt-plugins"><?php echo __('AHG Plugins') ?></label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input custom-opt" type="checkbox" id="opt-framework" checked>
                                <label class="form-check-label" for="opt-framework"><?php echo __('AHG Framework') ?></label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input custom-opt" type="checkbox" id="opt-fuseki">
                                <label class="form-check-label" for="opt-fuseki"><?php echo __('Fuseki/RIC') ?></label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="#" data-bs-toggle="collapse" data-bs-target="#customOptions" class="small text-decoration-none">
                        <i class="bi bi-gear me-1"></i><?php echo __('Custom options') ?>
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel') ?></button>
                <button type="button" class="btn btn-primary" id="btn-start-backup">
                    <i class="bi bi-play-fill me-1"></i><?php echo __('Start Backup') ?>
                </button>
            </div>
        </div>
    </div>
</div>
<style>
.preset-card { cursor: pointer; transition: all 0.2s; border: 2px solid transparent; }
.preset-card:hover { border-color: var(--bs-primary); transform: translateY(-2px); }
.preset-card.selected { border-color: var(--bs-primary); background-color: rgba(var(--bs-primary-rgb), 0.1); }
</style>

<!-- Progress Modal -->
<div class="modal fade" id="progressModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden"><?php echo __('Loading...') ?></span>
                </div>
                <h5 id="progress-message"><?php echo __('Creating backup...') ?></h5>
                <p class="text-muted"><?php echo __('Please wait, this may take a few minutes.') ?></p>
            </div>
        </div>
    </div>
</div>

<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
document.addEventListener('DOMContentLoaded', function() {
    const createModal = new bootstrap.Modal(document.getElementById('createBackupModal'));
    const progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
    
    // Preset card selection
    const presetCards = document.querySelectorAll('.preset-card');
    const presetInput = document.getElementById('backup-preset');
    
    presetCards.forEach(card => {
        card.addEventListener('click', function() {
            presetCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            presetInput.value = this.dataset.preset;
        });
    });
    
    // Show create backup modal
    document.querySelectorAll('#btn-create-backup, #btn-first-backup, #btn-backup-full').forEach(btn => {
        btn.addEventListener('click', function() {
            createModal.show();
        });
    });
    
    // Quick backup buttons
    document.getElementById('btn-backup-db-only')?.addEventListener('click', function(e) {
        e.preventDefault();
        presetCards.forEach(c => c.classList.remove('selected'));
        document.querySelector('.preset-card[data-preset="db"]')?.classList.add('selected');
        presetInput.value = 'db';
        createModal.show();
    });
    
    // Start backup
    document.getElementById('btn-start-backup').addEventListener('click', function() {
        createModal.hide();
        progressModal.show();
        
        // Check if custom options are expanded
        const customExpanded = document.getElementById('customOptions')?.classList.contains('show');
        
        let options;
        if (customExpanded) {
            options = new URLSearchParams({
                preset: 'custom',
                database: document.getElementById('opt-database')?.checked ? 1 : 0,
                digital_objects: document.getElementById('opt-digital-objects')?.checked ? 1 : 0,
                uploads: document.getElementById('opt-uploads')?.checked ? 1 : 0,
                atom_base: document.getElementById('opt-atom-base')?.checked ? 1 : 0,
                plugins: document.getElementById('opt-plugins')?.checked ? 1 : 0,
                framework: document.getElementById('opt-framework')?.checked ? 1 : 0,
                fuseki: document.getElementById('opt-fuseki')?.checked ? 1 : 0
            });
        } else {
            options = new URLSearchParams({
                preset: presetInput.value
            });
        }
        
        fetch('<?php echo url_for(['module' => 'backup', 'action' => 'create']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: options
        })
        .then(response => response.json())
        .then(data => {
            progressModal.hide();
            if (data.error) {
                alert('<?php echo __('Backup failed') ?>: ' + data.error);
            } else {
                let msg = '<?php echo __('Backup created successfully!') ?>';
                if (data.zip_file) {
                    msg += '\nZIP: ' + data.zip_file;
                }
                alert(msg);
                window.location.reload();
            }
        })
        .catch(error => {
            progressModal.hide();
            alert('<?php echo __('Error') ?>: ' + error);
        });
    });
    
    // Delete backup
    document.querySelectorAll('.btn-delete-backup').forEach(btn => {
        btn.addEventListener('click', function() {
            const backupId = this.dataset.id;
            if (!confirm('<?php echo __('Are you sure you want to delete this backup?') ?>')) {
                return;
            }
            fetch('<?php echo url_for(['module' => 'backup', 'action' => 'delete']) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'id=' + encodeURIComponent(backupId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('<?php echo __('Delete failed') ?>: ' + data.error);
                } else {
                    window.location.reload();
                }
            })
            .catch(error => {
                alert('<?php echo __('Error') ?>: ' + error);
            });
        });
    });
});
</script>

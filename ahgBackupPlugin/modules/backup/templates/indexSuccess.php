<?php
$backups = $sf_data->getRaw('backups') ?? [];
$schedules = $sf_data->getRaw('schedules') ?? [];
$backupService = $sf_data->getRaw('backupService');
$pendingUploads = $sf_data->getRaw('pendingUploads') ?? [];

$settingsService = new \AtomExtensions\Services\BackupSettingsService();
$dbConfig = $settingsService->getDbConfigFromFile();
$settings = $settingsService->all();

$totalSize = 0;
foreach ($backups as $backup) {
    $totalSize += $backup['size'] ?? 0;
}
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
        <a href="<?php echo url_for(['module' => 'backup', 'action' => 'upload']) ?>" class="btn btn-outline-primary ms-2">
            <i class="fas fa-upload me-1"></i><?php echo __('Upload Backup') ?>
            <?php if (!empty($pendingUploads)): ?>
                <span class="badge bg-warning text-dark ms-1"><?php echo count($pendingUploads) ?></span>
            <?php endif; ?>
        </a>
    </div>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBackupModal">
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
                <hr>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-test-connection">
                    <i class="fas fa-plug me-1"></i><?php echo __('Test Connection') ?>
                </button>
                <span id="connection-status" class="ms-2"></span>
            </div>
        </div>

        <!-- Storage Info -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-folder me-2"></i><?php echo __('Storage') ?></h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li><strong><?php echo __('Path') ?>:</strong> <code class="small"><?php echo esc_entities($settings['backup_path'] ?? '/var/backups/atom') ?></code></li>
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
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#dbBackupModal">
                        <i class="fas fa-database me-1"></i><?php echo __('Database Only') ?>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#fullBackupModal">
                        <i class="fas fa-archive me-1"></i><?php echo __('Full Backup') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Backups List -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i><?php echo __('Backups') ?></h5>
                <span class="badge bg-secondary"><?php echo count($backups) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($backups)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p><?php echo __('No backups found') ?></p>
                        <button type="button" class="btn btn-primary btn-quick-backup" data-type="full">
                            <i class="fas fa-plus me-1"></i><?php echo __('Create First Backup') ?>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo __('Date') ?></th>
                                    <th><?php echo __('Type') ?></th>
                                    <th><?php echo __('Components') ?></th>
                                    <th><?php echo __('Size') ?></th>
                                    <th class="text-end"><?php echo __('Actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                    <?php
                                    $components = $backup['components'] ?? [];
                                    $typeClass = ($backup['type'] ?? 'manual') === 'scheduled' ? 'info' : 'primary';
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_entities($backup['id']) ?></strong>
                                            <?php if (!empty($backup['created_at'])): ?>
                                                <br><small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($backup['created_at'])) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $typeClass ?>"><?php echo ucfirst($backup['type'] ?? 'manual') ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($components['database'])): ?><span class="badge bg-success me-1" title="Database"><i class="fas fa-database"></i></span><?php endif; ?>
                                            <?php if (!empty($components['uploads'])): ?><span class="badge bg-warning text-dark me-1" title="Uploads"><i class="fas fa-images"></i></span><?php endif; ?>
                                            <?php if (!empty($components['plugins'])): ?><span class="badge bg-info me-1" title="Plugins"><i class="fas fa-puzzle-piece"></i></span><?php endif; ?>
                                            <?php if (!empty($components['framework'])): ?><span class="badge bg-secondary me-1" title="Framework"><i class="fas fa-code"></i></span><?php endif; ?>
                                        </td>
                                        <td><?php echo $backupService ? $backupService->formatSize($backup['size'] ?? 0) : round(($backup['size'] ?? 0) / 1024 / 1024, 2) . ' MB' ?></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo url_for(['module' => 'backup', 'action' => 'restore', 'id' => $backup['id']]) ?>" class="btn btn-outline-success" title="<?php echo __('Restore') ?>">
                                                    <i class="fas fa-undo"></i>
                                                </a>
                                                <a href="<?php echo url_for(['module' => 'backup', 'action' => 'download', 'id' => $backup['id']]) ?>" class="btn btn-outline-primary" title="<?php echo __('Download') ?>">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger btn-delete-backup" data-id="<?php echo esc_entities($backup['id']) ?>" title="<?php echo __('Delete') ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Backup Modal -->
<div class="modal fade" id="createBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i><?php echo __('Create Backup') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted"><?php echo __('Select components to include in this backup:') ?></p>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="backup-database" checked>
                    <label class="form-check-label" for="backup-database">
                        <i class="fas fa-database me-1 text-success"></i><?php echo __('Database') ?>
                        <small class="text-muted">(<?php echo __('Required') ?>)</small>
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="backup-uploads">
                    <label class="form-check-label" for="backup-uploads">
                        <i class="fas fa-images me-1 text-warning"></i><?php echo __('Uploads') ?>
                        <small class="text-muted">(<?php echo __('Digital objects') ?>)</small>
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="backup-plugins">
                    <label class="form-check-label" for="backup-plugins">
                        <i class="fas fa-puzzle-piece me-1 text-info"></i><?php echo __('Custom Plugins') ?>
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="backup-framework">
                    <label class="form-check-label" for="backup-framework">
                        <i class="fas fa-code me-1 text-secondary"></i><?php echo __('AHG Framework') ?>
                    </label>
                </div>
                <div id="backup-progress" class="mt-3 d-none">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                    </div>
                    <small class="text-muted mt-1 d-block" id="backup-status"><?php echo __('Creating backup...') ?></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel') ?></button>
                <button type="button" class="btn btn-primary" id="btn-start-backup">
                    <i class="fas fa-play me-1"></i><?php echo __('Start Backup') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Database Only Backup Modal -->
<div class="modal fade" id="dbBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-database me-2"></i><?php echo __('Database Backup') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted"><?php echo __('Select components to include:') ?></p>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="db-opt-database" checked disabled>
                    <label class="form-check-label" for="db-opt-database">
                        <i class="fas fa-database me-1 text-success"></i><?php echo __('Database') ?>
                        <small class="text-muted">(<?php echo __('Required') ?>)</small>
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="db-opt-uploads">
                    <label class="form-check-label" for="db-opt-uploads">
                        <i class="fas fa-images me-1 text-warning"></i><?php echo __('Uploads / Digital Objects') ?>
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="db-opt-plugins">
                    <label class="form-check-label" for="db-opt-plugins">
                        <i class="fas fa-puzzle-piece me-1 text-info"></i><?php echo __('Custom Plugins') ?>
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="db-opt-framework">
                    <label class="form-check-label" for="db-opt-framework">
                        <i class="fas fa-code me-1 text-secondary"></i><?php echo __('AHG Framework') ?>
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="db-opt-fuseki">
                    <label class="form-check-label" for="db-opt-fuseki">
                        <i class="fas fa-project-diagram me-1 text-purple"></i><?php echo __('Fuseki / RIC Triplestore') ?>
                    </label>
                </div>
                <div id="db-backup-progress" class="mt-3 d-none">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 100%"></div>
                    </div>
                    <small class="text-muted mt-1 d-block"><?php echo __('Creating backup...') ?></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel') ?></button>
                <button type="button" class="btn btn-primary" id="btn-db-backup">
                    <i class="fas fa-play me-1"></i><?php echo __('Start Backup') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Full Backup Modal -->
<div class="modal fade" id="fullBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-archive me-2"></i><?php echo __('Full Backup') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted"><?php echo __('Select components to include:') ?></p>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="full-opt-database" checked disabled>
                    <label class="form-check-label" for="full-opt-database">
                        <i class="fas fa-database me-1 text-success"></i><?php echo __('Database') ?>
                        <small class="text-muted">(<?php echo __('Required') ?>)</small>
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="full-opt-uploads" checked>
                    <label class="form-check-label" for="full-opt-uploads">
                        <i class="fas fa-images me-1 text-warning"></i><?php echo __('Uploads / Digital Objects') ?>
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="full-opt-plugins" checked>
                    <label class="form-check-label" for="full-opt-plugins">
                        <i class="fas fa-puzzle-piece me-1 text-info"></i><?php echo __('Custom Plugins') ?>
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="full-opt-framework" checked>
                    <label class="form-check-label" for="full-opt-framework">
                        <i class="fas fa-code me-1 text-secondary"></i><?php echo __('AHG Framework') ?>
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="full-opt-fuseki">
                    <label class="form-check-label" for="full-opt-fuseki">
                        <i class="fas fa-project-diagram me-1 text-purple"></i><?php echo __('Fuseki / RIC Triplestore') ?>
                        <small class="text-muted">(<?php echo __('May take longer') ?>)</small>
                    </label>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="full-select-all"><?php echo __('Select All') ?></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="full-select-none"><?php echo __('Deselect All') ?></button>
                </div>
                <div id="full-backup-progress" class="mt-3 d-none">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-dark" style="width: 100%"></div>
                    </div>
                    <small class="text-muted mt-1 d-block"><?php echo __('Creating backup...') ?></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel') ?></button>
                <button type="button" class="btn btn-dark" id="btn-full-backup">
                    <i class="fas fa-play me-1"></i><?php echo __('Start Backup') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Test connection
    document.getElementById('btn-test-connection')?.addEventListener('click', function() {
        const btn = this;
        const status = document.getElementById('connection-status');
        btn.disabled = true;
        status.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        
        fetch('<?php echo url_for(['module' => 'backup', 'action' => 'testConnection']) ?>', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(r => r.json())
        .then(data => {
            status.innerHTML = data.status === 'success' 
                ? '<span class="text-success"><i class="fas fa-check"></i> ' + data.message + '</span>'
                : '<span class="text-danger"><i class="fas fa-times"></i> ' + data.message + '</span>';
        })
        .catch(e => {
            status.innerHTML = '<span class="text-danger"><i class="fas fa-times"></i> Error</span>';
        })
        .finally(() => btn.disabled = false);
    });

    // Database backup from modal
    document.getElementById('btn-db-backup')?.addEventListener('click', function() {
        const options = {
            database: true,
            uploads: document.getElementById('db-opt-uploads')?.checked || false,
            plugins: document.getElementById('db-opt-plugins')?.checked || false,
            framework: document.getElementById('db-opt-framework')?.checked || false,
            fuseki: document.getElementById('db-opt-fuseki')?.checked || false
        };
        document.getElementById('db-backup-progress').classList.remove('d-none');
        this.disabled = true;
        createBackup(options, this);
    });

    // Full backup from modal
    document.getElementById('btn-full-backup')?.addEventListener('click', function() {
        const options = {
            database: true,
            uploads: document.getElementById('full-opt-uploads')?.checked || false,
            plugins: document.getElementById('full-opt-plugins')?.checked || false,
            framework: document.getElementById('full-opt-framework')?.checked || false,
            fuseki: document.getElementById('full-opt-fuseki')?.checked || false
        };
        document.getElementById('full-backup-progress').classList.remove('d-none');
        this.disabled = true;
        createBackup(options, this);
    });

    // Full backup select all/none
    document.getElementById('full-select-all')?.addEventListener('click', function() {
        ['full-opt-uploads', 'full-opt-plugins', 'full-opt-framework', 'full-opt-fuseki'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.checked = true;
        });
    });
    document.getElementById('full-select-none')?.addEventListener('click', function() {
        ['full-opt-uploads', 'full-opt-plugins', 'full-opt-framework', 'full-opt-fuseki'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.checked = false;
        });
    });

    // Start backup from modal
    document.getElementById('btn-start-backup')?.addEventListener('click', function() {
        const options = {
            database: document.getElementById('backup-database').checked,
            uploads: document.getElementById('backup-uploads').checked,
            plugins: document.getElementById('backup-plugins').checked,
            framework: document.getElementById('backup-framework').checked
        };
        
        document.getElementById('backup-progress').classList.remove('d-none');
        this.disabled = true;
        
        createBackup(options, this);
    });

    // Delete backup
    document.querySelectorAll('.btn-delete-backup').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('<?php echo __('Are you sure you want to delete this backup?') ?>')) return;
            
            const id = this.dataset.id;
            const row = this.closest('tr');
            
            fetch('<?php echo url_for(['module' => 'backup', 'action' => 'delete']) ?>', {
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

    function createBackup(options, btn) {
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creating...';
        btn.disabled = true;

        const params = new URLSearchParams();
        Object.keys(options).forEach(k => params.append(k, options[k] ? '1' : '0'));

        fetch('<?php echo url_for(['module' => 'backup', 'action' => 'create']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: params.toString()
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                alert('Error: ' + data.error);
            } else {
                location.reload();
            }
        })
        .catch(e => alert('Error: ' + e.message))
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
});
</script>

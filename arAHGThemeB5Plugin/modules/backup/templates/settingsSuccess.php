<?php
$settingsService = new \AtomExtensions\Services\BackupSettingsService();
$settings = $settingsService->all();
$dbConfig = $settingsService->getDbConfigFromFile();
$configFilePath = $settingsService->getConfigFilePath();

$customPlugins = $settings['custom_plugins'] ?? [];
if (is_string($customPlugins)) {
    $customPlugins = json_decode($customPlugins, true) ?? [];
}
?>

<?php echo get_component('default', 'updateCheck') ?>

<h1><?php echo __('Backup Settings') ?></h1>

<?php echo get_partial('default/breadcrumb', [
    'objects' => [
        ['title' => __('Admin'), 'url' => url_for(['module' => 'admin', 'action' => 'index'])],
        ['title' => __('Backup'), 'url' => url_for(['module' => 'backup', 'action' => 'index'])],
        ['title' => __('Settings')]
    ]
]) ?>

<?php if ($sf_user->hasFlash('notice')): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?php echo $sf_user->getFlash('notice') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif ?>

<?php if ($sf_user->hasFlash('error')): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?php echo $sf_user->getFlash('error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif ?>

<form method="post" action="<?php echo url_for(['module' => 'backup', 'action' => 'settings']) ?>">

    <!-- Database Settings (Read-only from config.php) -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-database me-2"></i><?php echo __('Database Settings') ?></h5>
            <span class="badge bg-info"><?php echo __('From config.php') ?></span>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo __('Database credentials are read from') ?> <code><?php echo esc_entities($configFilePath) ?></code>. 
                <?php echo __('Edit that file to change these values.') ?>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label"><?php echo __('Host') ?></label>
                    <input type="text" class="form-control" readonly
                           value="<?php echo esc_entities($dbConfig['db_host']) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label"><?php echo __('Port') ?></label>
                    <input type="text" class="form-control" readonly
                           value="<?php echo esc_entities($dbConfig['db_port'] ?? 3306) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label"><?php echo __('Database Name') ?></label>
                    <input type="text" class="form-control" readonly
                           value="<?php echo esc_entities($dbConfig['db_name']) ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo __('Username') ?></label>
                    <input type="text" class="form-control" readonly
                           value="<?php echo esc_entities($dbConfig['db_user']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo __('Password') ?></label>
                    <input type="password" class="form-control" readonly
                           value="<?php echo !empty($dbConfig['db_password']) ? '••••••••' : '' ?>"
                           placeholder="<?php echo empty($dbConfig['db_password']) ? __('Not set') : '' ?>">
                    <?php if (empty($dbConfig['db_password'])): ?>
                    <div class="form-text text-warning">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <?php echo __('No password set in config.php') ?>
                    </div>
                    <?php endif ?>
                </div>
            </div>
            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-outline-primary" id="btn-test-connection">
                    <i class="fas fa-plug me-1"></i><?php echo __('Test Connection') ?>
                </button>
            </div>
            <div id="connection-result"></div>
        </div>
    </div>

    <!-- Storage Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-folder me-2"></i><?php echo __('Storage Settings') ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo __('Backup Path') ?></label>
                    <input type="text" name="backup_path" class="form-control" 
                           value="<?php echo esc_entities($settings['backup_path'] ?? '/var/backups/atom') ?>">
                    <div class="form-text"><?php echo __('Directory where backups are stored') ?></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo __('Log Path') ?></label>
                    <input type="text" name="log_path" class="form-control" 
                           value="<?php echo esc_entities($settings['log_path'] ?? '/var/log/atom/backup.log') ?>">
                    <div class="form-text"><?php echo __('Backup log file location') ?></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label"><?php echo __('Max Backups') ?></label>
                    <input type="number" name="max_backups" class="form-control" min="1" max="100"
                           value="<?php echo (int)($settings['max_backups'] ?? 30) ?>">
                    <div class="form-text"><?php echo __('Maximum number of backups to keep') ?></div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label"><?php echo __('Retention Days') ?></label>
                    <input type="number" name="retention_days" class="form-control" min="1" max="365"
                           value="<?php echo (int)($settings['retention_days'] ?? 90) ?>">
                    <div class="form-text"><?php echo __('Days to keep backups') ?></div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label"><?php echo __('Compression Level') ?></label>
                    <input type="number" name="compression_level" class="form-control" min="1" max="9"
                           value="<?php echo (int)($settings['compression_level'] ?? 6) ?>">
                    <div class="form-text"><?php echo __('Gzip compression (1=fast, 9=best)') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Default Components -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-cubes me-2"></i><?php echo __('Default Backup Components') ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="include_database" id="include_database" 
                               <?php echo ($settings['include_database'] ?? true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="include_database">
                            <i class="fas fa-database me-1"></i><?php echo __('Database') ?>
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="include_uploads" id="include_uploads"
                               <?php echo ($settings['include_uploads'] ?? true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="include_uploads">
                            <i class="fas fa-folder me-1"></i><?php echo __('Uploads') ?>
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="include_plugins" id="include_plugins"
                               <?php echo ($settings['include_plugins'] ?? true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="include_plugins">
                            <i class="fas fa-puzzle-piece me-1"></i><?php echo __('Plugins') ?>
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="include_framework" id="include_framework"
                               <?php echo ($settings['include_framework'] ?? true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="include_framework">
                            <i class="fas fa-cogs me-1"></i><?php echo __('Framework') ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Plugins -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-puzzle-piece me-2"></i><?php echo __('Custom Plugins to Backup') ?></h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label"><?php echo __('Plugin Names (one per line)') ?></label>
                <textarea name="custom_plugins" class="form-control" rows="5"
                          placeholder="arAHGThemeB5Plugin&#10;ahgSecurityClearancePlugin&#10;sfMuseumPlugin"><?php echo esc_entities(implode("\n", $customPlugins)) ?></textarea>
                <div class="form-text"><?php echo __('Enter plugin directory names, one per line') ?></div>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-bell me-2"></i><?php echo __('Notifications') ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo __('Email Address') ?></label>
                    <input type="email" name="notify_email" class="form-control" 
                           value="<?php echo esc_entities($settings['notify_email'] ?? '') ?>"
                           placeholder="admin@example.com">
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="notify_on_success" id="notify_on_success"
                               <?php echo ($settings['notify_on_success'] ?? false) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notify_on_success">
                            <?php echo __('Notify on success') ?>
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="notify_on_failure" id="notify_on_failure"
                               <?php echo ($settings['notify_on_failure'] ?? true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notify_on_failure">
                            <?php echo __('Notify on failure') ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'backup', 'action' => 'index']) ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Cancel') ?>
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i><?php echo __('Save Settings') ?>
        </button>
    </div>
</form>

<script>
document.getElementById('btn-test-connection').addEventListener('click', function() {
    const btn = this;
    const resultDiv = document.getElementById('connection-result');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Testing...';
    
    fetch('<?php echo url_for(['module' => 'backup', 'action' => 'testConnection']) ?>', {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            resultDiv.innerHTML = '<div class="alert alert-success mt-3"><i class="fas fa-check-circle me-2"></i>' + data.message + '</div>';
        } else {
            resultDiv.innerHTML = '<div class="alert alert-danger mt-3"><i class="fas fa-times-circle me-2"></i>' + data.message + '</div>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class="alert alert-danger mt-3"><i class="fas fa-times-circle me-2"></i>Connection test failed</div>';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plug me-1"></i>Test Connection';
    });
});
</script>

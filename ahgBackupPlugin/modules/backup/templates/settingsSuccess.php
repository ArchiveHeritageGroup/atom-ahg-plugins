<?php
$settings = $sf_data->getRaw('settings') ?? [];
$settingsMap = $sf_data->getRaw('settingsMap') ?? [];
?>

<?php echo get_component('default', 'updateCheck') ?>

<h1><?php echo __('Backup Settings') ?></h1>

<?php echo get_partial('default/breadcrumb', [
    'objects' => [
        ['title' => __('Admin'), 'url' => url_for(['module' => 'admin', 'action' => 'index'])],
        ['title' => __('Backup & Restore'), 'url' => url_for(['module' => 'backup', 'action' => 'index'])],
        ['title' => __('Settings')]
    ]
]) ?>

<form method="post" action="<?php echo url_for(['module' => 'backup', 'action' => 'settings']) ?>">
    <div class="row">
        <div class="col-lg-8">
            <!-- Storage Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-folder me-2"></i><?php echo __('Storage Settings') ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="backup_path"><?php echo __('Backup Path') ?></label>
                        <input type="text" class="form-control" id="backup_path" name="backup_path" value="<?php echo esc_entities($settingsMap['backup_path'] ?? '/var/backups/atom') ?>">
                        <div class="form-text"><?php echo __('Directory where backups will be stored') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="log_path"><?php echo __('Log Path') ?></label>
                        <input type="text" class="form-control" id="log_path" name="log_path" value="<?php echo esc_entities($settingsMap['log_path'] ?? '/var/log/atom/backup.log') ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="max_backups"><?php echo __('Max Backups') ?></label>
                            <input type="number" class="form-control" id="max_backups" name="max_backups" value="<?php echo (int)($settingsMap['max_backups'] ?? 30) ?>" min="1" max="999">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="retention_days"><?php echo __('Retention Days') ?></label>
                            <input type="number" class="form-control" id="retention_days" name="retention_days" value="<?php echo (int)($settingsMap['retention_days'] ?? 90) ?>" min="1" max="9999">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Default Components -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cubes me-2"></i><?php echo __('Default Components') ?></h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="include_database" name="include_database" value="1" <?php echo ($settingsMap['include_database'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="include_database">
                            <i class="fas fa-database text-success me-1"></i><?php echo __('Database') ?>
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="include_uploads" name="include_uploads" value="1" <?php echo ($settingsMap['include_uploads'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="include_uploads">
                            <i class="fas fa-images text-warning me-1"></i><?php echo __('Uploads (Digital Objects)') ?>
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="include_plugins" name="include_plugins" value="1" <?php echo ($settingsMap['include_plugins'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="include_plugins">
                            <i class="fas fa-puzzle-piece text-info me-1"></i><?php echo __('Custom Plugins') ?>
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="include_framework" name="include_framework" value="1" <?php echo ($settingsMap['include_framework'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="include_framework">
                            <i class="fas fa-code text-secondary me-1"></i><?php echo __('AHG Framework') ?>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Custom Plugins List -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i><?php echo __('Custom Plugins to Backup') ?></h5>
                </div>
                <div class="card-body">
                    <?php
                    $customPlugins = $settingsMap['custom_plugins'] ?? '[]';
                    $pluginList = json_decode($customPlugins, true) ?: [];
                    ?>
                    <textarea class="form-control font-monospace" id="custom_plugins" name="custom_plugins" rows="6" placeholder="ahgThemeB5Plugin&#10;ahgBackupPlugin&#10;ahgResearchPlugin"><?php echo esc_entities(implode("\n", $pluginList)) ?></textarea>
                    <div class="form-text"><?php echo __('One plugin name per line. Only these plugins will be included in plugin backups.') ?></div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bell me-2"></i><?php echo __('Notifications') ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="notify_email"><?php echo __('Notification Email') ?></label>
                        <input type="email" class="form-control" id="notify_email" name="notify_email" value="<?php echo esc_entities($settingsMap['notify_email'] ?? '') ?>">
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="notify_on_success" name="notify_on_success" value="1" <?php echo ($settingsMap['notify_on_success'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notify_on_success"><?php echo __('Notify on successful backup') ?></label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notify_on_failure" name="notify_on_failure" value="1" <?php echo ($settingsMap['notify_on_failure'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notify_on_failure"><?php echo __('Notify on backup failure') ?></label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Help') ?></h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted"><?php echo __('Configure default backup settings. These can be overridden when creating individual backups.') ?></p>
                    <hr>
                    <p class="small text-muted mb-0"><?php echo __('Scheduled backups use a cron job. Set up with:') ?></p>
                    <code class="small">0 2 * * * /usr/share/nginx/archive/atom-framework/scripts/run-backup.sh</code>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <a href="<?php echo url_for(['module' => 'backup', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back') ?>
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i><?php echo __('Save Settings') ?>
        </button>
    </div>
</form>

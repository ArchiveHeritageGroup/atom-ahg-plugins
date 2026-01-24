<?php
/**
 * Preservation Settings Page
 * Configure backup replication targets
 */

$title = __('Preservation & Backup Settings');
slot('title', $title);
?>

<div class="ahg-settings-page">
    <!-- Back Link -->
    <div class="mb-3">
        <a href="<?php echo url_for(['module' => 'settings', 'action' => 'settings']); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to AHG Settings'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm ms-2">
            <i class="fas fa-shield-alt me-1"></i><?php echo __('Preservation Dashboard'); ?>
        </a>
    </div>

    <!-- Page Header -->
    <div class="page-header mb-4">
        <h1><i class="fas fa-cloud-upload-alt text-success"></i> <?php echo $title; ?></h1>
        <p class="text-muted"><?php echo __('Configure backup replication targets for digital preservation'); ?></p>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?php echo $stats['total_targets']; ?></h3>
                    <small><?php echo __('Total Targets'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?php echo $stats['active_targets']; ?></h3>
                    <small><?php echo __('Active Targets'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?php echo $stats['successful_syncs']; ?></h3>
                    <small><?php echo __('Successful Syncs'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-<?php echo $stats['failed_syncs'] > 0 ? 'danger' : 'secondary'; ?> text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?php echo $stats['failed_syncs']; ?></h3>
                    <small><?php echo __('Failed Syncs'); ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Replication Targets -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-server me-2"></i><?php echo __('Replication Targets'); ?></span>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addTargetModal">
                        <i class="fas fa-plus me-1"></i><?php echo __('Add Target'); ?>
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?php echo __('Name'); ?></th>
                                <th><?php echo __('Type'); ?></th>
                                <th><?php echo __('Path/Bucket'); ?></th>
                                <th><?php echo __('Status'); ?></th>
                                <th><?php echo __('Last Sync'); ?></th>
                                <th><?php echo __('Actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($targets)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <?php echo __('No replication targets configured'); ?>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($targets as $target): ?>
                                <?php $config = json_decode($target->connection_config, true) ?: []; ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($target->name); ?></strong>
                                        <?php if ($target->description): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($target->description); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($target->target_type); ?></span></td>
                                    <td><code><?php echo htmlspecialchars($config['path'] ?? $config['bucket'] ?? '-'); ?></code></td>
                                    <td>
                                        <?php if ($target->is_active): ?>
                                            <span class="badge bg-success"><?php echo __('Active'); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo __('Inactive'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($target->last_sync_at): ?>
                                            <small><?php echo date('Y-m-d H:i', strtotime($target->last_sync_at)); ?></small>
                                            <?php if ($target->last_sync_status === 'success'): ?>
                                                <span class="text-success"><i class="fas fa-check"></i></span>
                                            <?php elseif ($target->last_sync_status === 'failed'): ?>
                                                <span class="text-danger"><i class="fas fa-times"></i></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <small class="text-muted"><?php echo __('Never'); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editTargetModal<?php echo $target->id; ?>" title="<?php echo __('Edit'); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="action_type" value="toggle_target">
                                                <input type="hidden" name="target_id" value="<?php echo $target->id; ?>">
                                                <button type="submit" class="btn btn-outline-<?php echo $target->is_active ? 'warning' : 'success'; ?>" title="<?php echo $target->is_active ? __('Disable') : __('Enable'); ?>">
                                                    <i class="fas fa-<?php echo $target->is_active ? 'pause' : 'play'; ?>"></i>
                                                </button>
                                            </form>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('<?php echo __('Delete this target?'); ?>');">
                                                <input type="hidden" name="action_type" value="delete_target">
                                                <input type="hidden" name="target_id" value="<?php echo $target->id; ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="<?php echo __('Delete'); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Edit Modal for this target -->
                                <div class="modal fade" id="editTargetModal<?php echo $target->id; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="post">
                                                <input type="hidden" name="action_type" value="update_target">
                                                <input type="hidden" name="target_id" value="<?php echo $target->id; ?>">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><?php echo __('Edit Target'); ?>: <?php echo htmlspecialchars($target->name); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label"><?php echo __('Name'); ?></label>
                                                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($target->name); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label"><?php echo __('Type'); ?></label>
                                                        <select name="target_type" class="form-select" required>
                                                            <option value="local" <?php echo $target->target_type === 'local' ? 'selected' : ''; ?>>Local Directory</option>
                                                            <option value="rsync" <?php echo $target->target_type === 'rsync' ? 'selected' : ''; ?>>Remote (rsync/SSH)</option>
                                                            <option value="sftp" <?php echo $target->target_type === 'sftp' ? 'selected' : ''; ?>>SFTP</option>
                                                            <option value="s3" <?php echo $target->target_type === 's3' ? 'selected' : ''; ?>>Amazon S3</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label"><?php echo __('Path / Bucket'); ?></label>
                                                        <input type="text" name="path" class="form-control" value="<?php echo htmlspecialchars($config['path'] ?? ''); ?>" placeholder="/var/backups/atom">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label"><?php echo __('Host'); ?> <small class="text-muted">(for remote targets)</small></label>
                                                        <input type="text" name="host" class="form-control" value="<?php echo htmlspecialchars($config['host'] ?? ''); ?>" placeholder="backup.server.com">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label"><?php echo __('Port'); ?></label>
                                                        <input type="number" name="port" class="form-control" value="<?php echo htmlspecialchars($config['port'] ?? 22); ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label"><?php echo __('Description'); ?></label>
                                                        <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($target->description ?? ''); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
                                                    <button type="submit" class="btn btn-primary"><?php echo __('Save Changes'); ?></button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Sync Logs & CLI Commands -->
        <div class="col-lg-4">
            <!-- Recent Logs -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-2"></i><?php echo __('Recent Sync Logs'); ?>
                </div>
                <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                    <?php if (empty($recentLogs)): ?>
                    <div class="list-group-item text-center text-muted">
                        <?php echo __('No sync logs yet'); ?>
                    </div>
                    <?php else: ?>
                        <?php foreach ($recentLogs as $log): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <strong><?php echo htmlspecialchars($log->target_name); ?></strong>
                                <?php if ($log->status === 'completed'): ?>
                                    <span class="badge bg-success"><?php echo __('OK'); ?></span>
                                <?php elseif ($log->status === 'failed'): ?>
                                    <span class="badge bg-danger"><?php echo __('Failed'); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-info"><?php echo ucfirst($log->status); ?></span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">
                                <?php echo date('Y-m-d H:i', strtotime($log->started_at)); ?>
                                <?php if ($log->files_synced): ?>
                                - <?php echo number_format($log->files_synced); ?> files
                                <?php endif; ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- CLI Commands -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-terminal me-2"></i><?php echo __('CLI Commands'); ?>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2"><?php echo __('Run backups from command line:'); ?></p>
                    <pre class="bg-dark text-light p-2 rounded small mb-0"><code># List targets
php symfony preservation:replicate --list

# Run sync (all targets)
php symfony preservation:replicate

# Dry run preview
php symfony preservation:replicate --dry-run

# Verify backups
php symfony preservation:verify-backup --status</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Target Modal -->
<div class="modal fade" id="addTargetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action_type" value="add_target">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i><?php echo __('Add Replication Target'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Name'); ?> *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., offsite-backup" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Type'); ?> *</label>
                        <select name="target_type" class="form-select" id="targetType" required onchange="toggleFields()">
                            <option value="local">Local Directory</option>
                            <option value="rsync">Remote (rsync/SSH)</option>
                            <option value="sftp">SFTP</option>
                            <option value="s3">Amazon S3</option>
                        </select>
                    </div>
                    <div class="mb-3" id="pathField">
                        <label class="form-label"><?php echo __('Path'); ?> *</label>
                        <input type="text" name="path" class="form-control" placeholder="/var/backups/atom">
                    </div>
                    <div class="mb-3 remote-field" style="display:none;">
                        <label class="form-label"><?php echo __('Host'); ?></label>
                        <input type="text" name="host" class="form-control" placeholder="backup.server.com">
                    </div>
                    <div class="mb-3 remote-field" style="display:none;">
                        <label class="form-label"><?php echo __('Port'); ?></label>
                        <input type="number" name="port" class="form-control" value="22">
                    </div>
                    <div class="mb-3 remote-field" style="display:none;">
                        <label class="form-label"><?php echo __('User'); ?></label>
                        <input type="text" name="user" class="form-control" placeholder="backup">
                    </div>
                    <div class="mb-3 s3-field" style="display:none;">
                        <label class="form-label"><?php echo __('Bucket'); ?></label>
                        <input type="text" name="bucket" class="form-control" placeholder="my-archive-bucket">
                    </div>
                    <div class="mb-3 s3-field" style="display:none;">
                        <label class="form-label"><?php echo __('Region'); ?></label>
                        <input type="text" name="region" class="form-control" placeholder="af-south-1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Description'); ?></label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
                    <button type="submit" class="btn btn-success"><?php echo __('Add Target'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleFields() {
    var type = document.getElementById('targetType').value;
    var remoteFields = document.querySelectorAll('.remote-field');
    var s3Fields = document.querySelectorAll('.s3-field');
    var pathField = document.getElementById('pathField');

    // Hide all
    remoteFields.forEach(f => f.style.display = 'none');
    s3Fields.forEach(f => f.style.display = 'none');

    if (type === 'rsync' || type === 'sftp') {
        remoteFields.forEach(f => f.style.display = 'block');
        pathField.style.display = 'block';
    } else if (type === 's3') {
        s3Fields.forEach(f => f.style.display = 'block');
        pathField.style.display = 'none';
    } else {
        pathField.style.display = 'block';
    }
}
</script>

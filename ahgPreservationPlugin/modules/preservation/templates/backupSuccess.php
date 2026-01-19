<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-cloud-arrow-up text-info me-2"></i><?php echo __('Backup & Replication'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<!-- Quick Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i><?php echo __('Back to Dashboard'); ?>
        </a>
    </div>
</div>

<!-- Verification Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('Verified'); ?></h6>
                        <h2 class="mb-0"><?php echo number_format($verificationStats['passed'] ?? 0); ?></h2>
                    </div>
                    <i class="bi bi-patch-check fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('Failed'); ?></h6>
                        <h2 class="mb-0"><?php echo number_format($verificationStats['failed'] ?? 0); ?></h2>
                    </div>
                    <i class="bi bi-x-octagon fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('Warnings'); ?></h6>
                        <h2 class="mb-0"><?php echo number_format($verificationStats['warning'] ?? 0); ?></h2>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('Replication Targets'); ?></h6>
                        <h2 class="mb-0"><?php echo count($replicationTargets ?? []); ?></h2>
                    </div>
                    <i class="bi bi-hdd-network fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Replication Targets -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-hdd-network me-2"></i><?php echo __('Replication Targets'); ?>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Name'); ?></th>
                            <th><?php echo __('Type'); ?></th>
                            <th><?php echo __('Status'); ?></th>
                            <th><?php echo __('Path'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($replicationTargets)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">
                                <?php echo __('No replication targets configured'); ?>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($replicationTargets as $target): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($target->name); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($target->target_type); ?></span></td>
                                <td>
                                    <?php if ($target->is_active): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?php echo htmlspecialchars(substr($target->target_path, 0, 30)); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Replications -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-arrow-repeat me-2"></i><?php echo __('Recent Replications'); ?>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Target'); ?></th>
                            <th><?php echo __('Status'); ?></th>
                            <th><?php echo __('Files'); ?></th>
                            <th><?php echo __('Started'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentReplications)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">
                                <?php echo __('No replication logs yet'); ?>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recentReplications as $rep): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rep->target_name ?? 'Unknown'); ?></td>
                                <td>
                                    <?php if ($rep->status === 'completed'): ?>
                                        <span class="badge bg-success">Completed</span>
                                    <?php elseif ($rep->status === 'running'): ?>
                                        <span class="badge bg-info">Running</span>
                                    <?php elseif ($rep->status === 'failed'): ?>
                                        <span class="badge bg-danger">Failed</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo ucfirst($rep->status); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($rep->files_synced ?? 0); ?></td>
                                <td><small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($rep->started_at)); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- CLI Commands -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-terminal me-2"></i><?php echo __('CLI Commands'); ?>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Backup Verification</h6>
                <pre class="bg-dark text-light p-3 rounded"><code># Show verification status
php symfony preservation:verify-backup --status

# Verify all backups in directory
php symfony preservation:verify-backup --backup-dir=/var/backups/atom

# Verify specific backup file
php symfony preservation:verify-backup --path=/backup.tar.gz</code></pre>
            </div>
            <div class="col-md-6">
                <h6>Replication</h6>
                <pre class="bg-dark text-light p-3 rounded"><code># List replication targets
php symfony preservation:replicate --list

# Sync all active targets
php symfony preservation:replicate

# Sync specific target
php symfony preservation:replicate --target=offsite

# Preview sync (dry run)
php symfony preservation:replicate --dry-run</code></pre>
            </div>
        </div>
    </div>
</div>

<!-- Recent Verifications Table -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-list-check me-2"></i><?php echo __('Recent Backup Verifications'); ?>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('Backup'); ?></th>
                    <th><?php echo __('Type'); ?></th>
                    <th><?php echo __('Status'); ?></th>
                    <th><?php echo __('Size'); ?></th>
                    <th><?php echo __('Verified'); ?></th>
                    <th><?php echo __('By'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentVerifications)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">
                        <?php echo __('No backup verifications performed yet'); ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($recentVerifications as $v): ?>
                    <tr>
                        <td>
                            <small><?php echo htmlspecialchars(basename($v->backup_path)); ?></small>
                        </td>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($v->backup_type ?? 'full'); ?></span></td>
                        <td>
                            <?php if ($v->status === 'passed'): ?>
                                <span class="badge bg-success">Passed</span>
                            <?php elseif ($v->status === 'failed'): ?>
                                <span class="badge bg-danger">Failed</span>
                            <?php elseif ($v->status === 'warning'): ?>
                                <span class="badge bg-warning">Warning</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo ucfirst($v->status); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo number_format($v->file_size ?? 0); ?> bytes</small></td>
                        <td><small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($v->verified_at)); ?></small></td>
                        <td><small><?php echo htmlspecialchars($v->verified_by ?? 'system'); ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php end_slot() ?>

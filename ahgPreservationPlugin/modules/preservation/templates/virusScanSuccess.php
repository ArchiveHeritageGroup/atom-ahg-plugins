<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-shield-exclamation text-danger me-2"></i><?php echo __('Virus Scanning'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<!-- ClamAV Status -->
<div class="alert <?php echo $clamAvAvailable ? 'alert-success' : 'alert-warning'; ?> mb-4">
    <div class="d-flex align-items-center">
        <i class="bi <?php echo $clamAvAvailable ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?> fs-3 me-3"></i>
        <div class="flex-grow-1">
            <?php if ($clamAvAvailable): ?>
                <strong>ClamAV is installed and available</strong>
                <?php if ($clamAvVersion): ?>
                <br><small class="text-muted">
                    Scanner: <?php echo htmlspecialchars($clamAvVersion['scanner']); ?> |
                    Version: <?php echo htmlspecialchars($clamAvVersion['version']); ?> |
                    Database: <?php echo htmlspecialchars($clamAvVersion['database']); ?>
                </small>
                <?php endif; ?>
            <?php else: ?>
                <strong>ClamAV is not installed</strong>
                <br><small>Install with: <code>sudo apt install clamav clamav-daemon && sudo freshclam</code></small>
            <?php endif; ?>
        </div>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i><?php echo __('Back to Dashboard'); ?>
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('Clean'); ?></h6>
                        <h2 class="mb-0"><?php echo number_format($scanStats['clean'] ?? 0); ?></h2>
                    </div>
                    <i class="bi bi-check-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('Infected'); ?></h6>
                        <h2 class="mb-0"><?php echo number_format($scanStats['infected'] ?? 0); ?></h2>
                    </div>
                    <i class="bi bi-bug fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('Errors'); ?></h6>
                        <h2 class="mb-0"><?php echo number_format($scanStats['error'] ?? 0); ?></h2>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('Not Scanned'); ?></h6>
                        <h2 class="mb-0"><?php echo number_format($unscannedObjects ?? 0); ?></h2>
                    </div>
                    <i class="bi bi-question-circle fs-1 opacity-50"></i>
                </div>
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
        <p class="mb-2">Run virus scans from the command line:</p>
        <pre class="bg-dark text-light p-3 rounded mb-0"><code># Show ClamAV status
php symfony preservation:virus-scan --status

# Scan up to 100 new objects
php symfony preservation:virus-scan

# Scan specific object
php symfony preservation:virus-scan --object-id=123

# Scan 500 objects
php symfony preservation:virus-scan --limit=500</code></pre>
    </div>
</div>

<!-- Recent Scans Table -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-list-ul me-2"></i><?php echo __('Recent Virus Scans'); ?>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('File'); ?></th>
                    <th><?php echo __('Status'); ?></th>
                    <th><?php echo __('Threat'); ?></th>
                    <th><?php echo __('Scanner'); ?></th>
                    <th><?php echo __('Scanned'); ?></th>
                    <th><?php echo __('By'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentScans)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">
                        <?php echo __('No virus scans performed yet'); ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($recentScans as $scan): ?>
                    <tr>
                        <td>
                            <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'object', 'id' => $scan->digital_object_id]); ?>">
                                <?php echo htmlspecialchars(substr($scan->filename ?? 'Unknown', 0, 40)); ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($scan->status === 'clean'): ?>
                                <span class="badge bg-success">Clean</span>
                            <?php elseif ($scan->status === 'infected'): ?>
                                <span class="badge bg-danger">Infected</span>
                            <?php elseif ($scan->status === 'error'): ?>
                                <span class="badge bg-warning">Error</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo ucfirst($scan->status); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($scan->threat_name): ?>
                                <span class="text-danger"><?php echo htmlspecialchars($scan->threat_name); ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo htmlspecialchars($scan->scanner_name ?? 'unknown'); ?></small></td>
                        <td><small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($scan->scanned_at)); ?></small></td>
                        <td><small><?php echo htmlspecialchars($scan->scanned_by ?? 'system'); ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php end_slot() ?>

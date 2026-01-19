<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-list-check text-primary me-2"></i><?php echo __('Fixity Check Log'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<!-- Status Filter -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="btn-group">
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'fixityLog']); ?>"
           class="btn btn-<?php echo !$currentStatus ? 'primary' : 'outline-primary'; ?>">
            All (<?php echo $statusCounts['all']; ?>)
        </a>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'fixityLog', 'status' => 'pass']); ?>"
           class="btn btn-<?php echo $currentStatus === 'pass' ? 'success' : 'outline-success'; ?>">
            Pass (<?php echo $statusCounts['pass']; ?>)
        </a>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'fixityLog', 'status' => 'fail']); ?>"
           class="btn btn-<?php echo $currentStatus === 'fail' ? 'danger' : 'outline-danger'; ?>">
            Fail (<?php echo $statusCounts['fail']; ?>)
        </a>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'fixityLog', 'status' => 'error']); ?>"
           class="btn btn-<?php echo $currentStatus === 'error' ? 'warning' : 'outline-warning'; ?>">
            Error (<?php echo $statusCounts['error']; ?>)
        </a>
    </div>
    <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i><?php echo __('Back to Dashboard'); ?>
    </a>
</div>

<!-- Fixity Log Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('Object'); ?></th>
                    <th><?php echo __('File'); ?></th>
                    <th><?php echo __('Algorithm'); ?></th>
                    <th><?php echo __('Status'); ?></th>
                    <th><?php echo __('Checked By'); ?></th>
                    <th><?php echo __('Duration'); ?></th>
                    <th><?php echo __('Checked At'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($checks)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <?php echo __('No fixity checks found'); ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($checks as $check): ?>
                    <tr>
                        <td>
                            <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'object', 'id' => $check->digital_object_id]); ?>">
                                <?php echo htmlspecialchars(substr($check->object_title ?? 'Untitled', 0, 30)); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars(substr($check->filename ?? 'Unknown', 0, 25)); ?></td>
                        <td><code><?php echo strtoupper($check->algorithm); ?></code></td>
                        <td>
                            <?php if ($check->status === 'pass'): ?>
                                <span class="badge bg-success">Pass</span>
                            <?php elseif ($check->status === 'fail'): ?>
                                <span class="badge bg-danger" title="<?php echo htmlspecialchars($check->error_message ?? ''); ?>">Fail</span>
                            <?php elseif ($check->status === 'error'): ?>
                                <span class="badge bg-warning" title="<?php echo htmlspecialchars($check->error_message ?? ''); ?>">Error</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo ucfirst($check->status); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($check->checked_by); ?></td>
                        <td><?php echo $check->duration_ms; ?>ms</td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($check->checked_at)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php end_slot() ?>

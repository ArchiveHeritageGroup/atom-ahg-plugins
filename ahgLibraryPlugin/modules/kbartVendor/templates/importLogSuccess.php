<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('KBART Management'); ?></h4>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for(['module' => 'kbartVendor', 'action' => 'index']); ?>"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Vendors'); ?></a></li>
    </ul>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1>
    <i class="fas fa-history"></i>
    <?php echo __('KBART Import Log'); ?>
    <small class="text-muted">— <?php echo htmlspecialchars($vendor->name); ?></small>
</h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php if ($sf_user->hasFlash('notice')): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo $sf_user->getFlash('notice'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $sf_user->getFlash('error'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body py-2">
        <div class="row text-center">
            <div class="col-4">
                <span class="text-muted small"><?php echo __('Feed URL'); ?></span><br>
                <a href="<?php echo htmlspecialchars($vendor->feed_url); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo htmlspecialchars(\Qubit:: truncateUrl($vendor->feed_url, 50)); ?>
                    <i class="fas fa-external-link-alt ms-1"></i>
                </a>
            </div>
            <div class="col-4">
                <span class="text-muted small"><?php echo __('Status'); ?></span><br>
                <?php if ($vendor->active): ?>
                    <span class="badge bg-success"><?php echo __('Active'); ?></span>
                <?php else: ?>
                    <span class="badge bg-secondary"><?php echo __('Inactive'); ?></span>
                <?php endif; ?>
            </div>
            <div class="col-4">
                <span class="text-muted small"><?php echo __('Last Fetch'); ?></span><br>
                <strong><?php echo $vendor->last_fetch_at ? date('Y-m-d H:i:s', strtotime($vendor->last_fetch_at)) : '—'; ?></strong>
            </div>
        </div>
    </div>
</div>

<?php if (empty($logs)): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <?php echo __('No import logs yet. Click "Fetch Now" on the vendor list to trigger a fetch.'); ?>
</div>
<?php else: ?>
<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><?php echo __('Import History'); ?></h5>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th><?php echo __('Fetched At'); ?></th>
                    <th class="text-center"><?php echo __('Rows'); ?></th>
                    <th class="text-center"><?php echo __('New'); ?></th>
                    <th class="text-center"><?php echo __('Removed'); ?></th>
                    <th><?php echo __('Status'); ?></th>
                    <th><?php echo __('Error / Notes'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr class="<?php echo $log->error ? 'table-danger' : ''; ?>">
                    <td><small><?php echo date('Y-m-d H:i:s', strtotime($log->fetched_at)); ?></small></td>
                    <td class="text-center">
                        <?php if ($log->row_count > 0): ?>
                            <span class="badge bg-primary"><?php echo number_format($log->row_count); ?></span>
                        <?php else: ?>
                            <span class="text-muted">0</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($log->new_count > 0): ?>
                            <span class="badge bg-success"><?php echo number_format($log->new_count); ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($log->removed_count > 0): ?>
                            <span class="badge bg-warning text-dark"><?php echo number_format($log->removed_count); ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($log->error): ?>
                            <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i><?php echo __('Failed'); ?></span>
                        <?php else: ?>
                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i><?php echo __('OK'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($log->error): ?>
                            <small class="text-danger" title="<?php echo htmlspecialchars($log->error); ?>">
                                <?php echo htmlspecialchars(\Qubit:: truncate($log->error, 60)); ?>
                            </small>
                        <?php else: ?>
                            <span class="text-success">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php // Pagination ?>
<?php if ($totalPages > 1): ?>
<nav aria-label="Import log pagination" class="mt-3">
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?php echo url_for(['module' => 'kbartVendor', 'action' => 'importLog', 'id' => $vendor->id, 'page' => $page - 1]); ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        <?php endif; ?>
        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo url_for(['module' => 'kbartVendor', 'action' => 'importLog', 'id' => $vendor->id, 'page' => $p]); ?>">
                    <?php echo $p; ?>
                </a>
            </li>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="<?php echo url_for(['module' => 'kbartVendor', 'action' => 'importLog', 'id' => $vendor->id, 'page' => $page + 1]); ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?php endif; ?>
<?php end_slot(); ?>
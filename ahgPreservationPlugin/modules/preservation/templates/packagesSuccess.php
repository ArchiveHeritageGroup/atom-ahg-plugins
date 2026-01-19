<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-archive text-primary me-2"></i><?php echo __('OAIS Packages'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('Total Packages'); ?></h6>
                        <h2 class="mb-0"><?php echo number_format($stats['total_packages']); ?></h2>
                        <small><?php echo $stats['total_size_formatted']; ?></small>
                    </div>
                    <i class="bi bi-archive fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('SIPs'); ?></h6>
                        <h2 class="mb-0"><?php echo number_format($stats['by_type']['sip']['count'] ?? 0); ?></h2>
                        <small><?php echo __('Submission'); ?></small>
                    </div>
                    <i class="bi bi-box-arrow-in-right fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('AIPs'); ?></h6>
                        <h2 class="mb-0"><?php echo number_format($stats['by_type']['aip']['count'] ?? 0); ?></h2>
                        <small><?php echo __('Archival'); ?></small>
                    </div>
                    <i class="bi bi-safe fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('DIPs'); ?></h6>
                        <h2 class="mb-0"><?php echo number_format($stats['by_type']['dip']['count'] ?? 0); ?></h2>
                        <small><?php echo __('Dissemination'); ?></small>
                    </div>
                    <i class="bi bi-box-arrow-right fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Actions and Filters -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'packageEdit']); ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i><?php echo __('Create Package'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'index']); ?>" class="btn btn-outline-secondary ms-2">
            <i class="bi bi-arrow-left me-1"></i><?php echo __('Back to Dashboard'); ?>
        </a>
    </div>
    <div class="d-flex gap-2">
        <!-- Type Filter -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-funnel me-1"></i>
                <?php echo $currentType ? strtoupper($currentType) : __('All Types'); ?>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item <?php echo !$currentType ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'preservation', 'action' => 'packages']); ?>"><?php echo __('All Types'); ?></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item <?php echo 'sip' === $currentType ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'preservation', 'action' => 'packages', 'type' => 'sip']); ?>">SIP - Submission</a></li>
                <li><a class="dropdown-item <?php echo 'aip' === $currentType ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'preservation', 'action' => 'packages', 'type' => 'aip']); ?>">AIP - Archival</a></li>
                <li><a class="dropdown-item <?php echo 'dip' === $currentType ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'preservation', 'action' => 'packages', 'type' => 'dip']); ?>">DIP - Dissemination</a></li>
            </ul>
        </div>
        <!-- Status Filter -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <?php echo $currentStatus ? ucfirst($currentStatus) : __('All Statuses'); ?>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item <?php echo !$currentStatus ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'preservation', 'action' => 'packages', 'type' => $currentType]); ?>"><?php echo __('All Statuses'); ?></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'preservation', 'action' => 'packages', 'type' => $currentType, 'status' => 'draft']); ?>">Draft</a></li>
                <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'preservation', 'action' => 'packages', 'type' => $currentType, 'status' => 'complete']); ?>">Complete</a></li>
                <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'preservation', 'action' => 'packages', 'type' => $currentType, 'status' => 'validated']); ?>">Validated</a></li>
                <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'preservation', 'action' => 'packages', 'type' => $currentType, 'status' => 'exported']); ?>">Exported</a></li>
                <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'preservation', 'action' => 'packages', 'type' => $currentType, 'status' => 'error']); ?>">Error</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Packages Table -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-list-ul me-2"></i><?php echo __('Packages'); ?>
        <?php if ($currentType || $currentStatus): ?>
            <span class="badge bg-secondary ms-2">
                <?php
                $filters = [];
                if ($currentType) $filters[] = strtoupper($currentType);
                if ($currentStatus) $filters[] = ucfirst($currentStatus);
                echo implode(' / ', $filters);
                ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('Package'); ?></th>
                    <th><?php echo __('Type'); ?></th>
                    <th><?php echo __('Status'); ?></th>
                    <th><?php echo __('Objects'); ?></th>
                    <th><?php echo __('Size'); ?></th>
                    <th><?php echo __('Created'); ?></th>
                    <th class="text-end"><?php echo __('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($packages)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-archive fs-1 d-block mb-2 opacity-25"></i>
                        <?php echo __('No packages found.'); ?>
                        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'packageEdit']); ?>" class="d-block mt-2"><?php echo __('Create your first package'); ?></a>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($packages as $pkg): ?>
                    <tr>
                        <td>
                            <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'packageView', 'id' => $pkg->id]); ?>" class="fw-bold text-decoration-none">
                                <?php echo htmlspecialchars($pkg->name); ?>
                            </a>
                            <br>
                            <small class="text-muted font-monospace"><?php echo substr($pkg->uuid, 0, 8); ?>...</small>
                        </td>
                        <td>
                            <?php
                            $typeClass = ['sip' => 'info', 'aip' => 'success', 'dip' => 'warning'][$pkg->package_type] ?? 'secondary';
                            $typeIcon = ['sip' => 'box-arrow-in-right', 'aip' => 'safe', 'dip' => 'box-arrow-right'][$pkg->package_type] ?? 'archive';
                            ?>
                            <span class="badge bg-<?php echo $typeClass; ?>">
                                <i class="bi bi-<?php echo $typeIcon; ?> me-1"></i>
                                <?php echo strtoupper($pkg->package_type); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $statusClass = [
                                'draft' => 'secondary',
                                'building' => 'warning',
                                'complete' => 'info',
                                'validated' => 'primary',
                                'exported' => 'success',
                                'error' => 'danger'
                            ][$pkg->status] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($pkg->status); ?></span>
                        </td>
                        <td><?php echo number_format($pkg->object_count); ?></td>
                        <td><?php echo $pkg->total_size ? formatBytes($pkg->total_size) : '-'; ?></td>
                        <td>
                            <small><?php echo date('Y-m-d H:i', strtotime($pkg->created_at)); ?></small>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'packageView', 'id' => $pkg->id]); ?>" class="btn btn-outline-primary" title="<?php echo __('View'); ?>">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ('draft' === $pkg->status): ?>
                                <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'packageEdit', 'id' => $pkg->id]); ?>" class="btn btn-outline-secondary" title="<?php echo __('Edit'); ?>">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($pkg->export_path): ?>
                                <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'packageDownload', 'id' => $pkg->id]); ?>" class="btn btn-outline-success" title="<?php echo __('Download'); ?>">
                                    <i class="bi bi-download"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
function formatBytes($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' bytes';
}
?>

<?php end_slot() ?>

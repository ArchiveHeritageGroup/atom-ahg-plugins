<?php use_helper('Date') ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-link me-2"></i>DOI Management</h1>
            <p class="text-muted">Mint and manage DOIs via DataCite</p>
        </div>
        <div class="col-auto">
            <div class="btn-group me-2">
                <a href="<?php echo url_for(['module' => 'doi', 'action' => 'export', 'format' => 'csv']) ?>" class="btn btn-outline-primary" title="Export CSV">
                    <i class="fas fa-file-csv me-1"></i> Export
                </a>
                <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'doi', 'action' => 'export', 'format' => 'csv']) ?>"><i class="fas fa-file-csv me-2"></i>Export as CSV</a></li>
                    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'doi', 'action' => 'export', 'format' => 'json']) ?>"><i class="fas fa-file-code me-2"></i>Export as JSON</a></li>
                </ul>
            </div>
            <a href="<?php echo url_for(['module' => 'doi', 'action' => 'sync']) ?>" class="btn btn-outline-secondary me-2">
                <i class="fas fa-sync me-1"></i> Bulk Sync
            </a>
            <a href="<?php echo url_for(['module' => 'doi', 'action' => 'batchMint']) ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Batch Mint
            </a>
            <a href="<?php echo url_for(['module' => 'doi', 'action' => 'config']) ?>" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-cog me-1"></i> Configuration
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3><?php echo $stats['total'] ?? 0 ?></h3>
                    <p class="mb-0">Total DOIs</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?php echo $stats['by_status']['findable'] ?? 0 ?></h3>
                    <p class="mb-0">Findable</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3><?php echo $stats['by_status']['registered'] ?? 0 ?></h3>
                    <p class="mb-0">Registered</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h3><?php echo $stats['by_status']['draft'] ?? 0 ?></h3>
                    <p class="mb-0">Draft</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3><?php echo $stats['queue_pending'] ?? 0 ?></h3>
                    <p class="mb-0">Queue Pending</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h3><?php echo ($stats['by_status']['failed'] ?? 0) + ($stats['queue_failed'] ?? 0) ?></h3>
                    <p class="mb-0">Failed</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-list me-2"></i>Browse DOIs</h5>
                    <p class="card-text">View and manage all minted DOIs.</p>
                    <a href="<?php echo url_for(['module' => 'doi', 'action' => 'browse']) ?>" class="btn btn-outline-primary">Browse</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-tasks me-2"></i>Queue</h5>
                    <p class="card-text">View pending minting operations.</p>
                    <a href="<?php echo url_for(['module' => 'doi', 'action' => 'queue']) ?>" class="btn btn-outline-primary">View Queue</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-chart-bar me-2"></i>Reports</h5>
                    <p class="card-text">DOI statistics and reports.</p>
                    <a href="<?php echo url_for(['module' => 'doi', 'action' => 'report']) ?>" class="btn btn-outline-primary">View Reports</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-cog me-2"></i>Configuration</h5>
                    <p class="card-text">DataCite API settings.</p>
                    <a href="<?php echo url_for(['module' => 'doi', 'action' => 'config']) ?>" class="btn btn-outline-primary">Configure</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent DOIs -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recently Minted DOIs</h5>
            <a href="<?php echo url_for(['module' => 'doi', 'action' => 'browse']) ?>" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body p-0">
            <?php if ($recentDois->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-link fa-3x mb-3"></i>
                    <p>No DOIs minted yet.</p>
                    <a href="<?php echo url_for(['module' => 'doi', 'action' => 'batchMint']) ?>" class="btn btn-primary">Mint Your First DOI</a>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>DOI</th>
                            <th>Record</th>
                            <th>Status</th>
                            <th>Minted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentDois as $doi): ?>
                            <tr>
                                <td>
                                    <a href="https://doi.org/<?php echo htmlspecialchars($doi->doi) ?>" target="_blank" class="text-monospace">
                                        <?php echo htmlspecialchars($doi->doi) ?>
                                        <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                                    </a>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($doi->object_title ?? 'Untitled') ?>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'findable' => 'success',
                                        'registered' => 'info',
                                        'draft' => 'secondary',
                                        'failed' => 'danger',
                                    ];
                                    $color = $statusColors[$doi->status] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $color ?>"><?php echo htmlspecialchars($doi->status) ?></span>
                                </td>
                                <td>
                                    <?php echo $doi->minted_at ? date('Y-m-d H:i', strtotime($doi->minted_at)) : '-' ?>
                                </td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'doi', 'action' => 'view', 'id' => $doi->id]) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            <?php endif ?>
        </div>
    </div>
</div>

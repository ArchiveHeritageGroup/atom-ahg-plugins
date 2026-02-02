<?php use_helper('Date') ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-list me-2"></i>Browse DOIs</h1>
            <p class="text-muted">View and manage all minted DOIs</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'doi', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Dashboard
            </a>
            <a href="<?php echo url_for(['module' => 'doi', 'action' => 'export', 'format' => 'csv', 'status' => $currentStatus]) ?>" class="btn btn-outline-primary">
                <i class="fas fa-download me-1"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="<?php echo url_for(['module' => 'doi', 'action' => 'browse']) ?>" class="row g-3">
                <div class="col-auto">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="findable" <?php echo $currentStatus === 'findable' ? 'selected' : '' ?>>Findable</option>
                        <option value="registered" <?php echo $currentStatus === 'registered' ? 'selected' : '' ?>>Registered</option>
                        <option value="draft" <?php echo $currentStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="deleted" <?php echo $currentStatus === 'deleted' ? 'selected' : '' ?>>Deleted</option>
                    </select>
                </div>
                <div class="col-auto d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DOI List -->
    <div class="card">
        <div class="card-body p-0">
            <?php if ($dois->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-link fa-3x mb-3"></i>
                    <p>No DOIs found matching the criteria.</p>
                    <a href="<?php echo url_for(['module' => 'doi', 'action' => 'batchMint']) ?>" class="btn btn-primary">Mint DOIs</a>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>DOI</th>
                            <th>Record</th>
                            <th>Status</th>
                            <th>Minted</th>
                            <th>Last Sync</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dois as $doi): ?>
                            <tr>
                                <td>
                                    <a href="https://doi.org/<?php echo htmlspecialchars($doi->doi) ?>" target="_blank" class="text-monospace">
                                        <?php echo htmlspecialchars($doi->doi) ?>
                                        <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($doi->slug): ?>
                                        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $doi->slug]) ?>">
                                            <?php echo htmlspecialchars($doi->object_title ?? 'Untitled') ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($doi->object_title ?? 'Untitled') ?>
                                    <?php endif ?>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'findable' => 'success',
                                        'registered' => 'info',
                                        'draft' => 'secondary',
                                        'deleted' => 'danger',
                                    ];
                                    $color = $statusColors[$doi->status] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $color ?>"><?php echo htmlspecialchars($doi->status) ?></span>
                                </td>
                                <td>
                                    <?php echo $doi->minted_at ? date('Y-m-d', strtotime($doi->minted_at)) : '-' ?>
                                </td>
                                <td>
                                    <?php echo $doi->last_sync_at ? date('Y-m-d', strtotime($doi->last_sync_at)) : '-' ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo url_for(['module' => 'doi', 'action' => 'view', 'id' => $doi->id]) ?>" class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo url_for(['module' => 'doi', 'action' => 'update', 'id' => $doi->id]) ?>" class="btn btn-outline-secondary" title="Sync">
                                            <i class="fas fa-sync"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>

                <?php if (method_exists($dois, 'links')): ?>
                    <div class="card-footer">
                        <?php echo $dois->links() ?>
                    </div>
                <?php endif ?>
            <?php endif ?>
        </div>
    </div>
</div>

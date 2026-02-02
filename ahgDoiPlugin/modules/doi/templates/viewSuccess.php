<?php use_helper('Date') ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-link me-2"></i>DOI Details</h1>
            <p class="text-muted">View and manage DOI record</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'doi', 'action' => 'browse']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Browse
            </a>
        </div>
    </div>

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

    <div class="row">
        <div class="col-lg-8">
            <!-- DOI Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">DOI Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">DOI</dt>
                        <dd class="col-sm-9">
                            <a href="https://doi.org/<?php echo htmlspecialchars($doi->doi) ?>" target="_blank" class="text-monospace">
                                <?php echo htmlspecialchars($doi->doi) ?>
                                <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                            </a>
                        </dd>

                        <dt class="col-sm-3">Record</dt>
                        <dd class="col-sm-9">
                            <?php if ($doi->slug): ?>
                                <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $doi->slug]) ?>">
                                    <?php echo htmlspecialchars($doi->object_title ?? 'Untitled') ?>
                                </a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($doi->object_title ?? 'Untitled') ?>
                            <?php endif ?>
                        </dd>

                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9">
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
                        </dd>

                        <dt class="col-sm-3">Minted At</dt>
                        <dd class="col-sm-9"><?php echo $doi->minted_at ? date('Y-m-d H:i:s', strtotime($doi->minted_at)) : '-' ?></dd>

                        <dt class="col-sm-3">Last Sync</dt>
                        <dd class="col-sm-9"><?php echo $doi->last_sync_at ? date('Y-m-d H:i:s', strtotime($doi->last_sync_at)) : '-' ?></dd>

                        <?php if ($doi->status === 'deleted'): ?>
                            <dt class="col-sm-3">Deactivated At</dt>
                            <dd class="col-sm-9"><?php echo $doi->deactivated_at ? date('Y-m-d H:i:s', strtotime($doi->deactivated_at)) : '-' ?></dd>

                            <?php if ($doi->deactivation_reason): ?>
                                <dt class="col-sm-3">Reason</dt>
                                <dd class="col-sm-9"><?php echo htmlspecialchars($doi->deactivation_reason) ?></dd>
                            <?php endif ?>
                        <?php endif ?>
                    </dl>
                </div>
            </div>

            <!-- Metadata -->
            <?php if ($doi->metadata_json): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">DataCite Metadata</h5>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0" style="max-height: 400px; overflow: auto;"><code><?php echo htmlspecialchars(json_encode(json_decode($doi->metadata_json), JSON_PRETTY_PRINT)) ?></code></pre>
                    </div>
                </div>
            <?php endif ?>

            <!-- Activity Log -->
            <?php if (!$logs->isEmpty()): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Activity Log</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Status Change</th>
                                    <th>Date</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log->action) ?></td>
                                        <td>
                                            <?php if ($log->status_before || $log->status_after): ?>
                                                <?php echo htmlspecialchars($log->status_before ?? '-') ?> → <?php echo htmlspecialchars($log->status_after ?? '-') ?>
                                            <?php else: ?>
                                                -
                                            <?php endif ?>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($log->performed_at)) ?></td>
                                        <td>
                                            <?php if ($log->details): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($log->details, 0, 100)) ?></small>
                                            <?php endif ?>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif ?>
        </div>

        <div class="col-lg-4">
            <!-- Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo url_for(['module' => 'doi', 'action' => 'verify', 'id' => $doi->id]) ?>" class="btn btn-outline-primary">
                            <i class="fas fa-check-circle me-1"></i> Verify Resolution
                        </a>

                        <?php if ($doi->status !== 'deleted'): ?>
                            <a href="<?php echo url_for(['module' => 'doi', 'action' => 'update', 'id' => $doi->id]) ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-sync me-1"></i> Sync Metadata
                            </a>
                            <a href="<?php echo url_for(['module' => 'doi', 'action' => 'deactivate', 'id' => $doi->id]) ?>" class="btn btn-outline-danger">
                                <i class="fas fa-ban me-1"></i> Deactivate DOI
                            </a>
                        <?php else: ?>
                            <a href="<?php echo url_for(['module' => 'doi', 'action' => 'reactivate', 'id' => $doi->id]) ?>" class="btn btn-success">
                                <i class="fas fa-redo me-1"></i> Reactivate DOI
                            </a>
                        <?php endif ?>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Links</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <a href="https://doi.org/<?php echo htmlspecialchars($doi->doi) ?>" target="_blank">
                                <i class="fas fa-external-link-alt me-1"></i> doi.org landing page
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="https://api.datacite.org/dois/<?php echo urlencode($doi->doi) ?>" target="_blank">
                                <i class="fas fa-code me-1"></i> DataCite API record
                            </a>
                        </li>
                        <?php if ($doi->slug): ?>
                            <li>
                                <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $doi->slug]) ?>">
                                    <i class="fas fa-file-alt me-1"></i> View AtoM record
                                </a>
                            </li>
                        <?php endif ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

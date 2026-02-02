<?php use_helper('Date') ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-tasks me-2"></i>DOI Queue</h1>
            <p class="text-muted">Manage pending DOI operations</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'doi', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $sf_user->getFlash('notice') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>

    <!-- Queue Status Summary -->
    <?php
    $pending = $queue->where('status', 'pending')->count();
    $processing = $queue->where('status', 'processing')->count();
    $failed = $queue->where('status', 'failed')->count();
    $completed = $queue->where('status', 'completed')->count();
    ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3><?php echo $pending ?></h3>
                    <p class="mb-0">Pending</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3><?php echo $processing ?></h3>
                    <p class="mb-0">Processing</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h3><?php echo $failed ?></h3>
                    <p class="mb-0">Failed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?php echo $completed ?></h3>
                    <p class="mb-0">Completed</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CLI Command Help -->
    <div class="alert alert-info">
        <i class="fas fa-terminal me-2"></i>
        <strong>Process queue via CLI:</strong>
        <code class="ms-2">php symfony doi:process-queue</code>
    </div>

    <!-- Queue List -->
    <div class="card">
        <div class="card-body p-0">
            <?php if ($queue->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-tasks fa-3x mb-3"></i>
                    <p>The queue is empty.</p>
                    <a href="<?php echo url_for(['module' => 'doi', 'action' => 'batchMint']) ?>" class="btn btn-primary">Queue Records for Minting</a>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Record</th>
                            <th>Action</th>
                            <th>Status</th>
                            <th>Attempts</th>
                            <th>Scheduled</th>
                            <th>Error</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($queue as $item): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($item->object_title ?? "Object #{$item->information_object_id}") ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($item->action) ?></span>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                    ];
                                    $color = $statusColors[$item->status] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $color ?>"><?php echo htmlspecialchars($item->status) ?></span>
                                </td>
                                <td>
                                    <?php echo $item->attempts ?>/<?php echo $item->max_attempts ?? 3 ?>
                                </td>
                                <td>
                                    <?php echo $item->scheduled_at ? date('Y-m-d H:i', strtotime($item->scheduled_at)) : '-' ?>
                                </td>
                                <td>
                                    <?php if ($item->last_error): ?>
                                        <small class="text-danger" title="<?php echo htmlspecialchars($item->last_error) ?>">
                                            <?php echo htmlspecialchars(substr($item->last_error, 0, 50)) ?>...
                                        </small>
                                    <?php else: ?>
                                        -
                                    <?php endif ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($item->status === 'failed'): ?>
                                        <a href="<?php echo url_for(['module' => 'doi', 'action' => 'queueRetry', 'id' => $item->id]) ?>"
                                           class="btn btn-sm btn-outline-primary"
                                           title="Retry">
                                            <i class="fas fa-redo"></i>
                                        </a>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            <?php endif ?>
        </div>
    </div>
</div>

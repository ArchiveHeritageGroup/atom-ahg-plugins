<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-clock-rotate-left me-2"></i>Scan history: <?php echo htmlspecialchars($folder->code) ?></h1>
            <p class="text-muted"><code><?php echo htmlspecialchars($folder->path) ?></code></p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'scanManage', 'action' => 'run', 'id' => $folder->id]) ?>" class="btn btn-success">
                <i class="fas fa-play me-1"></i> Scan now
            </a>
            <a href="<?php echo url_for(['module' => 'scanManage', 'action' => 'index']) ?>" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <?php if (empty($events)): ?>
        <div class="card"><div class="card-body text-center text-muted py-5">No scan passes recorded yet.</div></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead>
                    <tr>
                        <th>When</th><th>Status</th><th>Detected</th><th>Enqueued</th>
                        <th>Duplicate</th><th>Quiet</th><th>Failed</th><th>Job</th><th>Message</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($events as $e): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($e->created_at) ?></td>
                        <td>
                            <?php
                            $cls = $e->status === 'failed' ? 'danger' : ($e->status === 'idle' ? 'secondary' : 'success');
                            ?>
                            <span class="badge bg-<?php echo $cls ?>"><?php echo htmlspecialchars($e->status) ?></span>
                        </td>
                        <td><?php echo (int) $e->detected ?></td>
                        <td><?php echo (int) $e->enqueued ?></td>
                        <td><?php echo (int) $e->skipped_duplicate ?></td>
                        <td><?php echo (int) $e->skipped_quiet ?></td>
                        <td><?php echo (int) $e->failed ?></td>
                        <td><?php echo $e->job_id ? '#' . (int) $e->job_id : '-' ?></td>
                        <td><small class="text-muted"><?php echo $e->message ? htmlspecialchars($e->message) : '' ?></small></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</div>

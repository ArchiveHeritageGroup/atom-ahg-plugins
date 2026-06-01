<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-folder-tree me-2"></i>Watched Folders</h1>
            <p class="text-muted">Streaming ingest: drop files into a watched folder and they are detected, deduplicated and ingested automatically.</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'scanManage', 'action' => 'new']) ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New Folder
            </a>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-terminal me-1"></i>
        Run the watcher from cron or systemd:
        <code>php bin/atom scan:watch --once</code> (per minute) or
        <code>php bin/atom scan:watch --interval=30</code> (continuous).
    </div>

    <?php if (empty($folders)): ?>
        <div class="card"><div class="card-body text-center text-muted py-5">
            No watched folders configured yet.
        </div></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Path</th>
                        <th>Layout</th>
                        <th>Sector / Standard</th>
                        <th>Last scan</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($folders as $f): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($f->code) ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($f->label) ?></small></td>
                        <td><code><?php echo htmlspecialchars($f->path) ?></code></td>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($f->layout) ?></span></td>
                        <td><?php echo htmlspecialchars(($f->sector ?? '-') . ' / ' . ($f->standard ?? '-')) ?></td>
                        <td><?php echo $f->last_scanned_at ? htmlspecialchars($f->last_scanned_at) : '<span class="text-muted">never</span>' ?></td>
                        <td>
                            <?php if ($f->enabled): ?>
                                <span class="badge bg-success">enabled</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">disabled</span>
                            <?php endif ?>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo url_for(['module' => 'scanManage', 'action' => 'edit', 'id' => $f->id]) ?>"><i class="fas fa-pen"></i></a>
                            <a class="btn btn-sm btn-outline-success" href="<?php echo url_for(['module' => 'scanManage', 'action' => 'run', 'id' => $f->id]) ?>" title="Scan now"><i class="fas fa-play"></i></a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo url_for(['module' => 'scanManage', 'action' => 'history', 'id' => $f->id]) ?>" title="History"><i class="fas fa-clock-rotate-left"></i></a>
                            <a class="btn btn-sm btn-outline-warning" href="<?php echo url_for(['module' => 'scanManage', 'action' => 'toggle', 'id' => $f->id]) ?>" title="Toggle"><i class="fas fa-power-off"></i></a>
                            <a class="btn btn-sm btn-outline-danger" href="<?php echo url_for(['module' => 'scanManage', 'action' => 'delete', 'id' => $f->id]) ?>" onclick="return confirm('Remove this watched folder? Ingest history is retained.');" title="Delete"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</div>

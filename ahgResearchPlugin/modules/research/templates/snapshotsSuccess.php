<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">Snapshots</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Snapshots</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSnapshotModal"><i class="fas fa-camera me-1"></i> Create Snapshot</button>
</div>

<?php if (empty($snapshots)): ?>
    <div class="alert alert-info">No snapshots yet. Create one to freeze the current state of a collection.</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover">
        <thead><tr><th>Title</th><th>Items</th><th>Hash</th><th>Status</th><th>Created</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($snapshots as $s): ?>
            <tr>
                <td><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewSnapshot', 'id' => $s->id]); ?>"><?php echo htmlspecialchars($s->title); ?></a></td>
                <td><?php echo (int) $s->item_count; ?></td>
                <td><code class="small"><?php echo substr($s->hash_sha256 ?? '', 0, 12); ?>...</code></td>
                <td><span class="badge bg-<?php echo $s->status === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($s->status); ?></span></td>
                <td><?php echo $s->created_at; ?></td>
                <td><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewSnapshot', 'id' => $s->id]); ?>" class="btn btn-sm btn-outline-primary">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="modal fade" id="createSnapshotModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'createSnapshot', 'project_id' => $project->id]); ?>">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Create Snapshot</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                    <div class="mb-3"><label class="form-label">Collection ID (optional - freezes collection)</label><input type="number" name="collection_id" class="form-control"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Create</button></div>
            </div>
        </form>
    </div>
</div>

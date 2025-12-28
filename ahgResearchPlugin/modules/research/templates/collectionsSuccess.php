<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'workspace']); ?>">Workspace</a></li>
        <li class="breadcrumb-item active">Collections</li>
    </ol>
</nav>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0"><i class="fas fa-folder text-primary me-2"></i>My Collections</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-1"></i> New Collection</button>
</div>
<?php if ($sf_user->hasFlash('success')): ?><div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div><?php endif; ?>
<div class="row">
    <?php if (!empty($collections)): ?>
        <?php foreach ($collections as $c): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $c->name; ?></h5>
                        <p class="card-text text-muted"><?php echo $c->description ?: 'No description'; ?></p>
                        <p class="mb-0"><span class="badge bg-primary"><?php echo $c->item_count; ?> items</span><?php if ($c->is_public): ?> <span class="badge bg-success">Public</span><?php endif; ?></p>
                    </div>
                    <div class="card-footer"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewCollection', 'id' => $c->id]); ?>" class="btn btn-sm btn-outline-primary">View Collection</a></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12"><div class="card"><div class="card-body text-center text-muted"><i class="fas fa-folder-open fa-3x mb-3"></i><p>No collections yet. Create one to organize your research materials.</p></div></div></div>
    <?php endif; ?>
</div>
<div class="modal fade" id="createModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="post"><input type="hidden" name="do" value="create">
        <div class="modal-header"><h5 class="modal-title">New Collection</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
            <div class="form-check"><input type="checkbox" name="is_public" value="1" class="form-check-input" id="isPublic"><label class="form-check-label" for="isPublic">Make public</label></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Create</button></div>
    </form>
</div></div></div>

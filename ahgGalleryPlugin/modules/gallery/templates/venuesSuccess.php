<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'dashboard']); ?>">Gallery</a></li>
        <li class="breadcrumb-item active">Venues</li>
    </ol>
</nav>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0"><i class="fas fa-building text-primary me-2"></i>Venues & Spaces</h1>
    <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'createVenue']); ?>" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Venue</a>
</div>
<?php if ($sf_user->hasFlash('success')): ?><div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div><?php endif; ?>
<div class="row">
    <?php foreach ($venues as $v): ?>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo $v->name; ?></h5>
                    <?php if ($v->is_active): ?><span class="badge bg-success">Active</span><?php else: ?><span class="badge bg-secondary">Inactive</span><?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($v->description): ?><p><?php echo $v->description; ?></p><?php endif; ?>
                    <table class="table table-sm table-borderless mb-3">
                        <tr><th width="120">Address:</th><td><?php echo $v->address ?: '-'; ?></td></tr>
                        <tr><th>Area:</th><td><?php echo $v->total_area_sqm ? $v->total_area_sqm . ' m²' : '-'; ?></td></tr>
                        <tr><th>Climate:</th><td><?php echo $v->climate_controlled ? '<i class="fas fa-check text-success"></i> Controlled' : '<i class="fas fa-times text-muted"></i> No'; ?></td></tr>
                    </table>
                    <h6>Spaces (<?php echo count($v->spaces); ?>)</h6>
                    <?php if (!empty($v->spaces)): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($v->spaces as $s): ?>
                                <li class="list-group-item px-0 d-flex justify-content-between">
                                    <span><?php echo $s->name; ?></span>
                                    <small class="text-muted"><?php echo $s->area_sqm ? $s->area_sqm . ' m²' : ''; ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0">No spaces defined</p>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'viewVenue', 'id' => $v->id]); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i> Manage</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($venues)): ?>
        <div class="col-12"><div class="alert alert-info">No venues. <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'createVenue']); ?>">Create one</a>.</div></div>
    <?php endif; ?>
</div>

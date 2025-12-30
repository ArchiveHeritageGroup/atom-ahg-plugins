<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'dashboard']); ?>">Gallery</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'venues']); ?>">Venues</a></li>
        <li class="breadcrumb-item active"><?php echo $venue->name; ?></li>
    </ol>
</nav>
<?php if ($sf_user->hasFlash('success')): ?><div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div><?php endif; ?>
<h1 class="h2 mb-4"><i class="fas fa-building text-primary me-2"></i><?php echo $venue->name; ?> <?php if (!$venue->is_active): ?><span class="badge bg-secondary">Inactive</span><?php endif; ?></h1>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Venue</h5></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="do" value="update">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" value="<?php echo $venue->name; ?>" required></div>
                            <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"><?php echo $venue->description; ?></textarea></div>
                            <div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?php echo $venue->address; ?></textarea></div>
                        </div>
                        <div class="col-md-6">
                            <div class="row mb-3">
                                <div class="col-6"><label class="form-label">Area (m²)</label><input type="number" name="total_area_sqm" class="form-control" step="0.01" value="<?php echo $venue->total_area_sqm; ?>"></div>
                                <div class="col-6"><label class="form-label">Capacity</label><input type="number" name="max_capacity" class="form-control" value="<?php echo $venue->max_capacity; ?>"></div>
                            </div>
                            <div class="mb-3"><label class="form-label">Security</label>
                                <select name="security_level" class="form-select">
                                    <option value="">Select...</option>
                                    <?php foreach (['basic', 'standard', 'high', 'maximum'] as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo $venue->security_level === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-check mb-2"><input type="checkbox" name="climate_controlled" class="form-check-input" id="climate" <?php echo $venue->climate_controlled ? 'checked' : ''; ?>><label class="form-check-label" for="climate">Climate Controlled</label></div>
                            <div class="form-check mb-3"><input type="checkbox" name="is_active" class="form-check-input" id="active" <?php echo $venue->is_active ? 'checked' : ''; ?>><label class="form-check-label" for="active">Active</label></div>
                        </div>
                    </div>
                    <h6>Contact</h6>
                    <div class="row mb-3">
                        <div class="col-4"><input type="text" name="contact_name" class="form-control" placeholder="Name" value="<?php echo $venue->contact_name; ?>"></div>
                        <div class="col-4"><input type="email" name="contact_email" class="form-control" placeholder="Email" value="<?php echo $venue->contact_email; ?>"></div>
                        <div class="col-4"><input type="tel" name="contact_phone" class="form-control" placeholder="Phone" value="<?php echo $venue->contact_phone; ?>"></div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-th-large me-2"></i>Spaces (<?php echo count($spaces); ?>)</h5>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addSpaceModal"><i class="fas fa-plus"></i></button>
            </div>
            <?php if (!empty($spaces)): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($spaces as $s): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo $s->name; ?></strong>
                                <br><small class="text-muted"><?php echo $s->area_sqm ? $s->area_sqm . ' m²' : ''; ?><?php echo $s->wall_length_m ? ' • ' . $s->wall_length_m . 'm wall' : ''; ?></small>
                            </div>
                            <form method="post" class="d-inline"><input type="hidden" name="do" value="delete_space"><input type="hidden" name="space_id" value="<?php echo $s->id; ?>"><button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this space?')"><i class="fas fa-trash"></i></button></form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="card-body text-muted">No spaces defined</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Space Modal -->
<div class="modal fade" id="addSpaceModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="post"><input type="hidden" name="do" value="add_space">
        <div class="modal-header"><h5 class="modal-title">Add Space</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="space_name" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Description</label><textarea name="space_description" class="form-control" rows="2"></textarea></div>
            <div class="row mb-3">
                <div class="col-4"><label class="form-label">Area (m²)</label><input type="number" name="area_sqm" class="form-control" step="0.01"></div>
                <div class="col-4"><label class="form-label">Wall (m)</label><input type="number" name="wall_length_m" class="form-control" step="0.01"></div>
                <div class="col-4"><label class="form-label">Height (m)</label><input type="number" name="height_m" class="form-control" step="0.01"></div>
            </div>
            <div class="mb-3"><label class="form-label">Lighting</label><input type="text" name="lighting_type" class="form-control" placeholder="e.g., Natural, Track, Spot"></div>
            <div class="form-check"><input type="checkbox" name="space_climate" class="form-check-input" id="spaceClimate"><label class="form-check-label" for="spaceClimate">Climate Controlled</label></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Add Space</button></div>
    </form>
</div></div></div>

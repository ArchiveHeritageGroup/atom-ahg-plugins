<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">My Profile</li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-user-circle text-primary me-2"></i>My Profile</h1>

<?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Profile Details</h5>
                <span class="badge bg-<?php echo $researcher->status === 'approved' ? 'success' : ($researcher->status === 'pending' ? 'warning' : 'danger'); ?>"><?php echo ucfirst($researcher->status); ?></span>
            </div>
            <div class="card-body">
                <?php if ($researcher->status === 'expired' || ($researcher->status === 'approved' && $researcher->expires_at && strtotime($researcher->expires_at) < strtotime('+30 days'))): ?>
                <div class="alert alert-<?php echo $researcher->status === 'expired' ? 'danger' : 'warning'; ?> d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-<?php echo $researcher->status === 'expired' ? 'exclamation-circle' : 'clock'; ?> me-2"></i>
                        <?php if ($researcher->status === 'expired'): ?>
                            <?php echo __('Your researcher registration has expired.'); ?>
                        <?php else: ?>
                            <?php echo __('Your registration expires on') . ' ' . date('M j, Y', strtotime($researcher->expires_at)); ?>
                        <?php endif; ?>
                    </div>
                    <a href="<?php echo url_for('research/renewal'); ?>" class="btn btn-sm btn-<?php echo $researcher->status === 'expired' ? 'danger' : 'warning'; ?>">
                        <i class="fas fa-sync-alt me-1"></i><?php echo __('Request Renewal'); ?>
                    </a>
                </div>
                <?php endif; ?>
                <form method="post">
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="form-label">Title</label>
                            <select name="title" class="form-select">
                                <option value="">--</option>
                                <?php foreach (['Mr', 'Mrs', 'Ms', 'Dr', 'Prof'] as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo $researcher->title === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" value="<?php echo $researcher->first_name; ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" value="<?php echo $researcher->last_name; ?>" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo $researcher->email; ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo $researcher->phone; ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Affiliation</label>
                            <select name="affiliation_type" class="form-select">
                                <?php foreach (['independent' => 'Independent', 'academic' => 'Academic', 'government' => 'Government', 'private' => 'Private', 'student' => 'Student'] as $k => $v): ?>
                                    <option value="<?php echo $k; ?>" <?php echo $researcher->affiliation_type === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Institution</label>
                            <input type="text" name="institution" class="form-control" value="<?php echo $researcher->institution; ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control" value="<?php echo $researcher->department; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Position</label>
                            <input type="text" name="position" class="form-control" value="<?php echo $researcher->position; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ORCID ID</label>
                        <input type="text" name="orcid_id" class="form-control" value="<?php echo $researcher->orcid_id; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Research Interests</label>
                        <textarea name="research_interests" class="form-control" rows="2"><?php echo $researcher->research_interests; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Project</label>
                        <textarea name="current_project" class="form-control" rows="2"><?php echo $researcher->current_project; ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header bg-info text-white"><h6 class="mb-0"><i class="fas fa-calendar me-2"></i>Recent Bookings</h6></div>
            <?php if (!empty($bookings)): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach (array_slice((array)$bookings, 0, 5) as $b): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?php echo date('M j', strtotime($b->booking_date)); ?> - <?php echo $b->room_name; ?></span>
                            <span class="badge bg-<?php echo $b->status === 'confirmed' ? 'success' : ($b->status === 'pending' ? 'warning' : 'secondary'); ?>"><?php echo $b->status; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="card-body text-muted">No bookings yet</div>
            <?php endif; ?>
        </div>
        <div class="card mb-3">
            <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="fas fa-folder me-2"></i>Collections</h6></div>
            <?php if (!empty($collections)): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($collections as $c): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewCollection', 'id' => $c->id]); ?>"><?php echo $c->name; ?></a>
                            <span class="badge bg-secondary"><?php echo $c->item_count; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="card-body text-muted">No collections</div>
            <?php endif; ?>
        </div>
    </div>
</div>

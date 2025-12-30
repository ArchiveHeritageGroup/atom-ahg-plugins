<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'dashboard']); ?>">Gallery</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'exhibitions']); ?>">Exhibitions</a></li>
        <li class="breadcrumb-item active"><?php echo $exhibition->title; ?></li>
    </ol>
</nav>
<?php if ($sf_user->hasFlash('success')): ?><div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div><?php endif; ?>
<?php $colors = ['planning' => 'info', 'confirmed' => 'primary', 'installing' => 'warning', 'open' => 'success', 'closing' => 'warning', 'closed' => 'secondary', 'cancelled' => 'danger']; ?>
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h2 mb-1"><?php echo $exhibition->title; ?> <span class="badge bg-<?php echo $colors[$exhibition->status] ?? 'secondary'; ?>"><?php echo ucfirst($exhibition->status); ?></span></h1>
        <?php if ($exhibition->subtitle): ?><p class="lead text-muted mb-0"><?php echo $exhibition->subtitle; ?></p><?php endif; ?>
    </div>
    <form method="post" class="d-flex gap-2">
        <input type="hidden" name="do" value="update_status">
        <select name="status" class="form-select form-select-sm" style="width:auto">
            <?php foreach (['planning', 'confirmed', 'installing', 'open', 'closing', 'closed', 'cancelled'] as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo $exhibition->status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
    </form>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Details</h5></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th width="100">Type:</th><td><?php echo ucfirst($exhibition->exhibition_type); ?></td></tr>
                    <tr><th>Venue:</th><td><?php echo $exhibition->venue_name ?: '-'; ?></td></tr>
                    <tr><th>Curator:</th><td><?php echo $exhibition->curator ?: '-'; ?></td></tr>
                    <tr><th>Dates:</th><td><?php echo $exhibition->start_date ? $exhibition->start_date . ' to ' . $exhibition->end_date : '-'; ?></td></tr>
                    <tr><th>Budget:</th><td><?php echo $exhibition->budget ? 'R ' . number_format($exhibition->budget, 2) : '-'; ?></td></tr>
                </table>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between"><h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Checklist</h5><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal"><i class="fas fa-plus"></i></button></div>
            <?php if (!empty($exhibition->checklist)): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($exhibition->checklist as $t): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <?php if ($t->status === 'completed'): ?><s class="text-muted"><?php echo $t->task_name; ?></s>
                                <?php else: ?><strong><?php echo $t->task_name; ?></strong><?php endif; ?>
                                <?php if ($t->due_date): ?><br><small class="text-muted">Due: <?php echo $t->due_date; ?></small><?php endif; ?>
                            </div>
                            <?php if ($t->status !== 'completed'): ?>
                                <form method="post"><input type="hidden" name="do" value="complete_task"><input type="hidden" name="task_id" value="<?php echo $t->id; ?>"><button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check"></i></button></form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="card-body text-muted">No tasks</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between"><h5 class="mb-0"><i class="fas fa-cubes me-2"></i>Exhibition Objects (<?php echo count($exhibition->objects); ?>)</h5><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'addExhibitionObject', 'exhibition_id' => $exhibition->id]); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-plus"></i> Add</a></div>
            <?php if (!empty($exhibition->objects)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>#</th><th>Object</th><th>Space</th><th>Section</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($exhibition->objects as $o): ?>
                                <tr>
                                    <td><?php echo $o->display_order; ?></td>
                                    <td><a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $o->slug]); ?>"><?php echo $o->object_title ?: 'Untitled'; ?></a></td>
                                    <td><?php echo $o->space_name ?: '-'; ?></td>
                                    <td><?php echo $o->section ?: '-'; ?></td>
                                    <td><form method="post" class="d-inline"><input type="hidden" name="do" value="remove_object"><input type="hidden" name="object_id" value="<?php echo $o->object_id; ?>"><button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove?')"><i class="fas fa-times"></i></button></form></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="card-body text-center text-muted"><i class="fas fa-cubes fa-3x mb-3"></i><p>No objects added yet</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="addTaskModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="post"><input type="hidden" name="do" value="add_task">
        <div class="modal-header"><h5 class="modal-title">Add Task</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Task *</label><input type="text" name="task_name" class="form-control" required></div>
            <div class="row mb-3">
                <div class="col-6"><label class="form-label">Category</label><select name="category" class="form-select"><option value="planning">Planning</option><option value="design">Design</option><option value="marketing">Marketing</option><option value="installation">Installation</option><option value="opening">Opening</option><option value="operation">Operation</option><option value="closing">Closing</option></select></div>
                <div class="col-6"><label class="form-label">Priority</label><select name="priority" class="form-select"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div>
            </div>
            <div class="row mb-3">
                <div class="col-6"><label class="form-label">Assigned To</label><input type="text" name="assigned_to" class="form-control"></div>
                <div class="col-6"><label class="form-label">Due Date</label><input type="date" name="due_date" class="form-control"></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Add Task</button></div>
    </form>
</div></div></div>

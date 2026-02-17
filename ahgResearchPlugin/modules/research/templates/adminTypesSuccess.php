<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Researcher Types</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2"><i class="fas fa-user-tag text-primary me-2"></i>Researcher Types</h1>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'editResearcherType']); ?>" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Add Type
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Max Advance Days</th>
                    <th>Max Hours/Day</th>
                    <th>Max Materials</th>
                    <th>Auto Approve</th>
                    <th>Status</th>
                    <th width="100">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($types)): ?>
                    <?php foreach ($types as $type): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($type->name); ?></strong>
                                <?php if ($type->description): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($type->description, 0, 50)); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><code><?php echo $type->code; ?></code></td>
                            <td><?php echo $type->max_booking_days_advance; ?> days</td>
                            <td><?php echo $type->max_booking_hours_per_day; ?> hrs</td>
                            <td><?php echo $type->max_materials_per_booking; ?></td>
                            <td>
                                <?php if ($type->auto_approve): ?>
                                    <span class="badge bg-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($type->is_active): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo url_for(['module' => 'research', 'action' => 'editResearcherType', 'id' => $type->id]); ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No researcher types configured</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'adminTypes']); ?>">Researcher Types</a></li>
        <li class="breadcrumb-item active"><?php echo $isNew ? 'Add Type' : 'Edit Type'; ?></li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-user-tag text-primary me-2"></i><?php echo $isNew ? 'Add Researcher Type' : 'Edit Researcher Type'; ?></h1>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="post">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Name *</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($type->name ?? ''); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Code *</label>
                            <input type="text" name="code" class="form-control" value="<?php echo htmlspecialchars($type->code ?? ''); ?>" required pattern="[a-z_]+" title="Lowercase letters and underscores only">
                            <small class="text-muted">Lowercase, no spaces</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($type->description ?? ''); ?></textarea>
                    </div>

                    <hr class="my-4">
                    <h5>Booking Limits</h5>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Max Advance Booking (days)</label>
                            <input type="number" name="max_booking_days_advance" class="form-control" value="<?php echo $type->max_booking_days_advance ?? 14; ?>" min="1" max="365">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Max Hours Per Day</label>
                            <input type="number" name="max_booking_hours_per_day" class="form-control" value="<?php echo $type->max_booking_hours_per_day ?? 4; ?>" min="1" max="12">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Max Materials Per Booking</label>
                            <input type="number" name="max_materials_per_booking" class="form-control" value="<?php echo $type->max_materials_per_booking ?? 10; ?>" min="1" max="100">
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5>Permissions</h5>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="can_remote_access" class="form-check-input" id="can_remote_access" <?php echo ($type->can_remote_access ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="can_remote_access">Can Access Remotely</label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" name="can_request_reproductions" class="form-check-input" id="can_request_reproductions" <?php echo ($type->can_request_reproductions ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="can_request_reproductions">Can Request Reproductions</label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" name="can_export_data" class="form-check-input" id="can_export_data" <?php echo ($type->can_export_data ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="can_export_data">Can Export Data</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="requires_id_verification" class="form-check-input" id="requires_id_verification" <?php echo ($type->requires_id_verification ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="requires_id_verification">Requires ID Verification</label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" name="auto_approve" class="form-check-input" id="auto_approve" <?php echo ($type->auto_approve ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="auto_approve">Auto-Approve Registrations</label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" name="is_active" class="form-check-input" id="is_active" <?php echo ($type->is_active ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5>Other Settings</h5>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Registration Expiry (months)</label>
                            <input type="number" name="expiry_months" class="form-control" value="<?php echo $type->expiry_months ?? 12; ?>" min="1" max="120">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority Level</label>
                            <input type="number" name="priority_level" class="form-control" value="<?php echo $type->priority_level ?? 5; ?>" min="1" max="10">
                            <small class="text-muted">1 = highest priority</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="<?php echo $type->sort_order ?? 100; ?>" min="0">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
                        <a href="<?php echo url_for(['module' => 'research', 'action' => 'adminTypes']); ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

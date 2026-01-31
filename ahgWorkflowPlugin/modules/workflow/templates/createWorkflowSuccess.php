<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('workflow/dashboard') ?>">Workflow</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for('workflow/admin') ?>">Admin</a></li>
            <li class="breadcrumb-item active">Create Workflow</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Create New Workflow</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Workflow Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="e.g., Standard Review Process">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2" placeholder="Brief description of this workflow..."></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="scope_type" class="form-label">Scope</label>
                                <select class="form-select" id="scope_type" name="scope_type">
                                    <option value="global">Global (All repositories)</option>
                                    <option value="repository">Specific Repository</option>
                                    <option value="collection">Specific Collection</option>
                                </select>
                                <small class="form-text text-muted">Where this workflow applies</small>
                            </div>
                            <div class="col-md-6" id="scope_id_container" style="display:none">
                                <label for="scope_id" class="form-label">Select Target</label>
                                <select class="form-select" id="scope_id" name="scope_id">
                                    <option value="">Select...</option>
                                    <?php foreach ($repositories as $repo): ?>
                                        <option value="<?php echo $repo->id ?>"><?php echo esc_entities($repo->name) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="trigger_event" class="form-label">Trigger Event</label>
                                <select class="form-select" id="trigger_event" name="trigger_event">
                                    <option value="submit">On Submit</option>
                                    <option value="create">On Create</option>
                                    <option value="update">On Update</option>
                                    <option value="publish">On Publish</option>
                                    <option value="manual">Manual Only</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="applies_to" class="form-label">Applies To</label>
                                <select class="form-select" id="applies_to" name="applies_to">
                                    <option value="information_object">Archival Descriptions</option>
                                    <option value="actor">Authority Records</option>
                                    <option value="accession">Accessions</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1">
                                    <label class="form-check-label" for="is_default">Default for Scope</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notification_enabled" name="notification_enabled" value="1" checked>
                                    <label class="form-check-label" for="notification_enabled">Send Notifications</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Create Workflow
                            </button>
                            <a href="<?php echo url_for('workflow/admin') ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6><i class="fas fa-info-circle me-1"></i>About Workflows</h6>
                    <p class="small text-muted mb-2">
                        Workflows define the approval process for archival submissions. Each workflow can have multiple steps that must be completed in order.
                    </p>
                    <p class="small text-muted mb-0">
                        After creating a workflow, add steps to define the review process. Each step can require specific roles or security clearance levels.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('scope_type').addEventListener('change', function() {
    var container = document.getElementById('scope_id_container');
    container.style.display = this.value === 'global' ? 'none' : '';
});
</script>

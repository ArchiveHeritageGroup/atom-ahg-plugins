<?php use_helper('Date') ?>

<h1>IIIF Authentication Settings</h1>

<section class="card mb-4">
    <div class="card-header">
        <h2 class="h5 mb-0">Authentication Services</h2>
    </div>
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Profile</th>
                    <th>Label</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $service): ?>
                <tr>
                    <td><code><?php echo esc_specialchars($service->name) ?></code></td>
                    <td>
                        <span class="badge bg-secondary"><?php echo esc_specialchars($service->profile) ?></span>
                    </td>
                    <td><?php echo esc_specialchars($service->label) ?></td>
                    <td>
                        <?php if ($service->is_active): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <div class="card-header">
        <h2 class="h5 mb-0">Protected Resources</h2>
    </div>
    <div class="card-body">
        <?php if (empty($resources)): ?>
            <p class="text-muted">No resources are currently protected.</p>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Resource</th>
                        <th>Service</th>
                        <th>Apply to Children</th>
                        <th>Degraded Access</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resources as $resource): ?>
                    <tr>
                        <td>
                            <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $resource->slug]) ?>">
                                <?php echo esc_specialchars($resource->title ?: $resource->slug) ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $resource->service_profile === 'login' ? 'primary' : 'info' ?>">
                                <?php echo esc_specialchars($resource->service_name) ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $resource->apply_to_children ? 'Yes' : 'No' ?>
                        </td>
                        <td>
                            <?php echo $resource->degraded_access ? 'Yes (' . $resource->degraded_width . 'px)' : 'No' ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-danger" onclick="removeProtection(<?php echo $resource->object_id ?>)">
                                Remove
                            </button>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>
</section>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function removeProtection(objectId) {
    if (!confirm('Remove protection from this resource?')) return;

    fetch('<?php echo url_for('iiif_auth_unprotect') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'object_id=' + objectId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to remove protection');
        }
    });
}
</script>

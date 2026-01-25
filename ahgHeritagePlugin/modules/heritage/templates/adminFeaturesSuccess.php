<?php
/**
 * Heritage Admin Feature Toggles.
 */

decorate_with('layout_2col');
?>

<?php slot('title'); ?>
<h1 class="h3">
    <i class="fas fa-toggle-on me-2"></i>Feature Toggles
</h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<?php include_partial('heritage/adminSidebar', ['active' => 'features']); ?>
<?php end_slot(); ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent">
        <h5 class="mb-0">Platform Features</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Feature</th>
                        <th>Code</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($features as $feature): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_specialchars($feature->feature_name ?? $feature->feature_code); ?></strong>
                            <?php if (!empty($feature->config_json)): ?>
                            <br><small class="text-muted">Has configuration</small>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo esc_specialchars($feature->feature_code); ?></code></td>
                        <td class="text-center">
                            <?php if ($feature->is_enabled): ?>
                            <span class="badge bg-success">Enabled</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <form method="post" class="d-inline">
                                <input type="hidden" name="feature_code" value="<?php echo esc_specialchars($feature->feature_code); ?>">
                                <input type="hidden" name="toggle_action" value="toggle">
                                <button type="submit" class="btn btn-sm btn-outline-<?php echo $feature->is_enabled ? 'secondary' : 'success'; ?>">
                                    <?php echo $feature->is_enabled ? 'Disable' : 'Enable'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($features)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">No features configured.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4">
    <i class="fas fa-info-circle me-2"></i>
    Feature toggles control platform functionality. Disabled features will not be available to users.
</div>

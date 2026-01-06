<?php slot('title') ?><?php echo __('Heritage Accounting Settings') ?><?php end_slot() ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0"><i class="fas fa-cog me-2"></i><?php echo __('Heritage Accounting Settings') ?></h1>
                <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'standardList']); ?>" class="btn btn-outline-primary">
                    <i class="fas fa-edit me-1"></i><?php echo __('Manage Standards'); ?>
                </a>
            </div>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success"><?php echo $sf_user->getFlash('notice') ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i><?php echo __('Accounting Standards') ?></h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4"><?php echo __('Enable or disable accounting standards for your organization. At least one standard should be enabled.') ?></p>
                
                <div class="row">
                    <?php foreach ($standards as $std): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card h-100 <?php echo $std->is_active ? 'border-success' : 'border-secondary' ?>">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                               class="form-check-input" 
                                               name="enabled[]" 
                                               value="<?php echo $std->id ?>" 
                                               id="std_<?php echo $std->id ?>"
                                               <?php echo $std->is_active ? 'checked' : '' ?>
                                               >
                                        <label class="form-check-label" for="std_<?php echo $std->id ?>">
                                            <strong><?php echo esc_entities($std->code) ?></strong>
                                        </label>
                                    </div>
                                    <p class="mb-1 mt-2"><?php echo esc_entities($std->name) ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-globe me-1"></i><?php echo esc_entities($std->country) ?>
                                    </small>
                                    <div class="mt-2">
                                        <?php if ($std->capitalisation_required): ?>
                                            <span class="badge bg-warning text-dark">Capitalisation Required</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Capitalisation Optional</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="alert alert-info mt-3 mb-0">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> <em>Required</em> means capitalisation is mandatory under that accounting standard. <em>Optional</em> means capitalisation is at the organisation's discretion. The checkboxes control which standards are available for use in your system.
            </div>
            <div class="card-footer">
                <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'dashboard']) ?>" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Dashboard') ?>
                </a>
                <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i><?php echo __('Save Settings') ?></button>
            </div>
        </div>
    </form>
</div>

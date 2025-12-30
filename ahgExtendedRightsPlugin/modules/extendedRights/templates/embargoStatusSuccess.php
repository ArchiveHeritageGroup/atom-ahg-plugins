<?php use_helper('Date'); ?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'staticpage', 'action' => 'static', 'id' => 'homepage']); ?>"><?php echo __('Home'); ?></a></li>
                <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'dashboard']); ?>"><?php echo __('Rights Management'); ?></a></li>
                <li class="breadcrumb-item active"><?php echo __('Embargo Status'); ?></li>
            </ol>
        </nav>
        <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'dashboard']); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Dashboard'); ?>
        </a>
    </div>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-lock me-2"></i><?php echo __('Embargo Status'); ?></h4>
        </div>
        <div class="card-body">
            <?php if (isset($objectId) && $objectId): ?>
                <?php include_partial('extendedRights/embargoStatus', ['objectId' => $objectId]); ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i><?php echo __('No object specified. Please select a record to view its embargo status.'); ?>
                </div>
                
                <h5 class="mt-4"><?php echo __('View All Embargoes'); ?></h5>
                <p><?php echo __('You can view and manage all embargoes from the embargoes list.'); ?></p>
                <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'embargoes']); ?>" class="btn btn-primary">
                    <i class="fas fa-list me-1"></i><?php echo __('View All Embargoes'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

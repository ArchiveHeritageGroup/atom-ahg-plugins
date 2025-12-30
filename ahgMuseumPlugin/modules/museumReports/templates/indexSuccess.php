<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Museum Reports'); ?></h4>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for(['module' => 'museumReports', 'action' => 'objects']); ?>"><i class="fas fa-cube me-2"></i><?php echo __('Objects'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'museumReports', 'action' => 'creators']); ?>"><i class="fas fa-user-edit me-2"></i><?php echo __('Creators'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'museumReports', 'action' => 'conditionReport']); ?>"><i class="fas fa-heartbeat me-2"></i><?php echo __('Condition'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'museumReports', 'action' => 'provenance']); ?>"><i class="fas fa-history me-2"></i><?php echo __('Provenance'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'museumReports', 'action' => 'stylePeriod']); ?>"><i class="fas fa-theater-masks me-2"></i><?php echo __('Style & Period'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'museumReports', 'action' => 'materials']); ?>"><i class="fas fa-layer-group me-2"></i><?php echo __('Materials'); ?></a></li>
    </ul>
    <hr>
    <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Browse'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-landmark"></i> <?php echo __('Museum Reports Dashboard'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="museum-reports-dashboard">
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <h2><?php echo number_format($stats['totalObjects']); ?></h2>
                    <p class="mb-0"><?php echo __('Total Objects'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h2><?php echo number_format($stats['withProvenance']); ?></h2>
                    <p class="mb-0"><?php echo __('With Provenance'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <h2><?php echo count($stats['byCondition']); ?></h2>
                    <p class="mb-0"><?php echo __('Condition Assessed'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-shapes me-2"></i><?php echo __('By Work Type'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (empty($stats['byWorkType'])): ?>
                    <li class="list-group-item text-muted"><?php echo __('No work types recorded'); ?></li>
                    <?php else: ?>
                    <?php foreach ($stats['byWorkType'] as $type): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <?php echo esc_specialchars($type->work_type); ?>
                        <span class="badge bg-primary"><?php echo $type->count; ?></span>
                    </li>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i><?php echo __('By Condition'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (empty($stats['byCondition'])): ?>
                    <li class="list-group-item text-muted"><?php echo __('No conditions recorded'); ?></li>
                    <?php else: ?>
                    <?php foreach ($stats['byCondition'] as $cond): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <?php echo esc_specialchars(ucfirst($cond->condition_term)); ?>
                        <span class="badge bg-<?php echo in_array($cond->condition_term, ['poor', 'critical']) ? 'danger' : 'success'; ?>"><?php echo $cond->count; ?></span>
                    </li>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php end_slot(); ?>

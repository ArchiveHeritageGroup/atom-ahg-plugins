<?php decorate_with('layout_2col.php') ?>

<?php slot('title') ?>
<div class="d-flex align-items-center">
    <i class="fas fa-comment-alt fa-lg text-primary me-3"></i>
    <div>
        <h1 class="h3 mb-0"><?php echo __('View Feedback') ?></h1>
        <small class="text-muted"><?php echo __('ID: %1%', ['%1%' => $resource->id]) ?></small>
    </div>
</div>
<?php end_slot() ?>

<?php slot('sidebar') ?>
<div class="card mb-3">
    <div class="card-header bg-<?php echo ($resource->status_id == QubitTerm::PENDING_ID) ? 'warning text-dark' : 'success text-white' ?>">
        <i class="fas fa-<?php echo ($resource->status_id == QubitTerm::PENDING_ID) ? 'clock' : 'check-circle' ?> me-1"></i>
        <?php echo ($resource->status_id == QubitTerm::PENDING_ID) ? __('Pending') : __('Completed') ?>
    </div>
    <div class="card-body">
        <p class="mb-2"><strong><?php echo __('Submitted:') ?></strong><br>
        <?php echo date('d M Y H:i', strtotime($resource->created_at)) ?></p>
        <?php if ($resource->completed_at): ?>
        <p class="mb-0"><strong><?php echo __('Completed:') ?></strong><br>
        <?php echo date('d M Y H:i', strtotime($resource->completed_at)) ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if ($informationObject): ?>
<div class="card mb-3">
    <div class="card-header bg-light">
        <i class="fas fa-link me-1"></i> <?php echo __('Linked Record') ?>
    </div>
    <div class="card-body">
        <a href="<?php echo url_for([$informationObject, 'module' => 'informationobject']) ?>">
            <?php echo esc_entities($informationObject->getTitle(['cultureFallback' => true])) ?>
        </a>
    </div>
</div>
<?php endif; ?>

<?php if ($sf_user->isAuthenticated()): ?>
<div class="card">
    <div class="card-header bg-light">
        <i class="fas fa-cog me-1"></i> <?php echo __('Actions') ?>
    </div>
    <div class="card-body d-grid gap-2">
        <a href="<?php echo url_for([$resource, 'module' => 'ahgFeedback', 'action' => 'edit']) ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-edit me-1"></i> <?php echo __('Edit') ?>
        </a>
    </div>
</div>
<?php endif; ?>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="card shadow-sm mb-3">
    <div class="card-header bg-light">
        <i class="fas fa-comment-alt me-2"></i><?php echo __('Feedback Details') ?>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="text-muted small"><?php echo __('Subject') ?></label>
                <p class="fw-semibold mb-0"><?php echo esc_entities($resource->name) ?></p>
            </div>
            <div class="col-md-6">
                <label class="text-muted small"><?php echo __('Type') ?></label>
                <p class="mb-0">
                    <?php 
                    $type = $feedbackTypes[$resource->feed_type_id] ?? __('General');
                    $badgeClass = ['bg-secondary', 'bg-danger', 'bg-info', 'bg-primary', 'bg-warning text-dark'][$resource->feed_type_id] ?? 'bg-secondary';
                    ?>
                    <span class="badge <?php echo $badgeClass ?>"><?php echo $type ?></span>
                </p>
            </div>
        </div>
        <div class="mb-3">
            <label class="text-muted small"><?php echo __('Remarks') ?></label>
            <p class="mb-0"><?php echo nl2br(esc_entities($resource->remarks)) ?></p>
        </div>
        <?php if ($resource->feed_relationship): ?>
        <div class="mb-0">
            <label class="text-muted small"><?php echo __('Relationship') ?></label>
            <p class="mb-0"><?php echo esc_entities($resource->feed_relationship) ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <i class="fas fa-address-card me-2"></i><?php echo __('Contact Information') ?>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <label class="text-muted small"><?php echo __('Name') ?></label>
                <p class="fw-semibold mb-2"><?php echo esc_entities($resource->feed_name . ' ' . $resource->feed_surname) ?></p>
            </div>
            <div class="col-md-6">
                <label class="text-muted small"><?php echo __('Email') ?></label>
                <p class="mb-2">
                    <?php if ($resource->feed_email): ?>
                    <a href="mailto:<?php echo $resource->feed_email ?>"><?php echo esc_entities($resource->feed_email) ?></a>
                    <?php else: ?>
                    <span class="text-muted">-</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-md-6">
                <label class="text-muted small"><?php echo __('Phone') ?></label>
                <p class="mb-0"><?php echo esc_entities($resource->feed_phone) ?: '-' ?></p>
            </div>
        </div>
    </div>
</div>
<?php end_slot() ?>

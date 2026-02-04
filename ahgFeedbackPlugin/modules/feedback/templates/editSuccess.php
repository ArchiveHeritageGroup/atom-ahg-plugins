<?php decorate_with('layout_2col.php') ?>

<?php slot('title') ?>
<div class="d-flex align-items-center">
    <i class="fas fa-edit fa-lg text-primary me-3"></i>
    <div>
        <h1 class="h3 mb-0"><?php echo __('Review Feedback') ?></h1>
        <small class="text-muted"><?php echo __('ID: %1%', ['%1%' => $resource->id]) ?></small>
    </div>
</div>
<?php end_slot() ?>

<?php slot('sidebar') ?>
<!-- Status Card -->
<div class="card mb-3">
    <div class="card-header bg-<?php echo ($feedback->status === 'pending') ? 'warning text-dark' : 'success text-white' ?>">
        <i class="fas fa-<?php echo ($feedback->status === 'pending') ? 'clock' : 'check-circle' ?> me-1"></i>
        <?php echo ($feedback->status === 'pending') ? __('Pending Review') : __('Completed') ?>
    </div>
    <div class="card-body">
        <p class="mb-2"><strong><?php echo __('Created:') ?></strong><br>
        <i class="fas fa-calendar-plus text-muted me-1"></i><?php echo date('d M Y H:i', strtotime($resource->created_at)) ?></p>
        
        <?php if ($resource->completed_at): ?>
        <p class="mb-0"><strong><?php echo __('Completed:') ?></strong><br>
        <i class="fas fa-calendar-check text-success me-1"></i><?php echo date('d M Y H:i', strtotime($resource->completed_at)) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Linked Record -->
<?php if ($informationObject): ?>
<div class="card mb-3">
    <div class="card-header bg-light">
        <i class="fas fa-link me-1"></i> <?php echo __('Linked Record') ?>
    </div>
    <div class="card-body">
        <a href="<?php echo url_for([$informationObject, 'module' => 'informationobject']) ?>" class="text-decoration-none">
            <i class="fas fa-file-alt me-1"></i>
            <?php echo esc_entities($informationObject->getTitle(['cultureFallback' => true])) ?>
        </a>
        <?php if ($informationObject->identifier): ?>
        <br><small class="text-muted"><?php echo esc_entities($informationObject->identifier) ?></small>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header bg-light">
        <i class="fas fa-bolt me-1"></i> <?php echo __('Quick Actions') ?>
    </div>
    <div class="card-body d-grid gap-2">
        <?php if ($resource->feed_email): ?>
        <a href="mailto:<?php echo $resource->feed_email ?>?subject=Re: Your Feedback" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-reply me-1"></i> <?php echo __('Reply by Email') ?>
        </a>
        <?php endif; ?>
        <a href="<?php echo url_for(['module' => 'feedback', 'action' => 'delete', 'id' => $feedback->id]) ?>"
           class="btn btn-outline-danger btn-sm"
           onclick="return confirm('<?php echo __('Are you sure you want to delete this feedback?') ?>');">
            <i class="fas fa-trash me-1"></i> <?php echo __('Delete') ?>
        </a>
    </div>
</div>
<?php end_slot() ?>

<?php slot('content') ?>
<?php echo $form->renderGlobalErrors() ?>
<?php echo $form->renderFormTag(url_for(['module' => 'feedback', 'action' => 'edit', 'id' => $feedback->id]), ['class' => 'form-vertical']) ?>
<?php echo $form->renderHiddenFields() ?>

<!-- Feedback Content -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-comment-alt me-2"></i><?php echo __('Feedback Details') ?>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold"><?php echo __('Subject/Record') ?></label>
                <?php echo $form->name->render(['class' => 'form-control', 'readonly' => 'readonly']) ?>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold"><?php echo __('Feedback Type') ?></label>
                <?php echo $form->feed_type_id->render(['class' => 'form-select']) ?>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('Remarks / Comments') ?></label>
            <?php echo $form->remarks->render(['class' => 'form-control', 'rows' => 5]) ?>
        </div>
        
        <div class="mb-0">
            <label class="form-label fw-semibold"><?php echo __('Relationship to Record') ?></label>
            <?php echo $form->feed_relationship->render(['class' => 'form-control', 'rows' => 2]) ?>
        </div>
    </div>
</div>

<!-- Contact Information -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-light">
        <i class="fas fa-address-card me-2"></i><?php echo __('Contact Information') ?>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold"><?php echo __('Name') ?></label>
                <?php echo $form->feed_name->render(['class' => 'form-control']) ?>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold"><?php echo __('Surname') ?></label>
                <?php echo $form->feed_surname->render(['class' => 'form-control']) ?>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold"><?php echo __('Phone') ?></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    <?php echo $form->feed_phone->render(['class' => 'form-control']) ?>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold"><?php echo __('Email') ?></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <?php echo $form->feed_email->render(['class' => 'form-control']) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admin Section -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-dark text-white">
        <i class="fas fa-user-shield me-2"></i><?php echo __('Admin Review') ?>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold"><?php echo __('Status') ?></label>
                <?php echo $form->status->render(['class' => 'form-select']) ?>
                <small class="text-muted"><?php echo __('Setting to Completed will record the completion date') ?></small>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold"><?php echo __('Admin Notes') ?></label>
                <?php echo $form->admin_notes->render(['class' => 'form-control', 'rows' => 3, 'placeholder' => __('Internal notes (not visible to submitter)')]) ?>
            </div>
        </div>
    </div>
</div>

<!-- Actions -->
<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <a href="<?php echo url_for(['module' => 'feedback', 'action' => 'browse']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to List') ?>
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> <?php echo __('Save Changes') ?>
            </button>
        </div>
    </div>
</div>

</form>
<?php end_slot() ?>

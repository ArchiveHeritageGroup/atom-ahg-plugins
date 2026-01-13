<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
<div class="d-flex align-items-center mb-3">
    <i class="fas fa-comments fa-2x text-primary me-3"></i>
    <div>
        <h1 class="h3 mb-0"><?php echo __('General Feedback') ?></h1>
        <p class="text-muted mb-0"><?php echo __('Share your feedback, suggestions, or report issues') ?></p>
    </div>
</div>
<?php end_slot() ?>

<?php slot('content') ?>
<?php echo $form->renderGlobalErrors() ?>
<?php echo $form->renderFormTag(url_for(['module' => 'ahgFeedback', 'action' => 'general']), ['class' => 'form-vertical']) ?>
<?php echo $form->renderHiddenFields() ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        
        <!-- Feedback Type & Content -->
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-comment-alt me-2"></i><?php echo __('Your Feedback') ?>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?php echo __('Subject') ?> <span class="text-danger">*</span></label>
                    <?php echo $form->subject->render(['class' => 'form-control', 'placeholder' => __('Brief subject of your feedback')]) ?>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?php echo __('Feedback Type') ?> <span class="text-danger">*</span></label>
                    <?php echo $form->feed_type_id->render(['class' => 'form-select']) ?>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?php echo __('Your Feedback / Comments') ?> <span class="text-danger">*</span></label>
                    <?php echo $form->remarks->render(['class' => 'form-control', 'rows' => 6, 'placeholder' => __('Please provide details about your feedback, suggestion, or issue...')]) ?>
                </div>
                
                <div class="mb-0">
                    <label class="form-label fw-semibold"><?php echo __('Your Relationship to the Archive') ?></label>
                    <?php echo $form->feed_relationship->render(['class' => 'form-control', 'rows' => 2, 'placeholder' => __('e.g., Researcher, visitor, community member, donor...')]) ?>
                </div>
            </div>
        </div>

        <!-- Contact Details -->
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light">
                <i class="fas fa-address-card me-2"></i><?php echo __('Your Contact Details') ?>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3"><?php echo __('Please provide your contact details so we can follow up if needed.') ?></p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?php echo __('Name') ?> <span class="text-danger">*</span></label>
                        <?php echo $form->feed_name->render(['class' => 'form-control', 'placeholder' => __('Your first name')]) ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?php echo __('Surname') ?> <span class="text-danger">*</span></label>
                        <?php echo $form->feed_surname->render(['class' => 'form-control', 'placeholder' => __('Your surname')]) ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?php echo __('Phone Number') ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <?php echo $form->feed_phone->render(['class' => 'form-control', 'placeholder' => __('Contact number')]) ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?php echo __('Email Address') ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <?php echo $form->feed_email->render(['class' => 'form-control', 'placeholder' => __('your@email.com')]) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Actions -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <a href="<?php echo url_for('@homepage') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> <?php echo __('Cancel') ?>
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane me-1"></i> <?php echo __('Submit Feedback') ?>
                    </button>
                </div>
            </div>
        </div>
        
    </div>
</div>

</form>
<?php end_slot() ?>

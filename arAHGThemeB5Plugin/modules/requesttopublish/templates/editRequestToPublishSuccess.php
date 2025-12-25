<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-paper-plane me-2"></i><?php echo __('Review Request to Publish'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php echo $form->renderGlobalErrors(); ?>
<?php echo $form->renderFormTag(url_for([$resource, 'module' => 'requesttopublish', 'action' => 'editRequestToPublish']), ['id' => 'editRequestForm']); ?>
<?php echo $form->renderHiddenFields(); ?>

<!-- Status Badge -->
<div class="mb-4">
  <?php if ($resource->statusId == QubitTerm::IN_REVIEW_ID): ?>
    <span class="badge bg-warning text-dark fs-6"><i class="fas fa-clock me-1"></i><?php echo __('In Review'); ?></span>
  <?php elseif ($resource->statusId == QubitTerm::REJECTED_ID): ?>
    <span class="badge bg-danger fs-6"><i class="fas fa-times me-1"></i><?php echo __('Rejected'); ?></span>
  <?php else: ?>
    <span class="badge bg-success fs-6"><i class="fas fa-check me-1"></i><?php echo __('Approved'); ?></span>
  <?php endif; ?>
</div>

<!-- Requester Information -->
<div class="card mb-4">
  <div class="card-header bg-success text-white">
    <i class="fas fa-user me-2"></i><?php echo __('Requester Information'); ?>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-6 mb-3">
        <?php echo $form->rtp_name
          ->label(__('Name'))
          ->renderRow(['class' => 'form-control', 'readonly' => 'readonly']); ?>
      </div>
      <div class="col-md-6 mb-3">
        <?php echo $form->rtp_surname
          ->label(__('Surname'))
          ->renderRow(['class' => 'form-control', 'readonly' => 'readonly']); ?>
      </div>
    </div>
    
    <div class="row">
      <div class="col-md-6 mb-3">
        <?php echo $form->rtp_phone
          ->label(__('Phone Number'))
          ->renderRow(['class' => 'form-control', 'readonly' => 'readonly']); ?>
      </div>
      <div class="col-md-6 mb-3">
        <?php echo $form->rtp_email
          ->label(__('e-Mail Address'))
          ->renderRow(['class' => 'form-control', 'readonly' => 'readonly']); ?>
      </div>
    </div>
    
    <div class="mb-3">
      <?php echo $form->rtp_institution
        ->label(__('Institution'))
        ->renderRow(['class' => 'form-control', 'readonly' => 'readonly']); ?>
    </div>
  </div>
</div>

<!-- Request Details -->
<div class="card mb-4">
  <div class="card-header bg-success text-white">
    <i class="fas fa-file-alt me-2"></i><?php echo __('Request Details'); ?>
  </div>
  <div class="card-body">
    <div class="mb-3">
      <?php echo $form->rtp_planned_use
        ->label(__('Planned Use'))
        ->renderRow(['class' => 'form-control', 'readonly' => 'readonly', 'rows' => 3]); ?>
    </div>
    
    <div class="mb-3">
      <?php echo $form->rtp_need_image_by
        ->label(__('Need Image By'))
        ->renderRow(['class' => 'form-control', 'readonly' => 'readonly']); ?>
    </div>
    
    <div class="mb-3">
      <?php echo $form->rtp_motivation
        ->label(__('Motivation'))
        ->renderRow(['class' => 'form-control', 'readonly' => 'readonly', 'rows' => 4]); ?>
    </div>
  </div>
</div>

<!-- Timeline -->
<div class="card mb-4">
  <div class="card-header bg-success text-white">
    <i class="fas fa-calendar-alt me-2"></i><?php echo __('Timeline'); ?>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-6 mb-3">
        <?php echo $form->createdAt
          ->label(__('Created On'))
          ->renderRow(['class' => 'form-control', 'readonly' => 'readonly']); ?>
      </div>
      <?php if ($resource->statusId != QubitTerm::IN_REVIEW_ID): ?>
        <div class="col-md-6 mb-3">
          <?php echo $form->completedAt
            ->label(__('Completed At'))
            ->renderRow(['class' => 'form-control', 'readonly' => 'readonly']); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Decision Section (only for reviewers on pending requests) -->
<?php if ($resource->statusId == QubitTerm::IN_REVIEW_ID && $resource->unique_identifier != $this->context->user->getAttribute('user_id')): ?>
  <div class="card mb-4 border-primary">
    <div class="card-header bg-primary text-white">
      <i class="fas fa-gavel me-2"></i><?php echo __('Decision'); ?>
    </div>
    <div class="card-body">
      <p class="text-muted mb-3"><?php echo __('Please select an outcome for this request:'); ?></p>
      <div class="mb-3">
        <?php echo $form->outcome
          ->label(__('Outcome'))
          ->renderRow(['class' => 'form-select']); ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Actions -->
<section class="actions">
  <ul class="list-unstyled d-flex flex-wrap gap-2">
    <li><?php echo link_to(__('Back to List'), ['module' => 'requesttopublish', 'action' => 'browse'], ['class' => 'btn atom-btn-outline-light']); ?></li>
    <?php if ($resource->statusId == QubitTerm::IN_REVIEW_ID && $resource->unique_identifier != $this->context->user->getAttribute('user_id')): ?>
      <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Submit Decision'); ?>"></li>
    <?php endif; ?>
  </ul>
</section>

</form>

<?php end_slot(); ?>

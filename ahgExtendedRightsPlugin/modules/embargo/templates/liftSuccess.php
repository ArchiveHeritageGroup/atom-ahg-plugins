<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Lift Embargo'); ?></h1>
  <p class="lead"><?php echo $resource->title ?? $resource->slug; ?></p>
<?php end_slot(); ?>

<div class="alert alert-info mb-4">
  <i class="fas fa-info-circle me-2"></i>
  <?php echo __('Lifting this embargo will immediately restore access to the record.'); ?>
</div>

<form method="post" action="<?php echo url_for(['module' => 'embargo', 'action' => 'lift', 'id' => $embargo->id]); ?>">
  <div class="card mb-4">
    <div class="card-header bg-success text-white">
      <h4 class="mb-0"><i class="fas fa-unlock me-2"></i><?php echo __('Confirm Lift Embargo'); ?></h4>
    </div>
    <div class="card-body">
      <div class="row mb-3">
        <div class="col-md-4"><strong><?php echo __('Embargo Type'); ?>:</strong></div>
        <div class="col-md-8"><?php echo ucfirst(str_replace('_', ' ', $embargo->embargo_type ?? 'full')); ?></div>
      </div>
      <div class="row mb-3">
        <div class="col-md-4"><strong><?php echo __('Start Date'); ?>:</strong></div>
        <div class="col-md-8"><?php echo $embargo->start_date; ?></div>
      </div>
      <?php if ($embargo->end_date): ?>
      <div class="row mb-3">
        <div class="col-md-4"><strong><?php echo __('End Date'); ?>:</strong></div>
        <div class="col-md-8"><?php echo $embargo->end_date; ?></div>
      </div>
      <?php endif; ?>
      
      <hr>
      
      <div class="mb-3">
        <label for="lift_reason" class="form-label"><?php echo __('Reason for lifting (optional)'); ?></label>
        <textarea name="lift_reason" id="lift_reason" class="form-control" rows="3" placeholder="<?php echo __('e.g., Embargo period completed, Permission granted, Error correction'); ?>"></textarea>
      </div>
    </div>
  </div>

  <div class="actions">
    <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $resource->slug]); ?>" class="btn btn-secondary">
      <?php echo __('Cancel'); ?>
    </a>
    <button type="submit" class="btn btn-success">
      <i class="fas fa-unlock"></i> <?php echo __('Lift Embargo'); ?>
    </button>
  </div>
</form>

<?php decorate_with('layout_2col.php'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
  <div class="card">
    <div class="card-header bg-light">
      <h5 class="mb-0"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></h5>
    </div>
    <div class="card-body">
      <a href="/<?php echo esc_entities($resource->slug); ?>" class="btn btn-outline-secondary w-100">
        <i class="fas fa-arrow-left me-2"></i><?php echo __('Cancel and return'); ?>
      </a>
    </div>
  </div>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-eraser me-2"></i><?php echo __('Clear Extended Rights'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="card">
  <div class="card-header bg-warning">
    <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i><?php echo __('Confirm Clear Rights'); ?></h4>
  </div>
  <div class="card-body">
    <p class="lead"><?php echo __('Are you sure you want to clear all extended rights from this record?'); ?></p>

    <div class="alert alert-info">
      <strong><?php echo __('The following rights will be removed:'); ?></strong>
      <ul class="mb-0 mt-2">
        <?php if ($currentRights->rights_statement): ?>
          <li><i class="fas fa-balance-scale me-1"></i><?php echo __('Rights Statement:'); ?> <?php echo esc_entities($currentRights->rights_statement->name); ?></li>
        <?php endif; ?>
        <?php if ($currentRights->cc_license): ?>
          <li><i class="fab fa-creative-commons me-1"></i><?php echo __('Creative Commons License:'); ?> <?php echo esc_entities($currentRights->cc_license->name); ?></li>
        <?php endif; ?>
        <?php $tkLabels = $sf_data->getRaw('currentRights')->tk_labels; ?>
        <?php if (!empty($tkLabels)): ?>
          <li><i class="fas fa-hand-holding-heart me-1"></i><?php echo __('Traditional Knowledge Labels:'); ?> <?php echo esc_entities(implode(', ', $tkLabels)); ?></li>
        <?php endif; ?>
        <?php if ($currentRights->rights_holder): ?>
          <li><i class="fas fa-user me-1"></i><?php echo __('Rights Holder:'); ?> <?php echo esc_entities($currentRights->rights_holder->name); ?></li>
        <?php endif; ?>
        <?php if (!$currentRights->rights_statement && !$currentRights->cc_license && empty($currentRights->tk_labels) && !$currentRights->rights_holder): ?>
          <li class="text-muted"><em><?php echo __('No extended rights currently assigned'); ?></em></li>
        <?php endif; ?>
      </ul>
    </div>

    <p class="text-muted small"><?php echo __('Note: This action will not affect embargoes. Use the embargo management to lift embargoes.'); ?></p>

    <form method="post">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-danger">
          <i class="fas fa-eraser me-2"></i><?php echo __('Yes, clear all rights'); ?>
        </button>
        <a href="/<?php echo esc_entities($resource->slug); ?>" class="btn btn-secondary">
          <i class="fas fa-times me-2"></i><?php echo __('Cancel'); ?>
        </a>
      </div>
    </form>
  </div>
</div>
<?php end_slot(); ?>

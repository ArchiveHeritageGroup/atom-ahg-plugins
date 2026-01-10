<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-lock me-2"></i><?php echo __('Access Restricted'); ?></h1>
<?php end_slot(); ?>

<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card border-warning">
      <div class="card-header bg-warning text-dark">
        <h4 class="mb-0">
          <i class="fas fa-exclamation-triangle me-2"></i>
          <?php echo esc_entities($embargoInfo['type_label'] ?? __('Access Restricted')); ?>
        </h4>
      </div>
      <div class="card-body text-center py-5">
        <i class="fas fa-lock fa-5x text-warning mb-4"></i>
        
        <h3 class="mb-3"><?php echo __('This record is under embargo'); ?></h3>
        
        <?php if (!empty($embargoInfo['public_message'])): ?>
          <p class="lead"><?php echo esc_entities($embargoInfo['public_message']); ?></p>
        <?php else: ?>
          <p class="lead"><?php echo __('Access to this material is currently restricted and not available for public viewing.'); ?></p>
        <?php endif; ?>
        
        <?php if (!$embargoInfo['is_perpetual'] && !empty($embargoInfo['end_date'])): ?>
          <div class="alert alert-info d-inline-block mt-3">
            <i class="fas fa-calendar-alt me-2"></i>
            <?php echo __('This record will become available on %1%', ['%1%' => date('j F Y', strtotime($embargoInfo['end_date']))]); ?>
          </div>
        <?php elseif ($embargoInfo['is_perpetual']): ?>
          <div class="alert alert-secondary d-inline-block mt-3">
            <i class="fas fa-ban me-2"></i>
            <?php echo __('This restriction is indefinite'); ?>
          </div>
        <?php endif; ?>
        
        <div class="mt-4">
          <a href="<?php echo url_for('@homepage'); ?>" class="btn btn-primary">
            <i class="fas fa-home me-2"></i><?php echo __('Return to homepage'); ?>
          </a>
          <a href="javascript:history.back()" class="btn btn-outline-secondary ms-2">
            <i class="fas fa-arrow-left me-2"></i><?php echo __('Go back'); ?>
          </a>
        </div>
        
        <?php if (sfContext::getInstance()->getUser()->isAuthenticated()): ?>
          <hr class="my-4">
          <p class="text-muted small">
            <i class="fas fa-info-circle me-1"></i>
            <?php echo __('If you believe you should have access to this record, please contact the repository administrator.'); ?>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

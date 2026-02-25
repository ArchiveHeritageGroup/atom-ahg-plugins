<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Unsubscribe'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card mt-5">
      <div class="card-body p-4 text-center">
        <?php if (!empty($result['success'])): ?>
          <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
          <h2 class="h4 mb-2"><?php echo __('Unsubscribed'); ?></h2>
          <p class="text-muted mb-3">
            <?php echo __('You have been unsubscribed from the AtoM Registry newsletter.'); ?>
            <?php if (!empty($result['email'])): ?>
              <br><strong><?php echo htmlspecialchars($result['email'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php endif; ?>
          </p>
          <p class="small text-muted"><?php echo __('Changed your mind?'); ?> <a href="<?php echo url_for(['module' => 'registry', 'action' => 'newsletterSubscribe']); ?>"><?php echo __('Re-subscribe'); ?></a></p>
        <?php else: ?>
          <i class="fas fa-exclamation-circle text-warning fa-3x mb-3"></i>
          <h2 class="h4 mb-2"><?php echo __('Unsubscribe Failed'); ?></h2>
          <p class="text-muted"><?php echo htmlspecialchars($error ?? 'Invalid unsubscribe link.', ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'index']); ?>" class="btn btn-outline-primary mt-2">
          <i class="fas fa-home me-1"></i> <?php echo __('Back to Registry'); ?>
        </a>
      </div>
    </div>
  </div>
</div>

<?php end_slot(); ?>

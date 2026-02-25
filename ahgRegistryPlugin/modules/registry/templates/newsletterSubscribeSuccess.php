<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Subscribe to Newsletter'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Newsletter')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-6">

    <div class="card">
      <div class="card-body p-4">
        <h1 class="h3 mb-3"><i class="fas fa-envelope text-primary me-2"></i><?php echo __('Subscribe to Newsletter'); ?></h1>
        <p class="text-muted mb-4"><?php echo __('Stay up to date with the latest news from the AtoM Registry community. Get updates on new institutions, vendors, software releases, and community events.'); ?></p>

        <?php if (!empty($result['success']) && empty($result['already_subscribed'])): ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle me-1"></i>
            <?php if (!empty($result['resubscribed'])): ?>
              <?php echo __('Welcome back! You have been re-subscribed to the newsletter.'); ?>
            <?php else: ?>
              <?php echo __('You have been subscribed to the newsletter. Thank you!'); ?>
            <?php endif; ?>
          </div>
        <?php elseif (!empty($result['already_subscribed'])): ?>
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-1"></i>
            <?php echo __('You are already subscribed to the newsletter.'); ?>
          </div>
        <?php else: ?>

          <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>

          <form method="post">
            <div class="mb-3">
              <label for="ns-name" class="form-label"><?php echo __('Name'); ?></label>
              <input type="text" class="form-control" id="ns-name" name="name" value="<?php echo htmlspecialchars($sf_user->isAuthenticated() ? ($sf_user->getAttribute('user_name', '')) : '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="mb-3">
              <label for="ns-email" class="form-label"><?php echo __('Email Address'); ?> <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="ns-email" name="email" required value="<?php echo htmlspecialchars($sf_user->isAuthenticated() ? ($sf_user->getAttribute('user_email', '')) : '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <button type="submit" class="btn btn-primary w-100">
              <i class="fas fa-paper-plane me-1"></i> <?php echo __('Subscribe'); ?>
            </button>
          </form>

        <?php endif; ?>

        <hr class="my-3">
        <p class="small text-muted text-center mb-0">
          <?php echo __('You can unsubscribe at any time via the link in each newsletter.'); ?>
        </p>
      </div>
    </div>

  </div>
</div>

<?php end_slot(); ?>

<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Forgot Password'); ?> — AtoM Registry<?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Sign In'), 'url' => url_for(['module' => 'registry', 'action' => 'login'])],
  ['label' => __('Forgot Password')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-5">

    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h4 class="card-title mb-1"><i class="fas fa-key me-2 text-primary"></i><?php echo __('Forgot Password'); ?></h4>
        <p class="text-muted small mb-3"><?php echo __('Enter your email address and we will send you a link to reset your password.'); ?></p>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
          <div class="alert alert-success py-2 small"><i class="fas fa-check-circle me-1"></i> <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php else: ?>

        <form method="post" action="/registry/forgot-password">
          <div class="mb-3">
            <label for="email" class="form-label small fw-semibold"><?php echo __('Email Address'); ?></label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
              <input type="email" class="form-control" id="email" name="email" required autofocus placeholder="you@example.com">
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 mb-2">
            <i class="fas fa-paper-plane me-1"></i> <?php echo __('Send Reset Link'); ?>
          </button>
        </form>

        <?php endif; ?>

        <hr class="my-3">
        <p class="text-center small mb-0">
          <a href="/registry/login" class="fw-semibold"><i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Sign In'); ?></a>
        </p>
      </div>
    </div>

  </div>
</div>

<?php end_slot(); ?>

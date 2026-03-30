<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Reset Password'); ?> — AtoM Registry<?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Reset Password')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-5">

    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h4 class="card-title mb-1"><i class="fas fa-lock me-2 text-primary"></i><?php echo __('Reset Password'); ?></h4>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php if (empty($validToken)): ?>
            <p class="text-center small">
              <a href="/registry/forgot-password" class="fw-semibold"><?php echo __('Request a new reset link'); ?></a>
            </p>
          <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($validToken)): ?>
        <p class="text-muted small mb-3"><?php echo __('Enter your new password below.'); ?></p>

        <form method="post" action="/registry/reset-password?token=<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

          <div class="mb-3">
            <label for="password" class="form-label small fw-semibold"><?php echo __('New Password'); ?></label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
              <input type="password" class="form-control" id="password" name="password" required minlength="8" placeholder="<?php echo __('Minimum 8 characters'); ?>">
            </div>
          </div>

          <div class="mb-3">
            <label for="password_confirm" class="form-label small fw-semibold"><?php echo __('Confirm Password'); ?></label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
              <input type="password" class="form-control" id="password_confirm" name="password_confirm" required minlength="8">
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 mb-2">
            <i class="fas fa-save me-1"></i> <?php echo __('Reset Password'); ?>
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

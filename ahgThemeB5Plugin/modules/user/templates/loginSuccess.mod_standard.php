<?php decorate_with('layout_1col'); ?>
<?php use_helper('Javascript'); ?>
<?php slot('content'); ?>
  <?php echo $form->renderGlobalErrors(); ?>
  <?php echo $form->renderFormTag(url_for(['module' => 'user', 'action' => 'login'])); ?>
    <?php echo $form->renderHiddenFields(); ?>
    <div class="accordion mb-3">
      <div class="accordion-item">
        <h2 class="accordion-header" id="login-heading">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#login-collapse" aria-expanded="true" aria-controls="login-collapse">
            <?php if ('user' != $sf_request->module || 'login' != $sf_request->action) { ?>
              <?php echo __('Please log in to access that page'); ?>
            <?php } else { ?>
              <?php echo __('Log in'); ?>
            <?php } ?>
          </button>
        </h2>
        <div id="login-collapse" class="accordion-collapse collapse show" aria-labelledby="login-heading">
          <div class="accordion-body">
            <?php echo render_field($form->email, null, ['type' => 'email', 'autofocus' => 'autofocus', 'required' => 'required']); ?>
            <?php echo render_field($form->password, null, ['type' => 'password', 'autocomplete' => 'off', 'required' => 'required']); ?>
          </div>
        </div>
      </div>
    </div>
    <div class="alert alert-info py-2 mb-3">
      <i class="fas fa-info-circle me-1"></i><strong><?php echo __('Demo'); ?>:</strong>
      <code>louise@theahg.co.za</code> / <code>Password@123</code>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <button type="submit" class="btn atom-btn-outline-success">
        <i class="fas fa-sign-in-alt me-1"></i><?php echo __('Log in'); ?>
      </button>
      <a href="<?php echo url_for(['module' => 'user', 'action' => 'passwordReset']); ?>" class="text-muted">
        <i class="fas fa-key me-1"></i><?php echo __('Forgot password?'); ?>
      </a>
    </div>
  </form>

  <hr class="my-4">

  <!-- Researcher Registration -->
  <div class="card border-primary">
    <div class="card-body text-center">
      <h5 class="card-title"><i class="fas fa-user-graduate text-primary me-2"></i><?php echo __('New Researcher?'); ?></h5>
      <p class="card-text text-muted">
        <?php echo __('Register to access the reading room, request archival materials, and save your research.'); ?>
      </p>
      <a href="<?php echo url_for(['module' => 'research', 'action' => 'publicRegister']); ?>" class="btn btn-primary">
        <i class="fas fa-user-plus me-2"></i><?php echo __('Register as Researcher'); ?>
      </a>
    </div>
  </div>

  <!-- Research Services Link -->
  <div class="text-center mt-3">
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>" class="text-muted">
      <i class="fas fa-book-reader me-1"></i><?php echo __('View Research Services'); ?>
    </a>
  </div>
<?php end_slot(); ?>

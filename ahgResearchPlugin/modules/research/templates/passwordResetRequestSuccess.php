<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-key text-primary me-2"></i><?php echo __('Reset Password'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">
        <i class="fas fa-envelope me-2"></i><?php echo __('Request Password Reset'); ?>
      </div>
      <div class="card-body">
        <p class="text-muted mb-4">
          <?php echo __('Enter your email address and we will send you instructions to reset your password.'); ?>
        </p>
        <form method="post">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Email Address'); ?> <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required placeholder="your.email@example.com">
          </div>
          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-paper-plane me-2"></i><?php echo __('Send Reset Link'); ?>
            </button>
          </div>
        </form>
        <hr>
        <div class="text-center">
          <a href="<?php echo url_for(['module' => 'user', 'action' => 'login']); ?>"><?php echo __('Back to Login'); ?></a>
          <span class="mx-2">|</span>
          <a href="<?php echo url_for(['module' => 'research', 'action' => 'publicRegister']); ?>"><?php echo __('Create Account'); ?></a>
        </div>
      </div>
    </div>
  </div>
</div>
<?php end_slot() ?>

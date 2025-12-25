<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-key text-primary me-2"></i><?php echo __('Set New Password'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">
        <i class="fas fa-lock me-2"></i><?php echo __('Create New Password'); ?>
      </div>
      <div class="card-body">
        <p class="mb-4">
          <?php echo __('Enter a new password for account:'); ?> <strong><?php echo htmlspecialchars($user->username); ?></strong>
        </p>
        <form method="post">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
          
          <div class="mb-3">
            <label class="form-label"><?php echo __('New Password'); ?> <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required minlength="8">
            <small class="text-muted"><?php echo __('At least 8 characters'); ?></small>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Confirm New Password'); ?> <span class="text-danger">*</span></label>
            <input type="password" name="confirm_password" class="form-control" required minlength="8">
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-check me-2"></i><?php echo __('Update Password'); ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php end_slot() ?>

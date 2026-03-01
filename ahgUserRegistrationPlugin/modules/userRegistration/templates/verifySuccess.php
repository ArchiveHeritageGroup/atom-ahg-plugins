<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Email Verification'); ?></h1>
<?php end_slot(); ?>

<div class="row justify-content-center">
  <div class="col-md-8 col-lg-6">
    <?php if (!empty($verified)): ?>
      <div class="alert alert-success">
        <h5><?php echo __('Email verified successfully!'); ?></h5>
        <p><?php echo __('Your registration is now pending administrator approval. You will receive an email once your account has been reviewed.'); ?></p>
      </div>
    <?php else: ?>
      <div class="alert alert-danger">
        <h5><?php echo __('Verification failed'); ?></h5>
        <p><?php echo esc_entities($error ?? __('An unknown error occurred.')); ?></p>
        <a href="<?php echo url_for(['module' => 'userRegistration', 'action' => 'register']); ?>" class="btn btn-outline-primary btn-sm mt-2">
          <?php echo __('Register again'); ?>
        </a>
      </div>
    <?php endif; ?>
  </div>
</div>

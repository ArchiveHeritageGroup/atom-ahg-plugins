<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0"><?php echo __('Change password'); ?></h1>
    <span class="small">
      <?php echo esc_specialchars($userRecord['username']); ?>
      <?php if ($isSelf) { ?>
        <span class="badge bg-info"><?php echo __('(you)'); ?></span>
      <?php } ?>
    </span>
  </div>
<?php end_slot(); ?>

<?php if (!empty($errors)) { ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $error) { ?>
        <li><?php echo esc_specialchars($error); ?></li>
      <?php } ?>
    </ul>
  </div>
<?php } ?>

<form method="post" action="<?php echo url_for('@user_password_override?slug=' . $userRecord['slug']); ?>" class="mt-2" autocomplete="off">

  <?php if (!$sf_user->isAdministrator()) { ?>
    <div class="mb-3 row">
      <label class="col-sm-3 col-form-label" for="current_pw"><?php echo __('Current password'); ?></label>
      <div class="col-sm-6">
        <input type="password" class="form-control" id="current_pw" name="current_pw" autocomplete="current-password" required>
      </div>
    </div>
  <?php } ?>

  <div class="mb-3 row">
    <label class="col-sm-3 col-form-label" for="new_pw"><?php echo __('New password'); ?></label>
    <div class="col-sm-6">
      <input type="password" class="form-control" id="new_pw" name="new_pw" autocomplete="new-password" required>
      <div class="form-text"><?php echo __('At least 8 characters.'); ?></div>
    </div>
  </div>

  <div class="mb-3 row">
    <label class="col-sm-3 col-form-label" for="confirm_pw"><?php echo __('Confirm new password'); ?></label>
    <div class="col-sm-6">
      <input type="password" class="form-control" id="confirm_pw" name="confirm_pw" autocomplete="new-password" required>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-6 offset-sm-3">
      <button type="submit" class="btn btn-primary"><?php echo __('Update password'); ?></button>
      <a class="btn btn-secondary" href="<?php echo url_for('@user_view_override?slug=' . $userRecord['slug']); ?>"><?php echo __('Cancel'); ?></a>
    </div>
  </div>

</form>

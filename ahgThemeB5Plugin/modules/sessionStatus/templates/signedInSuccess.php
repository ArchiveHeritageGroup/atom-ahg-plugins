<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo $signedIn ? __('You are signed in again') : __('You are not signed in'); ?></h1>
<?php end_slot(); ?>

<?php if ($signedIn) { ?>
  <div class="alert alert-success" role="status">
    <p class="mb-2"><?php echo __('Go back to the tab you were working in. What you typed is still there: press Save to keep it.'); ?></p>
    <p class="mb-0"><?php echo __('You can close this tab.'); ?></p>
  </div>
<?php } else { ?>
  <div class="alert alert-warning" role="status">
    <p class="mb-0"><?php echo link_to(__('Sign in'), ['module' => 'user', 'action' => 'login', 'next' => url_for('@ahg_session_signed_in')]); ?></p>
  </div>
<?php } ?>

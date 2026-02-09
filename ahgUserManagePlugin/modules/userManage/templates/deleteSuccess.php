<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Are you sure you want to delete %1%?', ['%1%' => esc_specialchars($userRecord['username'])]); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php echo $form->renderGlobalErrors(); ?>

  <form method="post" action="<?php echo url_for('@user_delete_override?slug=' . $userRecord['slug']); ?>">

    <?php echo $form->renderHiddenFields(); ?>
    <input type="hidden" name="sf_method" value="delete">

    <div class="alert alert-warning">
      <?php echo __('This action will permanently delete this user account and all associated permissions. This cannot be undone.'); ?>
    </div>

    <ul class="actions mb-3 nav gap-2">
      <li><?php echo link_to(__('Cancel'), '@user_view_override?slug=' . $userRecord['slug'], ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
      <li><input class="btn atom-btn-outline-danger" type="submit" value="<?php echo __('Delete'); ?>"></li>
    </ul>

  </form>

<?php end_slot(); ?>

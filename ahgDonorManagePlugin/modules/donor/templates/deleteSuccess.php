<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Are you sure you want to delete %1%?', ['%1%' => esc_specialchars($donor['authorizedFormOfName'] ?: __('Untitled'))]); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php echo $form->renderGlobalErrors(); ?>

  <form method="post" action="<?php echo url_for(['module' => 'donor', 'slug' => $donor['slug'], 'action' => 'delete']); ?>">

    <?php echo $form->renderHiddenFields(); ?>
    <input type="hidden" name="sf_method" value="delete">

    <ul class="actions mb-3 nav gap-2">
      <li><?php echo link_to(__('Cancel'), ['module' => 'donor', 'slug' => $donor['slug']], ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
      <li><input class="btn atom-btn-outline-danger" type="submit" value="<?php echo __('Delete'); ?>"></li>
    </ul>

  </form>

<?php end_slot(); ?>

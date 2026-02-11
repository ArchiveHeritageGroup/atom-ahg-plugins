<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Are you sure you want to delete %1%?', ['%1%' => esc_specialchars($pageRecord['title'] ?: __('Untitled'))]); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php echo $form->renderGlobalErrors(); ?>

  <form method="post" action="<?php echo url_for('@staticpage_delete?id=' . $sf_data->getRaw('pageRecord')['id']); ?>">

    <?php echo $form->renderHiddenFields(); ?>
    <input type="hidden" name="sf_method" value="delete">

    <div class="alert alert-warning">
      <?php echo __('This action will permanently delete this static page. This cannot be undone.'); ?>
    </div>

    <div class="mb-3">
      <strong><?php echo __('Title'); ?>:</strong>
      <?php echo esc_specialchars($pageRecord['title'] ?? ''); ?>
    </div>

    <div class="mb-3">
      <strong><?php echo __('Slug'); ?>:</strong>
      <code><?php echo esc_specialchars($pageRecord['slug'] ?? ''); ?></code>
    </div>

    <ul class="actions mb-3 nav gap-2">
      <li><?php echo link_to(__('Cancel'), '@staticpage_list', ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
      <li><input class="btn atom-btn-outline-danger" type="submit" value="<?php echo __('Delete'); ?>"></li>
    </ul>

  </form>

<?php end_slot(); ?>

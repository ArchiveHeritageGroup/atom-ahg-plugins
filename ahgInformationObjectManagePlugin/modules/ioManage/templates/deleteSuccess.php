<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Are you sure you want to delete %1%?', ['%1%' => esc_specialchars($io['title'] ?: __('Untitled'))]); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php if (!empty($errors)) { ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($sf_data->getRaw('errors') as $error) { ?>
          <li><?php echo $error; ?></li>
        <?php } ?>
      </ul>
    </div>
  <?php } ?>

  <?php if ($hasChildren) { ?>
    <div class="alert alert-warning">
      <?php echo __('This description has child records. You must delete or move all children before you can delete this description.'); ?>
    </div>
  <?php } ?>

  <?php echo $form->renderGlobalErrors(); ?>

  <form method="post" action="<?php echo url_for('@io_delete_override?slug=' . $io['slug']); ?>">

    <?php echo $form->renderHiddenFields(); ?>
    <input type="hidden" name="sf_method" value="delete">

    <ul class="actions mb-3 nav gap-2">
      <li><?php echo link_to(__('Cancel'), '/' . $io['slug'], ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
      <?php if (!$hasChildren) { ?>
        <li><input class="btn atom-btn-outline-danger" type="submit" value="<?php echo __('Delete'); ?>"></li>
      <?php } ?>
    </ul>

  </form>

<?php end_slot(); ?>

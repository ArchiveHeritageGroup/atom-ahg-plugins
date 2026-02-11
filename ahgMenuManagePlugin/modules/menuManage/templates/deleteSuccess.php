<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Are you sure you want to delete %1%?', ['%1%' => esc_specialchars($menuRecord['label'] ?: $menuRecord['name'] ?: __('Untitled'))]); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php
    $rawRecord = $sf_data->getRaw('menuRecord');
    $rawIsProtected = $sf_data->getRaw('isProtected');
  ?>

  <?php if ($rawIsProtected) { ?>
    <div class="alert alert-danger">
      <?php echo __('This menu item is protected and cannot be deleted.'); ?>
    </div>

    <ul class="actions mb-3 nav gap-2">
      <li><?php echo link_to(__('Back to list'), '@menu_list', ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
    </ul>
  <?php } else { ?>

    <?php echo $form->renderGlobalErrors(); ?>

    <form method="post" action="<?php echo url_for('@menu_delete?id=' . $rawRecord['id']); ?>">

      <?php echo $form->renderHiddenFields(); ?>
      <input type="hidden" name="sf_method" value="delete">

      <div class="alert alert-warning">
        <?php echo __('This action will permanently delete this menu item and all its children. This cannot be undone.'); ?>
      </div>

      <div class="mb-3">
        <strong><?php echo __('Name'); ?>:</strong>
        <?php echo esc_specialchars($rawRecord['name'] ?? ''); ?>
      </div>

      <div class="mb-3">
        <strong><?php echo __('Label'); ?>:</strong>
        <?php echo esc_specialchars($rawRecord['label'] ?? ''); ?>
      </div>

      <?php if (!empty($rawRecord['path'])) { ?>
        <div class="mb-3">
          <strong><?php echo __('Path'); ?>:</strong>
          <code><?php echo esc_specialchars($rawRecord['path']); ?></code>
        </div>
      <?php } ?>

      <?php if ($rawRecord['hasChildren'] ?? false) { ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-triangle me-1"></i>
          <?php echo __('Warning: This menu item has children. Deleting it will also delete all child menu items.'); ?>
        </div>
      <?php } ?>

      <ul class="actions mb-3 nav gap-2">
        <li><?php echo link_to(__('Cancel'), '@menu_list', ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
        <li><input class="btn atom-btn-outline-danger" type="submit" value="<?php echo __('Delete'); ?>"></li>
      </ul>

    </form>

  <?php } ?>

<?php end_slot(); ?>

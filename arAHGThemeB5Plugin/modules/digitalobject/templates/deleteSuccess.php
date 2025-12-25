<?php decorate_with('layout_1col') ?>

<?php
$resource = $sf_data->getRaw('resource');
$parent = $sf_data->getRaw('parent');
$redirectTarget = $sf_data->getRaw('redirectTarget');
?>

<?php slot('title') ?>
  <h1><?php echo __('Are you sure you want to delete this %1%?', ['%1%' => mb_strtolower(sfConfig::get('app_ui_label_digitalobject', 'digital object'))]) ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

  <div class="alert alert-warning">
    <?php if ($parent): ?>
      <p><?php echo __('This will delete the derivative image. The master file will not be affected.') ?></p>
    <?php else: ?>
      <p><?php echo __('This will permanently delete the digital object and all its derivatives.') ?></p>
    <?php endif; ?>
  </div>

  <?php echo render_show(__('Filename'), render_value($resource->name)) ?>

  <?php echo render_show(__('Filesize'), hr_filesize($resource->byte_size)) ?>

  <form method="post">
    <input type="hidden" name="delete" value="1">

    <section class="actions">
      <ul>
        <?php if ($parent): ?>
          <li><?php echo link_to(__('Cancel'), ['module' => 'digitalobject', 'action' => 'edit', 'id' => $parent->id], ['class' => 'c-btn']) ?></li>
        <?php elseif ($redirectTarget): ?>
          <li><?php echo link_to(__('Cancel'), ['module' => $redirectTarget->module, 'slug' => $redirectTarget->slug], ['class' => 'c-btn']) ?></li>
        <?php else: ?>
          <li><?php echo link_to(__('Cancel'), '@homepage', ['class' => 'c-btn']) ?></li>
        <?php endif; ?>
        <li><input class="c-btn c-btn-delete" type="submit" value="<?php echo __('Delete') ?>"></li>
      </ul>
    </section>
  </form>

<?php end_slot() ?>
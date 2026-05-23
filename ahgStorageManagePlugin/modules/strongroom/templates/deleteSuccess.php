<?php
/*
 * heratio#145 — Strongroom delete confirmation (AtoM Heratio).
 * GET renders this confirm view; POST (back to executeDelete) performs the delete.
 */
?>
<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Delete strongroom'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <div class="alert alert-warning">
    <?php echo __('You are about to delete %1%. This cannot be undone.',
        ['%1%' => esc_specialchars($room->name)]); ?>
  </div>

  <?php if ($occupantCount > 0) { ?>
    <div class="alert alert-danger">
      <strong><?php echo __('Blocked:'); ?></strong>
      <?php echo format_number_choice(
          '[1]%1% physical object is still assigned to this strongroom. Move it out before deleting.|(1,+Inf]%1% physical objects are still assigned to this strongroom. Move them out before deleting.',
          ['%1%' => $occupantCount],
          $occupantCount
      ); ?>
      <div class="mt-2">
        <a href="<?php echo url_for(['module' => 'strongroom', 'action' => 'show', 'slug' => $room->slug]); ?>"
           class="btn btn-sm btn-outline-secondary"><?php echo __('Back to strongroom'); ?></a>
      </div>
    </div>
  <?php } else { ?>
    <form method="post"
          action="<?php echo url_for(['module' => 'strongroom', 'action' => 'delete', 'slug' => $room->slug]); ?>">
      <button type="submit" class="btn btn-danger">
        <i class="fas fa-trash me-1"></i><?php echo __('Delete strongroom'); ?>
      </button>
      <a href="<?php echo url_for(['module' => 'strongroom', 'action' => 'show', 'slug' => $room->slug]); ?>"
         class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
    </form>
  <?php } ?>
<?php end_slot(); ?>

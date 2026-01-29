<?php if ($wide): ?>
<a href="#" class="atom-icon-link clipboard"
  data-clipboard-slug="<?php echo $slug; ?>"
  data-clipboard-type="<?php echo $type; ?>"
  data-title="<?php echo __('Add'); ?>"
  data-alt-title="<?php echo __('Remove'); ?>">
  <i class="fas fa-fw fa-paperclip me-1" aria-hidden="true"></i><?php echo __('Add'); ?>
</a>
<?php else: ?>
<button
  class="btn atom-btn-white ms-auto active-primary clipboard"
  data-clipboard-slug="<?php echo $slug; ?>"
  data-clipboard-type="<?php echo $type; ?>"
  data-tooltip="true"
  data-title="<?php echo __('Add to clipboard'); ?>"
  data-alt-title="<?php echo __('Remove from clipboard'); ?>">
  <i class="fas fa-lg fa-paperclip" aria-hidden="true"></i>
  <span class="visually-hidden"><?php echo __('Add to clipboard'); ?></span>
</button>
<?php endif; ?>

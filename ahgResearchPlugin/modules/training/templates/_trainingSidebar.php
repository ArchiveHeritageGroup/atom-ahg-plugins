<div class="list-group mb-4">
  <a href="<?php echo url_for(['module' => 'training', 'action' => 'index']); ?>"
     class="list-group-item list-group-item-action <?php echo ($active ?? '') === 'training' ? 'active' : ''; ?>">
    <i class="fas fa-graduation-cap me-2"></i><?php echo __('Training courses'); ?>
  </a>
  <a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"
     class="list-group-item list-group-item-action">
    <i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Research'); ?>
  </a>
</div>

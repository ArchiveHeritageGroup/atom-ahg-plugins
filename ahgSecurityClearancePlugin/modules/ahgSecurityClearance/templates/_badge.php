<?php
/**
 * Security Badge Component Template.
 *
 * Displays security classification badge for an information object.
 */
if (!$classification): ?>
  <span class="badge bg-success security-badge" title="<?php echo __('Public'); ?>">
    <i class="fa fa-globe"></i> <?php echo __('Public'); ?>
  </span>
<?php else: ?>
  <?php
    $badgeClass = 'bg-secondary';
    switch ($classification->classificationLevel) {
        case 0: $badgeClass = 'bg-success'; break;
        case 1: $badgeClass = 'bg-info'; break;
        case 2: $badgeClass = 'bg-warning text-dark'; break;
        case 3: $badgeClass = 'bg-orange'; break;
        case 4: $badgeClass = 'bg-danger'; break;
        case 5: $badgeClass = 'bg-purple'; break;
    }
  ?>
  <span class="badge <?php echo $badgeClass; ?> security-badge" 
        title="<?php echo $classification->classificationName; ?>">
    <i class="<?php echo $classification->classificationIcon ?? 'fa-lock'; ?>"></i>
    <?php echo $classification->classificationName; ?>
  </span>
<?php endif; ?>

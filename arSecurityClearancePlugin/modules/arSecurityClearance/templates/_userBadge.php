<?php
/**
 * User Clearance Badge Component Template.
 *
 * Displays user's security clearance level.
 */
if (!$clearance): ?>
  <span class="badge bg-secondary security-badge" title="<?php echo __('No clearance'); ?>">
    <i class="fa fa-user"></i> <?php echo __('No Clearance'); ?>
  </span>
<?php else: ?>
  <?php
    $badgeClass = 'bg-secondary';
    switch ($clearance->classificationLevel) {
        case 0: $badgeClass = 'bg-success'; break;
        case 1: $badgeClass = 'bg-info'; break;
        case 2: $badgeClass = 'bg-warning text-dark'; break;
        case 3: $badgeClass = 'bg-orange'; break;
        case 4: $badgeClass = 'bg-danger'; break;
        case 5: $badgeClass = 'bg-purple'; break;
    }
    
    $expired = $clearance->expiresAt && strtotime($clearance->expiresAt) < time();
  ?>
  <span class="badge <?php echo $expired ? 'bg-secondary' : $badgeClass; ?> security-badge" 
        title="<?php echo $clearance->classificationName; ?><?php echo $expired ? ' ('.__('Expired').')' : ''; ?>">
    <i class="<?php echo $clearance->classificationIcon ?? 'fa-user-shield'; ?>"></i>
    <?php echo $clearance->classificationName; ?>
    <?php if ($expired): ?>
      <small>(<?php echo __('Expired'); ?>)</small>
    <?php endif; ?>
  </span>
<?php endif; ?>

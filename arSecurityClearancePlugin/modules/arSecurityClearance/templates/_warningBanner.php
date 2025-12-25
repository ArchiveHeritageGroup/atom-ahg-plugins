<?php
/**
 * Security Warning Banner Component Template.
 *
 * Displays classification warning banner at top of classified records.
 * Include in indexSuccess.php templates for information objects.
 */
if (!$classification) {
    return; // No classification - no banner
}

$bannerClass = 'alert-secondary';
$borderClass = '';
switch ($classification->classificationLevel) {
    case 1:
        $bannerClass = 'alert-info';
        break;
    case 2:
        $bannerClass = 'alert-warning';
        break;
    case 3:
        $bannerClass = 'alert-warning';
        $borderClass = 'security-classified';
        break;
    case 4:
        $bannerClass = 'alert-danger';
        $borderClass = 'security-secret';
        break;
    case 5:
        $bannerClass = 'alert-danger';
        $borderClass = 'security-top-secret';
        break;
}
?>

<div class="alert <?php echo $bannerClass; ?> <?php echo $borderClass; ?> d-flex align-items-center mb-3" role="alert">
  <i class="<?php echo $classification->classificationIcon ?? 'fa-lock'; ?> fa-2x me-3"></i>
  <div>
    <strong><?php echo __('CLASSIFIED: %1%', ['%1%' => strtoupper($classification->classificationName)]); ?></strong>
    <?php if ($classification->handlingInstructions): ?>
      <br><small><?php echo $classification->handlingInstructions; ?></small>
    <?php endif; ?>
  </div>
  <div class="ms-auto">
    <span class="badge" style="background-color: <?php echo $classification->classificationColor; ?>; font-size: 1rem;">
      <?php echo __('Level %1%', ['%1%' => $classification->classificationLevel]); ?>
    </span>
  </div>
</div>

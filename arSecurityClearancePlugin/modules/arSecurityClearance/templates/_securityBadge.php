<?php
/**
 * Security Classification Badge for indexSuccess.php
 * Displays classification badge when record is classified
 */

require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
require_once sfConfig::get('sf_root_dir').'/atom-framework/src/Services/SecurityClearanceService.php';

$classification = \AtomExtensions\Services\SecurityClearanceService::getObjectClassification($resource->id);

if (!$classification) {
    return; // No classification = public, don't show anything
}

$badgeClass = 'bg-success';
$alertClass = 'alert-info';
switch ($classification->classificationLevel) {
    case 1: $badgeClass = 'bg-info'; $alertClass = 'alert-info'; break;
    case 2: $badgeClass = 'bg-warning text-dark'; $alertClass = 'alert-warning'; break;
    case 3: $badgeClass = 'bg-orange'; $alertClass = 'alert-warning'; break;
    case 4: $badgeClass = 'bg-danger'; $alertClass = 'alert-danger'; break;
    case 5: $badgeClass = 'bg-purple'; $alertClass = 'alert-danger'; break;
}
?>

<div class="alert <?php echo $alertClass; ?> d-flex align-items-center mb-3 py-2" role="alert">
  <div class="flex-grow-1">
    <strong><?php echo __('Security Classification'); ?>:</strong>
    <span class="badge <?php echo $badgeClass; ?> ms-2">
      <i class="fas <?php echo $classification->classificationIcon ?? 'fa-lock'; ?> me-1"></i>
      <?php echo $classification->classificationName; ?>
    </span>
    <?php if ($classification->handling_instructions): ?>
      <br><small class="text-muted"><?php echo $classification->handling_instructions; ?></small>
    <?php endif; ?>
  </div>
</div>

<style>
.bg-orange { background-color: #fd7e14 !important; color: white; }
.bg-purple { background-color: #6f42c1 !important; color: white; }
</style>

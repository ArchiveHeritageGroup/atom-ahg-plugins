<?php
/**
 * Clipboard Extended Rights Actions
 * Add batch rights operations to clipboard
 */

$clipboardCount = count($sf_user->getAttribute('clipboard', []));
if ($clipboardCount === 0) {
    return;
}

$clipboard = $sf_user->getAttribute('clipboard', []);
$objectIds = implode(',', $clipboard);
?>

<div class="card mb-3">
  <div class="card-header">
    <h5 class="mb-0"><i class="fas fa-copyright me-1"></i><?php echo __('Rights Operations'); ?></h5>
  </div>
  <div class="card-body">
    <p class="text-muted small mb-3">
      <?php echo __('Apply to %1% selected items', ['%1%' => '<strong>' . $clipboardCount . '</strong>']); ?>
    </p>
    <div class="d-grid gap-2">
      <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'batch']); ?>?object_ids=<?php echo $objectIds; ?>" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-layer-group me-1"></i><?php echo __('Batch Assign Rights'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'batch']); ?>?object_ids=<?php echo $objectIds; ?>&batch_action=embargo" class="btn btn-outline-warning btn-sm">
        <i class="fas fa-lock me-1"></i><?php echo __('Batch Apply Embargo'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'export']); ?>?ids=<?php echo $objectIds; ?>" class="btn btn-outline-success btn-sm">
        <i class="fas fa-download me-1"></i><?php echo __('Export Rights (JSON-LD)'); ?>
      </a>
    </div>
  </div>
</div>

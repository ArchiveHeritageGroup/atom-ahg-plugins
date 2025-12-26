<?php
/**
 * Rights action button
 */
$slug = $object->slug ?? '';
$canEdit = $data['rights']['can_edit'] ?? false;
$hasRights = !empty($data['rights']['records']);
$isRestricted = !($data['rights']['access_check']['allowed'] ?? true);
?>
<a href="<?php echo url_for(['module' => 'rights', 'action' => 'index', 'slug' => $slug]); ?>" 
   class="btn btn-<?php echo $isRestricted ? 'warning' : ($hasRights ? 'outline-primary' : 'outline-secondary'); ?> btn-sm me-1"
   title="<?php echo __('View rights'); ?>">
    <i class="fas fa-<?php echo $isRestricted ? 'lock' : 'balance-scale'; ?> me-1"></i>
    <?php echo __('Rights'); ?>
    <?php if ($isRestricted): ?>
        <span class="badge bg-danger ms-1">!</span>
    <?php endif; ?>
</a>

<?php
/**
 * Extended Rights Context Menu Items
 */
if (!isset($resource) || !$resource->id) {
    return;
}

$canEdit = $sf_user->isAuthenticated() && \AtomExtensions\Services\AclService::check($resource, 'update');

// Check if has extended rights
$hasRights = \Illuminate\Database\Capsule\Manager::table('extended_rights')
    ->where('object_id', $resource->id)
    ->exists();

// Check if has active embargo and get its ID
$activeEmbargo = \Illuminate\Database\Capsule\Manager::table('rights_embargo')
    ->where('object_id', $resource->id)
    ->where('status', 'active')
    ->first();

$hasEmbargo = $activeEmbargo !== null;
?>

<?php if ($canEdit): ?>
<section id="extended-rights-menu">
  <h4><?php echo __('Rights'); ?></h4>
  <ul class="list-unstyled">
    <li>
      <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'edit', 'slug' => $resource->slug]); ?>">
        <i class="fas fa-copyright fa-fw me-1"></i>
        <?php echo $hasRights ? __('Edit extended rights') : __('Add extended rights'); ?>
      </a>
    </li>
    <?php if ($hasRights): ?>
    <li>
      <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'clear', 'slug' => $resource->slug]); ?>" class="text-danger">
        <i class="fas fa-eraser fa-fw me-1"></i>
        <?php echo __('Clear extended rights'); ?>
      </a>
    </li>
    <?php endif; ?>
    <?php if ($hasEmbargo): ?>
    <li>
      <a href="<?php echo url_for(['module' => 'embargo', 'action' => 'edit', 'id' => $activeEmbargo->id]); ?>">
        <i class="fas fa-edit fa-fw me-1"></i>
        <?php echo __('Edit embargo'); ?>
      </a>
    </li>
    <li>
      <a href="<?php echo url_for(['module' => 'embargo', 'action' => 'lift', 'id' => $activeEmbargo->id]); ?>" class="text-success">
        <i class="fas fa-unlock fa-fw me-1"></i>
        <?php echo __('Lift embargo'); ?>
      </a>
    </li>
    <?php else: ?>
    <li>
      <a href="<?php echo url_for(['module' => 'embargo', 'action' => 'add', 'objectId' => $resource->id]); ?>">
        <i class="fas fa-lock fa-fw me-1"></i>
        <?php echo __('Add embargo'); ?>
      </a>
    </li>
    <?php endif; ?>
    <li>
      <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'export', 'id' => $resource->id]); ?>">
        <i class="fas fa-download fa-fw me-1"></i>
        <?php echo __('Export rights (JSON-LD)'); ?>
      </a>
    </li>
  </ul>
</section>
<?php endif; ?>

<?php if ($hasRights || $hasEmbargo): ?>
<section id="rights-status-menu">
  <h4><?php echo __('Rights Status'); ?></h4>
  <ul class="list-unstyled">
    <?php if ($hasRights): ?>
    <li><i class="fas fa-copyright text-info fa-fw me-1"></i><?php echo __('Extended rights applied'); ?></li>
    <?php endif; ?>
    <?php if ($hasEmbargo): ?>
    <li><i class="fas fa-lock text-warning fa-fw me-1"></i><?php echo __('Under embargo'); ?></li>
    <?php endif; ?>
  </ul>
</section>
<?php endif; ?>
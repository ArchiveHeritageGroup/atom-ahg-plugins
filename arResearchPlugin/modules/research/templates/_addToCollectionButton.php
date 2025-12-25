<?php
// Only show for authenticated researchers with approved status
$showButton = false;
$collections = [];
if ($sf_user->isAuthenticated()) {
    try {
        $userId = $sf_user->getAttribute('user_id');
        $researcher = \Illuminate\Database\Capsule\Manager::table('research_researcher')
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->first();
        if ($researcher) {
            $showButton = true;
            $collections = \Illuminate\Database\Capsule\Manager::table('research_collection')
                ->where('researcher_id', $researcher->id)
                ->orderBy('name')
                ->get()->toArray();
        }
    } catch (Exception $e) {
        // Silently fail
    }
}
?>
<?php if ($showButton): ?>
<div class="dropdown d-inline-block">
  <button class="btn btn-sm btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="<?php echo __('Add to my collection'); ?>">
    <i class="fas fa-folder-plus"></i>
  </button>
  <ul class="dropdown-menu dropdown-menu-end">
    <li><h6 class="dropdown-header"><?php echo __('Add to Collection'); ?></h6></li>
    <?php if (!empty($collections)): ?>
      <?php foreach ($collections as $col): ?>
        <li>
          <a class="dropdown-item add-to-collection-btn" href="#" 
             data-object-id="<?php echo $objectId; ?>" 
             data-collection-id="<?php echo $col->id; ?>">
            <i class="fas fa-folder me-2"></i><?php echo htmlspecialchars($col->name); ?>
          </a>
        </li>
      <?php endforeach; ?>
      <li><hr class="dropdown-divider"></li>
    <?php endif; ?>
    <li>
      <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#newCollectionModal" data-object-id="<?php echo $objectId; ?>">
        <i class="fas fa-plus me-2 text-success"></i><?php echo __('New Collection...'); ?>
      </a>
    </li>
  </ul>
</div>
<?php endif; ?>

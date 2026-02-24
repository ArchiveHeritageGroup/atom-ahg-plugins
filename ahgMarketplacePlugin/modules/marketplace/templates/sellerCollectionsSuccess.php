<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('My Collections'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'dashboard']); ?>"><?php echo __('Dashboard'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('My Collections'); ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('notice'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('error'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('My Collections'); ?></h1>
  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerCollectionCreate']); ?>" class="btn btn-primary">
    <i class="fas fa-plus me-1"></i> <?php echo __('Create Collection'); ?>
  </a>
</div>

<?php if (empty($collections)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-layer-group fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No collections yet'); ?></h5>
      <p class="text-muted"><?php echo __('Create collections to group and showcase your listings.'); ?></p>
      <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerCollectionCreate']); ?>" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> <?php echo __('Create Collection'); ?>
      </a>
    </div>
  </div>
<?php else: ?>
  <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
    <?php foreach ($collections as $col): ?>
      <div class="col">
        <div class="card h-100">
          <?php if ($col->cover_image_path): ?>
            <img src="<?php echo esc_entities($col->cover_image_path); ?>" class="card-img-top" alt="<?php echo esc_entities($col->title); ?>" style="height: 180px; object-fit: cover;">
          <?php else: ?>
            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 180px;">
              <i class="fas fa-layer-group fa-3x text-muted"></i>
            </div>
          <?php endif; ?>
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h6 class="card-title mb-0"><?php echo esc_entities($col->title); ?></h6>
              <span class="badge bg-secondary"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $col->collection_type ?? 'curated'))); ?></span>
            </div>
            <?php if (isset($col->item_count)): ?>
              <p class="small text-muted mb-2"><?php echo __('%1% items', ['%1%' => (int) $col->item_count]); ?></p>
            <?php endif; ?>
            <?php if ($col->description): ?>
              <p class="small text-muted mb-0"><?php echo esc_entities(mb_strimwidth($col->description, 0, 100, '...')); ?></p>
            <?php endif; ?>
          </div>
          <div class="card-footer bg-transparent d-flex gap-2">
            <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerCollectionCreate', 'id' => $col->id]); ?>" class="btn btn-sm btn-outline-primary flex-grow-1">
              <i class="fas fa-edit me-1"></i><?php echo __('Edit'); ?>
            </a>
            <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerCollections']); ?>" class="d-inline">
              <input type="hidden" name="form_action" value="delete">
              <input type="hidden" name="collection_id" value="<?php echo (int) $col->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?php echo __('Delete this collection?'); ?>');">
                <i class="fas fa-trash"></i>
              </button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php end_slot(); ?>

<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Featured'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('marketplace/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Marketplace'), 'url' => url_for(['module' => 'marketplace', 'action' => 'browse'])],
  ['label' => __('Featured')],
]]); ?>

<!-- Featured listings -->
<div class="mb-5">
  <h1 class="h3 mb-1"><?php echo __('Featured Listings'); ?></h1>
  <p class="text-muted mb-4"><?php echo __('Hand-picked selections from our marketplace.'); ?></p>

  <?php if (!empty($featuredListings)): ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">
      <?php foreach ($featuredListings as $listing): ?>
        <?php include_partial('marketplace/listingCard', ['listing' => $listing]); ?>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-center py-4">
      <p class="text-muted"><?php echo __('No featured listings at this time.'); ?></p>
    </div>
  <?php endif; ?>
</div>

<!-- Featured collections -->
<?php if (!empty($featuredCollections)): ?>
  <hr class="mb-5">
  <div class="mb-4">
    <h3 class="mb-1"><?php echo __('Featured Collections'); ?></h3>
    <p class="text-muted mb-4"><?php echo __('Curated collections from sellers and the marketplace team.'); ?></p>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
      <?php foreach ($featuredCollections as $col): ?>
        <div class="col">
          <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'collection', 'slug' => $col->slug]); ?>" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm">
              <?php if ($col->cover_image_path): ?>
                <img src="<?php echo esc_entities($col->cover_image_path); ?>" class="card-img-top" alt="<?php echo esc_entities($col->title); ?>" style="height: 200px; object-fit: cover;">
              <?php else: ?>
                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                  <i class="fas fa-layer-group fa-3x text-muted"></i>
                </div>
              <?php endif; ?>
              <div class="card-body">
                <h5 class="card-title mb-1"><?php echo esc_entities($col->title); ?></h5>
                <?php if ($col->description): ?>
                  <p class="card-text small text-muted mb-2"><?php echo esc_entities(mb_strimwidth($col->description, 0, 120, '...')); ?></p>
                <?php endif; ?>
                <?php if (isset($col->item_count)): ?>
                  <span class="badge bg-primary"><?php echo (int) $col->item_count; ?> <?php echo __('items'); ?></span>
                <?php endif; ?>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<div class="text-center mt-4">
  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>" class="btn btn-outline-primary">
    <?php echo __('Browse All Listings'); ?> <i class="fas fa-arrow-right ms-1"></i>
  </a>
</div>

<?php end_slot(); ?>

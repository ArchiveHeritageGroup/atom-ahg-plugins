<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo esc_entities($collection->title); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('marketplace/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Marketplace'), 'url' => url_for(['module' => 'marketplace', 'action' => 'browse'])],
  ['label' => __('Featured'), 'url' => url_for(['module' => 'marketplace', 'action' => 'featured'])],
  ['label' => esc_entities($collection->title)],
]]); ?>

<!-- Cover image banner -->
<?php if ($collection->cover_image_path): ?>
  <div class="rounded overflow-hidden mb-4" style="max-height: 300px;">
    <img src="<?php echo esc_entities($collection->cover_image_path); ?>" alt="<?php echo esc_entities($collection->title); ?>" class="w-100" style="object-fit: cover; max-height: 300px;">
  </div>
<?php endif; ?>

<!-- Collection header -->
<div class="mb-4">
  <h1 class="h3 mb-2"><?php echo esc_entities($collection->title); ?></h1>

  <?php if ($collection->collection_type && $collection->collection_type !== 'curated'): ?>
    <span class="badge bg-secondary mb-2"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $collection->collection_type))); ?></span>
  <?php endif; ?>

  <?php if ($collection->description): ?>
    <p class="lead mb-3"><?php echo nl2br(esc_entities($collection->description)); ?></p>
  <?php endif; ?>

  <p class="text-muted small">
    <?php echo __('%1% items in this collection', ['%1%' => count($items)]); ?>
  </p>
</div>

<!-- Items grid -->
<?php if (!empty($items)): ?>
  <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">
    <?php foreach ($items as $item): ?>
      <div class="col">
        <?php if (isset($item->listing)): ?>
          <?php include_partial('marketplace/listingCard', ['listing' => $item->listing]); ?>
        <?php else: ?>
          <?php include_partial('marketplace/listingCard', ['listing' => $item]); ?>
        <?php endif; ?>

        <?php if (isset($item->curator_note) && $item->curator_note): ?>
          <div class="mt-1 px-2">
            <p class="small fst-italic text-muted mb-0">
              <i class="fas fa-quote-left me-1 small"></i>
              <?php echo esc_entities($item->curator_note); ?>
            </p>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="text-center py-5">
    <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
    <h5><?php echo __('This collection is empty'); ?></h5>
    <p class="text-muted"><?php echo __('Items will appear here once they are added.'); ?></p>
  </div>
<?php endif; ?>

<div class="text-center mt-4">
  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'featured']); ?>" class="btn btn-outline-primary">
    <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Featured'); ?>
  </a>
  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>" class="btn btn-outline-secondary ms-2">
    <?php echo __('Browse Marketplace'); ?>
  </a>
</div>

<?php end_slot(); ?>

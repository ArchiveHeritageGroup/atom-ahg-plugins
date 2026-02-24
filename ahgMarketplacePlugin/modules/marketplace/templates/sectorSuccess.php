<?php decorate_with('layout_1col'); ?>

<?php
  $sectorLabels = [
    'gallery' => __('Gallery Marketplace'),
    'museum' => __('Museum Marketplace'),
    'archive' => __('Archive Marketplace'),
    'library' => __('Library Marketplace'),
    'dam' => __('Digital Asset Marketplace'),
  ];
  $sectorLabel = isset($sectorLabels[$sector]) ? $sectorLabels[$sector] : ucfirst($sector) . ' ' . __('Marketplace');
  $sectorIcons = [
    'gallery' => 'fa-palette',
    'museum' => 'fa-landmark',
    'archive' => 'fa-archive',
    'library' => 'fa-book',
    'dam' => 'fa-photo-video',
  ];
  $sectorIcon = isset($sectorIcons[$sector]) ? $sectorIcons[$sector] : 'fa-store';
?>

<?php slot('title'); ?><?php echo esc_entities($sectorLabel); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('marketplace/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Marketplace'), 'url' => url_for(['module' => 'marketplace', 'action' => 'browse'])],
  ['label' => esc_entities($sectorLabel)],
]]); ?>

<!-- Sector hero banner -->
<div class="bg-primary text-white rounded p-4 p-md-5 mb-4">
  <div class="d-flex align-items-center">
    <i class="fas <?php echo $sectorIcon; ?> fa-3x me-4 d-none d-md-block"></i>
    <div>
      <h1 class="h3 mb-1"><?php echo esc_entities($sectorLabel); ?></h1>
      <p class="mb-0 opacity-75"><?php echo __('%1% listings available', ['%1%' => number_format($total)]); ?></p>
    </div>
  </div>
</div>

<!-- Category cards -->
<?php if (!empty($categories)): ?>
  <h4 class="mb-3"><?php echo __('Browse by Category'); ?></h4>
  <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3 mb-5">
    <?php foreach ($categories as $cat): ?>
      <div class="col">
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'category', 'sector' => $sector, 'slug' => $cat->slug]); ?>" class="text-decoration-none">
          <div class="card h-100 text-center border-0 shadow-sm hover-shadow">
            <div class="card-body py-4">
              <?php if ($cat->icon): ?>
                <i class="fas <?php echo esc_entities($cat->icon); ?> fa-2x text-primary mb-2"></i>
              <?php else: ?>
                <i class="fas fa-tag fa-2x text-primary mb-2"></i>
              <?php endif; ?>
              <h6 class="card-title mb-1 small"><?php echo esc_entities($cat->name); ?></h6>
              <?php if (isset($cat->listing_count)): ?>
                <small class="text-muted"><?php echo number_format((int) $cat->listing_count); ?> <?php echo __('items'); ?></small>
              <?php endif; ?>
            </div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Featured / recent listings for this sector -->
<?php if (!empty($listings)): ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><?php echo __('Latest Listings'); ?></h4>
    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse', 'sector' => $sector]); ?>" class="btn btn-outline-primary btn-sm">
      <?php echo __('Browse All'); ?> <i class="fas fa-arrow-right ms-1"></i>
    </a>
  </div>
  <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">
    <?php foreach ($listings as $listing): ?>
      <?php include_partial('marketplace/listingCard', ['listing' => $listing]); ?>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($total > 24): ?>
    <?php $totalPages = (int) ceil($total / 24); ?>
    <nav class="mt-4">
      <ul class="pagination justify-content-center">
        <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sector', 'sector' => $sector, 'page' => $page - 1]); ?>">&laquo;</a>
        </li>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sector', 'sector' => $sector, 'page' => $i]); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sector', 'sector' => $sector, 'page' => $page + 1]); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
<?php else: ?>
  <div class="text-center py-5">
    <i class="fas <?php echo $sectorIcon; ?> fa-3x text-muted mb-3"></i>
    <h5><?php echo __('No listings in this sector yet'); ?></h5>
    <p class="text-muted"><?php echo __('Be the first to list an item in the %1% sector.', ['%1%' => ucfirst($sector)]); ?></p>
    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>" class="btn btn-primary"><?php echo __('Browse Marketplace'); ?></a>
  </div>
<?php endif; ?>

<?php end_slot(); ?>

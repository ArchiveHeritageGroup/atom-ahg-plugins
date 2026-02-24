<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo esc_entities($category->name); ?> - <?php echo ucfirst($sector); ?> <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('marketplace/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Marketplace'), 'url' => url_for(['module' => 'marketplace', 'action' => 'browse'])],
  ['label' => ucfirst($sector), 'url' => url_for(['module' => 'marketplace', 'action' => 'sector', 'sector' => $sector])],
  ['label' => esc_entities($category->name)],
]]); ?>

<div class="row mb-4">
  <div class="col">
    <h1 class="h3 mb-1"><?php echo esc_entities($category->name); ?></h1>
    <?php if ($category->description): ?>
      <p class="text-muted mb-1"><?php echo esc_entities($category->description); ?></p>
    <?php endif; ?>
    <p class="small text-muted"><?php echo __('%1% listings', ['%1%' => number_format($total)]); ?></p>
  </div>
</div>

<div class="row">

  <!-- Light filter sidebar -->
  <div class="col-lg-3 mb-4">
    <div class="d-lg-none mb-3">
      <button class="btn btn-outline-secondary btn-sm w-100" type="button" data-bs-toggle="collapse" data-bs-target="#catFilters">
        <i class="fas fa-filter me-1"></i> <?php echo __('Filters'); ?>
      </button>
    </div>
    <div class="collapse d-lg-block" id="catFilters">
      <form method="get" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'category', 'sector' => $sector, 'slug' => $category->slug]); ?>">

        <div class="card mb-3">
          <div class="card-header py-2 fw-semibold small"><?php echo __('Listing Type'); ?></div>
          <div class="card-body py-2">
            <select name="listing_type" class="form-select form-select-sm">
              <option value=""><?php echo __('All Types'); ?></option>
              <option value="fixed_price"><?php echo __('Buy Now'); ?></option>
              <option value="auction"><?php echo __('Auction'); ?></option>
              <option value="offer_only"><?php echo __('Make an Offer'); ?></option>
            </select>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header py-2 fw-semibold small"><?php echo __('Price Range'); ?></div>
          <div class="card-body py-2">
            <div class="row g-2">
              <div class="col-6">
                <input type="number" class="form-control form-control-sm" name="price_min" placeholder="<?php echo __('Min'); ?>" min="0" step="0.01">
              </div>
              <div class="col-6">
                <input type="number" class="form-control form-control-sm" name="price_max" placeholder="<?php echo __('Max'); ?>" min="0" step="0.01">
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header py-2 fw-semibold small"><?php echo __('Condition'); ?></div>
          <div class="card-body py-2">
            <select name="condition_rating" class="form-select form-select-sm">
              <option value=""><?php echo __('Any'); ?></option>
              <option value="mint"><?php echo __('Mint'); ?></option>
              <option value="excellent"><?php echo __('Excellent'); ?></option>
              <option value="good"><?php echo __('Good'); ?></option>
              <option value="fair"><?php echo __('Fair'); ?></option>
              <option value="poor"><?php echo __('Poor'); ?></option>
            </select>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header py-2 fw-semibold small"><?php echo __('Sort'); ?></div>
          <div class="card-body py-2">
            <select name="sort" class="form-select form-select-sm">
              <option value="newest"><?php echo __('Newest'); ?></option>
              <option value="price_asc"><?php echo __('Price: Low to High'); ?></option>
              <option value="price_desc"><?php echo __('Price: High to Low'); ?></option>
              <option value="popular"><?php echo __('Popular'); ?></option>
            </select>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-sm w-100"><?php echo __('Apply'); ?></button>
      </form>
    </div>
  </div>

  <!-- Listings grid -->
  <div class="col-lg-9">
    <?php if (!empty($listings)): ?>
      <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3">
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
              <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'category', 'sector' => $sector, 'slug' => $category->slug, 'page' => $page - 1]); ?>">&laquo;</a>
            </li>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
              <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
                <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'category', 'sector' => $sector, 'slug' => $category->slug, 'page' => $i]); ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
              <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'category', 'sector' => $sector, 'slug' => $category->slug, 'page' => $page + 1]); ?>">&raquo;</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    <?php else: ?>
      <div class="text-center py-5">
        <i class="fas fa-tag fa-3x text-muted mb-3"></i>
        <h5><?php echo __('No listings in this category'); ?></h5>
        <p class="text-muted"><?php echo __('Check back later or browse other categories.'); ?></p>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sector', 'sector' => $sector]); ?>" class="btn btn-primary"><?php echo __('Back to %1%', ['%1%' => ucfirst($sector)]); ?></a>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php end_slot(); ?>

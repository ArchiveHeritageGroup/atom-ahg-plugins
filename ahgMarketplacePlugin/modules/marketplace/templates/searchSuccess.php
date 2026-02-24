<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Search Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('marketplace/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Marketplace'), 'url' => url_for(['module' => 'marketplace', 'action' => 'browse'])],
  ['label' => __('Search')],
]]); ?>

<!-- Search input -->
<div class="row justify-content-center mb-4">
  <div class="col-lg-8">
    <form method="get" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'search']); ?>">
      <div class="input-group input-group-lg">
        <input type="text" class="form-control" name="query" value="<?php echo esc_entities($query); ?>" placeholder="<?php echo __('Search listings, artists, categories...'); ?>" autofocus>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-search me-1"></i> <?php echo __('Search'); ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Results count -->
<?php if (!empty($query)): ?>
  <p class="text-muted mb-3">
    <?php echo __('%1% results for "%2%"', ['%1%' => number_format($total), '%2%' => esc_entities($query)]); ?>
  </p>
<?php endif; ?>

<div class="row">

  <!-- Compact filter sidebar -->
  <div class="col-lg-3 mb-4">
    <div class="d-lg-none mb-3">
      <button class="btn btn-outline-secondary btn-sm w-100" type="button" data-bs-toggle="collapse" data-bs-target="#searchFilters">
        <i class="fas fa-filter me-1"></i> <?php echo __('Filters'); ?>
      </button>
    </div>
    <div class="collapse d-lg-block" id="searchFilters">
      <form method="get" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'search']); ?>">
        <input type="hidden" name="query" value="<?php echo esc_entities($query); ?>">

        <div class="card mb-3">
          <div class="card-header py-2 fw-semibold small"><?php echo __('Listing Type'); ?></div>
          <div class="card-body py-2">
            <?php $types = ['' => __('All'), 'fixed_price' => __('Buy Now'), 'auction' => __('Auction'), 'offer_only' => __('Offers')]; ?>
            <?php foreach ($types as $val => $label): ?>
              <div class="form-check form-check-sm">
                <input class="form-check-input" type="radio" name="listing_type" value="<?php echo $val; ?>" id="st-<?php echo $val ?: 'all'; ?>"<?php echo (isset($filters['listing_type']) ? $filters['listing_type'] : '') === $val ? ' checked' : ''; ?>>
                <label class="form-check-label small" for="st-<?php echo $val ?: 'all'; ?>"><?php echo $label; ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header py-2 fw-semibold small"><?php echo __('Price Range'); ?></div>
          <div class="card-body py-2">
            <div class="row g-2">
              <div class="col-6">
                <input type="number" class="form-control form-control-sm" name="price_min" placeholder="<?php echo __('Min'); ?>" value="<?php echo isset($filters['price_min']) ? esc_entities($filters['price_min']) : ''; ?>" min="0" step="0.01">
              </div>
              <div class="col-6">
                <input type="number" class="form-control form-control-sm" name="price_max" placeholder="<?php echo __('Max'); ?>" value="<?php echo isset($filters['price_max']) ? esc_entities($filters['price_max']) : ''; ?>" min="0" step="0.01">
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header py-2 fw-semibold small"><?php echo __('Sector'); ?></div>
          <div class="card-body py-2">
            <?php $sectorList = ['gallery', 'museum', 'archive', 'library', 'dam']; ?>
            <?php foreach ($sectorList as $s): ?>
              <div class="form-check form-check-sm">
                <input class="form-check-input" type="checkbox" name="sector[]" value="<?php echo $s; ?>" id="ss-<?php echo $s; ?>"<?php echo (isset($filters['sector']) && ((is_array($filters['sector']) && in_array($s, $filters['sector'])) || $filters['sector'] === $s)) ? ' checked' : ''; ?>>
                <label class="form-check-label small" for="ss-<?php echo $s; ?>">
                  <?php echo esc_entities(ucfirst($s)); ?>
                  <?php if (isset($facets['sectors'][$s])): ?>
                    <span class="badge bg-secondary"><?php echo (int) $facets['sectors'][$s]; ?></span>
                  <?php endif; ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header py-2 fw-semibold small"><?php echo __('Condition'); ?></div>
          <div class="card-body py-2">
            <select name="condition_rating" class="form-select form-select-sm">
              <option value=""><?php echo __('Any'); ?></option>
              <?php $conditions = ['mint' => __('Mint'), 'excellent' => __('Excellent'), 'good' => __('Good'), 'fair' => __('Fair'), 'poor' => __('Poor')]; ?>
              <?php foreach ($conditions as $val => $label): ?>
                <option value="<?php echo $val; ?>"<?php echo (isset($filters['condition_rating']) && $filters['condition_rating'] === $val) ? ' selected' : ''; ?>><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-sm w-100 mb-2"><?php echo __('Apply'); ?></button>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'search', 'query' => $query]); ?>" class="btn btn-outline-secondary btn-sm w-100"><?php echo __('Clear'); ?></a>
      </form>
    </div>
  </div>

  <!-- Results grid -->
  <div class="col-lg-9">
    <?php if (!empty($results)): ?>
      <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3">
        <?php foreach ($results as $listing): ?>
          <?php include_partial('marketplace/listingCard', ['listing' => $listing]); ?>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($total > 24): ?>
        <?php $totalPages = (int) ceil($total / 24); ?>
        <nav aria-label="<?php echo __('Page navigation'); ?>" class="mt-4">
          <ul class="pagination justify-content-center">
            <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
              <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'search', 'query' => $query, 'page' => $page - 1]); ?>">&laquo;</a>
            </li>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
              <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
                <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'search', 'query' => $query, 'page' => $i]); ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
              <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'search', 'query' => $query, 'page' => $page + 1]); ?>">&raquo;</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    <?php else: ?>
      <div class="text-center py-5">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h5><?php echo __('No results found'); ?></h5>
        <?php if (!empty($query)): ?>
          <p class="text-muted"><?php echo __('No listings match "%1%". Try different keywords or broaden your filters.', ['%1%' => esc_entities($query)]); ?></p>
        <?php endif; ?>
        <div class="mt-3">
          <p class="small text-muted mb-2"><?php echo __('Suggestions:'); ?></p>
          <ul class="list-unstyled small text-muted">
            <li><?php echo __('Check for typos or use more general terms'); ?></li>
            <li><?php echo __('Remove some filters to see more results'); ?></li>
            <li><?php echo __('Try browsing by sector or category'); ?></li>
          </ul>
        </div>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>" class="btn btn-primary mt-2"><?php echo __('Browse All Listings'); ?></a>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php end_slot(); ?>

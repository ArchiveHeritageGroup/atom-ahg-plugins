<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('marketplace/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Marketplace')],
]]); ?>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('notice'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="row mb-4 align-items-center">
  <div class="col">
    <h1 class="h3 mb-1"><?php echo __('Marketplace'); ?></h1>
    <p class="text-muted mb-0"><?php echo __('%1% listings available', ['%1%' => number_format($total)]); ?></p>
  </div>
  <?php if ($sf_user->isAuthenticated()): ?>
  <div class="col-auto">
    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'dashboard']); ?>" class="btn btn-primary">
      <i class="fas fa-store me-1"></i> <?php echo __('Sell'); ?>
    </a>
  </div>
  <?php endif; ?>
</div>

<div class="row">

  <!-- Filter sidebar -->
  <div class="col-lg-3 mb-4">
    <div class="d-lg-none mb-3">
      <button class="btn btn-outline-secondary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#filterSidebar" aria-expanded="false">
        <i class="fas fa-filter me-1"></i> <?php echo __('Filters'); ?>
      </button>
    </div>
    <div class="collapse d-lg-block" id="filterSidebar">
      <form method="get" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>" id="marketplace-filter-form">
        <div class="card mb-3">
          <div class="card-header fw-semibold"><?php echo __('Sector'); ?></div>
          <div class="card-body">
            <?php foreach ($sectors as $s): ?>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="sector[]" value="<?php echo esc_entities($s); ?>" id="sector-<?php echo esc_entities($s); ?>"<?php echo (isset($filters['sector']) && ((is_array($filters['sector']) && in_array($s, $filters['sector'])) || $filters['sector'] === $s)) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="sector-<?php echo esc_entities($s); ?>">
                  <?php echo esc_entities(ucfirst($s)); ?>
                  <?php if (isset($facets['sectors'][$s])): ?>
                    <span class="badge bg-secondary ms-1"><?php echo (int) $facets['sectors'][$s]; ?></span>
                  <?php endif; ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header fw-semibold"><?php echo __('Category'); ?></div>
          <div class="card-body" style="max-height: 200px; overflow-y: auto;">
            <?php foreach ($categories as $cat): ?>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="category_id[]" value="<?php echo (int) $cat->id; ?>" id="cat-<?php echo (int) $cat->id; ?>"<?php echo (isset($filters['category_id']) && ((is_array($filters['category_id']) && in_array($cat->id, $filters['category_id'])) || $filters['category_id'] == $cat->id)) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="cat-<?php echo (int) $cat->id; ?>"><?php echo esc_entities($cat->name); ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header fw-semibold"><?php echo __('Listing Type'); ?></div>
          <div class="card-body">
            <?php $types = ['fixed_price' => __('Buy Now'), 'auction' => __('Auction'), 'offer_only' => __('Make an Offer')]; ?>
            <?php foreach ($types as $val => $label): ?>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="listing_type" value="<?php echo $val; ?>" id="type-<?php echo $val; ?>"<?php echo (isset($filters['listing_type']) && $filters['listing_type'] === $val) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="type-<?php echo $val; ?>"><?php echo $label; ?></label>
              </div>
            <?php endforeach; ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="listing_type" value="" id="type-all"<?php echo empty($filters['listing_type']) ? ' checked' : ''; ?>>
              <label class="form-check-label" for="type-all"><?php echo __('All Types'); ?></label>
            </div>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header fw-semibold"><?php echo __('Price Range'); ?></div>
          <div class="card-body">
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
          <div class="card-header fw-semibold"><?php echo __('Condition'); ?></div>
          <div class="card-body">
            <select name="condition_rating" class="form-select form-select-sm">
              <option value=""><?php echo __('Any Condition'); ?></option>
              <?php $conditions = ['mint' => __('Mint'), 'excellent' => __('Excellent'), 'good' => __('Good'), 'fair' => __('Fair'), 'poor' => __('Poor')]; ?>
              <?php foreach ($conditions as $val => $label): ?>
                <option value="<?php echo $val; ?>"<?php echo (isset($filters['condition_rating']) && $filters['condition_rating'] === $val) ? ' selected' : ''; ?>><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-2"><?php echo __('Apply Filters'); ?></button>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>" class="btn btn-outline-secondary w-100"><?php echo __('Clear Filters'); ?></a>
      </form>
    </div>
  </div>

  <!-- Main content -->
  <div class="col-lg-9">

    <!-- Sort and view controls -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="d-flex align-items-center gap-2">
        <label class="form-label mb-0 text-nowrap"><?php echo __('Sort by'); ?>:</label>
        <select class="form-select form-select-sm" id="marketplace-sort" style="width: auto;">
          <?php $sortOptions = ['newest' => __('Newest'), 'price_asc' => __('Price: Low to High'), 'price_desc' => __('Price: High to Low'), 'popular' => __('Popular'), 'ending_soon' => __('Ending Soon')]; ?>
          <?php foreach ($sortOptions as $val => $label): ?>
            <option value="<?php echo $val; ?>"<?php echo (isset($filters['sort']) && $filters['sort'] === $val) ? ' selected' : ''; ?>><?php echo $label; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="btn-group" role="group" aria-label="<?php echo __('View mode'); ?>">
        <button type="button" class="btn btn-sm btn-outline-secondary active" id="view-grid" title="<?php echo __('Grid view'); ?>">
          <i class="fas fa-th"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="view-list" title="<?php echo __('List view'); ?>">
          <i class="fas fa-list"></i>
        </button>
      </div>
    </div>

    <!-- Listing grid -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3" id="listings-grid">
      <?php if (!empty($listings)): ?>
        <?php foreach ($listings as $listing): ?>
          <?php include_partial('marketplace/listingCard', ['listing' => $listing]); ?>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="text-center py-5">
            <i class="fas fa-store fa-3x text-muted mb-3"></i>
            <h5><?php echo __('No listings found'); ?></h5>
            <p class="text-muted"><?php echo __('Try adjusting your filters or browse all listings.'); ?></p>
            <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>" class="btn btn-primary"><?php echo __('Browse All'); ?></a>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total > $limit): ?>
      <?php $totalPages = (int) ceil($total / $limit); ?>
      <nav aria-label="<?php echo __('Page navigation'); ?>" class="mt-4">
        <ul class="pagination justify-content-center">
          <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse', 'page' => $page - 1] + $filters); ?>">&laquo;</a>
          </li>
          <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
              <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse', 'page' => $i] + $filters); ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse', 'page' => $page + 1] + $filters); ?>">&raquo;</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>

  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  // Sort change
  var sortSelect = document.getElementById('marketplace-sort');
  if (sortSelect) {
    sortSelect.addEventListener('change', function() {
      var url = new URL(window.location.href);
      url.searchParams.set('sort', this.value);
      url.searchParams.delete('page');
      window.location.href = url.toString();
    });
  }

  // Grid/List toggle
  var grid = document.getElementById('listings-grid');
  var btnGrid = document.getElementById('view-grid');
  var btnList = document.getElementById('view-list');
  if (btnGrid && btnList && grid) {
    btnGrid.addEventListener('click', function() {
      grid.className = 'row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3';
      btnGrid.classList.add('active');
      btnList.classList.remove('active');
    });
    btnList.addEventListener('click', function() {
      grid.className = 'row row-cols-1 g-3';
      btnList.classList.add('active');
      btnGrid.classList.remove('active');
    });
  }
});
</script>

<?php end_slot(); ?>

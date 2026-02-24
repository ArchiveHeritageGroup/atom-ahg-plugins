<?php
/**
 * _filterSidebar.php - Collapsible filter panel for browse/search pages.
 *
 * Variables:
 *   $facets     (array)  Associative array of facet counts, e.g. ['sectors' => [...], 'types' => [...], 'conditions' => [...]]
 *   $filters    (array)  Currently active filter values from request
 *   $sectors    (array)  Available sector values
 *   $categories (array)  Available category objects (id, name)
 */
$facets     = $facets ?? [];
$filters    = $filters ?? [];
$sectors    = $sectors ?? [];
$categories = $categories ?? [];
?>
<form method="get" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>" id="mkt-filter-form">

  <!-- Sector -->
  <?php if (!empty($sectors)): ?>
    <div class="card mkt-filter-group mb-3">
      <div class="card-header fw-semibold py-2" data-bs-toggle="collapse" data-bs-target="#mkt-filter-sector" role="button" aria-expanded="true">
        <?php echo __('Sector'); ?>
        <i class="fas fa-chevron-down float-end mt-1 small"></i>
      </div>
      <div class="collapse show" id="mkt-filter-sector">
        <div class="card-body py-2">
          <?php foreach ($sectors as $s): ?>
            <?php $checked = isset($filters['sector']) && ((is_array($filters['sector']) && in_array($s, $filters['sector'])) || $filters['sector'] === $s); ?>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="sector[]" value="<?php echo esc_entities($s); ?>" id="mkt-f-sector-<?php echo esc_entities($s); ?>"<?php echo $checked ? ' checked' : ''; ?>>
              <label class="form-check-label" for="mkt-f-sector-<?php echo esc_entities($s); ?>">
                <?php echo esc_entities(ucfirst($s)); ?>
                <?php if (isset($facets['sectors'][$s])): ?>
                  <span class="mkt-filter-count badge bg-secondary ms-1"><?php echo (int) $facets['sectors'][$s]; ?></span>
                <?php endif; ?>
              </label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Listing Type -->
  <div class="card mkt-filter-group mb-3">
    <div class="card-header fw-semibold py-2" data-bs-toggle="collapse" data-bs-target="#mkt-filter-type" role="button" aria-expanded="true">
      <?php echo __('Listing Type'); ?>
      <i class="fas fa-chevron-down float-end mt-1 small"></i>
    </div>
    <div class="collapse show" id="mkt-filter-type">
      <div class="card-body py-2">
        <?php $types = ['fixed_price' => __('Buy Now'), 'auction' => __('Auction'), 'offer_only' => __('Make an Offer')]; ?>
        <?php foreach ($types as $val => $label): ?>
          <?php $checked = isset($filters['listing_type']) && ((is_array($filters['listing_type']) && in_array($val, $filters['listing_type'])) || $filters['listing_type'] === $val); ?>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="listing_type[]" value="<?php echo $val; ?>" id="mkt-f-type-<?php echo $val; ?>"<?php echo $checked ? ' checked' : ''; ?>>
            <label class="form-check-label" for="mkt-f-type-<?php echo $val; ?>">
              <?php echo $label; ?>
              <?php if (isset($facets['types'][$val])): ?>
                <span class="mkt-filter-count badge bg-secondary ms-1"><?php echo (int) $facets['types'][$val]; ?></span>
              <?php endif; ?>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Price Range -->
  <div class="card mkt-filter-group mb-3">
    <div class="card-header fw-semibold py-2" data-bs-toggle="collapse" data-bs-target="#mkt-filter-price" role="button" aria-expanded="true">
      <?php echo __('Price Range'); ?>
      <i class="fas fa-chevron-down float-end mt-1 small"></i>
    </div>
    <div class="collapse show" id="mkt-filter-price">
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
  </div>

  <!-- Condition -->
  <div class="card mkt-filter-group mb-3">
    <div class="card-header fw-semibold py-2" data-bs-toggle="collapse" data-bs-target="#mkt-filter-condition" role="button" aria-expanded="false">
      <?php echo __('Condition'); ?>
      <i class="fas fa-chevron-down float-end mt-1 small"></i>
    </div>
    <div class="collapse" id="mkt-filter-condition">
      <div class="card-body py-2">
        <?php $conditions = ['mint' => __('Mint'), 'excellent' => __('Excellent'), 'good' => __('Good'), 'fair' => __('Fair'), 'poor' => __('Poor')]; ?>
        <?php foreach ($conditions as $val => $label): ?>
          <?php $checked = isset($filters['condition_rating']) && ((is_array($filters['condition_rating']) && in_array($val, $filters['condition_rating'])) || $filters['condition_rating'] === $val); ?>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="condition_rating[]" value="<?php echo $val; ?>" id="mkt-f-cond-<?php echo $val; ?>"<?php echo $checked ? ' checked' : ''; ?>>
            <label class="form-check-label" for="mkt-f-cond-<?php echo $val; ?>">
              <?php echo $label; ?>
              <?php if (isset($facets['conditions'][$val])): ?>
                <span class="mkt-filter-count badge bg-secondary ms-1"><?php echo (int) $facets['conditions'][$val]; ?></span>
              <?php endif; ?>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Digital / Physical -->
  <div class="card mkt-filter-group mb-3">
    <div class="card-header fw-semibold py-2" data-bs-toggle="collapse" data-bs-target="#mkt-filter-delivery" role="button" aria-expanded="false">
      <?php echo __('Delivery'); ?>
      <i class="fas fa-chevron-down float-end mt-1 small"></i>
    </div>
    <div class="collapse" id="mkt-filter-delivery">
      <div class="card-body py-2">
        <div class="form-check">
          <input class="form-check-input" type="radio" name="delivery_type" value="" id="mkt-f-del-all"<?php echo empty($filters['delivery_type']) ? ' checked' : ''; ?>>
          <label class="form-check-label" for="mkt-f-del-all"><?php echo __('All'); ?></label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="delivery_type" value="physical" id="mkt-f-del-phys"<?php echo (isset($filters['delivery_type']) && $filters['delivery_type'] === 'physical') ? ' checked' : ''; ?>>
          <label class="form-check-label" for="mkt-f-del-phys"><?php echo __('Physical'); ?></label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="delivery_type" value="digital" id="mkt-f-del-dig"<?php echo (isset($filters['delivery_type']) && $filters['delivery_type'] === 'digital') ? ' checked' : ''; ?>>
          <label class="form-check-label" for="mkt-f-del-dig"><?php echo __('Digital'); ?></label>
        </div>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary w-100 mb-2"><?php echo __('Apply Filters'); ?></button>
  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>" class="btn btn-outline-secondary w-100"><?php echo __('Clear All'); ?></a>

</form>

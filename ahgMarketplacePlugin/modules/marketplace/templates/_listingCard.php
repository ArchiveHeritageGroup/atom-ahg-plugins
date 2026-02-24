<?php
/**
 * _listingCard.php - Bootstrap 5 card component for a marketplace listing.
 *
 * Variables:
 *   $listing  (object) title, slug, price, currency, featured_image_path,
 *             seller_name, seller_slug, listing_type, status, sector,
 *             artist_name, seller_rating, seller_verified, condition_rating,
 *             price_on_request
 */
$typeBadges = [
    'fixed_price'  => ['bg-primary',  __('Buy Now')],
    'auction'      => ['bg-warning text-dark', __('Auction')],
    'offer_only'   => ['bg-info text-dark',    __('Make an Offer')],
];
$badge = $typeBadges[$listing->listing_type] ?? ['bg-secondary', ucfirst(str_replace('_', ' ', $listing->listing_type))];
?>
<div class="col">
  <div class="card mkt-card h-100 position-relative">
    <?php if ($listing->status === 'sold'): ?>
      <div class="mkt-card-sold position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center rounded" style="z-index:2;">
        <span class="badge bg-dark fs-5 px-3 py-2"><?php echo __('SOLD'); ?></span>
      </div>
    <?php endif; ?>

    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $listing->slug]); ?>">
      <?php if ($listing->featured_image_path): ?>
        <img src="<?php echo esc_entities($listing->featured_image_path); ?>" class="card-img-top mkt-card-image" alt="<?php echo esc_entities($listing->title); ?>">
      <?php else: ?>
        <div class="card-img-top mkt-card-image bg-light d-flex align-items-center justify-content-center">
          <i class="fas fa-image fa-3x text-muted"></i>
        </div>
      <?php endif; ?>
    </a>

    <div class="card-body pb-2">
      <span class="badge <?php echo $badge[0]; ?> mb-2"><?php echo $badge[1]; ?></span>
      <h6 class="card-title mb-1">
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $listing->slug]); ?>" class="text-decoration-none text-dark">
          <?php echo esc_entities(mb_strimwidth($listing->title, 0, 70, '...')); ?>
        </a>
      </h6>
      <?php if (!empty($listing->artist_name)): ?>
        <p class="small text-muted mb-1"><?php echo esc_entities($listing->artist_name); ?></p>
      <?php endif; ?>

      <?php if (!empty($listing->price_on_request)): ?>
        <p class="mkt-price-por mb-0"><?php echo __('Price on Request'); ?></p>
      <?php else: ?>
        <p class="mkt-price mb-0">
          <?php echo esc_entities($listing->currency ?? 'ZAR'); ?> <?php echo number_format((float) ($listing->price ?? 0), 2); ?>
        </p>
      <?php endif; ?>
    </div>

    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center small">
      <span>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'seller', 'slug' => $listing->seller_slug]); ?>" class="text-decoration-none text-muted">
          <?php echo esc_entities($listing->seller_name); ?>
        </a>
        <?php if (!empty($listing->seller_verified)): ?>
          <i class="fas fa-check-circle text-primary ms-1" title="<?php echo __('Verified'); ?>"></i>
        <?php endif; ?>
      </span>
      <span>
        <?php if (!empty($listing->sector)): ?>
          <span class="badge bg-secondary"><?php echo esc_entities(ucfirst($listing->sector)); ?></span>
        <?php endif; ?>
        <?php if (!empty($listing->condition_rating)): ?>
          <span class="badge bg-outline-secondary border text-muted ms-1"><?php echo esc_entities(ucfirst($listing->condition_rating)); ?></span>
        <?php endif; ?>
      </span>
    </div>
  </div>
</div>

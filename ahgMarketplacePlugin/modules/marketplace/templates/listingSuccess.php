<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo esc_entities($listing->title); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php
  $sectorLabel = ucfirst($listing->sector);
  $categoryLabel = isset($listing->category_name) ? $listing->category_name : '';
?>

<?php include_partial('marketplace/breadcrumb', ['items' => array_filter([
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Marketplace'), 'url' => url_for(['module' => 'marketplace', 'action' => 'browse'])],
  ['label' => $sectorLabel, 'url' => url_for(['module' => 'marketplace', 'action' => 'sector', 'sector' => $listing->sector])],
  $categoryLabel ? ['label' => $categoryLabel, 'url' => url_for(['module' => 'marketplace', 'action' => 'category', 'sector' => $listing->sector, 'slug' => $listing->category_slug ?? ''])] : null,
  ['label' => esc_entities($listing->title)],
])]); ?>

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

<div class="row">

  <!-- Left column: Image gallery -->
  <div class="col-md-7 mb-4">
    <div class="position-relative mb-3">
      <?php
        $primaryImage = null;
        $thumbs = [];
        if (!empty($images)) {
          foreach ($images as $img) {
            if ($img->is_primary) { $primaryImage = $img; }
            $thumbs[] = $img;
          }
          if (!$primaryImage && count($thumbs) > 0) { $primaryImage = $thumbs[0]; }
        }
      ?>
      <div class="border rounded overflow-hidden bg-light text-center" style="min-height: 400px;">
        <?php if ($primaryImage): ?>
          <img src="<?php echo esc_entities($primaryImage->file_path); ?>" alt="<?php echo esc_entities($listing->title); ?>" class="img-fluid" id="main-image" style="max-height: 500px; object-fit: contain;">
        <?php elseif ($listing->featured_image_path): ?>
          <img src="<?php echo esc_entities($listing->featured_image_path); ?>" alt="<?php echo esc_entities($listing->title); ?>" class="img-fluid" id="main-image" style="max-height: 500px; object-fit: contain;">
        <?php else: ?>
          <div class="d-flex align-items-center justify-content-center h-100 py-5">
            <i class="fas fa-image fa-5x text-muted"></i>
          </div>
        <?php endif; ?>
      </div>

      <!-- Favourite button -->
      <?php if ($sf_user->isAuthenticated()): ?>
        <button type="button" class="btn btn-light position-absolute top-0 end-0 m-2 rounded-circle shadow-sm" id="btn-favourite" data-listing-id="<?php echo (int) $listing->id; ?>" title="<?php echo $isFavourited ? __('Remove from favourites') : __('Add to favourites'); ?>">
          <i class="<?php echo $isFavourited ? 'fas' : 'far'; ?> fa-heart text-danger"></i>
        </button>
      <?php endif; ?>
    </div>

    <!-- Thumbnails -->
    <?php if (count($thumbs) > 1): ?>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($thumbs as $idx => $thumb): ?>
          <div class="border rounded overflow-hidden listing-thumb" style="width: 80px; height: 80px; cursor: pointer;" data-src="<?php echo esc_entities($thumb->file_path); ?>">
            <img src="<?php echo esc_entities($thumb->file_path); ?>" alt="<?php echo esc_entities($thumb->caption ?? __('Image %1%', ['%1%' => $idx + 1])); ?>" class="w-100 h-100" style="object-fit: cover;">
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Right column: Listing details -->
  <div class="col-md-5 mb-4">
    <h1 class="h4 mb-2"><?php echo esc_entities($listing->title); ?></h1>

    <?php if ($listing->artist_name): ?>
      <p class="text-muted mb-2"><?php echo __('by %1%', ['%1%' => esc_entities($listing->artist_name)]); ?></p>
    <?php endif; ?>

    <?php if ($listing->listing_number): ?>
      <p class="small text-muted mb-3"><?php echo __('Listing #%1%', ['%1%' => esc_entities($listing->listing_number)]); ?></p>
    <?php endif; ?>

    <!-- Price / Bid section -->
    <div class="card mb-3">
      <div class="card-body">
        <?php if ($listing->price_on_request): ?>
          <p class="h5 mb-2"><?php echo __('Price on Request'); ?></p>
          <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'enquiryForm', 'slug' => $listing->slug]); ?>" class="btn btn-primary w-100">
            <i class="fas fa-envelope me-1"></i> <?php echo __('Enquire'); ?>
          </a>

        <?php elseif ($listing->listing_type === 'fixed_price'): ?>
          <p class="h4 text-primary mb-1">
            <?php echo esc_entities($listing->currency); ?> <?php echo number_format((float) $listing->price, 2); ?>
          </p>
          <?php if ($listing->condition_rating): ?>
            <p class="small text-muted mb-3"><?php echo __('Condition: %1%', ['%1%' => ucfirst($listing->condition_rating)]); ?></p>
          <?php endif; ?>
          <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'buy', 'slug' => $listing->slug]); ?>" class="btn btn-primary w-100 mb-2">
            <i class="fas fa-shopping-cart me-1"></i> <?php echo __('Buy Now'); ?>
          </a>
          <?php if ($listing->minimum_offer !== null): ?>
            <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'offerForm', 'slug' => $listing->slug]); ?>" class="btn btn-outline-primary w-100">
              <i class="fas fa-hand-holding-usd me-1"></i> <?php echo __('Make an Offer'); ?>
            </a>
          <?php endif; ?>

        <?php elseif ($listing->listing_type === 'auction' && $auction): ?>
          <div class="mb-2">
            <span class="small text-muted"><?php echo __('Current Bid'); ?></span>
            <p class="h4 text-primary mb-0">
              <?php echo esc_entities($listing->currency); ?> <?php echo number_format((float) ($auction->current_bid ?? $auction->starting_bid), 2); ?>
            </p>
            <span class="small text-muted"><?php echo __('%1% bids', ['%1%' => (int) ($auction->bid_count ?? 0)]); ?></span>
          </div>

          <!-- Countdown timer -->
          <div class="alert alert-warning py-2 mb-3">
            <i class="fas fa-clock me-1"></i>
            <span class="small"><?php echo __('Ends'); ?>:</span>
            <strong id="auction-countdown" data-end="<?php echo esc_entities($auction->end_time); ?>">--</strong>
          </div>

          <?php if ($sf_user->isAuthenticated()): ?>
            <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'bidForm', 'slug' => $listing->slug]); ?>">
              <div class="input-group mb-2">
                <span class="input-group-text"><?php echo esc_entities($listing->currency); ?></span>
                <input type="number" class="form-control" name="bid_amount" placeholder="<?php echo __('Your bid'); ?>" min="<?php echo (float) ($auction->current_bid ?? $auction->starting_bid) + (float) ($auction->bid_increment ?? 1); ?>" step="0.01" required>
                <button type="submit" class="btn btn-primary"><?php echo __('Place Bid'); ?></button>
              </div>
              <p class="small text-muted"><?php echo __('Minimum bid: %1% %2%', ['%1%' => esc_entities($listing->currency), '%2%' => number_format((float) ($auction->current_bid ?? $auction->starting_bid) + (float) ($auction->bid_increment ?? 1), 2)]); ?></p>
            </form>
          <?php else: ?>
            <a href="<?php echo url_for(['module' => 'user', 'action' => 'login']); ?>" class="btn btn-primary w-100">
              <?php echo __('Log in to Bid'); ?>
            </a>
          <?php endif; ?>

          <?php if ($auction->buy_now_price): ?>
            <hr>
            <p class="small text-muted mb-1"><?php echo __('Buy Now Price'); ?></p>
            <p class="h5 mb-2"><?php echo esc_entities($listing->currency); ?> <?php echo number_format((float) $auction->buy_now_price, 2); ?></p>
            <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'buy', 'slug' => $listing->slug]); ?>" class="btn btn-outline-primary w-100">
              <i class="fas fa-bolt me-1"></i> <?php echo __('Buy Now'); ?>
            </a>
          <?php endif; ?>

        <?php elseif ($listing->listing_type === 'offer_only'): ?>
          <p class="h5 mb-2"><?php echo __('Accepting Offers'); ?></p>
          <?php if ($listing->minimum_offer): ?>
            <p class="small text-muted mb-3"><?php echo __('Minimum offer: %1% %2%', ['%1%' => esc_entities($listing->currency), '%2%' => number_format((float) $listing->minimum_offer, 2)]); ?></p>
          <?php endif; ?>
          <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'offerForm', 'slug' => $listing->slug]); ?>" class="btn btn-primary w-100 mb-2">
            <i class="fas fa-hand-holding-usd me-1"></i> <?php echo __('Make an Offer'); ?>
          </a>
          <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'enquiryForm', 'slug' => $listing->slug]); ?>" class="btn btn-outline-secondary w-100">
            <i class="fas fa-envelope me-1"></i> <?php echo __('Enquire'); ?>
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Seller info card -->
    <?php if ($seller): ?>
      <div class="card mb-3">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <?php if ($seller->avatar_path): ?>
              <img src="<?php echo esc_entities($seller->avatar_path); ?>" alt="" class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">
            <?php else: ?>
              <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                <i class="fas fa-user"></i>
              </div>
            <?php endif; ?>
            <div>
              <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'seller', 'slug' => $seller->slug]); ?>" class="fw-semibold text-decoration-none">
                <?php echo esc_entities($seller->display_name); ?>
              </a>
              <?php if ($seller->verification_status === 'verified'): ?>
                <i class="fas fa-check-circle text-primary ms-1" title="<?php echo __('Verified Seller'); ?>"></i>
              <?php endif; ?>
              <div class="small text-muted">
                <?php if ($seller->average_rating > 0): ?>
                  <?php for ($s = 1; $s <= 5; $s++): ?>
                    <i class="fa<?php echo $s <= round($seller->average_rating) ? 's' : 'r'; ?> fa-star text-warning"></i>
                  <?php endfor; ?>
                  <span class="ms-1">(<?php echo (int) $seller->rating_count; ?>)</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'seller', 'slug' => $seller->slug]); ?>" class="btn btn-outline-secondary btn-sm w-100"><?php echo __('View Seller Profile'); ?></a>
        </div>
      </div>
    <?php endif; ?>

    <!-- Quick details -->
    <div class="card mb-3">
      <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
          <?php if ($listing->condition_rating): ?>
            <tr><td class="text-muted"><?php echo __('Condition'); ?></td><td><?php echo esc_entities(ucfirst($listing->condition_rating)); ?></td></tr>
          <?php endif; ?>
          <?php if ($listing->medium): ?>
            <tr><td class="text-muted"><?php echo __('Medium'); ?></td><td><?php echo esc_entities($listing->medium); ?></td></tr>
          <?php endif; ?>
          <?php if ($listing->dimensions): ?>
            <tr><td class="text-muted"><?php echo __('Dimensions'); ?></td><td><?php echo esc_entities($listing->dimensions); ?></td></tr>
          <?php endif; ?>
          <?php if ($listing->year_created): ?>
            <tr><td class="text-muted"><?php echo __('Year'); ?></td><td><?php echo esc_entities($listing->year_created); ?></td></tr>
          <?php endif; ?>
          <?php if ($listing->edition_info): ?>
            <tr><td class="text-muted"><?php echo __('Edition'); ?></td><td><?php echo esc_entities($listing->edition_info); ?></td></tr>
          <?php endif; ?>
          <?php if ($listing->is_signed): ?>
            <tr><td class="text-muted"><?php echo __('Signed'); ?></td><td><i class="fas fa-check text-success"></i> <?php echo __('Yes'); ?></td></tr>
          <?php endif; ?>
          <?php if ($listing->certificate_of_authenticity): ?>
            <tr><td class="text-muted"><?php echo __('COA'); ?></td><td><i class="fas fa-check text-success"></i> <?php echo __('Certificate of Authenticity'); ?></td></tr>
          <?php endif; ?>
          <?php if ($listing->is_framed): ?>
            <tr><td class="text-muted"><?php echo __('Framed'); ?></td><td><?php echo esc_entities($listing->frame_description ?: __('Yes')); ?></td></tr>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <!-- Shipping info -->
    <?php if ($listing->requires_shipping): ?>
      <div class="card mb-3">
        <div class="card-body">
          <h6 class="mb-2"><i class="fas fa-truck me-1"></i> <?php echo __('Shipping'); ?></h6>
          <?php if ($listing->free_shipping_domestic): ?>
            <p class="mb-1"><span class="badge bg-success"><?php echo __('Free Domestic Shipping'); ?></span></p>
          <?php elseif ($listing->shipping_domestic_price): ?>
            <p class="mb-1 small"><?php echo __('Domestic: %1% %2%', ['%1%' => esc_entities($listing->currency), '%2%' => number_format((float) $listing->shipping_domestic_price, 2)]); ?></p>
          <?php endif; ?>
          <?php if ($listing->shipping_international_price): ?>
            <p class="mb-1 small"><?php echo __('International: %1% %2%', ['%1%' => esc_entities($listing->currency), '%2%' => number_format((float) $listing->shipping_international_price, 2)]); ?></p>
          <?php endif; ?>
          <?php if ($listing->shipping_from_country): ?>
            <p class="mb-0 small text-muted"><?php echo __('Ships from: %1%', ['%1%' => esc_entities($listing->shipping_from_city ? $listing->shipping_from_city . ', ' . $listing->shipping_from_country : $listing->shipping_from_country)]); ?></p>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Detail tabs -->
<div class="row mt-2">
  <div class="col-12">
    <ul class="nav nav-tabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-description" data-bs-toggle="tab" data-bs-target="#panel-description" type="button" role="tab"><?php echo __('Description'); ?></button>
      </li>
      <?php if ($listing->provenance): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-provenance" data-bs-toggle="tab" data-bs-target="#panel-provenance" type="button" role="tab"><?php echo __('Provenance'); ?></button>
        </li>
      <?php endif; ?>
      <?php if ($listing->condition_description): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-condition" data-bs-toggle="tab" data-bs-target="#panel-condition" type="button" role="tab"><?php echo __('Condition Report'); ?></button>
        </li>
      <?php endif; ?>
      <?php if ($listing->requires_shipping && $listing->shipping_notes): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-shipping" data-bs-toggle="tab" data-bs-target="#panel-shipping" type="button" role="tab"><?php echo __('Shipping'); ?></button>
        </li>
      <?php endif; ?>
      <?php if ($seller): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-seller" data-bs-toggle="tab" data-bs-target="#panel-seller" type="button" role="tab"><?php echo __('Seller Info'); ?></button>
        </li>
      <?php endif; ?>
    </ul>
    <div class="tab-content border border-top-0 rounded-bottom p-4">
      <div class="tab-pane fade show active" id="panel-description" role="tabpanel">
        <?php if ($listing->description): ?>
          <div class="listing-description"><?php echo nl2br(esc_entities($listing->description)); ?></div>
        <?php else: ?>
          <p class="text-muted"><?php echo __('No description provided.'); ?></p>
        <?php endif; ?>
      </div>

      <?php if ($listing->provenance): ?>
        <div class="tab-pane fade" id="panel-provenance" role="tabpanel">
          <div><?php echo nl2br(esc_entities($listing->provenance)); ?></div>
        </div>
      <?php endif; ?>

      <?php if ($listing->condition_description): ?>
        <div class="tab-pane fade" id="panel-condition" role="tabpanel">
          <div><?php echo nl2br(esc_entities($listing->condition_description)); ?></div>
        </div>
      <?php endif; ?>

      <?php if ($listing->requires_shipping && $listing->shipping_notes): ?>
        <div class="tab-pane fade" id="panel-shipping" role="tabpanel">
          <div><?php echo nl2br(esc_entities($listing->shipping_notes)); ?></div>
          <?php if ($listing->insurance_value): ?>
            <p class="mt-2"><strong><?php echo __('Insurance Value'); ?>:</strong> <?php echo esc_entities($listing->currency); ?> <?php echo number_format((float) $listing->insurance_value, 2); ?></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($seller): ?>
        <div class="tab-pane fade" id="panel-seller" role="tabpanel">
          <div class="d-flex align-items-start">
            <?php if ($seller->avatar_path): ?>
              <img src="<?php echo esc_entities($seller->avatar_path); ?>" alt="" class="rounded-circle me-3" width="60" height="60" style="object-fit: cover;">
            <?php endif; ?>
            <div>
              <h6>
                <?php echo esc_entities($seller->display_name); ?>
                <?php if ($seller->verification_status === 'verified'): ?>
                  <i class="fas fa-check-circle text-primary ms-1"></i>
                <?php endif; ?>
              </h6>
              <p class="small text-muted mb-1"><?php echo esc_entities(ucfirst($seller->seller_type)); ?></p>
              <?php if ($seller->city || $seller->country): ?>
                <p class="small text-muted mb-2"><i class="fas fa-map-marker-alt me-1"></i> <?php echo esc_entities(implode(', ', array_filter([$seller->city, $seller->country]))); ?></p>
              <?php endif; ?>
              <?php if ($seller->bio): ?>
                <p class="small"><?php echo nl2br(esc_entities($seller->bio)); ?></p>
              <?php endif; ?>
              <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'seller', 'slug' => $seller->slug]); ?>" class="btn btn-outline-primary btn-sm"><?php echo __('View Full Profile'); ?></a>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Related listings -->
<?php if (!empty($relatedListings)): ?>
  <div class="mt-5">
    <h4 class="mb-3"><?php echo __('Related Listings'); ?></h4>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">
      <?php foreach ($relatedListings as $related): ?>
        <?php include_partial('marketplace/listingCard', ['listing' => $related]); ?>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  // Thumbnail click: swap main image
  document.querySelectorAll('.listing-thumb').forEach(function(thumb) {
    thumb.addEventListener('click', function() {
      var mainImg = document.getElementById('main-image');
      if (mainImg) {
        mainImg.src = this.getAttribute('data-src');
      }
    });
  });

  // Favourite toggle
  var favBtn = document.getElementById('btn-favourite');
  if (favBtn) {
    favBtn.addEventListener('click', function() {
      var listingId = this.getAttribute('data-listing-id');
      var icon = this.querySelector('i');
      fetch('<?php echo url_for(['module' => 'marketplace', 'action' => 'apiFavourite']); ?>'.replace(/\/$/,'') + '/' + listingId + '/favourite', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'}
      }).then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.favourited) {
            icon.className = 'fas fa-heart text-danger';
          } else {
            icon.className = 'far fa-heart text-danger';
          }
        });
    });
  }

  // Auction countdown
  var countdownEl = document.getElementById('auction-countdown');
  if (countdownEl) {
    var endTime = new Date(countdownEl.getAttribute('data-end')).getTime();
    function updateCountdown() {
      var now = new Date().getTime();
      var diff = endTime - now;
      if (diff <= 0) {
        countdownEl.textContent = '<?php echo __('Ended'); ?>';
        return;
      }
      var d = Math.floor(diff / 86400000);
      var h = Math.floor((diff % 86400000) / 3600000);
      var m = Math.floor((diff % 3600000) / 60000);
      var s = Math.floor((diff % 60000) / 1000);
      var parts = [];
      if (d > 0) parts.push(d + 'd');
      parts.push(h + 'h');
      parts.push(m + 'm');
      parts.push(s + 's');
      countdownEl.textContent = parts.join(' ');
    }
    updateCountdown();
    setInterval(updateCountdown, 1000);
  }
});
</script>

<?php end_slot(); ?>

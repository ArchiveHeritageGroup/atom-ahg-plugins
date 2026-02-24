<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Edit Listing'); ?>: <?php echo esc_entities($listing->title); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'dashboard']); ?>"><?php echo __('Dashboard'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListings']); ?>"><?php echo __('My Listings'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Edit'); ?></li>
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
  <h1 class="h3 mb-0"><?php echo __('Edit Listing: %1%', ['%1%' => esc_entities($listing->title)]); ?></h1>
  <div>
    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListingImages', 'id' => $listing->id]); ?>" class="btn btn-outline-secondary me-1">
      <i class="fas fa-images me-1"></i><?php echo __('Manage Images'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $listing->slug]); ?>" class="btn btn-outline-secondary" target="_blank">
      <i class="fas fa-external-link-alt me-1"></i><?php echo __('Preview'); ?>
    </a>
  </div>
</div>

<form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListingEdit', 'id' => $listing->id]); ?>" id="listing-form">

  <!-- Basic Info -->
  <div class="card mb-4">
    <div class="card-header fw-semibold"><?php echo __('Basic Information'); ?></div>
    <div class="card-body">
      <div class="mb-3">
        <label for="title" class="form-label"><?php echo __('Title'); ?> <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="title" name="title" value="<?php echo esc_entities($listing->title); ?>" required maxlength="500">
      </div>

      <div class="mb-3">
        <label for="short_description" class="form-label"><?php echo __('Short Description'); ?></label>
        <input type="text" class="form-control" id="short_description" name="short_description" value="<?php echo esc_entities($listing->short_description ?? ''); ?>" maxlength="1000">
      </div>

      <div class="mb-3">
        <label for="description" class="form-label"><?php echo __('Full Description'); ?></label>
        <textarea class="form-control" id="description" name="description" rows="5"><?php echo esc_entities($listing->description ?? ''); ?></textarea>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <label for="sector" class="form-label"><?php echo __('Sector'); ?> <span class="text-danger">*</span></label>
          <select class="form-select" id="sector" name="sector" required>
            <option value=""><?php echo __('-- Select Sector --'); ?></option>
            <?php $sectorList = ['gallery' => __('Gallery'), 'museum' => __('Museum'), 'archive' => __('Archive'), 'library' => __('Library'), 'dam' => __('Digital Asset Management')]; ?>
            <?php foreach ($sectorList as $val => $label): ?>
              <option value="<?php echo $val; ?>"<?php echo ($listing->sector ?? '') === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label for="category_id" class="form-label"><?php echo __('Category'); ?></label>
          <select class="form-select" id="category_id" name="category_id">
            <option value=""><?php echo __('-- Select Category --'); ?></option>
            <?php if (!empty($categories)): ?>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo (int) $cat->id; ?>" data-sector="<?php echo esc_entities($cat->sector); ?>"<?php echo ($listing->category_id ?? '') == $cat->id ? ' selected' : ''; ?>>
                  <?php echo esc_entities($cat->name); ?> (<?php echo esc_entities(ucfirst($cat->sector)); ?>)
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-4">
          <label for="medium" class="form-label"><?php echo __('Medium'); ?></label>
          <input type="text" class="form-control" id="medium" name="medium" value="<?php echo esc_entities($listing->medium ?? ''); ?>" maxlength="255">
        </div>
        <div class="col-md-4">
          <label for="dimensions" class="form-label"><?php echo __('Dimensions'); ?></label>
          <input type="text" class="form-control" id="dimensions" name="dimensions" value="<?php echo esc_entities($listing->dimensions ?? ''); ?>" maxlength="255">
        </div>
        <div class="col-md-4">
          <label for="weight_kg" class="form-label"><?php echo __('Weight (kg)'); ?></label>
          <input type="number" class="form-control" id="weight_kg" name="weight_kg" value="<?php echo esc_entities($listing->weight_kg ?? ''); ?>" min="0" step="0.01">
        </div>
      </div>
    </div>
  </div>

  <!-- Artwork Details -->
  <div class="card mb-4">
    <div class="card-header fw-semibold"><?php echo __('Artwork Details'); ?></div>
    <div class="card-body">
      <div class="row mb-3">
        <div class="col-md-4">
          <label for="year_created" class="form-label"><?php echo __('Year Created'); ?></label>
          <input type="text" class="form-control" id="year_created" name="year_created" value="<?php echo esc_entities($listing->year_created ?? ''); ?>" maxlength="50">
        </div>
        <div class="col-md-4">
          <label for="artist_name" class="form-label"><?php echo __('Artist Name'); ?></label>
          <input type="text" class="form-control" id="artist_name" name="artist_name" value="<?php echo esc_entities($listing->artist_name ?? ''); ?>" maxlength="255">
        </div>
        <div class="col-md-4">
          <label for="edition_info" class="form-label"><?php echo __('Edition Info'); ?></label>
          <input type="text" class="form-control" id="edition_info" name="edition_info" value="<?php echo esc_entities($listing->edition_info ?? ''); ?>" maxlength="255">
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_signed" name="is_signed" value="1"<?php echo !empty($listing->is_signed) ? ' checked' : ''; ?>>
            <label class="form-check-label" for="is_signed"><?php echo __('Is Signed'); ?></label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_framed" name="is_framed" value="1"<?php echo !empty($listing->is_framed) ? ' checked' : ''; ?>>
            <label class="form-check-label" for="is_framed"><?php echo __('Is Framed'); ?></label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="certificate_of_authenticity" name="certificate_of_authenticity" value="1"<?php echo !empty($listing->certificate_of_authenticity) ? ' checked' : ''; ?>>
            <label class="form-check-label" for="certificate_of_authenticity"><?php echo __('Certificate of Authenticity'); ?></label>
          </div>
        </div>
      </div>

      <div class="mb-3" id="frame-description-group" style="<?php echo empty($listing->is_framed) ? 'display: none;' : ''; ?>">
        <label for="frame_description" class="form-label"><?php echo __('Frame Description'); ?></label>
        <input type="text" class="form-control" id="frame_description" name="frame_description" value="<?php echo esc_entities($listing->frame_description ?? ''); ?>" maxlength="255">
      </div>
    </div>
  </div>

  <!-- Pricing -->
  <div class="card mb-4">
    <div class="card-header fw-semibold"><?php echo __('Pricing'); ?></div>
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label"><?php echo __('Listing Type'); ?> <span class="text-danger">*</span></label>
        <div>
          <?php $listingTypes = ['fixed_price' => __('Fixed Price'), 'auction' => __('Auction'), 'offer_only' => __('Offer Only')]; ?>
          <?php foreach ($listingTypes as $val => $label): ?>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="listing_type" id="type-<?php echo $val; ?>" value="<?php echo $val; ?>"<?php echo ($listing->listing_type ?? 'fixed_price') === $val ? ' checked' : ''; ?> required>
              <label class="form-check-label" for="type-<?php echo $val; ?>"><?php echo $label; ?></label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-4">
          <label for="currency" class="form-label"><?php echo __('Currency'); ?></label>
          <select class="form-select" id="currency" name="currency">
            <?php if (!empty($currencies)): ?>
              <?php foreach ($currencies as $cur): ?>
                <option value="<?php echo esc_entities($cur->code); ?>"<?php echo ($listing->currency ?? 'ZAR') === $cur->code ? ' selected' : ''; ?>>
                  <?php echo esc_entities($cur->code . ' (' . $cur->symbol . ')'); ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
      </div>

      <div id="fixed-price-fields">
        <div class="row mb-3">
          <div class="col-md-4">
            <label for="price" class="form-label"><?php echo __('Price'); ?></label>
            <input type="number" class="form-control" id="price" name="price" value="<?php echo esc_entities($listing->price ?? ''); ?>" min="0" step="0.01">
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="price_on_request" name="price_on_request" value="1"<?php echo !empty($listing->price_on_request) ? ' checked' : ''; ?>>
              <label class="form-check-label" for="price_on_request"><?php echo __('Price on Request'); ?></label>
            </div>
          </div>
          <div class="col-md-4">
            <label for="minimum_offer" class="form-label"><?php echo __('Minimum Offer'); ?></label>
            <input type="number" class="form-control" id="minimum_offer" name="minimum_offer" value="<?php echo esc_entities($listing->minimum_offer ?? ''); ?>" min="0" step="0.01">
          </div>
        </div>
      </div>

      <div id="auction-fields" style="display: none;">
        <div class="row mb-3">
          <div class="col-md-4">
            <label for="starting_bid" class="form-label"><?php echo __('Starting Bid'); ?></label>
            <input type="number" class="form-control" id="starting_bid" name="starting_bid" value="<?php echo esc_entities($listing->starting_bid ?? ''); ?>" min="0" step="0.01">
          </div>
          <div class="col-md-4">
            <label for="reserve_price" class="form-label"><?php echo __('Reserve Price'); ?></label>
            <input type="number" class="form-control" id="reserve_price" name="reserve_price" value="<?php echo esc_entities($listing->reserve_price ?? ''); ?>" min="0" step="0.01">
          </div>
          <div class="col-md-4">
            <label for="buy_now_price" class="form-label"><?php echo __('Buy Now Price'); ?></label>
            <input type="number" class="form-control" id="buy_now_price" name="buy_now_price" value="<?php echo esc_entities($listing->buy_now_price ?? ''); ?>" min="0" step="0.01">
          </div>
        </div>
      </div>

      <div id="offer-only-fields" style="display: none;">
        <div class="row mb-3">
          <div class="col-md-4">
            <label for="minimum_offer_only" class="form-label"><?php echo __('Minimum Offer'); ?></label>
            <input type="number" class="form-control" id="minimum_offer_only" name="minimum_offer_for_offer_only" value="<?php echo esc_entities($listing->minimum_offer ?? ''); ?>" min="0" step="0.01">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Condition -->
  <div class="card mb-4">
    <div class="card-header fw-semibold"><?php echo __('Condition'); ?></div>
    <div class="card-body">
      <div class="mb-3">
        <label for="condition_rating" class="form-label"><?php echo __('Condition Rating'); ?></label>
        <select class="form-select" id="condition_rating" name="condition_rating">
          <option value=""><?php echo __('-- Select --'); ?></option>
          <?php $conditions = ['mint' => __('Mint'), 'excellent' => __('Excellent'), 'good' => __('Good'), 'fair' => __('Fair'), 'poor' => __('Poor')]; ?>
          <?php foreach ($conditions as $val => $label): ?>
            <option value="<?php echo $val; ?>"<?php echo ($listing->condition_rating ?? '') === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label for="condition_description" class="form-label"><?php echo __('Condition Description'); ?></label>
        <textarea class="form-control" id="condition_description" name="condition_description" rows="3"><?php echo esc_entities($listing->condition_description ?? ''); ?></textarea>
      </div>
      <div class="mb-3">
        <label for="provenance" class="form-label"><?php echo __('Provenance'); ?></label>
        <textarea class="form-control" id="provenance" name="provenance" rows="3"><?php echo esc_entities($listing->provenance ?? ''); ?></textarea>
      </div>
    </div>
  </div>

  <!-- Shipping -->
  <div class="card mb-4">
    <div class="card-header fw-semibold"><?php echo __('Shipping'); ?></div>
    <div class="card-body">
      <div class="row mb-3">
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_physical" name="is_physical" value="1"<?php echo !empty($listing->is_physical) ? ' checked' : ''; ?>>
            <label class="form-check-label" for="is_physical"><?php echo __('Physical Item'); ?></label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_digital" name="is_digital" value="1"<?php echo !empty($listing->is_digital) ? ' checked' : ''; ?>>
            <label class="form-check-label" for="is_digital"><?php echo __('Digital Item'); ?></label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="requires_shipping" name="requires_shipping" value="1"<?php echo !empty($listing->requires_shipping) ? ' checked' : ''; ?>>
            <label class="form-check-label" for="requires_shipping"><?php echo __('Requires Shipping'); ?></label>
          </div>
        </div>
      </div>

      <div id="shipping-details">
        <div class="row mb-3">
          <div class="col-md-6">
            <label for="shipping_from_country" class="form-label"><?php echo __('Shipping From (Country)'); ?></label>
            <input type="text" class="form-control" id="shipping_from_country" name="shipping_from_country" value="<?php echo esc_entities($listing->shipping_from_country ?? ''); ?>" maxlength="100">
          </div>
          <div class="col-md-6">
            <label for="shipping_from_city" class="form-label"><?php echo __('Shipping From (City)'); ?></label>
            <input type="text" class="form-control" id="shipping_from_city" name="shipping_from_city" value="<?php echo esc_entities($listing->shipping_from_city ?? ''); ?>" maxlength="100">
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label for="shipping_domestic_price" class="form-label"><?php echo __('Domestic Shipping Price'); ?></label>
            <input type="number" class="form-control" id="shipping_domestic_price" name="shipping_domestic_price" value="<?php echo esc_entities($listing->shipping_domestic_price ?? ''); ?>" min="0" step="0.01">
          </div>
          <div class="col-md-4">
            <label for="shipping_international_price" class="form-label"><?php echo __('International Shipping Price'); ?></label>
            <input type="number" class="form-control" id="shipping_international_price" name="shipping_international_price" value="<?php echo esc_entities($listing->shipping_international_price ?? ''); ?>" min="0" step="0.01">
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="free_shipping_domestic" name="free_shipping_domestic" value="1"<?php echo !empty($listing->free_shipping_domestic) ? ' checked' : ''; ?>>
              <label class="form-check-label" for="free_shipping_domestic"><?php echo __('Free Domestic Shipping'); ?></label>
            </div>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label for="insurance_value" class="form-label"><?php echo __('Insurance Value'); ?></label>
            <input type="number" class="form-control" id="insurance_value" name="insurance_value" value="<?php echo esc_entities($listing->insurance_value ?? ''); ?>" min="0" step="0.01">
          </div>
        </div>

        <div class="mb-3">
          <label for="shipping_notes" class="form-label"><?php echo __('Shipping Notes'); ?></label>
          <textarea class="form-control" id="shipping_notes" name="shipping_notes" rows="2"><?php echo esc_entities($listing->shipping_notes ?? ''); ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- Tags -->
  <div class="card mb-4">
    <div class="card-header fw-semibold"><?php echo __('Tags'); ?></div>
    <div class="card-body">
      <?php
        $tagsStr = '';
        if (!empty($listing->tags)) {
          $tagsArr = is_string($listing->tags) ? json_decode($listing->tags, true) : (array) $listing->tags;
          $tagsStr = is_array($tagsArr) ? implode(', ', $tagsArr) : '';
        }
      ?>
      <label for="tags" class="form-label"><?php echo __('Tags'); ?></label>
      <input type="text" class="form-control" id="tags" name="tags" value="<?php echo esc_entities($tagsStr); ?>" placeholder="<?php echo __('Comma-separated'); ?>">
    </div>
  </div>

  <div class="d-flex justify-content-between">
    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListings']); ?>" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Listings'); ?>
    </a>
    <button type="submit" class="btn btn-primary btn-lg">
      <i class="fas fa-save me-1"></i> <?php echo __('Save Changes'); ?>
    </button>
  </div>

</form>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var sectorSelect = document.getElementById('sector');
  var categorySelect = document.getElementById('category_id');
  var typeRadios = document.querySelectorAll('input[name="listing_type"]');
  var fixedFields = document.getElementById('fixed-price-fields');
  var auctionFields = document.getElementById('auction-fields');
  var offerFields = document.getElementById('offer-only-fields');
  var framedCheckbox = document.getElementById('is_framed');
  var frameGroup = document.getElementById('frame-description-group');

  if (sectorSelect && categorySelect) {
    var allOptions = Array.from(categorySelect.querySelectorAll('option[data-sector]'));
    var currentVal = categorySelect.value;
    sectorSelect.addEventListener('change', function() {
      var selected = this.value;
      allOptions.forEach(function(opt) {
        opt.style.display = (selected === '' || opt.getAttribute('data-sector') === selected) ? '' : 'none';
      });
    });
    sectorSelect.dispatchEvent(new Event('change'));
    categorySelect.value = currentVal;
  }

  function togglePricingFields() {
    var checked = document.querySelector('input[name="listing_type"]:checked');
    var val = checked ? checked.value : 'fixed_price';
    fixedFields.style.display = (val === 'fixed_price') ? '' : 'none';
    auctionFields.style.display = (val === 'auction') ? '' : 'none';
    offerFields.style.display = (val === 'offer_only') ? '' : 'none';
  }
  typeRadios.forEach(function(radio) {
    radio.addEventListener('change', togglePricingFields);
  });
  togglePricingFields();

  if (framedCheckbox && frameGroup) {
    framedCheckbox.addEventListener('change', function() {
      frameGroup.style.display = this.checked ? '' : 'none';
    });
  }
});
</script>

<?php end_slot(); ?>

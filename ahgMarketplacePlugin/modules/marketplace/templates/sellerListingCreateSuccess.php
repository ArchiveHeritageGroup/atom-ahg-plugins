<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Create New Listing'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'dashboard']); ?>"><?php echo __('Dashboard'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListings']); ?>"><?php echo __('My Listings'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Create Listing'); ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('error'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<h1 class="h3 mb-4"><?php echo __('Create New Listing'); ?></h1>

<?php $pf = isset($prefill) && $prefill ? (is_object($prefill) && method_exists($prefill, 'getRawValue') ? $prefill->getRawValue() : $prefill) : null; ?>

<?php if ($pf): ?>
  <div class="alert alert-info">
    <i class="fas fa-link me-1"></i>
    <?php echo __('Creating listing from archival record: <strong>%1%</strong>', ['%1%' => esc_entities($pf->title)]); ?>
    <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $pf->slug]); ?>" class="ms-2" target="_blank"><i class="fas fa-external-link-alt"></i> <?php echo __('View Record'); ?></a>
  </div>
<?php endif; ?>

<form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListingCreate']); ?>" id="listing-form">

  <input type="hidden" name="information_object_id" id="information_object_id" value="<?php echo $pf ? (int) $pf->information_object_id : ''; ?>">

  <!-- Link to Archive Record -->
  <div class="card mb-4">
    <div class="card-header fw-semibold"><?php echo __('Link to Archive Record'); ?> <small class="text-muted fw-normal">(<?php echo __('Optional'); ?>)</small></div>
    <div class="card-body">
      <div class="mb-0 position-relative">
        <label for="io_search" class="form-label"><?php echo __('Search by title'); ?></label>
        <input type="text" class="form-control" id="io_search" autocomplete="off" placeholder="<?php echo __('Start typing to search archival records...'); ?>" value="<?php echo $pf ? esc_entities($pf->title) : ''; ?>">
        <div id="io_results" class="list-group position-absolute w-100 shadow-sm" style="z-index:1050; max-height:250px; overflow-y:auto; display:none;"></div>
        <div class="form-text"><?php echo __('Search and select an existing record to auto-fill title, description, and metadata.'); ?></div>
        <?php if ($pf): ?>
          <div class="mt-2" id="io_linked">
            <span class="badge bg-info"><i class="fas fa-link me-1"></i><?php echo esc_entities($pf->title); ?></span>
            <button type="button" class="btn btn-sm btn-link text-danger" id="io_unlink"><i class="fas fa-times"></i> <?php echo __('Unlink'); ?></button>
          </div>
        <?php else: ?>
          <div class="mt-2" id="io_linked" style="display:none;">
            <span class="badge bg-info" id="io_linked_label"></span>
            <button type="button" class="btn btn-sm btn-link text-danger" id="io_unlink"><i class="fas fa-times"></i> <?php echo __('Unlink'); ?></button>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Basic Info -->
  <div class="card mb-4">
    <div class="card-header fw-semibold"><?php echo __('Basic Information'); ?></div>
    <div class="card-body">
      <div class="mb-3">
        <label for="title" class="form-label"><?php echo __('Title'); ?> <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="title" name="title" value="<?php echo esc_entities($sf_request->getParameter('title', '') ?: ($pf->title ?? '')); ?>" required maxlength="500">
      </div>

      <div class="mb-3">
        <label for="short_description" class="form-label"><?php echo __('Short Description'); ?></label>
        <input type="text" class="form-control" id="short_description" name="short_description" value="<?php echo esc_entities($sf_request->getParameter('short_description', '')); ?>" maxlength="1000">
        <div class="form-text"><?php echo __('Brief summary shown in listing cards.'); ?></div>
      </div>

      <div class="mb-3">
        <label for="description" class="form-label"><?php echo __('Full Description'); ?></label>
        <textarea class="form-control" id="description" name="description" rows="5"><?php echo esc_entities($sf_request->getParameter('description', '') ?: ($pf->description ?? '')); ?></textarea>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <label for="sector" class="form-label"><?php echo __('Sector'); ?> <span class="text-danger">*</span></label>
          <select class="form-select" id="sector" name="sector" required>
            <option value=""><?php echo __('-- Select Sector --'); ?></option>
            <?php $sectorList = ['gallery' => __('Gallery'), 'museum' => __('Museum'), 'archive' => __('Archive'), 'library' => __('Library'), 'dam' => __('Digital Asset Management')]; ?>
            <?php foreach ($sectorList as $val => $label): ?>
              <option value="<?php echo $val; ?>"<?php echo ($sf_request->getParameter('sector') ?: ($pf->sector ?? '')) === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label for="category_id" class="form-label"><?php echo __('Category'); ?></label>
          <select class="form-select" id="category_id" name="category_id">
            <option value=""><?php echo __('-- Select Category --'); ?></option>
            <?php if (!empty($categories)): ?>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo (int) $cat->id; ?>" data-sector="<?php echo esc_entities($cat->sector); ?>"<?php echo $sf_request->getParameter('category_id') == $cat->id ? ' selected' : ''; ?>>
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
          <input type="text" class="form-control" id="medium" name="medium" value="<?php echo esc_entities($sf_request->getParameter('medium', '') ?: ($pf->medium ?? '')); ?>" maxlength="255">
        </div>
        <div class="col-md-4">
          <label for="dimensions" class="form-label"><?php echo __('Dimensions'); ?></label>
          <input type="text" class="form-control" id="dimensions" name="dimensions" value="<?php echo esc_entities($sf_request->getParameter('dimensions', '')); ?>" placeholder="<?php echo __('e.g. 60 x 80 cm'); ?>" maxlength="255">
        </div>
        <div class="col-md-4">
          <label for="weight_kg" class="form-label"><?php echo __('Weight (kg)'); ?></label>
          <input type="number" class="form-control" id="weight_kg" name="weight_kg" value="<?php echo esc_entities($sf_request->getParameter('weight_kg', '')); ?>" min="0" step="0.01">
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
          <input type="text" class="form-control" id="year_created" name="year_created" value="<?php echo esc_entities($sf_request->getParameter('year_created', '')); ?>" maxlength="50">
        </div>
        <div class="col-md-4">
          <label for="artist_name" class="form-label"><?php echo __('Artist Name'); ?></label>
          <input type="text" class="form-control" id="artist_name" name="artist_name" value="<?php echo esc_entities($sf_request->getParameter('artist_name', '')); ?>" maxlength="255">
        </div>
        <div class="col-md-4">
          <label for="edition_info" class="form-label"><?php echo __('Edition Info'); ?></label>
          <input type="text" class="form-control" id="edition_info" name="edition_info" value="<?php echo esc_entities($sf_request->getParameter('edition_info', '')); ?>" placeholder="<?php echo __('e.g. 1/50'); ?>" maxlength="255">
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_signed" name="is_signed" value="1"<?php echo $sf_request->getParameter('is_signed') ? ' checked' : ''; ?>>
            <label class="form-check-label" for="is_signed"><?php echo __('Is Signed'); ?></label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_framed" name="is_framed" value="1"<?php echo $sf_request->getParameter('is_framed') ? ' checked' : ''; ?>>
            <label class="form-check-label" for="is_framed"><?php echo __('Is Framed'); ?></label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="certificate_of_authenticity" name="certificate_of_authenticity" value="1"<?php echo $sf_request->getParameter('certificate_of_authenticity') ? ' checked' : ''; ?>>
            <label class="form-check-label" for="certificate_of_authenticity"><?php echo __('Certificate of Authenticity'); ?></label>
          </div>
        </div>
      </div>

      <div class="mb-3" id="frame-description-group" style="display: none;">
        <label for="frame_description" class="form-label"><?php echo __('Frame Description'); ?></label>
        <input type="text" class="form-control" id="frame_description" name="frame_description" value="<?php echo esc_entities($sf_request->getParameter('frame_description', '')); ?>" maxlength="255">
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
              <input class="form-check-input" type="radio" name="listing_type" id="type-<?php echo $val; ?>" value="<?php echo $val; ?>"<?php echo $sf_request->getParameter('listing_type', 'fixed_price') === $val ? ' checked' : ''; ?> required>
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
                <option value="<?php echo esc_entities($cur->code); ?>"<?php echo $sf_request->getParameter('currency', 'ZAR') === $cur->code ? ' selected' : ''; ?>>
                  <?php echo esc_entities($cur->code . ' (' . $cur->symbol . ')'); ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
      </div>

      <!-- Fixed price fields -->
      <div id="fixed-price-fields">
        <div class="row mb-3">
          <div class="col-md-4">
            <label for="price" class="form-label"><?php echo __('Price'); ?></label>
            <input type="number" class="form-control" id="price" name="price" value="<?php echo esc_entities($sf_request->getParameter('price', '')); ?>" min="0" step="0.01">
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="price_on_request" name="price_on_request" value="1"<?php echo $sf_request->getParameter('price_on_request') ? ' checked' : ''; ?>>
              <label class="form-check-label" for="price_on_request"><?php echo __('Price on Request'); ?></label>
            </div>
          </div>
          <div class="col-md-4">
            <label for="minimum_offer" class="form-label"><?php echo __('Minimum Offer'); ?></label>
            <input type="number" class="form-control" id="minimum_offer" name="minimum_offer" value="<?php echo esc_entities($sf_request->getParameter('minimum_offer', '')); ?>" min="0" step="0.01">
            <div class="form-text"><?php echo __('Leave blank to disable offers.'); ?></div>
          </div>
        </div>
      </div>

      <!-- Auction fields -->
      <div id="auction-fields" style="display: none;">
        <div class="row mb-3">
          <div class="col-md-4">
            <label for="starting_bid" class="form-label"><?php echo __('Starting Bid'); ?></label>
            <input type="number" class="form-control" id="starting_bid" name="starting_bid" value="<?php echo esc_entities($sf_request->getParameter('starting_bid', '')); ?>" min="0" step="0.01">
          </div>
          <div class="col-md-4">
            <label for="reserve_price" class="form-label"><?php echo __('Reserve Price'); ?></label>
            <input type="number" class="form-control" id="reserve_price" name="reserve_price" value="<?php echo esc_entities($sf_request->getParameter('reserve_price', '')); ?>" min="0" step="0.01">
            <div class="form-text"><?php echo __('Optional. Item will not sell below this price.'); ?></div>
          </div>
          <div class="col-md-4">
            <label for="buy_now_price" class="form-label"><?php echo __('Buy Now Price'); ?></label>
            <input type="number" class="form-control" id="buy_now_price" name="buy_now_price" value="<?php echo esc_entities($sf_request->getParameter('buy_now_price', '')); ?>" min="0" step="0.01">
            <div class="form-text"><?php echo __('Optional. Allow immediate purchase at this price.'); ?></div>
          </div>
        </div>
      </div>

      <!-- Offer-only fields -->
      <div id="offer-only-fields" style="display: none;">
        <div class="row mb-3">
          <div class="col-md-4">
            <label for="minimum_offer_only" class="form-label"><?php echo __('Minimum Offer'); ?></label>
            <input type="number" class="form-control" id="minimum_offer_only" name="minimum_offer_for_offer_only" value="<?php echo esc_entities($sf_request->getParameter('minimum_offer_for_offer_only', '')); ?>" min="0" step="0.01">
            <div class="form-text"><?php echo __('Optional minimum offer amount.'); ?></div>
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
            <option value="<?php echo $val; ?>"<?php echo $sf_request->getParameter('condition_rating') === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label for="condition_description" class="form-label"><?php echo __('Condition Description'); ?></label>
        <textarea class="form-control" id="condition_description" name="condition_description" rows="3"><?php echo esc_entities($sf_request->getParameter('condition_description', '')); ?></textarea>
      </div>
      <div class="mb-3">
        <label for="provenance" class="form-label"><?php echo __('Provenance'); ?></label>
        <textarea class="form-control" id="provenance" name="provenance" rows="3" placeholder="<?php echo __('History of ownership...'); ?>"><?php echo esc_entities($sf_request->getParameter('provenance', '') ?: ($pf->provenance ?? '')); ?></textarea>
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
            <input class="form-check-input" type="checkbox" id="is_physical" name="is_physical" value="1"<?php echo $sf_request->getParameter('is_physical', '1') ? ' checked' : ''; ?>>
            <label class="form-check-label" for="is_physical"><?php echo __('Physical Item'); ?></label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_digital" name="is_digital" value="1"<?php echo $sf_request->getParameter('is_digital') ? ' checked' : ''; ?>>
            <label class="form-check-label" for="is_digital"><?php echo __('Digital Item'); ?></label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="requires_shipping" name="requires_shipping" value="1"<?php echo $sf_request->getParameter('requires_shipping', '1') ? ' checked' : ''; ?>>
            <label class="form-check-label" for="requires_shipping"><?php echo __('Requires Shipping'); ?></label>
          </div>
        </div>
      </div>

      <div id="shipping-details">
        <div class="row mb-3">
          <div class="col-md-6">
            <label for="shipping_from_country" class="form-label"><?php echo __('Shipping From (Country)'); ?></label>
            <input type="text" class="form-control" id="shipping_from_country" name="shipping_from_country" value="<?php echo esc_entities($sf_request->getParameter('shipping_from_country', '')); ?>" maxlength="100">
          </div>
          <div class="col-md-6">
            <label for="shipping_from_city" class="form-label"><?php echo __('Shipping From (City)'); ?></label>
            <input type="text" class="form-control" id="shipping_from_city" name="shipping_from_city" value="<?php echo esc_entities($sf_request->getParameter('shipping_from_city', '')); ?>" maxlength="100">
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label for="shipping_domestic_price" class="form-label"><?php echo __('Domestic Shipping Price'); ?></label>
            <input type="number" class="form-control" id="shipping_domestic_price" name="shipping_domestic_price" value="<?php echo esc_entities($sf_request->getParameter('shipping_domestic_price', '')); ?>" min="0" step="0.01">
          </div>
          <div class="col-md-4">
            <label for="shipping_international_price" class="form-label"><?php echo __('International Shipping Price'); ?></label>
            <input type="number" class="form-control" id="shipping_international_price" name="shipping_international_price" value="<?php echo esc_entities($sf_request->getParameter('shipping_international_price', '')); ?>" min="0" step="0.01">
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="free_shipping_domestic" name="free_shipping_domestic" value="1"<?php echo $sf_request->getParameter('free_shipping_domestic') ? ' checked' : ''; ?>>
              <label class="form-check-label" for="free_shipping_domestic"><?php echo __('Free Domestic Shipping'); ?></label>
            </div>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label for="insurance_value" class="form-label"><?php echo __('Insurance Value'); ?></label>
            <input type="number" class="form-control" id="insurance_value" name="insurance_value" value="<?php echo esc_entities($sf_request->getParameter('insurance_value', '')); ?>" min="0" step="0.01">
          </div>
        </div>

        <div class="mb-3">
          <label for="shipping_notes" class="form-label"><?php echo __('Shipping Notes'); ?></label>
          <textarea class="form-control" id="shipping_notes" name="shipping_notes" rows="2"><?php echo esc_entities($sf_request->getParameter('shipping_notes', '')); ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- Tags -->
  <div class="card mb-4">
    <div class="card-header fw-semibold"><?php echo __('Tags'); ?></div>
    <div class="card-body">
      <label for="tags" class="form-label"><?php echo __('Tags'); ?></label>
      <input type="text" class="form-control" id="tags" name="tags" value="<?php echo esc_entities($sf_request->getParameter('tags', '')); ?>" placeholder="<?php echo __('Comma-separated, e.g. oil painting, landscape, contemporary'); ?>">
      <div class="form-text"><?php echo __('Separate tags with commas.'); ?></div>
    </div>
  </div>

  <div class="d-flex justify-content-between">
    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListings']); ?>" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Listings'); ?>
    </a>
    <button type="submit" class="btn btn-primary btn-lg">
      <i class="fas fa-save me-1"></i> <?php echo __('Save as Draft'); ?>
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

  // Filter categories by sector
  if (sectorSelect && categorySelect) {
    var allOptions = Array.from(categorySelect.querySelectorAll('option[data-sector]'));
    sectorSelect.addEventListener('change', function() {
      var selected = this.value;
      categorySelect.value = '';
      allOptions.forEach(function(opt) {
        opt.style.display = (selected === '' || opt.getAttribute('data-sector') === selected) ? '' : 'none';
      });
    });
    // Trigger on load
    sectorSelect.dispatchEvent(new Event('change'));
  }

  // Show/hide pricing fields based on listing type
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

  // Show/hide frame description
  if (framedCheckbox && frameGroup) {
    framedCheckbox.addEventListener('change', function() {
      frameGroup.style.display = this.checked ? '' : 'none';
    });
    frameGroup.style.display = framedCheckbox.checked ? '' : 'none';
  }

  // Archive record autocomplete
  var ioSearch = document.getElementById('io_search');
  var ioResults = document.getElementById('io_results');
  var ioHidden = document.getElementById('information_object_id');
  var ioLinked = document.getElementById('io_linked');
  var ioLabel = document.getElementById('io_linked_label');
  var ioUnlink = document.getElementById('io_unlink');
  var searchTimer = null;

  if (ioSearch) {
    ioSearch.addEventListener('input', function() {
      clearTimeout(searchTimer);
      var q = this.value.trim();
      if (q.length < 2) { ioResults.style.display = 'none'; return; }
      searchTimer = setTimeout(function() {
        fetch('/index.php/informationobject/autocomplete?query=' + encodeURIComponent(q) + '&limit=10')
          .then(function(r) { return r.json(); })
          .then(function(data) {
            ioResults.innerHTML = '';
            var items = data.results || data;
            if (!items.length) {
              ioResults.innerHTML = '<div class="list-group-item text-muted small">No records found</div>';
              ioResults.style.display = '';
              return;
            }
            items.forEach(function(item) {
              var a = document.createElement('a');
              a.href = '#';
              a.className = 'list-group-item list-group-item-action small';
              a.textContent = item.title || item.label || item.name || 'Untitled';
              a.dataset.id = item.id || item.object_id || '';
              a.dataset.title = item.title || item.label || item.name || '';
              a.addEventListener('click', function(e) {
                e.preventDefault();
                selectRecord(this.dataset.id, this.dataset.title);
              });
              ioResults.appendChild(a);
            });
            ioResults.style.display = '';
          })
          .catch(function() { ioResults.style.display = 'none'; });
      }, 300);
    });

    ioSearch.addEventListener('blur', function() {
      setTimeout(function() { ioResults.style.display = 'none'; }, 200);
    });
  }

  function selectRecord(id, title) {
    ioHidden.value = id;
    ioSearch.value = title;
    ioResults.style.display = 'none';
    // Auto-fill title if empty
    var titleField = document.getElementById('title');
    if (titleField && !titleField.value) titleField.value = title;
    // Show linked badge
    if (ioLabel) ioLabel.innerHTML = '<i class="fas fa-link me-1"></i>' + title;
    if (ioLinked) ioLinked.style.display = '';
  }

  if (ioUnlink) {
    ioUnlink.addEventListener('click', function() {
      ioHidden.value = '';
      ioSearch.value = '';
      if (ioLinked) ioLinked.style.display = 'none';
    });
  }
});
</script>

<?php end_slot(); ?>

<?php
/**
 * _offerForm.php - Inline offer form embedded on listing pages.
 *
 * Variables:
 *   $listing (object) id, slug, minimum_offer, currency
 */
$currencyDisplay = esc_entities($listing->currency ?? 'ZAR');
$minOffer = !empty($listing->minimum_offer) ? number_format((float) $listing->minimum_offer, 2, '.', '') : '0.01';
?>
<form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'offerForm', 'slug' => $listing->slug]); ?>" class="mkt-offer-form">

  <div class="mb-2">
    <label for="inline-offer-amount-<?php echo (int) $listing->id; ?>" class="form-label small fw-semibold">
      <?php echo __('Your Offer'); ?>
    </label>
    <div class="input-group">
      <span class="input-group-text"><?php echo $currencyDisplay; ?></span>
      <input type="number" class="form-control" id="inline-offer-amount-<?php echo (int) $listing->id; ?>"
             name="offer_amount" step="0.01" min="<?php echo $minOffer; ?>"
             placeholder="<?php echo __('Amount'); ?>" required>
    </div>
    <?php if (!empty($listing->minimum_offer)): ?>
      <div class="form-text small">
        <?php echo __('Minimum: %1% %2%', ['%1%' => $currencyDisplay, '%2%' => number_format((float) $listing->minimum_offer, 2)]); ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="mb-2">
    <textarea class="form-control form-control-sm" name="message" rows="2"
              placeholder="<?php echo __('Message to seller (optional)'); ?>"></textarea>
  </div>

  <button type="submit" class="btn btn-primary btn-sm w-100">
    <i class="fas fa-hand-holding-usd me-1"></i> <?php echo __('Submit Offer'); ?>
  </button>

</form>

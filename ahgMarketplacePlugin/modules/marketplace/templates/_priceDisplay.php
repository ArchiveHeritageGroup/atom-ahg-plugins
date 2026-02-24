<?php
/**
 * _priceDisplay.php - Formatted price display for listings.
 *
 * Variables:
 *   $price          (float|null)  numeric price
 *   $currency       (string)      currency code, e.g. 'ZAR', 'USD'
 *   $priceOnRequest (bool)        true if price is not disclosed
 *   $listingType    (string)      fixed_price | auction | offer_only
 */
$currencyDisplay = esc_entities($currency ?? 'ZAR');
?>
<?php if (!empty($priceOnRequest)): ?>
  <span class="mkt-price-por fst-italic text-muted"><?php echo __('Price on Request'); ?></span>
<?php elseif ($listingType === 'auction'): ?>
  <?php if ($price && $price > 0): ?>
    <span class="mkt-price"><?php echo __('Current Bid'); ?>: <?php echo $currencyDisplay; ?> <?php echo number_format((float) $price, 2); ?></span>
  <?php else: ?>
    <span class="mkt-price text-muted"><?php echo __('Starting at'); ?>: <?php echo $currencyDisplay; ?> <?php echo number_format((float) $price, 2); ?></span>
  <?php endif; ?>
<?php else: ?>
  <span class="mkt-price"><?php echo $currencyDisplay; ?> <?php echo number_format((float) $price, 2); ?></span>
<?php endif; ?>

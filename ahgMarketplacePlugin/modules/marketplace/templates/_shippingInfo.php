<?php
/**
 * _shippingInfo.php - Shipping details for a listing.
 *
 * Variables:
 *   $listing (object) requires_shipping, shipping_from_country, shipping_from_city,
 *            shipping_domestic_price, free_shipping_domestic,
 *            shipping_international_price, insurance_value, shipping_notes, currency
 */
$currencyDisplay = esc_entities($listing->currency ?? 'ZAR');
?>
<div class="mkt-shipping-info">
  <h6 class="mb-2"><i class="fas fa-truck me-1"></i> <?php echo __('Shipping'); ?></h6>

  <?php if (empty($listing->requires_shipping)): ?>
    <p class="mb-0 text-muted"><i class="fas fa-map-pin me-1"></i> <?php echo __('Collection only'); ?></p>
  <?php else: ?>

    <?php if ($listing->shipping_from_country || $listing->shipping_from_city): ?>
      <p class="small mb-1">
        <span class="text-muted"><?php echo __('Ships from'); ?>:</span>
        <?php echo esc_entities(implode(', ', array_filter([$listing->shipping_from_city ?? '', $listing->shipping_from_country ?? '']))); ?>
      </p>
    <?php endif; ?>

    <?php if (!empty($listing->free_shipping_domestic)): ?>
      <p class="small mb-1"><span class="badge bg-success"><?php echo __('Free Domestic Shipping'); ?></span></p>
    <?php elseif (!empty($listing->shipping_domestic_price)): ?>
      <p class="small mb-1">
        <span class="text-muted"><?php echo __('Domestic'); ?>:</span>
        <?php echo $currencyDisplay; ?> <?php echo number_format((float) $listing->shipping_domestic_price, 2); ?>
      </p>
    <?php endif; ?>

    <?php if (!empty($listing->shipping_international_price)): ?>
      <p class="small mb-1">
        <span class="text-muted"><?php echo __('International'); ?>:</span>
        <?php echo $currencyDisplay; ?> <?php echo number_format((float) $listing->shipping_international_price, 2); ?>
      </p>
    <?php endif; ?>

    <?php if (!empty($listing->insurance_value)): ?>
      <p class="small mb-1">
        <span class="text-muted"><?php echo __('Insurance value'); ?>:</span>
        <?php echo $currencyDisplay; ?> <?php echo number_format((float) $listing->insurance_value, 2); ?>
      </p>
    <?php endif; ?>

    <?php if (!empty($listing->shipping_notes)): ?>
      <p class="small text-muted mb-0"><?php echo nl2br(esc_entities($listing->shipping_notes)); ?></p>
    <?php endif; ?>

  <?php endif; ?>
</div>

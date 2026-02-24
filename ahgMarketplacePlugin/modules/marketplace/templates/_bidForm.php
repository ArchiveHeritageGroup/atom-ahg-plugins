<?php
/**
 * _bidForm.php - Inline bid form for auction listings.
 *
 * Variables:
 *   $auction  (object) current_bid, bid_increment, buy_now_price, starting_bid
 *   $listing  (object) id, slug, currency
 */
$currencyDisplay = esc_entities($listing->currency ?? 'ZAR');
$currentBid = (float) ($auction->current_bid ?? $auction->starting_bid ?? 0);
$increment  = (float) ($auction->bid_increment ?? 1);
$minNextBid = $currentBid + $increment;
?>
<div class="mkt-bid-form">

  <!-- Current bid display -->
  <div class="text-center mb-3">
    <span class="small text-muted d-block"><?php echo __('Current Bid'); ?></span>
    <span class="h4 text-primary" id="bid-current-<?php echo (int) $listing->id; ?>">
      <?php echo $currencyDisplay; ?> <?php echo number_format($currentBid, 2); ?>
    </span>
  </div>

  <!-- Bid input -->
  <form id="bid-form-<?php echo (int) $listing->id; ?>" class="mb-2">
    <input type="hidden" name="listing_id" value="<?php echo (int) $listing->id; ?>">

    <div class="mb-2">
      <label for="bid-amount-<?php echo (int) $listing->id; ?>" class="form-label small fw-semibold">
        <?php echo __('Your Bid'); ?>
      </label>
      <div class="input-group">
        <span class="input-group-text"><?php echo $currencyDisplay; ?></span>
        <input type="number" class="form-control" id="bid-amount-<?php echo (int) $listing->id; ?>"
               name="bid_amount" step="0.01"
               min="<?php echo number_format($minNextBid, 2, '.', ''); ?>"
               value="<?php echo number_format($minNextBid, 2, '.', ''); ?>"
               required>
      </div>
      <div class="form-text small">
        <?php echo __('Minimum bid: %1% %2%', ['%1%' => $currencyDisplay, '%2%' => number_format($minNextBid, 2)]); ?>
        <?php if ($increment > 0): ?>
          (<?php echo __('increment: %1%', ['%1%' => number_format($increment, 2)]); ?>)
        <?php endif; ?>
      </div>
    </div>

    <div class="mb-3">
      <label for="bid-max-<?php echo (int) $listing->id; ?>" class="form-label small">
        <?php echo __('Max Bid (Proxy)'); ?>
        <span class="text-muted">(<?php echo __('optional'); ?>)</span>
      </label>
      <div class="input-group">
        <span class="input-group-text"><?php echo $currencyDisplay; ?></span>
        <input type="number" class="form-control" id="bid-max-<?php echo (int) $listing->id; ?>"
               name="max_bid" step="0.01"
               placeholder="<?php echo __('Auto-bid up to...'); ?>">
      </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 mb-2" id="btn-place-bid-<?php echo (int) $listing->id; ?>">
      <i class="fas fa-gavel me-1"></i> <?php echo __('Place Bid'); ?>
    </button>

    <div id="bid-result-<?php echo (int) $listing->id; ?>" class="small"></div>
  </form>

  <?php if (!empty($auction->buy_now_price) && (float) $auction->buy_now_price > 0): ?>
    <hr class="my-2">
    <div class="text-center">
      <p class="small text-muted mb-1"><?php echo __('Buy Now Price'); ?></p>
      <p class="h5 mb-2"><?php echo $currencyDisplay; ?> <?php echo number_format((float) $auction->buy_now_price, 2); ?></p>
      <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'buy', 'slug' => $listing->slug]); ?>" class="btn btn-outline-primary btn-sm w-100">
        <i class="fas fa-bolt me-1"></i> <?php echo __('Buy Now'); ?>
      </a>
    </div>
  <?php endif; ?>
</div>

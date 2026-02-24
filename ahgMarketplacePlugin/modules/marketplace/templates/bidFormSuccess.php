<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Place a Bid'); ?> - <?php echo esc_entities($listing->title); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $listing->slug]); ?>"><?php echo esc_entities($listing->title); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Place a Bid'); ?></li>
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

<div class="row">
  <div class="col-lg-8 mx-auto">

    <h1 class="h3 mb-4"><?php echo __('Place a Bid'); ?></h1>

    <!-- Listing summary -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex">
          <?php if ($primaryImage): ?>
            <img src="<?php echo esc_entities($primaryImage->file_path); ?>" alt="<?php echo esc_entities($listing->title); ?>" class="rounded me-3" style="width: 100px; height: 100px; object-fit: cover;">
          <?php elseif ($listing->featured_image_path): ?>
            <img src="<?php echo esc_entities($listing->featured_image_path); ?>" alt="<?php echo esc_entities($listing->title); ?>" class="rounded me-3" style="width: 100px; height: 100px; object-fit: cover;">
          <?php else: ?>
            <div class="bg-light rounded d-flex align-items-center justify-content-center me-3" style="width: 100px; height: 100px;">
              <i class="fas fa-image fa-2x text-muted"></i>
            </div>
          <?php endif; ?>
          <div class="flex-grow-1">
            <h5 class="mb-1"><?php echo esc_entities($listing->title); ?></h5>
            <?php if ($listing->artist_name): ?>
              <p class="text-muted mb-1"><?php echo __('by %1%', ['%1%' => esc_entities($listing->artist_name)]); ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Bid form column -->
      <div class="col-md-7">

        <!-- Current bid display -->
        <div class="card border-primary mb-3">
          <div class="card-body text-center">
            <span class="text-muted d-block"><?php echo __('Current Bid'); ?></span>
            <p class="h2 text-primary mb-1">
              <?php echo esc_entities($listing->currency); ?> <?php echo number_format((float) $currentBid, 2); ?>
            </p>
            <span class="text-muted small"><?php echo __('%1% bids', ['%1%' => (int) ($auction->bid_count ?? 0)]); ?></span>
          </div>
        </div>

        <!-- Countdown timer -->
        <div class="alert alert-warning text-center mb-3">
          <i class="fas fa-clock me-1"></i>
          <span><?php echo __('Time Remaining'); ?>:</span>
          <strong id="bid-countdown" data-end="<?php echo esc_entities($auction->end_time); ?>">--</strong>
        </div>

        <!-- Bid form -->
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0"><i class="fas fa-gavel me-2"></i><?php echo __('Your Bid'); ?></h5>
          </div>
          <div class="card-body">
            <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'bidForm', 'slug' => $listing->slug]); ?>">

              <div class="mb-3">
                <label for="bid_amount" class="form-label"><?php echo __('Bid Amount'); ?> <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text"><?php echo esc_entities($listing->currency); ?></span>
                  <input type="number" class="form-control form-control-lg" id="bid_amount" name="bid_amount"
                         step="0.01"
                         min="<?php echo number_format($minBid, 2, '.', ''); ?>"
                         value="<?php echo number_format($minBid, 2, '.', ''); ?>"
                         required>
                </div>
                <div class="form-text">
                  <?php echo __('Minimum bid: %1% %2%', ['%1%' => esc_entities($listing->currency), '%2%' => number_format($minBid, 2)]); ?>
                  <?php if ($auction->bid_increment): ?>
                    (<?php echo __('increment: %1%', ['%1%' => number_format((float) $auction->bid_increment, 2)]); ?>)
                  <?php endif; ?>
                </div>
              </div>

              <div class="mb-4">
                <label for="max_bid" class="form-label">
                  <?php echo __('Maximum Bid (Proxy Bidding)'); ?>
                  <span class="text-muted">(<?php echo __('optional'); ?>)</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text"><?php echo esc_entities($listing->currency); ?></span>
                  <input type="number" class="form-control" id="max_bid" name="max_bid"
                         step="0.01" placeholder="<?php echo __('Auto-bid up to this amount'); ?>">
                </div>
                <div class="form-text">
                  <i class="fas fa-info-circle me-1"></i>
                  <?php echo __('The system will automatically bid on your behalf up to this amount to keep you in the lead.'); ?>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center">
                <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $listing->slug]); ?>" class="btn btn-outline-secondary">
                  <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back'); ?>
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                  <i class="fas fa-gavel me-1"></i> <?php echo __('Place Bid'); ?>
                </button>
              </div>

            </form>
          </div>
        </div>

      </div>

      <!-- Bid history column -->
      <div class="col-md-5">
        <div class="card">
          <div class="card-header">
            <h6 class="card-title mb-0"><i class="fas fa-history me-2"></i><?php echo __('Recent Bids'); ?></h6>
          </div>
          <div class="card-body p-0">
            <?php if (!empty($bidHistory)): ?>
              <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                  <thead class="table-light">
                    <tr>
                      <th class="small"><?php echo __('Bidder'); ?></th>
                      <th class="small text-end"><?php echo __('Amount'); ?></th>
                      <th class="small"><?php echo __('Time'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($bidHistory as $bid): ?>
                      <tr<?php echo $bid->is_winning ? ' class="table-success"' : ''; ?>>
                        <td class="small">
                          <?php echo __('Bidder #%1%', ['%1%' => substr(md5($bid->user_id), 0, 6)]); ?>
                          <?php if ($bid->is_winning): ?>
                            <span class="badge bg-success ms-1"><?php echo __('Leading'); ?></span>
                          <?php endif; ?>
                        </td>
                        <td class="small text-end fw-semibold">
                          <?php echo esc_entities($listing->currency); ?> <?php echo number_format((float) $bid->bid_amount, 2); ?>
                        </td>
                        <td class="small text-muted">
                          <?php
                            $bidTime = strtotime($bid->created_at);
                            $diff = time() - $bidTime;
                            if ($diff < 60) {
                                echo __('%1%s ago', ['%1%' => $diff]);
                            } elseif ($diff < 3600) {
                                echo __('%1%m ago', ['%1%' => floor($diff / 60)]);
                            } elseif ($diff < 86400) {
                                echo __('%1%h ago', ['%1%' => floor($diff / 3600)]);
                            } else {
                                echo date('d M H:i', $bidTime);
                            }
                          ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="p-3 text-center text-muted">
                <i class="fas fa-gavel fa-2x mb-2 d-block"></i>
                <?php echo __('No bids yet. Be the first!'); ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($auction->buy_now_price): ?>
          <div class="card mt-3">
            <div class="card-body text-center">
              <p class="small text-muted mb-1"><?php echo __('Buy Now Price'); ?></p>
              <p class="h5 mb-2"><?php echo esc_entities($listing->currency); ?> <?php echo number_format((float) $auction->buy_now_price, 2); ?></p>
              <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'buy', 'slug' => $listing->slug]); ?>" class="btn btn-outline-primary btn-sm w-100">
                <i class="fas fa-bolt me-1"></i> <?php echo __('Buy Now'); ?>
              </a>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var countdownEl = document.getElementById('bid-countdown');
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

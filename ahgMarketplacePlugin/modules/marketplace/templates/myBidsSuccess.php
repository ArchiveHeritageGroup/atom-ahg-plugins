<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('My Bids'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('My Bids'); ?></li>
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
  <h1 class="h3 mb-0"><?php echo __('My Bids'); ?></h1>
  <div>
    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myPurchases']); ?>" class="btn btn-outline-secondary btn-sm me-1">
      <i class="fas fa-shopping-bag me-1"></i><?php echo __('My Purchases'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myOffers']); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-hand-holding-usd me-1"></i><?php echo __('My Offers'); ?>
    </a>
  </div>
</div>

<?php if (empty($bids)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-gavel fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No bids yet'); ?></h5>
      <p class="text-muted"><?php echo __('Browse auctions to find items to bid on.'); ?></p>
      <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'auctionBrowse']); ?>" class="btn btn-primary">
        <i class="fas fa-gavel me-1"></i> <?php echo __('Browse Auctions'); ?>
      </a>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Item'); ?></th>
            <th class="text-end"><?php echo __('My Bid'); ?></th>
            <th class="text-end"><?php echo __('Current Bid'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th><?php echo __('Time Remaining'); ?></th>
            <th class="text-end"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bids as $bid): ?>
            <?php
              $isWinning = $bid->is_winning && $bid->auction_status === 'active';
              $isOutbid = !$bid->is_winning && $bid->auction_status === 'active';
              $isEnded = $bid->auction_status === 'ended';
              $timeRemaining = max(0, strtotime($bid->end_time) - time());
            ?>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <?php if ($bid->featured_image_path): ?>
                    <img src="<?php echo esc_entities($bid->featured_image_path); ?>" alt="" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                  <?php else: ?>
                    <div class="bg-light rounded d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                      <i class="fas fa-image text-muted small"></i>
                    </div>
                  <?php endif; ?>
                  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $bid->slug]); ?>" class="text-decoration-none fw-semibold">
                    <?php echo esc_entities($bid->title); ?>
                  </a>
                </div>
              </td>
              <td class="text-end fw-semibold">
                <?php echo number_format((float) $bid->bid_amount, 2); ?>
              </td>
              <td class="text-end">
                <?php echo number_format((float) $bid->current_bid, 2); ?>
              </td>
              <td>
                <?php if ($isWinning): ?>
                  <span class="badge bg-success"><i class="fas fa-trophy me-1"></i><?php echo __('Winning'); ?></span>
                <?php elseif ($isOutbid): ?>
                  <span class="badge bg-warning text-dark"><i class="fas fa-arrow-down me-1"></i><?php echo __('Outbid'); ?></span>
                <?php elseif ($isEnded && $bid->is_winning): ?>
                  <span class="badge bg-success"><i class="fas fa-check me-1"></i><?php echo __('Won'); ?></span>
                <?php elseif ($isEnded): ?>
                  <span class="badge bg-secondary"><?php echo __('Ended'); ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?php echo esc_entities(ucfirst($bid->auction_status)); ?></span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($timeRemaining > 0): ?>
                  <span class="bid-countdown small" data-end="<?php echo esc_entities($bid->end_time); ?>">
                    <?php
                      $d = floor($timeRemaining / 86400);
                      $h = floor(($timeRemaining % 86400) / 3600);
                      $m = floor(($timeRemaining % 3600) / 60);
                      $parts = [];
                      if ($d > 0) $parts[] = $d . 'd';
                      $parts[] = $h . 'h';
                      $parts[] = $m . 'm';
                      echo implode(' ', $parts);
                    ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted small"><?php echo __('Ended'); ?></span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <?php if ($isOutbid && $timeRemaining > 0): ?>
                  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'bidForm', 'slug' => $bid->slug]); ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-gavel me-1"></i><?php echo __('Bid Again'); ?>
                  </a>
                <?php elseif ($isWinning): ?>
                  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $bid->slug]); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-eye me-1"></i><?php echo __('View'); ?>
                  </a>
                <?php else: ?>
                  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $bid->slug]); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-eye me-1"></i><?php echo __('View'); ?>
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination -->
  <?php
    $totalPages = ceil($total / $limit);
    if ($totalPages > 1):
  ?>
    <nav class="mt-4" aria-label="<?php echo __('Pagination'); ?>">
      <ul class="pagination justify-content-center">
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myBids', 'page' => $page - 1]); ?>">&laquo;</a>
        </li>
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myBids', 'page' => $p]); ?>"><?php echo $p; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myBids', 'page' => $page + 1]); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.bid-countdown').forEach(function(el) {
    var endTime = new Date(el.getAttribute('data-end')).getTime();
    function update() {
      var diff = endTime - new Date().getTime();
      if (diff <= 0) {
        el.textContent = '<?php echo __('Ended'); ?>';
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
      el.textContent = parts.join(' ');
    }
    update();
    setInterval(update, 1000);
  });
});
</script>

<?php end_slot(); ?>

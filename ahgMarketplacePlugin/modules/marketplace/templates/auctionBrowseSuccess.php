<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Live Auctions'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('marketplace/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Marketplace'), 'url' => url_for(['module' => 'marketplace', 'action' => 'browse'])],
  ['label' => __('Live Auctions')],
]]); ?>

<h1 class="h3 mb-4">
  <i class="fas fa-gavel me-2 text-primary"></i><?php echo __('Live Auctions'); ?>
</h1>

<!-- Ending soon section -->
<?php if (!empty($endingSoon)): ?>
  <div class="mb-5">
    <h4 class="mb-3">
      <i class="fas fa-fire text-danger me-1"></i> <?php echo __('Ending Soon'); ?>
    </h4>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">
      <?php foreach ($endingSoon as $auc): ?>
        <div class="col">
          <div class="card h-100 border-danger">
            <?php if (isset($auc->featured_image_path) && $auc->featured_image_path): ?>
              <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $auc->slug]); ?>">
                <img src="<?php echo esc_entities($auc->featured_image_path); ?>" class="card-img-top" alt="<?php echo esc_entities($auc->title); ?>" style="height: 180px; object-fit: cover;">
              </a>
            <?php else: ?>
              <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $auc->slug]); ?>">
                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 180px;">
                  <i class="fas fa-gavel fa-2x text-muted"></i>
                </div>
              </a>
            <?php endif; ?>
            <div class="card-body">
              <h6 class="card-title mb-1">
                <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $auc->slug]); ?>" class="text-decoration-none">
                  <?php echo esc_entities(mb_strimwidth($auc->title, 0, 60, '...')); ?>
                </a>
              </h6>
              <p class="h6 text-primary mb-1">
                <?php echo esc_entities($auc->currency ?? 'ZAR'); ?> <?php echo number_format((float) ($auc->current_bid ?? $auc->starting_bid), 2); ?>
              </p>
              <p class="small text-muted mb-1"><?php echo __('%1% bids', ['%1%' => (int) ($auc->bid_count ?? 0)]); ?></p>
              <div class="alert alert-danger py-1 px-2 mb-0 small">
                <i class="fas fa-clock me-1"></i>
                <span class="auction-timer" data-end="<?php echo esc_entities($auc->end_time); ?>">--</span>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<!-- All active auctions -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><?php echo __('All Active Auctions'); ?> <span class="badge bg-secondary"><?php echo number_format($total); ?></span></h4>
</div>

<?php if (!empty($auctions)): ?>
  <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">
    <?php foreach ($auctions as $auc): ?>
      <div class="col">
        <div class="card h-100">
          <?php if (isset($auc->featured_image_path) && $auc->featured_image_path): ?>
            <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $auc->slug]); ?>">
              <img src="<?php echo esc_entities($auc->featured_image_path); ?>" class="card-img-top" alt="<?php echo esc_entities($auc->title); ?>" style="height: 200px; object-fit: cover;">
            </a>
          <?php else: ?>
            <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $auc->slug]); ?>">
              <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                <i class="fas fa-gavel fa-2x text-muted"></i>
              </div>
            </a>
          <?php endif; ?>
          <div class="card-body">
            <h6 class="card-title mb-1">
              <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $auc->slug]); ?>" class="text-decoration-none">
                <?php echo esc_entities(mb_strimwidth($auc->title, 0, 60, '...')); ?>
              </a>
            </h6>
            <?php if (isset($auc->artist_name) && $auc->artist_name): ?>
              <p class="small text-muted mb-1"><?php echo esc_entities($auc->artist_name); ?></p>
            <?php endif; ?>
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="small text-muted"><?php echo __('Current Bid'); ?></span>
              <span class="fw-semibold text-primary"><?php echo esc_entities($auc->currency ?? 'ZAR'); ?> <?php echo number_format((float) ($auc->current_bid ?? $auc->starting_bid), 2); ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="small text-muted"><?php echo __('%1% bids', ['%1%' => (int) ($auc->bid_count ?? 0)]); ?></span>
              <span class="small">
                <i class="fas fa-clock text-warning me-1"></i>
                <span class="auction-timer" data-end="<?php echo esc_entities($auc->end_time); ?>">--</span>
              </span>
            </div>
          </div>
          <div class="card-footer bg-transparent">
            <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $auc->slug]); ?>" class="btn btn-outline-primary btn-sm w-100">
              <?php echo __('View & Bid'); ?>
            </a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($total > 24): ?>
    <?php $totalPages = (int) ceil($total / 24); ?>
    <nav class="mt-4">
      <ul class="pagination justify-content-center">
        <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'auctionBrowse', 'page' => $page - 1]); ?>">&laquo;</a>
        </li>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'auctionBrowse', 'page' => $i]); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'auctionBrowse', 'page' => $page + 1]); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
<?php else: ?>
  <div class="text-center py-5">
    <i class="fas fa-gavel fa-3x text-muted mb-3"></i>
    <h5><?php echo __('No active auctions'); ?></h5>
    <p class="text-muted"><?php echo __('Check back soon for new auctions.'); ?></p>
    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>" class="btn btn-primary"><?php echo __('Browse Marketplace'); ?></a>
  </div>
<?php endif; ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  function updateTimers() {
    document.querySelectorAll('.auction-timer').forEach(function(el) {
      var endTime = new Date(el.getAttribute('data-end')).getTime();
      var now = new Date().getTime();
      var diff = endTime - now;
      if (diff <= 0) {
        el.textContent = '<?php echo __('Ended'); ?>';
        el.classList.add('text-danger');
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
    });
  }
  updateTimers();
  setInterval(updateTimers, 1000);
});
</script>

<?php end_slot(); ?>

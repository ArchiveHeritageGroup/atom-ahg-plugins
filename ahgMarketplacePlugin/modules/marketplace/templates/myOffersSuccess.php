<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('My Offers'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('My Offers'); ?></li>
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
  <h1 class="h3 mb-0"><?php echo __('My Offers'); ?></h1>
  <div>
    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myPurchases']); ?>" class="btn btn-outline-secondary btn-sm me-1">
      <i class="fas fa-shopping-bag me-1"></i><?php echo __('My Purchases'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myBids']); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-gavel me-1"></i><?php echo __('My Bids'); ?>
    </a>
  </div>
</div>

<?php if (empty($offers)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-hand-holding-usd fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No offers yet'); ?></h5>
      <p class="text-muted"><?php echo __('Browse listings and make offers on items you are interested in.'); ?></p>
      <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>" class="btn btn-primary">
        <i class="fas fa-search me-1"></i> <?php echo __('Browse Marketplace'); ?>
      </a>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Date'); ?></th>
            <th><?php echo __('Item'); ?></th>
            <th class="text-end"><?php echo __('Offer Amount'); ?></th>
            <th class="text-end"><?php echo __('Counter Amount'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th class="text-end"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($offers as $offer): ?>
            <tr>
              <td class="small text-muted"><?php echo date('d M Y', strtotime($offer->created_at)); ?></td>
              <td>
                <div class="d-flex align-items-center">
                  <?php if (isset($offer->featured_image_path) && $offer->featured_image_path): ?>
                    <img src="<?php echo esc_entities($offer->featured_image_path); ?>" alt="" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                  <?php else: ?>
                    <div class="bg-light rounded d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                      <i class="fas fa-image text-muted small"></i>
                    </div>
                  <?php endif; ?>
                  <?php if (isset($offer->slug) && $offer->slug): ?>
                    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $offer->slug]); ?>" class="text-decoration-none fw-semibold">
                      <?php echo esc_entities($offer->title ?? __('Listing')); ?>
                    </a>
                  <?php else: ?>
                    <span class="fw-semibold"><?php echo esc_entities($offer->title ?? __('Listing')); ?></span>
                  <?php endif; ?>
                </div>
              </td>
              <td class="text-end fw-semibold">
                <?php echo esc_entities($offer->currency); ?> <?php echo number_format((float) $offer->offer_amount, 2); ?>
              </td>
              <td class="text-end">
                <?php if ($offer->counter_amount): ?>
                  <span class="fw-semibold text-primary">
                    <?php echo esc_entities($offer->currency); ?> <?php echo number_format((float) $offer->counter_amount, 2); ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td>
                <?php
                  $statusClass = match($offer->status) {
                      'pending' => 'warning',
                      'accepted' => 'success',
                      'rejected' => 'danger',
                      'countered' => 'info',
                      'withdrawn' => 'secondary',
                      'expired' => 'secondary',
                      default => 'secondary',
                  };
                ?>
                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo esc_entities(ucfirst($offer->status)); ?></span>
                <?php if ($offer->expires_at && in_array($offer->status, ['pending', 'countered'])): ?>
                  <br><span class="small text-muted"><?php echo __('Expires'); ?>: <?php echo date('d M Y', strtotime($offer->expires_at)); ?></span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <?php if ($offer->status === 'countered'): ?>
                  <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'myOffers']); ?>" class="d-inline">
                    <input type="hidden" name="form_action" value="accept_counter">
                    <input type="hidden" name="offer_id" value="<?php echo (int) $offer->id; ?>">
                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('<?php echo __('Accept counter-offer of %1% %2%?', ['%1%' => esc_entities($offer->currency), '%2%' => number_format((float) $offer->counter_amount, 2)]); ?>');">
                      <i class="fas fa-check me-1"></i><?php echo __('Accept Counter'); ?>
                    </button>
                  </form>
                <?php endif; ?>

                <?php if (in_array($offer->status, ['pending', 'countered'])): ?>
                  <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'myOffers']); ?>" class="d-inline">
                    <input type="hidden" name="form_action" value="withdraw">
                    <input type="hidden" name="offer_id" value="<?php echo (int) $offer->id; ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('<?php echo __('Withdraw this offer?'); ?>');">
                      <i class="fas fa-times me-1"></i><?php echo __('Withdraw'); ?>
                    </button>
                  </form>
                <?php endif; ?>

                <?php if ($offer->seller_response): ?>
                  <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" title="<?php echo esc_entities($offer->seller_response); ?>">
                    <i class="fas fa-comment"></i>
                  </button>
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
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myOffers', 'page' => $page - 1]); ?>">&laquo;</a>
        </li>
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myOffers', 'page' => $p]); ?>"><?php echo $p; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myOffers', 'page' => $page + 1]); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function(el) { return new bootstrap.Tooltip(el); });
});
</script>

<?php end_slot(); ?>

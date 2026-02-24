<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Incoming Offers'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'dashboard']); ?>"><?php echo __('Dashboard'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Incoming Offers'); ?></li>
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

<h1 class="h3 mb-4"><?php echo __('Incoming Offers'); ?></h1>

<!-- Status filter -->
<?php
  $statusTabs = ['' => __('All'), 'pending' => __('Pending'), 'countered' => __('Countered')];
  $currentStatus = $sf_request->getParameter('status', '');
?>
<ul class="nav nav-tabs mb-4">
  <?php foreach ($statusTabs as $val => $label): ?>
    <li class="nav-item">
      <a class="nav-link<?php echo $currentStatus === $val ? ' active' : ''; ?>" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerOffers', 'status' => $val]); ?>">
        <?php echo $label; ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<?php if (empty($offers)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-hand-holding-usd fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No offers found'); ?></h5>
      <p class="text-muted"><?php echo __('Offers from buyers will appear here.'); ?></p>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Date'); ?></th>
            <th><?php echo __('Listing'); ?></th>
            <th><?php echo __('Buyer'); ?></th>
            <th class="text-end"><?php echo __('Offer Amount'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th class="text-end"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($offers as $offer): ?>
            <tr>
              <td class="small text-muted"><?php echo date('d M Y', strtotime($offer->created_at)); ?></td>
              <td>
                <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $offer->listing_slug ?? '']); ?>" class="text-decoration-none">
                  <?php echo esc_entities($offer->listing_title ?? '-'); ?>
                </a>
              </td>
              <td class="small">
                <?php echo esc_entities($offer->buyer_name ?? $offer->buyer_email ?? '-'); ?>
              </td>
              <td class="text-end fw-semibold">
                <?php echo esc_entities($offer->currency); ?> <?php echo number_format((float) $offer->offer_amount, 2); ?>
              </td>
              <td>
                <?php
                  $statusClass = match($offer->status) {
                      'pending' => 'warning',
                      'accepted' => 'success',
                      'rejected' => 'danger',
                      'countered' => 'info',
                      'withdrawn' => 'secondary',
                      'expired' => 'dark',
                      default => 'secondary',
                  };
                ?>
                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo esc_entities(ucfirst($offer->status)); ?></span>
              </td>
              <td class="text-end">
                <?php if ($offer->status === 'pending' || $offer->status === 'countered'): ?>
                  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerOfferRespond', 'id' => $offer->id]); ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-reply me-1"></i><?php echo __('Respond'); ?>
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
    $totalPages = (int) ceil($total / $limit);
    if ($totalPages > 1):
  ?>
    <nav class="mt-4" aria-label="<?php echo __('Pagination'); ?>">
      <ul class="pagination justify-content-center">
        <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerOffers', 'status' => $currentStatus, 'page' => $page - 1]); ?>">&laquo;</a>
        </li>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerOffers', 'status' => $currentStatus, 'page' => $i]); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerOffers', 'status' => $currentStatus, 'page' => $page + 1]); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<?php end_slot(); ?>

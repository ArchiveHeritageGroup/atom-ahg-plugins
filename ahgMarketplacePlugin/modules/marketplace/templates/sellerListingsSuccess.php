<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('My Listings'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'dashboard']); ?>"><?php echo __('Dashboard'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('My Listings'); ?></li>
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
  <h1 class="h3 mb-0"><?php echo __('My Listings'); ?></h1>
  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListingCreate']); ?>" class="btn btn-primary">
    <i class="fas fa-plus me-1"></i> <?php echo __('Create New Listing'); ?>
  </a>
</div>

<!-- Status filter tabs -->
<?php
  $statusTabs = [
    '' => __('All'),
    'draft' => __('Draft'),
    'active' => __('Active'),
    'pending_review' => __('Pending Review'),
    'sold' => __('Sold'),
    'expired' => __('Expired'),
  ];
  $currentStatus = $sf_request->getParameter('status', '');
?>
<ul class="nav nav-tabs mb-4">
  <?php foreach ($statusTabs as $val => $label): ?>
    <li class="nav-item">
      <a class="nav-link<?php echo $currentStatus === $val ? ' active' : ''; ?>" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListings', 'status' => $val]); ?>">
        <?php echo $label; ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<?php if (empty($listings)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-tags fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No listings found'); ?></h5>
      <p class="text-muted"><?php echo __('Create your first listing to start selling.'); ?></p>
      <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListingCreate']); ?>" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> <?php echo __('Create New Listing'); ?>
      </a>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 50px;"></th>
            <th><?php echo __('Title / Listing #'); ?></th>
            <th><?php echo __('Sector'); ?></th>
            <th><?php echo __('Type'); ?></th>
            <th class="text-end"><?php echo __('Price'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th class="text-end"><?php echo __('Views'); ?></th>
            <th><?php echo __('Created'); ?></th>
            <th class="text-end"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($listings as $listing): ?>
            <tr>
              <td>
                <?php if ($listing->featured_image_path): ?>
                  <img src="<?php echo esc_entities($listing->featured_image_path); ?>" alt="" class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                <?php else: ?>
                  <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="fas fa-image text-muted small"></i>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $listing->slug]); ?>" class="text-decoration-none fw-semibold">
                  <?php echo esc_entities($listing->title); ?>
                </a>
                <br><small class="text-muted"><?php echo esc_entities($listing->listing_number); ?></small>
              </td>
              <td><span class="badge bg-info"><?php echo esc_entities(ucfirst($listing->sector)); ?></span></td>
              <td><span class="badge bg-secondary"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $listing->listing_type))); ?></span></td>
              <td class="text-end">
                <?php if ($listing->price_on_request): ?>
                  <span class="text-muted"><?php echo __('POR'); ?></span>
                <?php elseif ($listing->price): ?>
                  <?php echo esc_entities($listing->currency); ?> <?php echo number_format((float) $listing->price, 2); ?>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
              <td>
                <?php
                  $statusClass = match($listing->status) {
                      'draft' => 'secondary',
                      'pending_review' => 'warning',
                      'active' => 'success',
                      'reserved' => 'info',
                      'sold' => 'primary',
                      'expired' => 'dark',
                      'withdrawn' => 'secondary',
                      'suspended' => 'danger',
                      default => 'secondary',
                  };
                ?>
                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $listing->status))); ?></span>
              </td>
              <td class="text-end small text-muted"><?php echo number_format((int) $listing->view_count); ?></td>
              <td class="small text-muted"><?php echo date('d M Y', strtotime($listing->created_at)); ?></td>
              <td class="text-end text-nowrap">
                <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListingEdit', 'id' => $listing->id]); ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('Edit'); ?>">
                  <i class="fas fa-edit"></i>
                </a>
                <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListingImages', 'id' => $listing->id]); ?>" class="btn btn-sm btn-outline-secondary" title="<?php echo __('Images'); ?>">
                  <i class="fas fa-images"></i>
                </a>
                <?php if ($listing->status === 'draft'): ?>
                  <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListingPublish', 'id' => $listing->id]); ?>" class="d-inline">
                    <button type="submit" class="btn btn-sm btn-outline-success" title="<?php echo __('Publish'); ?>" onclick="return confirm('<?php echo __('Publish this listing?'); ?>');">
                      <i class="fas fa-check"></i>
                    </button>
                  </form>
                <?php endif; ?>
                <?php if ($listing->status === 'active'): ?>
                  <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListingWithdraw', 'id' => $listing->id]); ?>" class="d-inline">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Withdraw'); ?>" onclick="return confirm('<?php echo __('Withdraw this listing from the marketplace?'); ?>');">
                      <i class="fas fa-times"></i>
                    </button>
                  </form>
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
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListings', 'status' => $currentStatus, 'page' => $page - 1]); ?>">&laquo;</a>
        </li>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListings', 'status' => $currentStatus, 'page' => $i]); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListings', 'status' => $currentStatus, 'page' => $page + 1]); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<?php end_slot(); ?>

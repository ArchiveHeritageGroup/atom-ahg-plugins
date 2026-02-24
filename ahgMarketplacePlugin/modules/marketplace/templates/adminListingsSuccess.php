<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Manage Listings'); ?> - <?php echo __('Marketplace Admin'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminDashboard']); ?>"><?php echo __('Marketplace Admin'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Listings'); ?></li>
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

<h1 class="h3 mb-4"><?php echo __('Manage Listings'); ?></h1>

<!-- Filter row -->
<div class="card mb-4">
  <div class="card-body">
    <form method="get" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminListings']); ?>" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small"><?php echo __('Status'); ?></label>
        <select name="status" class="form-select form-select-sm">
          <option value=""><?php echo __('All Statuses'); ?></option>
          <?php foreach (['draft', 'pending_review', 'active', 'sold', 'suspended', 'expired', 'withdrawn'] as $s): ?>
            <option value="<?php echo $s; ?>"<?php echo ($filters['status'] ?? '') === $s ? ' selected' : ''; ?>><?php echo esc_entities(ucfirst(str_replace('_', ' ', $s))); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small"><?php echo __('Sector'); ?></label>
        <select name="sector" class="form-select form-select-sm">
          <option value=""><?php echo __('All Sectors'); ?></option>
          <?php foreach (['gallery', 'museum', 'archive', 'library', 'dam'] as $sec): ?>
            <option value="<?php echo $sec; ?>"<?php echo ($filters['sector'] ?? '') === $sec ? ' selected' : ''; ?>><?php echo esc_entities(ucfirst($sec)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small"><?php echo __('Search'); ?></label>
        <input type="text" name="search" class="form-control form-control-sm" value="<?php echo esc_entities($filters['search'] ?? ''); ?>" placeholder="<?php echo __('Title, listing # or seller...'); ?>">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="fas fa-filter me-1"></i> <?php echo __('Filter'); ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Listings table -->
<?php if (empty($listings)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-tags fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No listings found'); ?></h5>
      <p class="text-muted"><?php echo __('Try adjusting your filters.'); ?></p>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 40px;"><?php echo __('ID'); ?></th>
            <th style="width: 50px;"></th>
            <th><?php echo __('Title'); ?></th>
            <th><?php echo __('Seller'); ?></th>
            <th><?php echo __('Sector'); ?></th>
            <th><?php echo __('Type'); ?></th>
            <th class="text-end"><?php echo __('Price'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th><?php echo __('Listed'); ?></th>
            <th class="text-end"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($listings as $listing): ?>
            <tr>
              <td class="small text-muted"><?php echo (int) $listing->id; ?></td>
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
              <td class="small"><?php echo esc_entities($listing->seller_name ?? '-'); ?></td>
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
              <td class="small text-muted"><?php echo date('d M Y', strtotime($listing->created_at)); ?></td>
              <td class="text-end text-nowrap">
                <?php if ($listing->status === 'pending_review'): ?>
                  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminListingReview', 'id' => $listing->id]); ?>" class="btn btn-sm btn-outline-warning" title="<?php echo __('Review'); ?>">
                    <i class="fas fa-clipboard-check"></i>
                  </a>
                <?php endif; ?>
                <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $listing->slug]); ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('View'); ?>">
                  <i class="fas fa-eye"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination -->
  <?php
    $limit = 30;
    $totalPages = (int) ceil($total / $limit);
    if ($totalPages > 1):
  ?>
    <nav class="mt-4" aria-label="<?php echo __('Pagination'); ?>">
      <ul class="pagination justify-content-center">
        <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminListings', 'status' => $filters['status'] ?? '', 'sector' => $filters['sector'] ?? '', 'search' => $filters['search'] ?? '', 'page' => $page - 1]); ?>">&laquo;</a>
        </li>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminListings', 'status' => $filters['status'] ?? '', 'sector' => $filters['sector'] ?? '', 'search' => $filters['search'] ?? '', 'page' => $i]); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminListings', 'status' => $filters['status'] ?? '', 'sector' => $filters['sector'] ?? '', 'search' => $filters['search'] ?? '', 'page' => $page + 1]); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<?php end_slot(); ?>

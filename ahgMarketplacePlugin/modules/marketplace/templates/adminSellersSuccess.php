<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Manage Sellers'); ?> - <?php echo __('Marketplace Admin'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminDashboard']); ?>"><?php echo __('Marketplace Admin'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Sellers'); ?></li>
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

<h1 class="h3 mb-4"><?php echo __('Manage Sellers'); ?></h1>

<!-- Filter row -->
<div class="card mb-4">
  <div class="card-body">
    <form method="get" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminSellers']); ?>" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small"><?php echo __('Verification Status'); ?></label>
        <select name="verification_status" class="form-select form-select-sm">
          <option value=""><?php echo __('All Statuses'); ?></option>
          <?php foreach (['unverified', 'pending', 'verified', 'suspended'] as $vs): ?>
            <option value="<?php echo $vs; ?>"<?php echo ($filters['verification_status'] ?? '') === $vs ? ' selected' : ''; ?>><?php echo esc_entities(ucfirst($vs)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-5">
        <label class="form-label small"><?php echo __('Search'); ?></label>
        <input type="text" name="search" class="form-control form-control-sm" value="<?php echo esc_entities($filters['search'] ?? ''); ?>" placeholder="<?php echo __('Name, email or slug...'); ?>">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="fas fa-filter me-1"></i> <?php echo __('Filter'); ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Sellers table -->
<?php if (empty($sellers)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-store fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No sellers found'); ?></h5>
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
            <th><?php echo __('Name'); ?></th>
            <th><?php echo __('Type'); ?></th>
            <th><?php echo __('Email'); ?></th>
            <th><?php echo __('Verification'); ?></th>
            <th class="text-end"><?php echo __('Sales'); ?></th>
            <th class="text-end"><?php echo __('Revenue'); ?></th>
            <th><?php echo __('Joined'); ?></th>
            <th class="text-end"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sellers as $seller): ?>
            <tr>
              <td class="small text-muted"><?php echo (int) $seller->id; ?></td>
              <td>
                <?php if ($seller->avatar_path): ?>
                  <img src="<?php echo esc_entities($seller->avatar_path); ?>" alt="" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                <?php else: ?>
                  <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="fas fa-user text-muted small"></i>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'seller', 'slug' => $seller->slug]); ?>" class="text-decoration-none fw-semibold">
                  <?php echo esc_entities($seller->display_name); ?>
                </a>
              </td>
              <td><span class="badge bg-secondary"><?php echo esc_entities(ucfirst($seller->seller_type ?? '-')); ?></span></td>
              <td class="small"><?php echo esc_entities($seller->email ?? '-'); ?></td>
              <td>
                <?php
                  $verifyClass = match($seller->verification_status ?? '') {
                      'verified' => 'success',
                      'pending' => 'warning',
                      'suspended' => 'secondary',
                      default => 'danger',
                  };
                ?>
                <span class="badge bg-<?php echo $verifyClass; ?>"><?php echo esc_entities(ucfirst($seller->verification_status ?? 'unverified')); ?></span>
              </td>
              <td class="text-end small"><?php echo number_format((int) ($seller->total_sales ?? 0)); ?></td>
              <td class="text-end small"><?php echo esc_entities($seller->payout_currency ?? 'ZAR'); ?> <?php echo number_format((float) ($seller->total_revenue ?? 0), 2); ?></td>
              <td class="small text-muted"><?php echo date('d M Y', strtotime($seller->created_at)); ?></td>
              <td class="text-end text-nowrap">
                <?php if (($seller->verification_status ?? '') !== 'verified'): ?>
                  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminSellerVerify', 'id' => $seller->id]); ?>" class="btn btn-sm btn-outline-success" title="<?php echo __('Verify'); ?>">
                    <i class="fas fa-check-circle"></i>
                  </a>
                <?php endif; ?>
                <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminSellerVerify', 'id' => $seller->id]); ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('View'); ?>">
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
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminSellers', 'verification_status' => $filters['verification_status'] ?? '', 'search' => $filters['search'] ?? '', 'page' => $page - 1]); ?>">&laquo;</a>
        </li>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminSellers', 'verification_status' => $filters['verification_status'] ?? '', 'search' => $filters['search'] ?? '', 'page' => $i]); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminSellers', 'verification_status' => $filters['verification_status'] ?? '', 'search' => $filters['search'] ?? '', 'page' => $page + 1]); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<?php end_slot(); ?>

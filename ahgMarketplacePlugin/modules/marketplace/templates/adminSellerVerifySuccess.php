<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Verify Seller'); ?> - <?php echo __('Marketplace Admin'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminDashboard']); ?>"><?php echo __('Marketplace Admin'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminSellers']); ?>"><?php echo __('Sellers'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Verify Seller'); ?></li>
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
  <h1 class="h3 mb-0"><?php echo __('Verify Seller'); ?></h1>
  <?php
    $verifyClass = match($seller->verification_status ?? '') {
        'verified' => 'success',
        'pending' => 'warning',
        'suspended' => 'secondary',
        default => 'danger',
    };
  ?>
  <span class="badge bg-<?php echo $verifyClass; ?> fs-6"><?php echo esc_entities(ucfirst($seller->verification_status ?? 'unverified')); ?></span>
</div>

<!-- Seller profile -->
<div class="row">

  <div class="col-lg-8">

    <!-- Banner -->
    <?php if ($seller->banner_path): ?>
      <div class="card mb-4">
        <img src="<?php echo esc_entities($seller->banner_path); ?>" alt="" class="card-img-top" style="max-height: 200px; object-fit: cover;">
      </div>
    <?php endif; ?>

    <!-- Profile details -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><?php echo __('Seller Profile'); ?></h5>
      </div>
      <div class="card-body">
        <div class="d-flex align-items-center mb-4">
          <?php if ($seller->avatar_path): ?>
            <img src="<?php echo esc_entities($seller->avatar_path); ?>" alt="" class="rounded-circle me-3" width="80" height="80" style="object-fit: cover;">
          <?php else: ?>
            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 80px; height: 80px;">
              <i class="fas fa-user fa-2x text-muted"></i>
            </div>
          <?php endif; ?>
          <div>
            <h4 class="mb-1"><?php echo esc_entities($seller->display_name); ?></h4>
            <p class="text-muted mb-0"><?php echo esc_entities($seller->tagline ?? ''); ?></p>
          </div>
        </div>

        <table class="table table-sm mb-0">
          <tbody>
            <tr>
              <th style="width: 200px;"><?php echo __('Seller Type'); ?></th>
              <td><span class="badge bg-secondary"><?php echo esc_entities(ucfirst($seller->seller_type ?? '-')); ?></span></td>
            </tr>
            <tr>
              <th><?php echo __('Email'); ?></th>
              <td><?php echo esc_entities($seller->email ?? '-'); ?></td>
            </tr>
            <tr>
              <th><?php echo __('Phone'); ?></th>
              <td><?php echo esc_entities($seller->phone ?? '-'); ?></td>
            </tr>
            <tr>
              <th><?php echo __('Location'); ?></th>
              <td>
                <?php
                  $locationParts = array_filter([
                      $seller->city ?? '',
                      $seller->state_province ?? '',
                      $seller->country ?? '',
                  ]);
                  echo !empty($locationParts) ? esc_entities(implode(', ', $locationParts)) : '-';
                ?>
              </td>
            </tr>
            <tr>
              <th><?php echo __('Website'); ?></th>
              <td>
                <?php if ($seller->website): ?>
                  <a href="<?php echo esc_entities($seller->website); ?>" target="_blank" rel="noopener"><?php echo esc_entities($seller->website); ?></a>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <th><?php echo __('Description'); ?></th>
              <td><?php echo nl2br(esc_entities($seller->description ?? '-')); ?></td>
            </tr>
            <tr>
              <th><?php echo __('Payout Method'); ?></th>
              <td><?php echo esc_entities(ucfirst(str_replace('_', ' ', $seller->payout_method ?? '-'))); ?></td>
            </tr>
            <tr>
              <th><?php echo __('Payout Currency'); ?></th>
              <td><?php echo esc_entities($seller->payout_currency ?? 'ZAR'); ?></td>
            </tr>
            <tr>
              <th><?php echo __('Joined'); ?></th>
              <td><?php echo date('d M Y H:i', strtotime($seller->created_at)); ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Admin actions -->
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0"><?php echo __('Admin Actions'); ?></h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <?php if (($seller->verification_status ?? '') !== 'verified'): ?>
            <div class="col-md-6">
              <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminSellerVerify', 'id' => $seller->id]); ?>">
                <input type="hidden" name="form_action" value="verify">
                <button type="submit" class="btn btn-success w-100" onclick="return confirm('<?php echo __('Verify this seller?'); ?>');">
                  <i class="fas fa-check-circle me-1"></i> <?php echo __('Verify Seller'); ?>
                </button>
              </form>
            </div>
          <?php endif; ?>
          <?php if (($seller->verification_status ?? '') !== 'suspended'): ?>
            <div class="col-md-6">
              <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminSellerVerify', 'id' => $seller->id]); ?>">
                <input type="hidden" name="form_action" value="suspend">
                <button type="submit" class="btn btn-warning w-100" onclick="return confirm('<?php echo __('Suspend this seller?'); ?>');">
                  <i class="fas fa-ban me-1"></i> <?php echo __('Suspend Seller'); ?>
                </button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>

  <!-- Stats sidebar -->
  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Seller Stats'); ?></div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Total Sales'); ?></span>
          <span class="fw-semibold"><?php echo number_format((int) ($seller->total_sales ?? 0)); ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Revenue'); ?></span>
          <span class="fw-semibold"><?php echo esc_entities($seller->payout_currency ?? 'ZAR'); ?> <?php echo number_format((float) ($seller->total_revenue ?? 0), 2); ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Rating'); ?></span>
          <span>
            <?php echo number_format((float) ($seller->average_rating ?? 0), 1); ?>
            <i class="fas fa-star text-warning small"></i>
            <small class="text-muted">(<?php echo (int) ($seller->rating_count ?? 0); ?>)</small>
          </span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Followers'); ?></span>
          <span><?php echo number_format((int) ($seller->follower_count ?? 0)); ?></span>
        </li>
      </ul>
    </div>

    <div class="card">
      <div class="card-body">
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'seller', 'slug' => $seller->slug]); ?>" class="btn btn-outline-primary w-100 mb-2">
          <i class="fas fa-external-link-alt me-1"></i> <?php echo __('View Public Profile'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminSellers']); ?>" class="btn btn-outline-secondary w-100">
          <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Sellers'); ?>
        </a>
      </div>
    </div>
  </div>

</div>

<?php end_slot(); ?>

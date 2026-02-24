<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Review Listing'); ?> - <?php echo __('Marketplace Admin'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminDashboard']); ?>"><?php echo __('Marketplace Admin'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminListings']); ?>"><?php echo __('Listings'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Review'); ?></li>
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
  <h1 class="h3 mb-0"><?php echo __('Review Listing'); ?></h1>
  <span class="badge bg-<?php echo $listing->status === 'pending_review' ? 'warning' : 'secondary'; ?> fs-6">
    <?php echo esc_entities(ucfirst(str_replace('_', ' ', $listing->status))); ?>
  </span>
</div>

<div class="row">

  <!-- Listing detail -->
  <div class="col-lg-8">

    <!-- Images gallery -->
    <?php if (!empty($images)): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0"><?php echo __('Images'); ?></h5>
        </div>
        <div class="card-body">
          <div class="row g-2">
            <?php foreach ($images as $img): ?>
              <div class="col-4 col-md-3">
                <img src="<?php echo esc_entities($img->image_path); ?>" alt="<?php echo esc_entities($img->alt_text ?? ''); ?>" class="img-fluid rounded border" style="width: 100%; height: 120px; object-fit: cover;">
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Listing fields -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><?php echo esc_entities($listing->title); ?></h5>
      </div>
      <div class="card-body">
        <table class="table table-sm mb-0">
          <tbody>
            <tr>
              <th style="width: 200px;"><?php echo __('Listing Number'); ?></th>
              <td><?php echo esc_entities($listing->listing_number); ?></td>
            </tr>
            <tr>
              <th><?php echo __('Sector'); ?></th>
              <td><span class="badge bg-info"><?php echo esc_entities(ucfirst($listing->sector)); ?></span></td>
            </tr>
            <tr>
              <th><?php echo __('Category'); ?></th>
              <td><?php echo esc_entities($listing->category_name ?? '-'); ?></td>
            </tr>
            <tr>
              <th><?php echo __('Listing Type'); ?></th>
              <td><span class="badge bg-secondary"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $listing->listing_type))); ?></span></td>
            </tr>
            <tr>
              <th><?php echo __('Price'); ?></th>
              <td>
                <?php if ($listing->price_on_request): ?>
                  <span class="text-muted"><?php echo __('Price on Request'); ?></span>
                <?php elseif ($listing->price): ?>
                  <strong><?php echo esc_entities($listing->currency); ?> <?php echo number_format((float) $listing->price, 2); ?></strong>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
            </tr>
            <?php if ($listing->listing_type === 'auction'): ?>
              <tr>
                <th><?php echo __('Starting Bid'); ?></th>
                <td><?php echo esc_entities($listing->currency); ?> <?php echo number_format((float) ($listing->auction_start_price ?? 0), 2); ?></td>
              </tr>
              <tr>
                <th><?php echo __('Reserve Price'); ?></th>
                <td><?php echo $listing->auction_reserve_price ? esc_entities($listing->currency) . ' ' . number_format((float) $listing->auction_reserve_price, 2) : '-'; ?></td>
              </tr>
            <?php endif; ?>
            <tr>
              <th><?php echo __('Condition'); ?></th>
              <td><?php echo esc_entities(ucfirst(str_replace('_', ' ', $listing->condition ?? '-'))); ?></td>
            </tr>
            <tr>
              <th><?php echo __('Description'); ?></th>
              <td><?php echo nl2br(esc_entities($listing->description ?? '-')); ?></td>
            </tr>
            <tr>
              <th><?php echo __('Provenance'); ?></th>
              <td><?php echo nl2br(esc_entities($listing->provenance ?? '-')); ?></td>
            </tr>
            <tr>
              <th><?php echo __('Dimensions'); ?></th>
              <td><?php echo esc_entities($listing->dimensions ?? '-'); ?></td>
            </tr>
            <tr>
              <th><?php echo __('Created'); ?></th>
              <td><?php echo date('d M Y H:i', strtotime($listing->created_at)); ?></td>
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
          <!-- Approve -->
          <div class="col-md-4">
            <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminListingReview', 'id' => $listing->id]); ?>">
              <input type="hidden" name="form_action" value="approve">
              <button type="submit" class="btn btn-success w-100" onclick="return confirm('<?php echo __('Approve this listing and make it active?'); ?>');">
                <i class="fas fa-check me-1"></i> <?php echo __('Approve'); ?>
              </button>
            </form>
          </div>

          <!-- Suspend -->
          <div class="col-md-4">
            <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminListingReview', 'id' => $listing->id]); ?>">
              <input type="hidden" name="form_action" value="suspend">
              <button type="submit" class="btn btn-warning w-100" onclick="return confirm('<?php echo __('Suspend this listing?'); ?>');">
                <i class="fas fa-pause me-1"></i> <?php echo __('Suspend'); ?>
              </button>
            </form>
          </div>

          <!-- Reject -->
          <div class="col-md-4">
            <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminListingReview', 'id' => $listing->id]); ?>">
              <input type="hidden" name="form_action" value="reject">
              <button type="submit" class="btn btn-danger w-100" onclick="return confirm('<?php echo __('Reject this listing and return it to draft?'); ?>');">
                <i class="fas fa-times me-1"></i> <?php echo __('Reject'); ?>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- Seller info sidebar -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0"><?php echo __('Seller Info'); ?></h5>
      </div>
      <div class="card-body text-center">
        <?php if ($seller->avatar_path): ?>
          <img src="<?php echo esc_entities($seller->avatar_path); ?>" alt="" class="rounded-circle mb-3" width="80" height="80" style="object-fit: cover;">
        <?php else: ?>
          <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
            <i class="fas fa-user fa-2x text-muted"></i>
          </div>
        <?php endif; ?>
        <h6><?php echo esc_entities($seller->display_name); ?></h6>
        <p class="small text-muted mb-2"><?php echo esc_entities($seller->email ?? ''); ?></p>
        <?php
          $verifyClass = match($seller->verification_status ?? '') {
              'verified' => 'success',
              'pending' => 'warning',
              'suspended' => 'secondary',
              default => 'danger',
          };
        ?>
        <span class="badge bg-<?php echo $verifyClass; ?>"><?php echo esc_entities(ucfirst($seller->verification_status ?? 'unverified')); ?></span>
      </div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Type'); ?></span>
          <span><?php echo esc_entities(ucfirst($seller->seller_type ?? '-')); ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Total Sales'); ?></span>
          <span><?php echo number_format((int) ($seller->total_sales ?? 0)); ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Rating'); ?></span>
          <span>
            <?php echo number_format((float) ($seller->average_rating ?? 0), 1); ?>
            <i class="fas fa-star text-warning small"></i>
          </span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Joined'); ?></span>
          <span class="small"><?php echo date('d M Y', strtotime($seller->created_at)); ?></span>
        </li>
      </ul>
      <div class="card-body">
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminSellerVerify', 'id' => $seller->id]); ?>" class="btn btn-sm btn-outline-primary w-100">
          <i class="fas fa-eye me-1"></i> <?php echo __('View Seller Profile'); ?>
        </a>
      </div>
    </div>
  </div>

</div>

<?php end_slot(); ?>

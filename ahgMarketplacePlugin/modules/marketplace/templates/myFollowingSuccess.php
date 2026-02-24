<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Following'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Following'); ?></li>
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
  <h1 class="h3 mb-0">
    <?php echo __('Sellers I Follow'); ?>
    <?php if ($total > 0): ?>
      <span class="badge bg-secondary ms-2"><?php echo (int) $total; ?></span>
    <?php endif; ?>
  </h1>
  <div>
    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myPurchases']); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-shopping-bag me-1"></i><?php echo __('My Purchases'); ?>
    </a>
  </div>
</div>

<?php if (empty($sellers)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('Not following any sellers yet'); ?></h5>
      <p class="text-muted"><?php echo __('Follow sellers to stay updated on their new listings.'); ?></p>
      <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>" class="btn btn-primary">
        <i class="fas fa-search me-1"></i> <?php echo __('Browse Marketplace'); ?>
      </a>
    </div>
  </div>
<?php else: ?>
  <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
    <?php foreach ($sellers as $seller): ?>
      <div class="col">
        <div class="card h-100 text-center">
          <div class="card-body">
            <?php if ($seller->avatar_path): ?>
              <img src="<?php echo esc_entities($seller->avatar_path); ?>" alt="<?php echo esc_entities($seller->display_name); ?>" class="rounded-circle mb-3" style="width: 80px; height: 80px; object-fit: cover;">
            <?php else: ?>
              <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                <i class="fas fa-user fa-2x"></i>
              </div>
            <?php endif; ?>

            <h6 class="mb-1">
              <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'seller', 'slug' => $seller->slug]); ?>" class="text-decoration-none">
                <?php echo esc_entities($seller->display_name); ?>
              </a>
              <?php if ($seller->verification_status === 'verified'): ?>
                <i class="fas fa-check-circle text-primary ms-1" title="<?php echo __('Verified'); ?>"></i>
              <?php endif; ?>
            </h6>

            <!-- Rating -->
            <?php if ($seller->average_rating > 0): ?>
              <div class="mb-2">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <i class="fa<?php echo $s <= round($seller->average_rating) ? 's' : 'r'; ?> fa-star text-warning small"></i>
                <?php endfor; ?>
                <span class="small text-muted ms-1">(<?php echo (int) $seller->rating_count; ?>)</span>
              </div>
            <?php endif; ?>

            <!-- Listing count -->
            <p class="small text-muted mb-3">
              <i class="fas fa-tag me-1"></i>
              <?php echo __('%1% active listings', ['%1%' => (int) $seller->listing_count]); ?>
            </p>

            <div class="d-grid gap-2">
              <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'seller', 'slug' => $seller->slug]); ?>" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-store me-1"></i> <?php echo __('View Profile'); ?>
              </a>
              <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'follow', 'seller' => $seller->slug]); ?>" class="d-inline">
                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                  <i class="fas fa-user-minus me-1"></i> <?php echo __('Unfollow'); ?>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php
    $totalPages = ceil($total / $limit);
    if ($totalPages > 1):
  ?>
    <nav class="mt-4" aria-label="<?php echo __('Pagination'); ?>">
      <ul class="pagination justify-content-center">
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myFollowing', 'page' => $page - 1]); ?>">&laquo;</a>
        </li>
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myFollowing', 'page' => $p]); ?>"><?php echo $p; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myFollowing', 'page' => $page + 1]); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<?php end_slot(); ?>

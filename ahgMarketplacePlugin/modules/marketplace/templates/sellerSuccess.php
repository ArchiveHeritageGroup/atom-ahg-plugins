<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo esc_entities($seller->display_name); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('marketplace/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Marketplace'), 'url' => url_for(['module' => 'marketplace', 'action' => 'browse'])],
  ['label' => esc_entities($seller->display_name)],
]]); ?>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('notice'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Banner + avatar -->
<div class="position-relative mb-4">
  <?php if ($seller->banner_path): ?>
    <div class="rounded overflow-hidden" style="height: 200px;">
      <img src="<?php echo esc_entities($seller->banner_path); ?>" alt="" class="w-100 h-100" style="object-fit: cover;">
    </div>
  <?php else: ?>
    <div class="rounded bg-secondary" style="height: 140px;"></div>
  <?php endif; ?>

  <div class="d-flex align-items-end ms-4" style="margin-top: -50px; position: relative; z-index: 1;">
    <?php if ($seller->avatar_path): ?>
      <img src="<?php echo esc_entities($seller->avatar_path); ?>" alt="" class="rounded-circle border border-3 border-white shadow" width="100" height="100" style="object-fit: cover;">
    <?php else: ?>
      <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center border border-3 border-white shadow" style="width: 100px; height: 100px;">
        <i class="fas fa-user fa-2x"></i>
      </div>
    <?php endif; ?>
    <div class="ms-3 mb-2">
      <h1 class="h4 mb-0">
        <?php echo esc_entities($seller->display_name); ?>
        <?php if ($seller->verification_status === 'verified'): ?>
          <i class="fas fa-check-circle text-primary ms-1" title="<?php echo __('Verified'); ?>"></i>
        <?php endif; ?>
      </h1>
      <span class="badge bg-secondary"><?php echo esc_entities(ucfirst($seller->seller_type)); ?></span>
      <?php if ($seller->city || $seller->country): ?>
        <span class="text-muted small ms-2"><i class="fas fa-map-marker-alt me-1"></i><?php echo esc_entities(implode(', ', array_filter([$seller->city, $seller->country]))); ?></span>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Stats row -->
<div class="row text-center mb-4 g-3">
  <div class="col-6 col-md-3">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="h5 mb-0"><?php echo number_format($total); ?></div>
        <small class="text-muted"><?php echo __('Listings'); ?></small>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="h5 mb-0"><?php echo number_format((int) $seller->total_sales); ?></div>
        <small class="text-muted"><?php echo __('Sales'); ?></small>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="h5 mb-0">
          <?php if ($seller->average_rating > 0): ?>
            <?php for ($s = 1; $s <= 5; $s++): ?>
              <i class="fa<?php echo $s <= round($seller->average_rating) ? 's' : 'r'; ?> fa-star text-warning" style="font-size: 0.85rem;"></i>
            <?php endfor; ?>
          <?php else: ?>
            &mdash;
          <?php endif; ?>
        </div>
        <small class="text-muted"><?php echo __('Rating (%1%)', ['%1%' => (int) $seller->rating_count]); ?></small>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="h5 mb-0"><?php echo number_format($followerCount); ?></div>
        <small class="text-muted"><?php echo __('Followers'); ?></small>
      </div>
    </div>
  </div>
</div>

<!-- Follow button -->
<?php if ($sf_user->isAuthenticated()): ?>
  <div class="mb-4">
    <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'follow', 'seller' => $seller->slug]); ?>" class="d-inline">
      <?php if ($isFollowing): ?>
        <button type="submit" class="btn btn-outline-secondary">
          <i class="fas fa-user-check me-1"></i> <?php echo __('Following'); ?>
        </button>
      <?php else: ?>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-user-plus me-1"></i> <?php echo __('Follow'); ?>
        </button>
      <?php endif; ?>
    </form>
  </div>
<?php endif; ?>

<!-- Bio -->
<?php if ($seller->bio): ?>
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title"><?php echo __('About'); ?></h5>
      <p class="mb-0"><?php echo nl2br(esc_entities($seller->bio)); ?></p>
      <?php if ($seller->website): ?>
        <p class="mt-2 mb-0"><a href="<?php echo esc_entities($seller->website); ?>" target="_blank" rel="noopener"><i class="fas fa-globe me-1"></i><?php echo esc_entities($seller->website); ?></a></p>
      <?php endif; ?>
      <?php if ($seller->instagram): ?>
        <p class="mt-1 mb-0"><a href="https://instagram.com/<?php echo esc_entities(ltrim($seller->instagram, '@')); ?>" target="_blank" rel="noopener"><i class="fab fa-instagram me-1"></i><?php echo esc_entities($seller->instagram); ?></a></p>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<!-- Tabs: Listings / Collections / Reviews -->
<ul class="nav nav-tabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="tab-listings" data-bs-toggle="tab" data-bs-target="#panel-listings" type="button" role="tab">
      <?php echo __('Active Listings'); ?> <span class="badge bg-secondary"><?php echo number_format($total); ?></span>
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="tab-collections" data-bs-toggle="tab" data-bs-target="#panel-collections" type="button" role="tab">
      <?php echo __('Collections'); ?> <span class="badge bg-secondary"><?php echo count($collections); ?></span>
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="tab-reviews" data-bs-toggle="tab" data-bs-target="#panel-reviews" type="button" role="tab">
      <?php echo __('Reviews'); ?>
    </button>
  </li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom p-4">

  <!-- Active Listings tab -->
  <div class="tab-pane fade show active" id="panel-listings" role="tabpanel">
    <?php if (!empty($listings)): ?>
      <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
        <?php foreach ($listings as $listing): ?>
          <?php include_partial('marketplace/listingCard', ['listing' => $listing]); ?>
        <?php endforeach; ?>
      </div>
      <?php if ($total > 24): ?>
        <?php $totalPages = (int) ceil($total / 24); ?>
        <nav class="mt-4">
          <ul class="pagination justify-content-center">
            <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
              <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'seller', 'slug' => $seller->slug, 'page' => $page - 1]); ?>">&laquo;</a>
            </li>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
              <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
                <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'seller', 'slug' => $seller->slug, 'page' => $i]); ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
              <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'seller', 'slug' => $seller->slug, 'page' => $page + 1]); ?>">&raquo;</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    <?php else: ?>
      <p class="text-muted text-center py-3"><?php echo __('No active listings at this time.'); ?></p>
    <?php endif; ?>
  </div>

  <!-- Collections tab -->
  <div class="tab-pane fade" id="panel-collections" role="tabpanel">
    <?php if (!empty($collections)): ?>
      <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
        <?php foreach ($collections as $col): ?>
          <div class="col">
            <div class="card h-100">
              <?php if ($col->cover_image_path): ?>
                <img src="<?php echo esc_entities($col->cover_image_path); ?>" class="card-img-top" alt="<?php echo esc_entities($col->title); ?>" style="height: 160px; object-fit: cover;">
              <?php else: ?>
                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 160px;">
                  <i class="fas fa-layer-group fa-2x text-muted"></i>
                </div>
              <?php endif; ?>
              <div class="card-body">
                <h6 class="card-title mb-1">
                  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'collection', 'slug' => $col->slug]); ?>" class="text-decoration-none">
                    <?php echo esc_entities($col->title); ?>
                  </a>
                </h6>
                <?php if ($col->description): ?>
                  <p class="card-text small text-muted mb-0"><?php echo esc_entities(mb_strimwidth($col->description, 0, 100, '...')); ?></p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-muted text-center py-3"><?php echo __('No public collections.'); ?></p>
    <?php endif; ?>
  </div>

  <!-- Reviews tab -->
  <div class="tab-pane fade" id="panel-reviews" role="tabpanel">
    <?php if (!empty($ratingStats) && $seller->rating_count > 0): ?>
      <!-- Rating distribution -->
      <div class="row mb-4">
        <div class="col-md-4 text-center mb-3 mb-md-0">
          <div class="h1 mb-0"><?php echo number_format((float) $seller->average_rating, 1); ?></div>
          <div>
            <?php for ($s = 1; $s <= 5; $s++): ?>
              <i class="fa<?php echo $s <= round($seller->average_rating) ? 's' : 'r'; ?> fa-star text-warning"></i>
            <?php endfor; ?>
          </div>
          <small class="text-muted"><?php echo __('%1% reviews', ['%1%' => (int) $seller->rating_count]); ?></small>
        </div>
        <div class="col-md-8">
          <?php for ($star = 5; $star >= 1; $star--): ?>
            <?php $count = isset($ratingStats[$star]) ? (int) $ratingStats[$star] : 0; ?>
            <?php $pct = $seller->rating_count > 0 ? round(($count / $seller->rating_count) * 100) : 0; ?>
            <div class="d-flex align-items-center mb-1">
              <span class="small text-nowrap me-2" style="width: 45px;"><?php echo $star; ?> <i class="fas fa-star text-warning small"></i></span>
              <div class="progress flex-grow-1" style="height: 8px;">
                <div class="progress-bar bg-warning" style="width: <?php echo $pct; ?>%;"></div>
              </div>
              <span class="small text-muted ms-2" style="width: 30px;"><?php echo $count; ?></span>
            </div>
          <?php endfor; ?>
        </div>
      </div>
      <hr>
    <?php endif; ?>

    <?php if (!empty($reviews)): ?>
      <?php foreach ($reviews as $review): ?>
        <div class="mb-3 pb-3 border-bottom">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <?php for ($s = 1; $s <= 5; $s++): ?>
                <i class="fa<?php echo $s <= (int) $review->rating ? 's' : 'r'; ?> fa-star text-warning small"></i>
              <?php endfor; ?>
              <?php if ($review->title): ?>
                <strong class="ms-2"><?php echo esc_entities($review->title); ?></strong>
              <?php endif; ?>
            </div>
            <small class="text-muted"><?php echo esc_entities($review->created_at); ?></small>
          </div>
          <?php if ($review->comment): ?>
            <p class="small mt-1 mb-0"><?php echo nl2br(esc_entities($review->comment)); ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-muted text-center py-3"><?php echo __('No reviews yet.'); ?></p>
    <?php endif; ?>
  </div>

</div>

<?php end_slot(); ?>

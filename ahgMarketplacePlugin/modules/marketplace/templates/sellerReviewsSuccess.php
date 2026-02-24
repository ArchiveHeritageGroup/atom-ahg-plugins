<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('My Reviews'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'dashboard']); ?>"><?php echo __('Dashboard'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Reviews'); ?></li>
  </ol>
</nav>

<h1 class="h3 mb-4"><?php echo __('My Reviews'); ?></h1>

<!-- Rating overview -->
<div class="card mb-4">
  <div class="card-body">
    <div class="row">
      <div class="col-md-4 text-center mb-3 mb-md-0">
        <div class="h1 mb-1"><?php echo number_format((float) ($seller->average_rating ?? 0), 1); ?></div>
        <div class="mb-1">
          <?php for ($s = 1; $s <= 5; $s++): ?>
            <i class="fa<?php echo $s <= round((float) ($seller->average_rating ?? 0)) ? 's' : 'r'; ?> fa-star text-warning"></i>
          <?php endfor; ?>
        </div>
        <small class="text-muted"><?php echo __('%1% reviews', ['%1%' => (int) ($seller->rating_count ?? 0)]); ?></small>
      </div>
      <div class="col-md-8">
        <!-- Rating distribution -->
        <?php for ($star = 5; $star >= 1; $star--): ?>
          <?php
            $count = isset($ratingDistribution[$star]) ? (int) $ratingDistribution[$star] : 0;
            $totalReviews = (int) ($seller->rating_count ?? 0);
            $pct = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0;
          ?>
          <div class="d-flex align-items-center mb-1">
            <span class="small text-nowrap me-2" style="width: 50px;"><?php echo $star; ?> <i class="fas fa-star text-warning small"></i></span>
            <div class="progress flex-grow-1" style="height: 10px;">
              <div class="progress-bar bg-warning" style="width: <?php echo $pct; ?>%;"></div>
            </div>
            <span class="small text-muted ms-2" style="width: 40px;"><?php echo $count; ?></span>
          </div>
        <?php endfor; ?>
      </div>
    </div>
  </div>
</div>

<!-- Individual reviews -->
<?php if (!empty($reviews)): ?>
  <div class="card">
    <div class="card-body">
      <?php foreach ($reviews as $idx => $review): ?>
        <?php if ($idx > 0): ?><hr><?php endif; ?>
        <div class="mb-2">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <?php for ($s = 1; $s <= 5; $s++): ?>
                <i class="fa<?php echo $s <= (int) $review->rating ? 's' : 'r'; ?> fa-star text-warning"></i>
              <?php endfor; ?>
              <?php if ($review->title): ?>
                <strong class="ms-2"><?php echo esc_entities($review->title); ?></strong>
              <?php endif; ?>
            </div>
            <small class="text-muted"><?php echo date('d M Y', strtotime($review->created_at)); ?></small>
          </div>
          <?php if ($review->comment): ?>
            <p class="mt-2 mb-1"><?php echo nl2br(esc_entities($review->comment)); ?></p>
          <?php endif; ?>
          <?php if (!empty($review->transaction_id)): ?>
            <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerTransactionDetail', 'id' => $review->transaction_id]); ?>" class="small text-muted">
              <i class="fas fa-link me-1"></i><?php echo __('View Transaction'); ?>
            </a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-star fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No reviews yet'); ?></h5>
      <p class="text-muted"><?php echo __('Reviews from buyers will appear here after completed transactions.'); ?></p>
    </div>
  </div>
<?php endif; ?>

<?php end_slot(); ?>

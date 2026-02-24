<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Leave a Review'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myPurchases']); ?>"><?php echo __('My Purchases'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Leave a Review'); ?></li>
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

<div class="row">
  <div class="col-lg-8 mx-auto">

    <h1 class="h3 mb-4"><?php echo __('Leave a Review'); ?></h1>

    <!-- Transaction summary -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex">
          <?php if ($transaction->featured_image_path): ?>
            <img src="<?php echo esc_entities($transaction->featured_image_path); ?>" alt="<?php echo esc_entities($transaction->title); ?>" class="rounded me-3" style="width: 100px; height: 100px; object-fit: cover;">
          <?php else: ?>
            <div class="bg-light rounded d-flex align-items-center justify-content-center me-3" style="width: 100px; height: 100px;">
              <i class="fas fa-image fa-2x text-muted"></i>
            </div>
          <?php endif; ?>
          <div>
            <h5 class="mb-1"><?php echo esc_entities($transaction->title); ?></h5>
            <?php if ($transaction->seller_name): ?>
              <p class="text-muted mb-1"><?php echo __('Sold by %1%', ['%1%' => esc_entities($transaction->seller_name)]); ?></p>
            <?php endif; ?>
            <p class="mb-0">
              <span class="fw-semibold"><?php echo esc_entities($transaction->currency); ?> <?php echo number_format((float) $transaction->grand_total, 2); ?></span>
              <span class="text-muted ms-2"><?php echo __('Transaction #%1%', ['%1%' => esc_entities($transaction->transaction_number)]); ?></span>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Review form -->
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-star me-2"></i><?php echo __('Your Review'); ?></h5>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'reviewForm', 'id' => $transaction->id]); ?>">

          <!-- Star rating -->
          <div class="mb-4">
            <label class="form-label"><?php echo __('Rating'); ?> <span class="text-danger">*</span></label>
            <div id="star-rating" class="d-flex gap-1">
              <?php for ($s = 1; $s <= 5; $s++): ?>
                <label class="star-label" style="cursor: pointer; font-size: 2rem;">
                  <input type="radio" name="rating" value="<?php echo $s; ?>" class="d-none" required>
                  <i class="far fa-star text-warning star-icon" data-star="<?php echo $s; ?>"></i>
                </label>
              <?php endfor; ?>
            </div>
            <div class="form-text" id="rating-text"><?php echo __('Click a star to rate'); ?></div>
          </div>

          <div class="mb-3">
            <label for="review_title" class="form-label"><?php echo __('Review Title'); ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="review_title" name="review_title"
                   value="<?php echo esc_entities($sf_request->getParameter('review_title', '')); ?>"
                   placeholder="<?php echo __('Summarize your experience'); ?>"
                   maxlength="255"
                   required>
          </div>

          <div class="mb-4">
            <label for="review_comment" class="form-label"><?php echo __('Your Review'); ?> <span class="text-muted">(<?php echo __('optional'); ?>)</span></label>
            <textarea class="form-control" id="review_comment" name="review_comment" rows="5"
                      placeholder="<?php echo __('Share more details about your experience with this seller...'); ?>"><?php echo esc_entities($sf_request->getParameter('review_comment', '')); ?></textarea>
          </div>

          <div class="d-flex justify-content-between align-items-center">
            <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myPurchases']); ?>" class="btn btn-outline-secondary">
              <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Purchases'); ?>
            </a>
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="fas fa-paper-plane me-1"></i> <?php echo __('Submit Review'); ?>
            </button>
          </div>

        </form>
      </div>
    </div>

  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var ratingLabels = ['', '<?php echo __('Poor'); ?>', '<?php echo __('Fair'); ?>', '<?php echo __('Good'); ?>', '<?php echo __('Very Good'); ?>', '<?php echo __('Excellent'); ?>'];
  var stars = document.querySelectorAll('#star-rating .star-icon');
  var ratingText = document.getElementById('rating-text');

  stars.forEach(function(star) {
    star.addEventListener('mouseenter', function() {
      var val = parseInt(this.getAttribute('data-star'));
      highlightStars(val);
    });

    star.parentElement.addEventListener('click', function() {
      var radio = this.querySelector('input');
      radio.checked = true;
      var val = parseInt(radio.value);
      highlightStars(val);
      ratingText.textContent = ratingLabels[val] || '';
    });
  });

  document.getElementById('star-rating').addEventListener('mouseleave', function() {
    var checked = document.querySelector('#star-rating input:checked');
    if (checked) {
      highlightStars(parseInt(checked.value));
    } else {
      highlightStars(0);
    }
  });

  function highlightStars(count) {
    stars.forEach(function(star) {
      var val = parseInt(star.getAttribute('data-star'));
      if (val <= count) {
        star.className = 'fas fa-star text-warning star-icon';
      } else {
        star.className = 'far fa-star text-warning star-icon';
      }
    });
  }
});
</script>

<?php end_slot(); ?>

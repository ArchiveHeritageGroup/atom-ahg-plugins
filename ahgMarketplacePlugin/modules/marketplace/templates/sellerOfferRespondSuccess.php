<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Respond to Offer'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'dashboard']); ?>"><?php echo __('Dashboard'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerOffers']); ?>"><?php echo __('Offers'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Respond'); ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('error'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-lg-8 mx-auto">

    <h1 class="h3 mb-4"><?php echo __('Respond to Offer'); ?></h1>

    <!-- Offer detail card -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex">
          <?php if (!empty($listing->featured_image_path)): ?>
            <img src="<?php echo esc_entities($listing->featured_image_path); ?>" alt="" class="rounded me-3" style="width: 100px; height: 100px; object-fit: cover;">
          <?php else: ?>
            <div class="bg-light rounded d-flex align-items-center justify-content-center me-3" style="width: 100px; height: 100px;">
              <i class="fas fa-image fa-2x text-muted"></i>
            </div>
          <?php endif; ?>
          <div class="flex-grow-1">
            <h5 class="mb-1">
              <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $listing->slug]); ?>" class="text-decoration-none">
                <?php echo esc_entities($listing->title); ?>
              </a>
            </h5>
            <?php if ($listing->price && !$listing->price_on_request): ?>
              <p class="text-muted mb-1"><?php echo __('Listing Price: %1% %2%', ['%1%' => esc_entities($listing->currency), '%2%' => number_format((float) $listing->price, 2)]); ?></p>
            <?php endif; ?>
            <p class="h5 text-primary mb-1"><?php echo __('Offer Amount: %1% %2%', ['%1%' => esc_entities($offer->currency), '%2%' => number_format((float) $offer->offer_amount, 2)]); ?></p>
            <p class="small text-muted mb-0"><?php echo __('From: %1%', ['%1%' => esc_entities($buyerName ?? '-')]); ?> &mdash; <?php echo date('d M Y H:i', strtotime($offer->created_at)); ?></p>
          </div>
        </div>
        <?php if ($offer->message): ?>
          <div class="mt-3 p-3 bg-light rounded">
            <strong class="small"><?php echo __('Buyer Message:'); ?></strong>
            <p class="mb-0 small"><?php echo nl2br(esc_entities($offer->message)); ?></p>
          </div>
        <?php endif; ?>
        <?php if ($offer->counter_amount): ?>
          <div class="mt-2 p-3 bg-warning bg-opacity-10 rounded">
            <strong class="small"><?php echo __('Previous Counter-Offer:'); ?></strong>
            <span class="fw-semibold"><?php echo esc_entities($offer->currency); ?> <?php echo number_format((float) $offer->counter_amount, 2); ?></span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Response form -->
    <div class="card">
      <div class="card-header fw-semibold"><?php echo __('Your Response'); ?></div>
      <div class="card-body">

        <!-- Accept -->
        <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerOfferRespond', 'id' => $offer->id]); ?>" class="mb-3">
          <input type="hidden" name="form_action" value="accept">
          <button type="submit" class="btn btn-success btn-lg w-100" onclick="return confirm('<?php echo __('Accept this offer of %1% %2%?', ['%1%' => esc_entities($offer->currency), '%2%' => number_format((float) $offer->offer_amount, 2)]); ?>');">
            <i class="fas fa-check me-1"></i> <?php echo __('Accept Offer (%1% %2%)', ['%1%' => esc_entities($offer->currency), '%2%' => number_format((float) $offer->offer_amount, 2)]); ?>
          </button>
        </form>

        <hr class="my-4">

        <!-- Reject -->
        <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerOfferRespond', 'id' => $offer->id]); ?>" class="mb-4">
          <input type="hidden" name="form_action" value="reject">
          <div class="mb-3">
            <label for="reject_message" class="form-label"><?php echo __('Rejection Message'); ?> <span class="text-muted">(<?php echo __('optional'); ?>)</span></label>
            <textarea class="form-control" id="reject_message" name="seller_response" rows="2" placeholder="<?php echo __('Optional message to the buyer...'); ?>"></textarea>
          </div>
          <button type="submit" class="btn btn-danger w-100" onclick="return confirm('<?php echo __('Reject this offer?'); ?>');">
            <i class="fas fa-times me-1"></i> <?php echo __('Reject Offer'); ?>
          </button>
        </form>

        <hr class="my-4">

        <!-- Counter-offer -->
        <h6 class="mb-3"><?php echo __('Counter-Offer'); ?></h6>
        <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerOfferRespond', 'id' => $offer->id]); ?>">
          <input type="hidden" name="form_action" value="counter">
          <div class="mb-3">
            <label for="counter_amount" class="form-label"><?php echo __('Counter Amount'); ?> <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><?php echo esc_entities($offer->currency); ?></span>
              <input type="number" class="form-control" id="counter_amount" name="counter_amount" min="0.01" step="0.01" required placeholder="<?php echo __('Your counter-offer amount'); ?>">
            </div>
          </div>
          <div class="mb-3">
            <label for="counter_message" class="form-label"><?php echo __('Message'); ?> <span class="text-muted">(<?php echo __('optional'); ?>)</span></label>
            <textarea class="form-control" id="counter_message" name="seller_response" rows="3" placeholder="<?php echo __('Explain your counter-offer...'); ?>"></textarea>
          </div>
          <button type="submit" class="btn btn-warning w-100">
            <i class="fas fa-exchange-alt me-1"></i> <?php echo __('Send Counter-Offer'); ?>
          </button>
        </form>

      </div>
    </div>

    <div class="mt-4">
      <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerOffers']); ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Offers'); ?>
      </a>
    </div>

  </div>
</div>

<?php end_slot(); ?>

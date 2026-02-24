<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Enquire'); ?> - <?php echo esc_entities($listing->title); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $listing->slug]); ?>"><?php echo esc_entities($listing->title); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Enquire'); ?></li>
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

    <h1 class="h3 mb-4"><?php echo __('Enquire About This Listing'); ?></h1>

    <!-- Listing summary -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex">
          <?php if ($primaryImage): ?>
            <img src="<?php echo esc_entities($primaryImage->file_path); ?>" alt="<?php echo esc_entities($listing->title); ?>" class="rounded me-3" style="width: 100px; height: 100px; object-fit: cover;">
          <?php elseif ($listing->featured_image_path): ?>
            <img src="<?php echo esc_entities($listing->featured_image_path); ?>" alt="<?php echo esc_entities($listing->title); ?>" class="rounded me-3" style="width: 100px; height: 100px; object-fit: cover;">
          <?php else: ?>
            <div class="bg-light rounded d-flex align-items-center justify-content-center me-3" style="width: 100px; height: 100px;">
              <i class="fas fa-image fa-2x text-muted"></i>
            </div>
          <?php endif; ?>
          <div>
            <h5 class="mb-1"><?php echo esc_entities($listing->title); ?></h5>
            <?php if ($listing->artist_name): ?>
              <p class="text-muted mb-1"><?php echo __('by %1%', ['%1%' => esc_entities($listing->artist_name)]); ?></p>
            <?php endif; ?>
            <?php if ($listing->price && !$listing->price_on_request): ?>
              <p class="h5 text-primary mb-0">
                <?php echo esc_entities($listing->currency); ?> <?php echo number_format((float) $listing->price, 2); ?>
              </p>
            <?php elseif ($listing->price_on_request): ?>
              <p class="h5 text-muted mb-0"><?php echo __('Price on Request'); ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Enquiry form -->
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-envelope me-2"></i><?php echo __('Your Enquiry'); ?></h5>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'enquiryForm', 'slug' => $listing->slug]); ?>">

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="enquiry_name" class="form-label"><?php echo __('Your Name'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="enquiry_name" name="enquiry_name"
                     value="<?php echo esc_entities($sf_request->getParameter('enquiry_name', $prefillName)); ?>"
                     required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="enquiry_email" class="form-label"><?php echo __('Email Address'); ?> <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="enquiry_email" name="enquiry_email"
                     value="<?php echo esc_entities($sf_request->getParameter('enquiry_email', $prefillEmail)); ?>"
                     required>
            </div>
          </div>

          <div class="mb-3">
            <label for="enquiry_phone" class="form-label"><?php echo __('Phone Number'); ?> <span class="text-muted">(<?php echo __('optional'); ?>)</span></label>
            <input type="tel" class="form-control" id="enquiry_phone" name="enquiry_phone"
                   value="<?php echo esc_entities($sf_request->getParameter('enquiry_phone', '')); ?>">
          </div>

          <div class="mb-3">
            <label for="enquiry_subject" class="form-label"><?php echo __('Subject'); ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="enquiry_subject" name="enquiry_subject"
                   value="<?php echo esc_entities($sf_request->getParameter('enquiry_subject', __('Enquiry about: %1%', ['%1%' => $listing->title]))); ?>"
                   required>
          </div>

          <div class="mb-4">
            <label for="enquiry_message" class="form-label"><?php echo __('Message'); ?> <span class="text-danger">*</span></label>
            <textarea class="form-control" id="enquiry_message" name="enquiry_message" rows="5"
                      placeholder="<?php echo __('Please enter your enquiry...'); ?>"
                      required><?php echo esc_entities($sf_request->getParameter('enquiry_message', '')); ?></textarea>
          </div>

          <div class="d-flex justify-content-between align-items-center">
            <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $listing->slug]); ?>" class="btn btn-outline-secondary">
              <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Listing'); ?>
            </a>
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="fas fa-paper-plane me-1"></i> <?php echo __('Send Enquiry'); ?>
            </button>
          </div>

        </form>
      </div>
    </div>

  </div>
</div>

<?php end_slot(); ?>

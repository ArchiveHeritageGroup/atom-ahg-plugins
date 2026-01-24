<?php use_helper('Text'); ?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8 text-center">
      <div class="card shadow">
        <div class="card-body py-5">
          <i class="fas fa-check-circle text-success fa-5x mb-4"></i>
          <h1 class="h2 mb-3"><?php echo __('Thank You!'); ?></h1>
          <p class="lead text-muted mb-4">
            <?php echo __('Your request has been submitted successfully.'); ?>
          </p>
          <p class="mb-4">
            <?php echo __('We will review your request and contact you via email with further information.'); ?>
          </p>
          <hr class="my-4">
          <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']); ?>" class="btn btn-primary btn-lg">
            <i class="fas fa-search me-2"></i><?php echo __('Continue Browsing'); ?>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

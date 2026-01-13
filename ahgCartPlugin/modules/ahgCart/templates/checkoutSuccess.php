<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-paper-plane me-2"></i><?php echo __('Request to Publish - Cart Submission'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="container-fluid px-0">
  <div class="row">
    <!-- Cart Items Summary -->
    <div class="col-md-4">
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
          <i class="fas fa-shopping-cart me-2"></i>
          <?php echo __('Items to Request'); ?>
          <span class="badge bg-light text-success ms-2"><?php echo $count; ?></span>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            <?php foreach ($items as $item): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                <div class="small">
                  <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item->slug]); ?>" class="text-decoration-none" target="_blank">
                    <?php echo esc_entities($item->title); ?>
                  </a>
                </div>
                <?php if ($item->has_digital_object): ?>
                  <span class="badge bg-success"><i class="fas fa-image"></i></span>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="card-footer">
          <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'browse']); ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Cart'); ?>
          </a>
        </div>
      </div>
    </div>

    <!-- Request to Publish Form -->
    <div class="col-md-8">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <i class="fas fa-user me-2"></i><?php echo __('Request to Publish Details'); ?>
        </div>
        <div class="card-body">
          <form method="post" action="<?php echo url_for(['module' => 'ahgCart', 'action' => 'checkout']); ?>">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="rtp_name" class="form-label"><?php echo __('Name'); ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="rtp_name" name="rtp_name" required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="rtp_surname" class="form-label"><?php echo __('Surname'); ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="rtp_surname" name="rtp_surname" required
                       value="<?php echo esc_entities($user->username ?? ''); ?>">
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="rtp_phone" class="form-label"><?php echo __('Phone Number'); ?> <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" id="rtp_phone" name="rtp_phone" required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="rtp_email" class="form-label"><?php echo __('Email'); ?> <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="rtp_email" name="rtp_email" required
                       value="<?php echo esc_entities($user->email ?? ''); ?>">
              </div>
            </div>

            <div class="mb-3">
              <label for="rtp_institution" class="form-label"><?php echo __('Institution'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="rtp_institution" name="rtp_institution" required>
            </div>

            <div class="mb-3">
              <label for="rtp_planned_use" class="form-label"><?php echo __('Planned Use'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="rtp_planned_use" name="rtp_planned_use" required
                     placeholder="<?php echo __('e.g., Publication, Exhibition, Research, Personal'); ?>">
            </div>

            <div class="mb-3">
              <label for="rtp_need_image_by" class="form-label"><?php echo __('Need Image(s) By'); ?></label>
              <input type="date" class="form-control" id="rtp_need_image_by" name="rtp_need_image_by">
            </div>

            <div class="mb-3">
              <label for="rtp_motivation" class="form-label"><?php echo __('Motivation'); ?></label>
              <textarea class="form-control" id="rtp_motivation" name="rtp_motivation" rows="3" 
                        placeholder="<?php echo __('Please describe your motivation for requesting these images...'); ?>"></textarea>
            </div>

            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              <?php echo __('By submitting this request, you agree to the repository\'s terms and conditions. A separate request will be created for each item in your cart.'); ?>
            </div>

            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-paper-plane me-2"></i><?php echo __('Submit Request for %1% Item(s)', ['%1%' => $count]); ?>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php end_slot(); ?>

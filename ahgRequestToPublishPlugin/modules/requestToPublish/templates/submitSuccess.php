<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
<div class="d-flex align-items-center mb-3">
  <i class="fas fa-paper-plane fa-2x text-primary me-3"></i>
  <div>
    <h1 class="h3 mb-0"><?php echo __('Request to Publish') ?></h1>
    <p class="text-muted mb-0"><?php echo __('Submit a request for publication of archival images') ?></p>
  </div>
</div>
<?php end_slot() ?>

<?php slot('content') ?>

<?php if ($sf_user->hasFlash('notice') && $sf_user->getFlash('notice')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo $sf_user->getFlash('notice') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if ($sf_user->hasFlash('error') && $sf_user->getFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $sf_user->getFlash('error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<form method="post" action="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'submit', 'slug' => $informationObject->slug]) ?>">

<div class="row justify-content-center">
  <div class="col-lg-8">
    <!-- Item Information (Read-only) -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="fas fa-file-alt me-2"></i><?php echo __('Requested Item') ?>
        </h5>
      </div>
      <div class="card-body bg-light">
        <div class="row">
          <div class="col-md-8">
            <label class="form-label text-muted small mb-1"><?php echo __('Title') ?></label>
            <p class="fw-semibold mb-0"><?php echo esc_entities($informationObject->title ?? $informationObject->slug) ?></p>
          </div>
          <div class="col-md-4">
            <label class="form-label text-muted small mb-1"><?php echo __('Identifier') ?></label>
            <p class="fw-semibold mb-0"><?php echo esc_entities($informationObject->identifier ?? '-') ?></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Request Details Form -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">
          <i class="fas fa-edit me-2"></i><?php echo __('Publication Request Details') ?>
        </h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12">
            <label for="rtp_planned_use" class="form-label fw-semibold">
              <?php echo __('Planned Use') ?> <span class="text-danger">*</span>
            </label>
            <textarea name="rtp_planned_use" id="rtp_planned_use" class="form-control" rows="4" required
                      placeholder="<?php echo __('Please describe how you intend to use the image(s)...') ?>"><?php echo $sf_request->getParameter('rtp_planned_use') ?></textarea>
            <div class="form-text"><?php echo __('e.g., Publication in book, exhibition, documentary, academic research, etc.') ?></div>
          </div>
          <div class="col-12">
            <label for="rtp_motivation" class="form-label fw-semibold">
              <?php echo __('Motivation / Additional Information') ?>
            </label>
            <textarea name="rtp_motivation" id="rtp_motivation" class="form-control" rows="3"
                      placeholder="<?php echo __('Any additional context or motivation for your request...') ?>"><?php echo $sf_request->getParameter('rtp_motivation') ?></textarea>
          </div>
          <div class="col-md-6">
            <label for="rtp_need_image_by" class="form-label fw-semibold">
              <?php echo __('Need Image By') ?>
            </label>
            <input type="date" name="rtp_need_image_by" id="rtp_need_image_by" class="form-control"
                   value="<?php echo $sf_request->getParameter('rtp_need_image_by') ?>">
            <div class="form-text"><?php echo __('When do you need the image(s)?') ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Contact Information -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-light">
        <h5 class="card-title mb-0">
          <i class="fas fa-address-card me-2"></i><?php echo __('Your Contact Details') ?>
        </h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label for="rtp_name" class="form-label fw-semibold">
              <?php echo __('First Name') ?> <span class="text-danger">*</span>
            </label>
            <input type="text" name="rtp_name" id="rtp_name" class="form-control" required
                   value="<?php echo esc_entities($sf_request->getParameter('rtp_name', $userName ?? '')) ?>"
                   placeholder="<?php echo __('Your first name') ?>">
          </div>
          <div class="col-md-6">
            <label for="rtp_surname" class="form-label fw-semibold">
              <?php echo __('Surname') ?> <span class="text-danger">*</span>
            </label>
            <input type="text" name="rtp_surname" id="rtp_surname" class="form-control" required
                   value="<?php echo esc_entities($sf_request->getParameter('rtp_surname', $userSurname ?? '')) ?>"
                   placeholder="<?php echo __('Your surname') ?>">
          </div>
          <div class="col-md-6">
            <label for="rtp_email" class="form-label fw-semibold">
              <?php echo __('Email Address') ?> <span class="text-danger">*</span>
            </label>
            <input type="email" name="rtp_email" id="rtp_email" class="form-control" required
                   value="<?php echo esc_entities($sf_request->getParameter('rtp_email', $userEmail ?? '')) ?>"
                   placeholder="<?php echo __('your.email@example.com') ?>">
          </div>
          <div class="col-md-6">
            <label for="rtp_phone" class="form-label fw-semibold">
              <?php echo __('Phone Number') ?>
            </label>
            <input type="tel" name="rtp_phone" id="rtp_phone" class="form-control"
                   value="<?php echo esc_entities($sf_request->getParameter('rtp_phone')) ?>"
                   placeholder="<?php echo __('+27 xx xxx xxxx') ?>">
          </div>
          <div class="col-12">
            <label for="rtp_institution" class="form-label fw-semibold">
              <?php echo __('Institution / Organization') ?>
            </label>
            <input type="text" name="rtp_institution" id="rtp_institution" class="form-control"
                   value="<?php echo esc_entities($sf_request->getParameter('rtp_institution')) ?>"
                   placeholder="<?php echo __('Your company, university, or organization') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Submit -->
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $informationObject->slug]) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-times me-1"></i><?php echo __('Cancel') ?>
          </a>
          <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-paper-plane me-1"></i><?php echo __('Submit Request') ?>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

</form>

<?php end_slot() ?>

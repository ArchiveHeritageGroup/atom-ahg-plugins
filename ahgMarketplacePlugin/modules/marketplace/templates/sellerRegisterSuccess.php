<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Register as Seller'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Register as Seller'); ?></li>
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

    <div class="card">
      <div class="card-header">
        <h1 class="h4 mb-0"><i class="fas fa-store me-2"></i><?php echo __('Register as a Seller'); ?></h1>
      </div>
      <div class="card-body">
        <p class="text-muted mb-4"><?php echo __('Fill in the details below to create your seller profile and start listing items on the marketplace.'); ?></p>

        <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerRegister']); ?>">

          <div class="mb-3">
            <label for="display_name" class="form-label"><?php echo __('Display Name'); ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="display_name" name="display_name" value="<?php echo esc_entities($sf_request->getParameter('display_name', '')); ?>" required maxlength="255">
            <div class="form-text"><?php echo __('This is the name buyers will see on your listings.'); ?></div>
          </div>

          <div class="mb-3">
            <label for="seller_type" class="form-label"><?php echo __('Seller Type'); ?> <span class="text-danger">*</span></label>
            <select class="form-select" id="seller_type" name="seller_type" required>
              <option value=""><?php echo __('-- Select Type --'); ?></option>
              <?php $types = ['artist' => __('Artist'), 'gallery' => __('Gallery'), 'institution' => __('Institution'), 'collector' => __('Collector'), 'estate' => __('Estate')]; ?>
              <?php foreach ($types as $val => $label): ?>
                <option value="<?php echo $val; ?>"<?php echo $sf_request->getParameter('seller_type') === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="email" class="form-label"><?php echo __('Email'); ?></label>
              <input type="email" class="form-control" id="email" name="email" value="<?php echo esc_entities($sf_request->getParameter('email', '')); ?>" maxlength="255">
            </div>
            <div class="col-md-6">
              <label for="phone" class="form-label"><?php echo __('Phone'); ?></label>
              <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo esc_entities($sf_request->getParameter('phone', '')); ?>" maxlength="50">
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="country" class="form-label"><?php echo __('Country'); ?></label>
              <input type="text" class="form-control" id="country" name="country" value="<?php echo esc_entities($sf_request->getParameter('country', '')); ?>" maxlength="100">
            </div>
            <div class="col-md-6">
              <label for="city" class="form-label"><?php echo __('City'); ?></label>
              <input type="text" class="form-control" id="city" name="city" value="<?php echo esc_entities($sf_request->getParameter('city', '')); ?>" maxlength="100">
            </div>
          </div>

          <div class="mb-3">
            <label for="bio" class="form-label"><?php echo __('Bio'); ?></label>
            <textarea class="form-control" id="bio" name="bio" rows="4" placeholder="<?php echo __('Tell buyers about yourself or your gallery...'); ?>"><?php echo esc_entities($sf_request->getParameter('bio', '')); ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Sectors'); ?></label>
            <div class="form-text mb-2"><?php echo __('Select the sectors you deal in.'); ?></div>
            <?php $sectorOptions = ['gallery' => __('Gallery'), 'museum' => __('Museum'), 'archive' => __('Archive'), 'library' => __('Library'), 'dam' => __('Digital Asset Management')]; ?>
            <?php $selectedSectors = $sf_request->getParameter('sectors', []); ?>
            <?php foreach ($sectorOptions as $val => $label): ?>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="sectors[]" value="<?php echo $val; ?>" id="sector-<?php echo $val; ?>"<?php echo (is_array($selectedSectors) && in_array($val, $selectedSectors)) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="sector-<?php echo $val; ?>"><?php echo $label; ?></label>
              </div>
            <?php endforeach; ?>
          </div>

          <hr class="my-4">

          <h5 class="mb-3"><?php echo __('Payout Preferences'); ?></h5>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="payout_method" class="form-label"><?php echo __('Payout Method'); ?></label>
              <select class="form-select" id="payout_method" name="payout_method">
                <?php $methods = ['bank_transfer' => __('Bank Transfer'), 'paypal' => __('PayPal'), 'payfast' => __('PayFast')]; ?>
                <?php foreach ($methods as $val => $label): ?>
                  <option value="<?php echo $val; ?>"<?php echo $sf_request->getParameter('payout_method') === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label for="payout_currency" class="form-label"><?php echo __('Payout Currency'); ?></label>
              <select class="form-select" id="payout_currency" name="payout_currency">
                <?php if (!empty($currencies)): ?>
                  <?php foreach ($currencies as $cur): ?>
                    <option value="<?php echo esc_entities($cur->code); ?>"<?php echo $sf_request->getParameter('payout_currency', 'ZAR') === $cur->code ? ' selected' : ''; ?>>
                      <?php echo esc_entities($cur->code . ' - ' . $cur->name); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
          </div>

          <hr class="my-4">

          <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" id="accept_terms" name="accept_terms" value="1" required>
            <label class="form-check-label" for="accept_terms">
              <?php echo __('I accept the <a href="%1%" target="_blank">Terms and Conditions</a> for sellers.', ['%1%' => url_for(['module' => 'marketplace', 'action' => 'browse']) . '/terms']); ?> <span class="text-danger">*</span>
            </label>
          </div>

          <div class="d-flex justify-content-between">
            <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>" class="btn btn-outline-secondary">
              <i class="fas fa-arrow-left me-1"></i> <?php echo __('Cancel'); ?>
            </a>
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="fas fa-store me-1"></i> <?php echo __('Register'); ?>
            </button>
          </div>

        </form>
      </div>
    </div>

  </div>
</div>

<?php end_slot(); ?>

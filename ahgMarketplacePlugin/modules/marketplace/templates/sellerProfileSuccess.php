<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Edit Seller Profile'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'dashboard']); ?>"><?php echo __('Dashboard'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Edit Profile'); ?></li>
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

    <h1 class="h3 mb-4"><?php echo __('Edit Seller Profile'); ?></h1>

    <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerProfile']); ?>" enctype="multipart/form-data">

      <!-- Avatar & Banner -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><?php echo __('Profile Images'); ?></div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="avatar" class="form-label"><?php echo __('Avatar'); ?></label>
              <?php if ($seller->avatar_path): ?>
                <div class="mb-2">
                  <img src="<?php echo esc_entities($seller->avatar_path); ?>" alt="" class="rounded-circle" width="80" height="80" style="object-fit: cover;">
                </div>
              <?php endif; ?>
              <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
              <div class="form-text"><?php echo __('Square image recommended. Max 2MB.'); ?></div>
            </div>
            <div class="col-md-6">
              <label for="banner" class="form-label"><?php echo __('Banner Image'); ?></label>
              <?php if ($seller->banner_path): ?>
                <div class="mb-2">
                  <img src="<?php echo esc_entities($seller->banner_path); ?>" alt="" class="rounded" width="200" height="60" style="object-fit: cover;">
                </div>
              <?php endif; ?>
              <input type="file" class="form-control" id="banner" name="banner" accept="image/*">
              <div class="form-text"><?php echo __('Recommended size: 1200x300px. Max 5MB.'); ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Basic info -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><?php echo __('Basic Information'); ?></div>
        <div class="card-body">
          <div class="mb-3">
            <label for="display_name" class="form-label"><?php echo __('Display Name'); ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="display_name" name="display_name" value="<?php echo esc_entities($seller->display_name); ?>" required maxlength="255">
          </div>

          <div class="mb-3">
            <label for="seller_type" class="form-label"><?php echo __('Seller Type'); ?></label>
            <select class="form-select" id="seller_type" name="seller_type">
              <?php $types = ['artist' => __('Artist'), 'gallery' => __('Gallery'), 'institution' => __('Institution'), 'collector' => __('Collector'), 'estate' => __('Estate')]; ?>
              <?php foreach ($types as $val => $label): ?>
                <option value="<?php echo $val; ?>"<?php echo ($seller->seller_type ?? '') === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="bio" class="form-label"><?php echo __('Bio'); ?></label>
            <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo esc_entities($seller->bio ?? ''); ?></textarea>
          </div>
        </div>
      </div>

      <!-- Contact -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><?php echo __('Contact Information'); ?></div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="email" class="form-label"><?php echo __('Email'); ?></label>
              <input type="email" class="form-control" id="email" name="email" value="<?php echo esc_entities($seller->email ?? ''); ?>" maxlength="255">
            </div>
            <div class="col-md-6">
              <label for="phone" class="form-label"><?php echo __('Phone'); ?></label>
              <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo esc_entities($seller->phone ?? ''); ?>" maxlength="50">
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="website" class="form-label"><?php echo __('Website'); ?></label>
              <input type="url" class="form-control" id="website" name="website" value="<?php echo esc_entities($seller->website ?? ''); ?>" placeholder="https://" maxlength="255">
            </div>
            <div class="col-md-6">
              <label for="instagram" class="form-label"><?php echo __('Instagram'); ?></label>
              <div class="input-group">
                <span class="input-group-text">@</span>
                <input type="text" class="form-control" id="instagram" name="instagram" value="<?php echo esc_entities(ltrim($seller->instagram ?? '', '@')); ?>" maxlength="255">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Location -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><?php echo __('Location'); ?></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <label for="country" class="form-label"><?php echo __('Country'); ?></label>
              <input type="text" class="form-control" id="country" name="country" value="<?php echo esc_entities($seller->country ?? ''); ?>" maxlength="100">
            </div>
            <div class="col-md-6">
              <label for="city" class="form-label"><?php echo __('City'); ?></label>
              <input type="text" class="form-control" id="city" name="city" value="<?php echo esc_entities($seller->city ?? ''); ?>" maxlength="100">
            </div>
          </div>
        </div>
      </div>

      <!-- Payout -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><?php echo __('Payout Settings'); ?></div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="payout_method" class="form-label"><?php echo __('Payout Method'); ?></label>
              <select class="form-select" id="payout_method" name="payout_method">
                <?php $methods = ['bank_transfer' => __('Bank Transfer'), 'paypal' => __('PayPal'), 'payfast' => __('PayFast')]; ?>
                <?php foreach ($methods as $val => $label): ?>
                  <option value="<?php echo $val; ?>"<?php echo ($seller->payout_method ?? '') === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label for="payout_currency" class="form-label"><?php echo __('Payout Currency'); ?></label>
              <select class="form-select" id="payout_currency" name="payout_currency">
                <?php if (!empty($currencies)): ?>
                  <?php foreach ($currencies as $cur): ?>
                    <option value="<?php echo esc_entities($cur->code); ?>"<?php echo ($seller->payout_currency ?? 'ZAR') === $cur->code ? ' selected' : ''; ?>>
                      <?php echo esc_entities($cur->code . ' - ' . $cur->name); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
          </div>

          <?php
            $payoutDetails = [];
            if (!empty($seller->payout_details)) {
              $payoutDetails = is_string($seller->payout_details) ? json_decode($seller->payout_details, true) : (array) $seller->payout_details;
            }
          ?>
          <fieldset class="border rounded p-3">
            <legend class="w-auto px-2 fs-6 fw-semibold"><?php echo __('Bank Details'); ?></legend>
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="account_name" class="form-label"><?php echo __('Account Name'); ?></label>
                <input type="text" class="form-control" id="account_name" name="payout_details[account_name]" value="<?php echo esc_entities($payoutDetails['account_name'] ?? ''); ?>">
              </div>
              <div class="col-md-6">
                <label for="account_number" class="form-label"><?php echo __('Account Number'); ?></label>
                <input type="text" class="form-control" id="account_number" name="payout_details[account_number]" value="<?php echo esc_entities($payoutDetails['account_number'] ?? ''); ?>">
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <label for="bank_name" class="form-label"><?php echo __('Bank Name'); ?></label>
                <input type="text" class="form-control" id="bank_name" name="payout_details[bank_name]" value="<?php echo esc_entities($payoutDetails['bank_name'] ?? ''); ?>">
              </div>
              <div class="col-md-6">
                <label for="branch_code" class="form-label"><?php echo __('Branch Code'); ?></label>
                <input type="text" class="form-control" id="branch_code" name="payout_details[branch_code]" value="<?php echo esc_entities($payoutDetails['branch_code'] ?? ''); ?>">
              </div>
            </div>
          </fieldset>
        </div>
      </div>

      <!-- Sectors -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><?php echo __('Sectors'); ?></div>
        <div class="card-body">
          <?php
            $sellerSectors = [];
            if (!empty($seller->sectors)) {
              $sellerSectors = is_string($seller->sectors) ? json_decode($seller->sectors, true) : (array) $seller->sectors;
            }
          ?>
          <?php $sectorOptions = ['gallery' => __('Gallery'), 'museum' => __('Museum'), 'archive' => __('Archive'), 'library' => __('Library'), 'dam' => __('Digital Asset Management')]; ?>
          <?php foreach ($sectorOptions as $val => $label): ?>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" name="sectors[]" value="<?php echo $val; ?>" id="sector-<?php echo $val; ?>"<?php echo (is_array($sellerSectors) && in_array($val, $sellerSectors)) ? ' checked' : ''; ?>>
              <label class="form-check-label" for="sector-<?php echo $val; ?>"><?php echo $label; ?></label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'dashboard']); ?>" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Dashboard'); ?>
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save me-1"></i> <?php echo __('Save Profile'); ?>
        </button>
      </div>

    </form>

  </div>
</div>

<?php end_slot(); ?>

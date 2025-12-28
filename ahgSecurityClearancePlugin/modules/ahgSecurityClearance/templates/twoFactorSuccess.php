<?php
/**
 * Two-Factor Authentication Template.
 */
?>

<div class="row justify-content-center mt-5">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header bg-danger text-white">
        <h4 class="mb-0">
          <i class="fas fa-shield-alt"></i> <?php echo __('Two-Factor Authentication Required') ?>
        </h4>
      </div>
      <div class="card-body">
        <div class="alert alert-warning">
          <i class="fas fa-exclamation-triangle"></i>
          <?php echo __('Your clearance level requires two-factor authentication to access classified materials.') ?>
        </div>

        <?php if ($clearance): ?>
        <p>
          <strong><?php echo __('Your Clearance:') ?></strong>
          <span class="badge" style="background-color: <?php echo $clearance->color ?>">
            <?php echo esc_entities($clearance->name) ?>
          </span>
        </p>
        <?php endif ?>

        <form action="/security/2fa/verify" method="post">
          <input type="hidden" name="return" value="<?php echo esc_entities($returnUrl) ?>">
          
          <div class="mb-4">
            <label class="form-label"><?php echo __('Enter Verification Code') ?></label>
            <input type="text" name="code" class="form-control form-control-lg text-center" 
                   maxlength="6" pattern="\d{6}" placeholder="000000" required autofocus
                   style="font-size: 2rem; letter-spacing: 0.5rem;">
            <div class="form-text">
              <?php echo __('Enter the 6-digit code from your authenticator app.') ?>
            </div>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="fas fa-check"></i> <?php echo __('Verify') ?>
            </button>
          </div>
        </form>

        <hr>

        <div class="text-center">
          <p class="text-muted small">
            <?php echo __("Don't have an authenticator app set up?") ?>
          </p>
          <a href="#" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-envelope"></i> <?php echo __('Send code via email') ?>
          </a>
        </div>
      </div>
    </div>

    <div class="text-center mt-3">
      <a href="/" class="text-muted"><?php echo __('Return to Home') ?></a>
    </div>
  </div>
</div>

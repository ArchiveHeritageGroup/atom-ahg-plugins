<?php
/**
 * Two-Factor Authentication Setup Template.
 *
 * Shows QR code for enrollment with an authenticator app (Google Authenticator,
 * Authy, Microsoft Authenticator, etc.). User must enter a valid code to confirm.
 */
$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';
?>

<div class="row justify-content-center mt-5">
  <div class="col-md-7">
    <div class="card">
      <div class="card-header bg-primary text-white">
        <h4 class="mb-0">
          <i class="fas fa-mobile-alt"></i> <?php echo __('Set Up Two-Factor Authentication') ?>
        </h4>
      </div>
      <div class="card-body">

        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i>
          <?php echo __('Your security clearance requires two-factor authentication. Follow the steps below to set up your authenticator app.') ?>
        </div>

        <h5><?php echo __('Step 1: Scan the QR Code') ?></h5>
        <p class="text-muted"><?php echo __('Open your authenticator app (Google Authenticator, Authy, or Microsoft Authenticator) and scan this QR code:') ?></p>

        <div class="text-center my-4">
          <img src="<?php echo esc_entities($qrCodeUrl) ?>" alt="QR Code" class="border rounded p-2" style="max-width: 220px;">
        </div>

        <div class="mb-4">
          <h6><?php echo __('Or enter this key manually:') ?></h6>
          <div class="input-group">
            <input type="text" class="form-control font-monospace text-center" value="<?php echo esc_entities($secret) ?>" readonly id="totp-secret">
            <button class="btn btn-outline-secondary" type="button" id="btn-copy-secret" title="Copy to clipboard">
              <i class="fas fa-copy"></i>
            </button>
          </div>
        </div>

        <hr>

        <h5><?php echo __('Step 2: Enter Verification Code') ?></h5>
        <p class="text-muted"><?php echo __('Enter the 6-digit code shown in your authenticator app to confirm setup:') ?></p>

        <form action="/security/2fa/confirm" method="post">
          <input type="hidden" name="return" value="<?php echo esc_entities($returnUrl ?? '/') ?>">
          <?php echo \AtomFramework\Services\CsrfService::renderHiddenField() ?>

          <div class="row justify-content-center">
            <div class="col-md-6">
              <div class="mb-3">
                <input type="text" name="code" class="form-control form-control-lg text-center"
                       maxlength="6" pattern="\d{6}" placeholder="000000" required
                       autocomplete="one-time-code" inputmode="numeric"
                       style="font-size: 2rem; letter-spacing: 0.5rem;">
              </div>
              <div class="d-grid">
                <button type="submit" class="btn btn-success btn-lg">
                  <i class="fas fa-check-circle"></i> <?php echo __('Confirm & Activate') ?>
                </button>
              </div>
            </div>
          </div>
        </form>

      </div>
    </div>

    <div class="text-center mt-3">
      <a href="/" class="text-muted"><?php echo __('Cancel and return home') ?></a>
    </div>
  </div>
</div>

<script <?php echo $nonceAttr ?>>
document.getElementById('btn-copy-secret').addEventListener('click', function() {
  var secret = document.getElementById('totp-secret');
  secret.select();
  navigator.clipboard.writeText(secret.value).then(function() {
    document.getElementById('btn-copy-secret').innerHTML = '<i class="fas fa-check"></i>';
    setTimeout(function() {
      document.getElementById('btn-copy-secret').innerHTML = '<i class="fas fa-copy"></i>';
    }, 2000);
  });
});
</script>

<?php
/**
 * Two-Factor Authentication Verification Template.
 *
 * Shown when classified access requires 2FA. User enters a 6-digit
 * code from their authenticator app (TOTP) or email fallback.
 */
$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';
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

        <?php if (isset($clearance) && $clearance): ?>
        <p>
          <strong><?php echo __('Your Clearance:') ?></strong>
          <span class="badge" style="background-color: <?php echo esc_entities($clearance->color ?? '#666') ?>">
            <?php echo esc_entities($clearance->name ?? '') ?>
          </span>
        </p>
        <?php endif ?>

        <?php if (!empty($hasPasskeys)): ?>
        <div class="d-grid gap-2 mb-3">
          <button type="button" class="btn btn-success btn-lg" id="wa-verify-btn">
            <i class="fas fa-fingerprint"></i> <?php echo __('Verify with a passkey') ?>
          </button>
          <div id="wa-verify-status" class="small text-center"></div>
        </div>
        <?php if (!empty($isEnrolled)): ?>
        <div class="text-center text-muted small mb-2">— <?php echo __('or') ?> —</div>
        <?php endif ?>
        <?php endif ?>

        <?php if (!empty($isEnrolled)): ?>
        <form action="/security/2fa/verify" method="post" id="2fa-form">
          <input type="hidden" name="return" value="<?php echo esc_entities($returnUrl ?? '/') ?>">
          <?php echo \AtomFramework\Services\CsrfService::renderHiddenField() ?>

          <div class="mb-4">
            <label class="form-label"><?php echo __('Enter Verification Code') ?></label>
            <input type="text" name="code" class="form-control form-control-lg text-center"
                   maxlength="6" pattern="\d{6}" placeholder="000000" required autofocus
                   autocomplete="one-time-code" inputmode="numeric"
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
            <?php echo __("Don't have your authenticator app handy?") ?>
          </p>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-send-email">
            <i class="fas fa-envelope"></i> <?php echo __('Send code via email') ?>
          </button>
          <div id="email-status" class="mt-2 small"></div>
        </div>
        <?php endif ?>
      </div>
    </div>

    <div class="text-center mt-3">
      <a href="/" class="text-muted"><?php echo __('Return to Home') ?></a>
    </div>
  </div>
</div>

<script <?php echo $nonceAttr ?>>
var sendEmailBtn = document.getElementById('btn-send-email');
if (sendEmailBtn) sendEmailBtn.addEventListener('click', function() {
  var btn = this;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

  fetch('/security/2fa/send-email', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('input[name="_csrf_token"]').value
    }
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    document.getElementById('email-status').innerHTML =
      '<span class="text-' + (data.success ? 'success' : 'danger') + '">' + data.message + '</span>';
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-envelope"></i> Send code via email';
  })
  .catch(function() {
    document.getElementById('email-status').innerHTML =
      '<span class="text-danger">Failed to send. Use your authenticator app.</span>';
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-envelope"></i> Send code via email';
  });
});
</script>

<?php if (!empty($hasPasskeys)): ?>
<script src="/plugins/ahgSecurityClearancePlugin/web/js/webauthn.js"></script>
<script <?php echo $nonceAttr ?>>
(function () {
  var btn = document.getElementById('wa-verify-btn'),
      status = document.getElementById('wa-verify-status'),
      ret = <?php echo json_encode($returnUrl ?? '/') ?>;
  function show(msg, cls) { status.className = 'small text-center text-' + cls; status.textContent = msg; }
  if (!window.PublicKeyCredential) { show('This browser does not support passkeys.', 'muted'); btn.disabled = true; return; }
  btn.addEventListener('click', function () {
    btn.disabled = true; show('Follow your device prompt…', 'muted');
    // No userId: the server uses the already-authenticated session user.
    window.AhgWebAuthn.authenticate()
      .then(function (ok) {
        if (ok) { show('Verified. Redirecting…', 'success'); window.location.href = ret; }
        else { show('Verification was not completed.', 'danger'); btn.disabled = false; }
      })
      .catch(function (e) { show('Failed: ' + (e && e.message ? e.message : e), 'danger'); btn.disabled = false; });
  });
})();
</script>
<?php endif ?>

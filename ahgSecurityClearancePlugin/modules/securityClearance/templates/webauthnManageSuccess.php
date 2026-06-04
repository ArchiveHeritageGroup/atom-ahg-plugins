<?php $keys = $sf_data->getRaw('passkeys'); $n = sfConfig::get('csp_nonce', ''); $nonce = $n ? ' ' . preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>
<div class="container py-4" style="max-width: 760px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <span class="h2"><i class="fas fa-fingerprint me-2"></i><?php echo __('Passkeys (WebAuthn / FIDO2)'); ?></span>
        <div class="btn-group">
            <?php if ($sf_user->hasCredential('administrator')): ?>
            <a href="/security/2fa/policy" class="btn btn-outline-primary btn-sm"><i class="fas fa-user-shield me-1"></i><?php echo __('MFA policy'); ?></a>
            <?php endif; ?>
            <a href="<?php echo url_for(['module' => 'securityClearance', 'action' => 'twoFactor']); ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('Back to 2FA'); ?></a>
        </div>
    </div>
    <p class="text-muted"><?php echo __('Register a security key, fingerprint, or device passkey as a second factor. Passkeys are phishing-resistant and never leave your device.'); ?></p>

    <?php if ($sf_user->hasFlash('success')): ?><div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div><?php endif; ?>
    <div id="wa-status" class="alert d-none"></div>

    <div class="card mb-4"><div class="card-header fw-bold"><?php echo __('Your passkeys'); ?></div>
    <div class="table-responsive"><table class="table mb-0 align-middle">
        <thead class="table-light"><tr><th><?php echo __('Label'); ?></th><th><?php echo __('Last used'); ?></th><th><?php echo __('Added'); ?></th><th></th></tr></thead>
        <tbody>
        <?php if (empty($keys)): ?>
        <tr><td colspan="4" class="text-center text-muted py-4"><?php echo __('No passkeys registered yet.'); ?></td></tr>
        <?php else: foreach ($keys as $k): ?>
        <tr>
            <td><i class="fas fa-key text-success me-1"></i><?php echo htmlspecialchars($k->label); ?></td>
            <td class="small text-muted"><?php echo $k->last_used_at ? htmlspecialchars(substr($k->last_used_at, 0, 16)) : '<span class="text-muted">'.__('never').'</span>'; ?></td>
            <td class="small text-muted"><?php echo htmlspecialchars(substr((string) $k->created_at, 0, 10)); ?></td>
            <td class="text-end">
                <form method="post" action="<?php echo url_for(['module' => 'securityClearance', 'action' => 'webauthnDelete', 'id' => $k->id]); ?>" onsubmit="return confirm('<?php echo __('Remove this passkey?'); ?>');">
                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table></div></div>

    <div class="card"><div class="card-body">
        <label class="form-label small"><?php echo __('Name this passkey (e.g. "YubiKey", "iPhone")'); ?></label>
        <div class="input-group">
            <input type="text" id="wa-label" class="form-control" placeholder="<?php echo __('Passkey'); ?>" maxlength="80">
            <button type="button" id="wa-register" class="btn btn-primary"><i class="fas fa-plus me-1"></i><?php echo __('Add a passkey'); ?></button>
        </div>
        <div class="form-text"><?php echo __('Your browser will prompt you to use a security key or device authenticator.'); ?></div>
    </div></div>
</div>

<script src="/plugins/ahgSecurityClearancePlugin/web/js/webauthn.js"></script>
<script<?php echo $nonce; ?>>
(function () {
  var btn = document.getElementById('wa-register'), status = document.getElementById('wa-status');
  function show(msg, cls) { status.className = 'alert alert-' + cls; status.textContent = msg; }
  if (!window.PublicKeyCredential) { show('This browser does not support WebAuthn passkeys.', 'warning'); btn.disabled = true; return; }
  btn.addEventListener('click', function () {
    btn.disabled = true; show('Follow your browser/device prompt…', 'info');
    window.AhgWebAuthn.register(document.getElementById('wa-label').value)
      .then(function (ok) {
        if (ok) { show('Passkey registered. Reloading…', 'success'); setTimeout(function () { location.reload(); }, 900); }
        else { show('Registration was not completed.', 'danger'); btn.disabled = false; }
      })
      .catch(function (e) { show('Registration failed: ' + (e && e.message ? e.message : e), 'danger'); btn.disabled = false; });
  });
})();
</script>

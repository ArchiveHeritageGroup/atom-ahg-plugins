<?php
$roles = $sf_data->getRaw('roles');
$required = array_map('intval', $sf_data->getRaw('requiredRoles'));
$descriptions = [
    99 => 'All authenticated users (everyone who can log in)',
    100 => 'Full administrators',
    101 => 'Editors — create/edit descriptions',
    102 => 'Contributors',
    103 => 'Translators',
];
?>
<div class="container py-4" style="max-width: 720px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <span class="h2"><i class="fas fa-user-shield me-2"></i><?php echo __('Per-role MFA policy'); ?></span>
        <a href="<?php echo url_for(['module' => 'securityClearance', 'action' => 'twoFactor']); ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('Back to 2FA'); ?></a>
    </div>
    <p class="text-muted"><?php echo __('Require members of the selected roles to complete two-factor authentication (TOTP or a passkey) before they can use the site. Users without a second factor are sent to enrol first.'); ?></p>

    <?php if ($sf_user->hasFlash('success')): ?><div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div><?php endif; ?>

    <?php // Dedicated POST route (any() 404s on POST); clean URL avoids the /index.php 301. ?>
    <form method="post" action="/security/2fa/policy/save">
        <div class="card mb-3"><div class="card-body">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" role="switch" id="mfa_enabled" name="mfa_enabled" value="1" <?php echo $enabled ? 'checked' : ''; ?>>
                <label class="form-check-label fw-bold" for="mfa_enabled"><?php echo __('Enforce per-role MFA'); ?></label>
            </div>
            <div class="form-text"><?php echo __('Master switch. When off, no role is gated regardless of the selections below.'); ?></div>
        </div></div>

        <div class="card mb-3"><div class="card-header fw-bold"><?php echo __('Roles that require MFA'); ?></div>
        <ul class="list-group list-group-flush">
            <?php foreach ($roles as $r): $id = (int) $r->id; ?>
            <li class="list-group-item">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="role_<?php echo $id; ?>" name="roles[]" value="<?php echo $id; ?>" <?php echo in_array($id, $required, true) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="role_<?php echo $id; ?>">
                        <strong><?php echo htmlspecialchars($r->name ?: ('group ' . $id)); ?></strong>
                        <span class="text-muted small d-block"><?php echo htmlspecialchars($descriptions[$id] ?? ''); ?></span>
                    </label>
                </div>
            </li>
            <?php endforeach; ?>
        </ul></div>

        <div class="alert alert-warning small"><i class="fas fa-exclamation-triangle me-1"></i><?php echo __('Tip: enrol your own passkey or authenticator before enabling this for the administrator role, or you will be redirected to set one up on your next request.'); ?>
            <a href="<?php echo url_for(['module' => 'securityClearance', 'action' => 'webauthnManage']); ?>"><?php echo __('Manage passkeys'); ?></a>.
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Save policy'); ?></button>
    </form>
</div>

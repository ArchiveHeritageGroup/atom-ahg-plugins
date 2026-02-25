<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Email Settings'); ?> — Admin<?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Email Settings')],
]]); ?>

<h1 class="h3 mb-4"><i class="fas fa-envelope me-2"></i><?php echo __('Email Settings'); ?></h1>

<?php if (!empty($saved)): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-1"></i> <?php echo __('Email settings saved successfully.'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (!empty($testResult)): ?>
  <?php if (!empty($testResult['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <i class="fas fa-check-circle me-1"></i> <?php echo __('Test email sent successfully!'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php else: ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="fas fa-exclamation-circle me-1"></i> <?php echo __('Test email failed:'); ?>
      <?php echo htmlspecialchars($testResult['error'] ?? 'Unknown error', ENT_QUOTES, 'UTF-8'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php $s = $emailSettings; ?>

<div class="row">
  <div class="col-lg-8">

    <!-- SMTP Configuration -->
    <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminEmail']); ?>">
      <input type="hidden" name="form_action" value="save">

      <div class="card mb-4">
        <div class="card-header fw-semibold">
          <i class="fas fa-server me-1"></i> <?php echo __('SMTP Configuration'); ?>
        </div>
        <div class="card-body">

          <div class="form-check form-switch mb-3">
            <input type="hidden" name="smtp_enabled" value="0">
            <input class="form-check-input" type="checkbox" id="smtp_enabled" name="smtp_enabled" value="1" <?php echo !empty($s['smtp_enabled']) && '0' !== $s['smtp_enabled'] ? 'checked' : ''; ?>>
            <label class="form-check-label fw-semibold" for="smtp_enabled"><?php echo __('Enable SMTP'); ?></label>
            <div class="form-text"><?php echo __('When disabled, emails will use PHP mail() which may not work on all servers.'); ?></div>
          </div>

          <hr>

          <div class="row">
            <div class="col-md-8 mb-3">
              <label for="smtp_host" class="form-label fw-semibold"><?php echo __('SMTP Host'); ?></label>
              <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($s['smtp_host'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="smtp.gmail.com">
            </div>
            <div class="col-md-4 mb-3">
              <label for="smtp_port" class="form-label fw-semibold"><?php echo __('Port'); ?></label>
              <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($s['smtp_port'] ?? '587', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>

          <div class="mb-3">
            <label for="smtp_encryption" class="form-label fw-semibold"><?php echo __('Encryption'); ?></label>
            <select class="form-select" id="smtp_encryption" name="smtp_encryption">
              <option value="tls" <?php echo 'tls' === ($s['smtp_encryption'] ?? '') ? 'selected' : ''; ?>>TLS (<?php echo __('Recommended'); ?>)</option>
              <option value="ssl" <?php echo 'ssl' === ($s['smtp_encryption'] ?? '') ? 'selected' : ''; ?>>SSL</option>
              <option value="none" <?php echo 'none' === ($s['smtp_encryption'] ?? '') ? 'selected' : ''; ?>><?php echo __('None'); ?></option>
            </select>
          </div>

          <div class="mb-3">
            <label for="smtp_username" class="form-label fw-semibold"><?php echo __('Username'); ?></label>
            <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars($s['smtp_username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="user@gmail.com">
          </div>

          <div class="mb-3">
            <label for="smtp_password" class="form-label fw-semibold"><?php echo __('Password'); ?></label>
            <input type="password" class="form-control" id="smtp_password" name="smtp_password" value="<?php echo htmlspecialchars($s['smtp_password'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="App password or SMTP password">
            <div class="form-text"><?php echo __('For Gmail, use an App Password (not your account password).'); ?></div>
          </div>

          <hr>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="smtp_from_email" class="form-label fw-semibold"><?php echo __('From Email'); ?></label>
              <input type="email" class="form-control" id="smtp_from_email" name="smtp_from_email" value="<?php echo htmlspecialchars($s['smtp_from_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="noreply@example.com">
            </div>
            <div class="col-md-6 mb-3">
              <label for="smtp_from_name" class="form-label fw-semibold"><?php echo __('From Name'); ?></label>
              <input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name" value="<?php echo htmlspecialchars($s['smtp_from_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="AtoM Registry">
            </div>
          </div>

        </div>
        <div class="card-footer text-end">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i> <?php echo __('Save Settings'); ?>
          </button>
        </div>
      </div>
    </form>

  </div>

  <div class="col-lg-4">

    <!-- Test Email -->
    <div class="card mb-4">
      <div class="card-header fw-semibold">
        <i class="fas fa-paper-plane me-1"></i> <?php echo __('Send Test Email'); ?>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminEmail']); ?>">
          <input type="hidden" name="form_action" value="test">
          <div class="mb-3">
            <label for="test_email" class="form-label small fw-semibold"><?php echo __('Recipient'); ?></label>
            <input type="email" class="form-control" id="test_email" name="test_email" required placeholder="you@example.com" value="<?php echo htmlspecialchars(sfContext::getInstance()->getUser()->getAttribute('user_email', ''), ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <button type="submit" class="btn btn-outline-primary w-100">
            <i class="fas fa-paper-plane me-1"></i> <?php echo __('Send Test'); ?>
          </button>
        </form>
      </div>
    </div>

    <!-- Info -->
    <div class="card bg-light border-0">
      <div class="card-body">
        <h6 class="fw-semibold mb-2"><i class="fas fa-info-circle text-info me-1"></i> <?php echo __('Gmail Setup'); ?></h6>
        <ol class="small text-muted mb-0 ps-3">
          <li>Go to Google Account > Security</li>
          <li>Enable 2-Step Verification</li>
          <li>Go to App Passwords</li>
          <li>Generate a new App Password</li>
          <li>Use that as the SMTP password</li>
        </ol>
        <hr>
        <h6 class="fw-semibold mb-2"><?php echo __('Common SMTP Settings'); ?></h6>
        <div class="small text-muted">
          <strong>Gmail:</strong> smtp.gmail.com : 587 (TLS)<br>
          <strong>Outlook:</strong> smtp.office365.com : 587 (TLS)<br>
          <strong>Yahoo:</strong> smtp.mail.yahoo.com : 587 (TLS)
        </div>
      </div>
    </div>

  </div>
</div>

<?php end_slot(); ?>

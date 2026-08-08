<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Email'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php if (!empty($tableMissing)) { ?>
    <div class="alert alert-danger">
      <?php echo __('The email_setting table is missing. Load ahgCorePlugin/database/install.sql and reload this page.'); ?>
    </div>
  <?php } ?>

  <p class="text-muted">
    <?php echo __('Outgoing mail for password resets and notifications. Until this is enabled and a host is set, password reset stores a token and sends nothing.'); ?>
  </p>

  <form method="post" action="<?php echo url_for(['module' => 'settings', 'action' => 'email']); ?>">

    <div class="form-check form-switch mb-4">
      <input class="form-check-input" type="checkbox" role="switch" id="smtp_enabled" name="smtp_enabled" value="1"
        <?php echo '1' === ($settings['smtp_enabled'] ?? '0') ? ' checked' : ''; ?>>
      <label class="form-check-label" for="smtp_enabled"><?php echo __('Send email from this site'); ?></label>
    </div>

    <div class="row">
      <div class="col-md-8 mb-3">
        <label class="form-label" for="smtp_host"><?php echo __('SMTP host'); ?></label>
        <input class="form-control" type="text" id="smtp_host" name="smtp_host"
               value="<?php echo htmlspecialchars($settings['smtp_host'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label" for="smtp_port"><?php echo __('Port'); ?></label>
        <input class="form-control" type="number" id="smtp_port" name="smtp_port"
               value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587', ENT_QUOTES, 'UTF-8'); ?>">
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label" for="smtp_encryption"><?php echo __('Encryption'); ?></label>
      <select class="form-select" id="smtp_encryption" name="smtp_encryption">
        <?php foreach (['tls' => 'TLS', 'ssl' => 'SSL', '' => __('None')] as $value => $label) { ?>
          <option value="<?php echo $value; ?>"<?php echo ($settings['smtp_encryption'] ?? 'tls') === $value ? ' selected' : ''; ?>><?php echo $label; ?></option>
        <?php } ?>
      </select>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label" for="smtp_username"><?php echo __('Username'); ?></label>
        <input class="form-control" type="text" id="smtp_username" name="smtp_username" autocomplete="off"
               value="<?php echo htmlspecialchars($settings['smtp_username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label" for="smtp_password"><?php echo __('Password'); ?></label>
        <?php // Never rendered back to the browser. Left blank, and a blank value
              // on save keeps whatever is stored rather than clearing it. ?>
        <input class="form-control" type="password" id="smtp_password" name="smtp_password" autocomplete="new-password" value="">
        <div class="form-text">
          <?php echo '' !== ($settings['smtp_password'] ?? '')
              ? __('A password is stored. Leave blank to keep it.')
              : __('No password stored.'); ?>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label" for="smtp_from_email"><?php echo __('From address'); ?></label>
        <input class="form-control" type="email" id="smtp_from_email" name="smtp_from_email"
               value="<?php echo htmlspecialchars($settings['smtp_from_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label" for="smtp_from_name"><?php echo __('From name'); ?></label>
        <input class="form-control" type="text" id="smtp_from_name" name="smtp_from_name"
               value="<?php echo htmlspecialchars($settings['smtp_from_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
      </div>
    </div>

    <input type="submit" class="btn atom-btn-outline-success" value="<?php echo __('Save'); ?>">

  </form>

<?php end_slot(); ?>

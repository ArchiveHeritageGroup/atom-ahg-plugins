<?php decorate_with('layout_1col'); ?>

<?php
  $p   = $sf_data->getRaw('partner');
  $cfg = ($p && is_array($p->endpoint_config ?? null)) ? $p->endpoint_config : [];
  $vendors = $sf_data->getRaw('vendors');
  $endpointType = $p->endpoint_type ?? 'SFTP';
  $cv = function ($key, $default = '') use ($cfg) { return esc_entities($cfg[$key] ?? $default); };
  // For a new partner default the operational flags to a safe state (TEST + active + ack).
  $isNew = !$p;
  $chk = function ($field, $isNew) use ($p) {
      if ($isNew) { return 'checked'; }
      return !empty($p->{$field}) ? 'checked' : '';
  };
?>

<?php slot('title'); ?>
  <h1><?php echo $p ? __('Edit Trading Partner') : __('Add Trading Partner'); ?></h1>
<?php end_slot(); ?>

<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<form method="post" action="<?php echo url_for(['module' => 'tradingPartner', 'action' => 'edit', 'id' => ($p->id ?? null)]); ?>">

  <div class="card mb-4">
    <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-handshake me-2"></i><?php echo __('Partner Identity'); ?></h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label required"><?php echo __('EDI Partner Code'); ?></label>
          <input type="text" name="edi_partner_code" class="form-control" maxlength="20" required value="<?php echo esc_entities($p->edi_partner_code ?? ''); ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label"><?php echo __('EDI Type'); ?></label>
          <select name="edi_type" class="form-select">
            <?php foreach (['EANCOM', 'X12', 'UN/EDIFACT', 'CUSTOM'] as $t): ?>
              <option value="<?php echo $t; ?>" <?php echo ($p->edi_type ?? 'EANCOM') === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label"><?php echo __('Message Profile'); ?></label>
          <select name="message_profile" class="form-select">
            <?php foreach (['EANCOM_S93', 'EANCOM_S94', 'X12_850', 'CUSTOM'] as $mp): ?>
              <option value="<?php echo $mp; ?>" <?php echo ($p->message_profile ?? 'EANCOM_S93') === $mp ? 'selected' : ''; ?>><?php echo $mp; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <?php if (!empty($vendors)): ?>
        <div class="mb-3">
          <label class="form-label"><?php echo __('Vendor'); ?></label>
          <select name="vendor_id" class="form-select">
            <option value=""><?php echo __('— none —'); ?></option>
            <?php foreach ($vendors as $v): ?>
              <option value="<?php echo (int) $v->id; ?>" <?php echo (int) ($p->vendor_id ?? 0) === (int) $v->id ? 'selected' : ''; ?>><?php echo esc_entities($v->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header bg-light"><h5 class="mb-0"><?php echo __('Transport / Endpoint'); ?></h5></div>
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label"><?php echo __('Endpoint Type'); ?></label>
        <select name="endpoint_type" id="endpoint_type" class="form-select">
          <?php foreach (['SFTP', 'AS2', 'HTTP_HTTPS', 'EMAIL', 'MANUAL'] as $et): ?>
            <option value="<?php echo $et; ?>" <?php echo $endpointType === $et ? 'selected' : ''; ?>><?php echo $et; ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="ep-section" data-ep="SFTP">
        <div class="row">
          <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('SFTP Host'); ?></label><input type="text" name="cfg_host" class="form-control" value="<?php echo $cv('host'); ?>"></div>
          <div class="col-md-2 mb-3"><label class="form-label"><?php echo __('Port'); ?></label><input type="number" name="cfg_port" class="form-control" value="<?php echo $cv('port', '22'); ?>"></div>
          <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Username'); ?></label><input type="text" name="cfg_username" class="form-control" value="<?php echo $cv('username'); ?>"></div>
          <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Password'); ?></label><input type="password" name="cfg_password" class="form-control" value="<?php echo $cv('password'); ?>"></div>
          <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Remote Path'); ?></label><input type="text" name="cfg_path" class="form-control" value="<?php echo $cv('path'); ?>"></div>
        </div>
      </div>

      <div class="ep-section" data-ep="AS2">
        <div class="row">
          <div class="col-md-8 mb-3"><label class="form-label"><?php echo __('AS2 URL'); ?></label><input type="url" name="cfg_as2_url" class="form-control" value="<?php echo $cv('as2_url'); ?>"></div>
          <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('AS2 Receiver ID'); ?></label><input type="text" name="cfg_as2_receiver_id" class="form-control" value="<?php echo $cv('as2_receiver_id'); ?>"></div>
        </div>
      </div>

      <div class="ep-section" data-ep="HTTP_HTTPS">
        <div class="mb-3"><label class="form-label"><?php echo __('HTTP/HTTPS URL'); ?></label><input type="url" name="cfg_url" class="form-control" value="<?php echo $cv('url'); ?>"></div>
      </div>

      <div class="ep-section" data-ep="EMAIL">
        <div class="row">
          <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('SMTP Host'); ?></label><input type="text" name="cfg_smtp_host" class="form-control" value="<?php echo $cv('smtp_host'); ?>"></div>
          <div class="col-md-2 mb-3"><label class="form-label"><?php echo __('Port'); ?></label><input type="number" name="cfg_smtp_port" class="form-control" value="<?php echo $cv('smtp_port', '587'); ?>"></div>
          <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Send To'); ?></label><input type="email" name="cfg_smtp_to" class="form-control" value="<?php echo $cv('smtp_to'); ?>"></div>
          <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('From'); ?></label><input type="email" name="cfg_smtp_from" class="form-control" value="<?php echo $cv('smtp_from'); ?>"></div>
        </div>
      </div>

      <div class="ep-section" data-ep="MANUAL">
        <p class="text-muted mb-0"><?php echo __('Manual mode writes EDI files to the outbound directory for batch pickup. No connection settings required.'); ?></p>
      </div>

      <div class="row mt-2">
        <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Outbound Directory'); ?></label><input type="text" name="outbound_directory" class="form-control" value="<?php echo esc_entities($p->outbound_directory ?? '/outbox/'); ?>"></div>
        <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Inbound Directory'); ?></label><input type="text" name="inbound_directory" class="form-control" value="<?php echo esc_entities($p->inbound_directory ?? '/inbox/'); ?>"></div>
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <div class="form-check"><input type="checkbox" class="form-check-input" id="test_mode" name="test_mode" value="1" <?php echo $chk('test_mode', $isNew); ?>><label class="form-check-label" for="test_mode"><?php echo __('Test mode (do not transmit)'); ?></label></div>
      <div class="form-check"><input type="checkbox" class="form-check-input" id="acknowledgement_required" name="acknowledgement_required" value="1" <?php echo $chk('acknowledgement_required', $isNew); ?>><label class="form-check-label" for="acknowledgement_required"><?php echo __('Acknowledgement required'); ?></label></div>
      <div class="form-check"><input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?php echo $chk('is_active', $isNew); ?>><label class="form-check-label" for="is_active"><?php echo __('Active'); ?></label></div>
      <div class="mt-3"><label class="form-label"><?php echo __('Notes'); ?></label><textarea name="notes" class="form-control" rows="2"><?php echo esc_entities($p->notes ?? ''); ?></textarea></div>
    </div>
  </div>

  <div class="d-flex justify-content-between">
    <a href="<?php echo url_for(['module' => 'tradingPartner', 'action' => 'index']); ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i><?php echo __('Cancel'); ?></a>
    <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i><?php echo __('Save'); ?></button>
  </div>
</form>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>>
  (function () {
    var sel = document.getElementById('endpoint_type');
    function sync() {
      var val = sel.value;
      document.querySelectorAll('.ep-section').forEach(function (s) {
        s.style.display = (s.getAttribute('data-ep') === val) ? '' : 'none';
      });
    }
    sel.addEventListener('change', sync);
    sync();
  })();
</script>

<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fa fa-cog"></i> <?php echo __('RIC Sync Configuration'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'index']); ?>"><?php echo __('RIC Dashboard'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Configuration'); ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
<?php endif; ?>

<form method="post">
  <div class="row g-4">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Fuseki Connection'); ?></h5></div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Endpoint URL'); ?></label>
            <input type="text" class="form-control" name="config[fuseki_endpoint]" value="<?php echo htmlspecialchars($config['fuseki_endpoint'] ?? ''); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Username'); ?></label>
            <input type="text" class="form-control" name="config[fuseki_username]" value="<?php echo htmlspecialchars($config['fuseki_username'] ?? ''); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Password'); ?></label>
            <input type="password" class="form-control" name="config[fuseki_password]" value="<?php echo htmlspecialchars($config['fuseki_password'] ?? ''); ?>">
          </div>
          <button type="button" class="btn btn-outline-secondary" onclick="testConnection()"><i class="fa fa-plug"></i> <?php echo __('Test Connection'); ?></button>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Sync Settings'); ?></h5></div>
        <div class="card-body">
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="config[sync_enabled]" value="1" <?php echo ($config['sync_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
            <label class="form-check-label"><?php echo __('Enable automatic sync'); ?></label>
          </div>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="config[queue_enabled]" value="1" <?php echo ($config['queue_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
            <label class="form-check-label"><?php echo __('Use async queue'); ?></label>
          </div>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="config[cascade_delete]" value="1" <?php echo ($config['cascade_delete'] ?? '1') === '1' ? 'checked' : ''; ?>>
            <label class="form-check-label"><?php echo __('Cascade delete references'); ?></label>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Batch Size'); ?></label>
            <input type="number" class="form-control" name="config[batch_size]" value="<?php echo htmlspecialchars($config['batch_size'] ?? '100'); ?>">
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-between mt-4">
    <a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'index']); ?>" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> <?php echo __('Back'); ?></a>
    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo __('Save Configuration'); ?></button>
  </div>
</form>

<?php end_slot(); ?>

<?php slot('after-content'); ?>
<script <?php echo __(sfConfig::get('csp_nonce', '')); ?>>
function testConnection() {
  const btn = event.target;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Testing...';
  
  fetch('<?php echo url_for(['module' => 'ricDashboard', 'action' => 'ajaxStats']); ?>')
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-plug"></i> Test Connection';
      if (data.fuseki_status.online) {
        alert('✓ Connection successful!\nTriple count: ' + data.fuseki_status.triple_count);
      } else {
        alert('✗ Connection failed\n' + (data.fuseki_status.error || 'Could not reach Fuseki'));
      }
    });
}
</script>
<?php end_slot(); ?>

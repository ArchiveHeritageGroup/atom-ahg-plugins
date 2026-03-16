<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Re-link Instance'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Instances'), 'url' => url_for(['module' => 'registry', 'action' => 'myInstitutionInstances'])],
  ['label' => __('Re-link')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-8">

    <h1 class="h3 mb-4">
      <i class="fas fa-link me-2"></i>
      <?php echo __('Re-link Instance: %1%', ['%1%' => htmlspecialchars($instance->name ?? '', ENT_QUOTES, 'UTF-8')]); ?>
    </h1>

    <?php if (isset($errors) && count($errors) > 0): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="card mb-4">
      <div class="card-header fw-semibold"><i class="fas fa-server me-2 text-primary"></i><?php echo __('Instance Details'); ?></div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4"><?php echo __('Name'); ?></dt>
          <dd class="col-sm-8"><?php echo htmlspecialchars($instance->name ?? '', ENT_QUOTES, 'UTF-8'); ?></dd>
          <dt class="col-sm-4"><?php echo __('URL'); ?></dt>
          <dd class="col-sm-8"><?php echo htmlspecialchars($instance->url ?? '-', ENT_QUOTES, 'UTF-8'); ?></dd>
          <dt class="col-sm-4"><?php echo __('Type'); ?></dt>
          <dd class="col-sm-8"><?php echo htmlspecialchars(ucfirst($instance->instance_type ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
        </dl>
      </div>
    </div>

    <form method="post">
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-building me-2 text-success"></i><?php echo __('Select Institution'); ?></div>
        <div class="card-body">
          <div class="mb-3">
            <input type="text" class="form-control mb-3" id="inst-filter" placeholder="<?php echo __('Filter institutions...'); ?>">
          </div>
          <div class="list-group" id="inst-list" style="max-height: 400px; overflow-y: auto;">
            <?php foreach ($institutions as $inst): ?>
            <label class="list-group-item list-group-item-action d-flex align-items-center inst-item">
              <input type="radio" name="institution_id" value="<?php echo (int) $inst->id; ?>" class="form-check-input me-3" required>
              <div>
                <strong><?php echo htmlspecialchars($inst->name, ENT_QUOTES, 'UTF-8'); ?></strong>
                <?php if (!empty($inst->city) || !empty($inst->country)): ?>
                  <br><small class="text-muted"><?php echo htmlspecialchars(implode(', ', array_filter([$inst->city ?? '', $inst->country ?? ''])), ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
              </div>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionInstances']); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-link me-1"></i> <?php echo __('Link to Institution'); ?></button>
      </div>
    </form>

  </div>
</div>

<script <?php echo $na; ?>>
document.getElementById('inst-filter').addEventListener('input', function() {
  var q = this.value.toLowerCase();
  document.querySelectorAll('.inst-item').forEach(function(el) {
    el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});
</script>

<?php end_slot(); ?>

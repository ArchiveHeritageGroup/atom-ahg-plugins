<?php decorate_with('layout_1col') ?>

<?php slot('title') ?>
  <h1><?php echo __('Audit Trail Settings') ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success"><?php echo $sf_user->getFlash('notice') ?></div>
<?php endif; ?>

<?php 
  $settingsArray = [];
  foreach ($settings as $s) {
    $settingsArray[$s->setting_key] = $s->typed_value;
  }
?>

<form method="post">
  <section class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><?php echo __('General Settings') ?></h5></div>
    <div class="card-body">
      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" id="audit_enabled" name="audit_enabled" value="1" <?php echo ($settingsArray['audit_enabled'] ?? false) ? 'checked' : '' ?>>
        <label class="form-check-label" for="audit_enabled"><strong><?php echo __('Enable Audit Logging') ?></strong></label>
      </div>
    </div>
  </section>

  <section class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><?php echo __('What to Log') ?></h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <?php foreach (['audit_views' => 'Log View Actions', 'audit_searches' => 'Log Search Queries', 'audit_downloads' => 'Log File Downloads'] as $key => $label): ?>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="<?php echo $key ?>" name="<?php echo $key ?>" value="1" <?php echo ($settingsArray[$key] ?? false) ? 'checked' : '' ?>>
            <label class="form-check-label" for="<?php echo $key ?>"><?php echo __($label) ?></label>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="col-md-6">
          <?php foreach (['audit_api_requests' => 'Log API Requests', 'audit_authentication' => 'Log Authentication Events', 'audit_sensitive_access' => 'Log Classified Access'] as $key => $label): ?>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="<?php echo $key ?>" name="<?php echo $key ?>" value="1" <?php echo ($settingsArray[$key] ?? false) ? 'checked' : '' ?>>
            <label class="form-check-label" for="<?php echo $key ?>"><?php echo __($label) ?></label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <section class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><?php echo __('Privacy Settings') ?></h5></div>
    <div class="card-body">
      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" id="audit_mask_sensitive" name="audit_mask_sensitive" value="1" <?php echo ($settingsArray['audit_mask_sensitive'] ?? false) ? 'checked' : '' ?>>
        <label class="form-check-label" for="audit_mask_sensitive"><?php echo __('Mask Sensitive Data') ?></label>
      </div>
      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" id="audit_ip_anonymize" name="audit_ip_anonymize" value="1" <?php echo ($settingsArray['audit_ip_anonymize'] ?? false) ? 'checked' : '' ?>>
        <label class="form-check-label" for="audit_ip_anonymize"><?php echo __('Anonymize IP Addresses (POPIA)') ?></label>
      </div>
    </div>
  </section>

  <div class="d-flex justify-content-between">
    <a href="<?php echo url_for(['module' => 'ahgAuditTrailPlugin', 'action' => 'browse']) ?>" class="btn btn-secondary"><?php echo __('Cancel') ?></a>
    <button type="submit" class="btn btn-primary"><?php echo __('Save Settings') ?></button>
  </div>
</form>
<?php end_slot() ?>

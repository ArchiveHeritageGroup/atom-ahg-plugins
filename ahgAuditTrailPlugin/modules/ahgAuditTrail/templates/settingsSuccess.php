<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-sliders-h me-2"></i><?php echo __('Audit Settings'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if ($sf_user->hasFlash('notice')): ?>
<div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
<?php endif; ?>

<form method="post">
<div class="row">
  <div class="col-md-6">
    <div class="card mb-4">
      <div class="card-header bg-primary text-white">
        <i class="fas fa-power-off me-1"></i><?php echo __('Master Controls'); ?>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="audit_enabled" value="1" id="audit_enabled" <?php echo ($settings['audit_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="audit_enabled"><strong><?php echo __('Enable Audit Logging'); ?></strong></label>
          </div>
          <small class="text-muted"><?php echo __('Master switch - disabling this stops all audit logging'); ?></small>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <i class="fas fa-user-shield me-1"></i><?php echo __('Authentication Events'); ?>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="audit_authentication" value="1" id="audit_authentication" <?php echo ($settings['audit_authentication'] ?? '1') == '1' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="audit_authentication"><?php echo __('Log Authentication Events'); ?></label>
          </div>
          <small class="text-muted"><?php echo __('Login, logout events'); ?></small>
        </div>
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="audit_failed_logins" value="1" id="audit_failed_logins" <?php echo ($settings['audit_failed_logins'] ?? '1') == '1' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="audit_failed_logins"><?php echo __('Log Failed Login Attempts'); ?></label>
          </div>
          <small class="text-muted"><?php echo __('Track unauthorized access attempts'); ?></small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card mb-4">
      <div class="card-header">
        <i class="fas fa-database me-1"></i><?php echo __('Data Operations'); ?>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="audit_creates" value="1" id="audit_creates" <?php echo ($settings['audit_creates'] ?? '1') == '1' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="audit_creates"><?php echo __('Log Create Actions'); ?></label>
          </div>
          <small class="text-muted"><?php echo __('New records created'); ?></small>
        </div>
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="audit_updates" value="1" id="audit_updates" <?php echo ($settings['audit_updates'] ?? '1') == '1' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="audit_updates"><?php echo __('Log Update Actions'); ?></label>
          </div>
          <small class="text-muted"><?php echo __('Records modified'); ?></small>
        </div>
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="audit_deletes" value="1" id="audit_deletes" <?php echo ($settings['audit_deletes'] ?? '1') == '1' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="audit_deletes"><?php echo __('Log Delete Actions'); ?></label>
          </div>
          <small class="text-muted"><?php echo __('Records removed'); ?></small>
        </div>
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="audit_views" value="1" id="audit_views" <?php echo ($settings['audit_views'] ?? '0') == '1' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="audit_views"><?php echo __('Log View Actions'); ?></label>
          </div>
          <small class="text-muted text-warning"><i class="fas fa-exclamation-triangle me-1"></i><?php echo __('Warning: Generates high volume of data'); ?></small>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <i class="fas fa-file-export me-1"></i><?php echo __('Import/Export & Files'); ?>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="audit_imports" value="1" id="audit_imports" <?php echo ($settings['audit_imports'] ?? '1') == '1' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="audit_imports"><?php echo __('Log Import Actions'); ?></label>
          </div>
          <small class="text-muted"><?php echo __('CSV, EAD, and other imports'); ?></small>
        </div>
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="audit_exports" value="1" id="audit_exports" <?php echo ($settings['audit_exports'] ?? '1') == '1' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="audit_exports"><?php echo __('Log Export Actions'); ?></label>
          </div>
          <small class="text-muted"><?php echo __('CSV, EAD, PDF exports'); ?></small>
        </div>
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="audit_downloads" value="1" id="audit_downloads" <?php echo ($settings['audit_downloads'] ?? '1') == '1' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="audit_downloads"><?php echo __('Log File Downloads'); ?></label>
          </div>
          <small class="text-muted"><?php echo __('Digital object downloads'); ?></small>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="card mb-4">
      <div class="card-header">
        <i class="fas fa-shield-alt me-1"></i><?php echo __('Security & Access'); ?>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="audit_sensitive_access" value="1" id="audit_sensitive_access" <?php echo ($settings['audit_sensitive_access'] ?? '1') == '1' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="audit_sensitive_access"><?php echo __('Log Classified Content Access'); ?></label>
          </div>
          <small class="text-muted"><?php echo __('Access to security-classified records'); ?></small>
        </div>
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="audit_permission_changes" value="1" id="audit_permission_changes" <?php echo ($settings['audit_permission_changes'] ?? '1') == '1' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="audit_permission_changes"><?php echo __('Log Permission Changes'); ?></label>
          </div>
          <small class="text-muted"><?php echo __('User and group permission modifications'); ?></small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card mb-4">
      <div class="card-header">
        <i class="fas fa-cog me-1"></i><?php echo __('Advanced Options'); ?>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="audit_api_requests" value="1" id="audit_api_requests" <?php echo ($settings['audit_api_requests'] ?? '0') == '1' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="audit_api_requests"><?php echo __('Log API Requests'); ?></label>
          </div>
          <small class="text-muted"><?php echo __('REST API activity'); ?></small>
        </div>
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="audit_searches" value="1" id="audit_searches" <?php echo ($settings['audit_searches'] ?? '0') == '1' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="audit_searches"><?php echo __('Log Search Queries'); ?></label>
          </div>
          <small class="text-muted text-warning"><i class="fas fa-exclamation-triangle me-1"></i><?php echo __('Warning: High volume'); ?></small>
        </div>
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="audit_ip_anonymize" value="1" id="audit_ip_anonymize" <?php echo ($settings['audit_ip_anonymize'] ?? '0') == '1' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="audit_ip_anonymize"><?php echo __('Anonymize IP Addresses'); ?></label>
          </div>
          <small class="text-muted"><?php echo __('POPIA compliance - mask last octet'); ?></small>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body d-flex justify-content-between">
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save me-1"></i><?php echo __('Save Settings'); ?>
    </button>
    <a href="<?php echo url_for(['module' => 'ahgAuditTrail', 'action' => 'dashboard']); ?>" class="btn btn-secondary">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Dashboard'); ?>
    </a>
  </div>
</div>
</form>
<?php end_slot() ?>

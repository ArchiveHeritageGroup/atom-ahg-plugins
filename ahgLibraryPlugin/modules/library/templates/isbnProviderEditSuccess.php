<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
<h1><i class="fas fa-book me-2"></i><?php echo $provider ? __('Edit Provider') : __('Add Provider'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<?php if ($sf_user->hasFlash('error')): ?>
<div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<form method="post">
<div class="card mb-4">
  <div class="card-header">
    <i class="fas fa-server me-1"></i><?php echo __('Provider Details'); ?>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <div class="mb-3">
          <label class="form-label" for="name"><?php echo __('Name'); ?> <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="name" name="name" value="<?php echo esc_specialchars($provider->name ?? ''); ?>" required>
          <small class="text-muted"><?php echo __('Display name for the provider'); ?></small>
        </div>
      </div>
      <div class="col-md-6">
        <div class="mb-3">
          <label class="form-label" for="slug"><?php echo __('Slug'); ?> <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="slug" name="slug" value="<?php echo esc_specialchars($provider->slug ?? ''); ?>" required pattern="[a-z0-9_-]+">
          <small class="text-muted"><?php echo __('Unique identifier (lowercase, no spaces)'); ?></small>
        </div>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label" for="api_endpoint"><?php echo __('API Endpoint'); ?> <span class="text-danger">*</span></label>
      <input type="url" class="form-control" id="api_endpoint" name="api_endpoint" value="<?php echo esc_specialchars($provider->api_endpoint ?? ''); ?>" required>
      <small class="text-muted"><?php echo __('Base URL for the API'); ?></small>
    </div>

    <div class="row">
      <div class="col-md-4">
        <div class="mb-3">
          <label class="form-label" for="priority"><?php echo __('Priority'); ?></label>
          <input type="number" class="form-control" id="priority" name="priority" value="<?php echo $provider->priority ?? 50; ?>" min="1" max="100">
          <small class="text-muted"><?php echo __('Lower = higher priority'); ?></small>
        </div>
      </div>
      <div class="col-md-4">
        <div class="mb-3">
          <label class="form-label" for="rate_limit_per_minute"><?php echo __('Rate Limit'); ?></label>
          <input type="number" class="form-control" id="rate_limit_per_minute" name="rate_limit_per_minute" value="<?php echo $provider->rate_limit_per_minute ?? 100; ?>" min="1">
          <small class="text-muted"><?php echo __('Requests per minute'); ?></small>
        </div>
      </div>
      <div class="col-md-4">
        <div class="mb-3">
          <label class="form-label" for="response_format"><?php echo __('Response Format'); ?></label>
          <select class="form-select" id="response_format" name="response_format">
            <option value="json" <?php echo ($provider->response_format ?? 'json') === 'json' ? 'selected' : ''; ?>>JSON</option>
            <option value="xml" <?php echo ($provider->response_format ?? '') === 'xml' ? 'selected' : ''; ?>>XML</option>
            <option value="marcxml" <?php echo ($provider->response_format ?? '') === 'marcxml' ? 'selected' : ''; ?>>MARCXML</option>
          </select>
        </div>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label" for="api_key_setting"><?php echo __('API Key Setting'); ?></label>
      <input type="text" class="form-control" id="api_key_setting" name="api_key_setting" value="<?php echo esc_specialchars($provider->api_key_setting ?? ''); ?>">
      <small class="text-muted"><?php echo __('Setting name where API key is stored (leave empty if not required)'); ?></small>
    </div>

    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" id="enabled" name="enabled" value="1" <?php echo ($provider->enabled ?? 1) ? 'checked' : ''; ?>>
      <label class="form-check-label" for="enabled"><?php echo __('Enabled'); ?></label>
    </div>
  </div>
</div>

<div class="d-flex justify-content-between">
  <a href="<?php echo url_for('library/isbnProviders') ?>" class="btn btn-secondary">
    <i class="fas fa-arrow-left me-1"></i><?php echo __('Cancel'); ?>
  </a>
  <button type="submit" class="btn btn-primary">
    <i class="fas fa-save me-1"></i><?php echo $provider ? __('Update Provider') : __('Add Provider'); ?>
  </button>
</div>
</form>

<?php end_slot() ?>

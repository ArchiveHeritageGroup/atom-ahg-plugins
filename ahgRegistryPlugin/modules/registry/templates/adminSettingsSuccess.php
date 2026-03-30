<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Registry Settings'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Settings')],
]]); ?>

<h1 class="h3 mb-4"><i class="fas fa-cog me-2 text-primary"></i><?php echo __('Registry Settings'); ?></h1>

<?php if (!empty($saved)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
  <i class="fas fa-check-circle me-2"></i>
  <?php echo __('Settings saved successfully.'); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('Close'); ?>"></button>
</div>
<?php endif; ?>

<?php if (!empty($settings) && count($settings) > 0): ?>

<?php
  // Group settings into logical sections by key prefix / purpose
  $sections = [
    'general' => [
      'label' => __('General'),
      'icon' => 'fa-sliders-h',
      'color' => 'text-primary',
      'keys' => ['registry_name', 'default_country', 'featured_count'],
    ],
    'registration' => [
      'label' => __('Registration & Moderation'),
      'icon' => 'fa-user-check',
      'color' => 'text-success',
      'keys' => ['allow_self_registration', 'moderation_enabled', 'blog_require_approval', 'discussion_require_approval'],
    ],
    'navigation' => [
      'label' => __('Navigation & Menus'),
      'icon' => 'fa-bars',
      'color' => 'text-info',
      'keys' => ['nav_show_community', 'nav_show_user_groups', 'nav_show_blog', 'nav_show_newsletters', 'nav_show_map', 'nav_show_search', 'nav_show_standards'],
    ],
    'map' => [
      'label' => __('Map Defaults'),
      'icon' => 'fa-map-marked-alt',
      'color' => 'text-danger',
      'keys' => ['map_default_lat', 'map_default_lng', 'map_default_zoom'],
    ],
    'sync' => [
      'label' => __('Instance Sync / Heartbeat'),
      'icon' => 'fa-sync-alt',
      'color' => 'text-warning',
      'keys' => ['heartbeat_interval_hours', 'heartbeat_offline_threshold_days'],
    ],
    'uploads' => [
      'label' => __('Uploads & Attachments'),
      'icon' => 'fa-cloud-upload-alt',
      'color' => 'text-secondary',
      'keys' => ['max_upload_size_mb', 'allowed_upload_extensions', 'max_attachment_size_mb', 'allowed_attachment_types', 'max_logo_size_mb', 'allowed_logo_types'],
    ],
    'oauth' => [
      'label' => __('OAuth / Social Login'),
      'icon' => 'fa-key',
      'color' => 'text-purple',
      'keys' => ['oauth_google_enabled', 'oauth_google_client_id', 'oauth_google_client_secret', 'oauth_facebook_enabled', 'oauth_facebook_app_id', 'oauth_facebook_app_secret', 'oauth_github_enabled', 'oauth_github_client_id', 'oauth_github_client_secret'],
    ],
    'smtp' => [
      'label' => __('Email / SMTP'),
      'icon' => 'fa-envelope',
      'color' => 'text-info',
      'keys' => ['smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password', 'smtp_from_email', 'smtp_from_name'],
    ],
    'footer' => [
      'label' => __('Footer'),
      'icon' => 'fa-window-minimize',
      'color' => 'text-muted',
      'keys' => ['footer_description', 'footer_copyright', 'footer_columns'],
    ],
  ];

  // Index settings by key for quick lookup
  $settingsByKey = [];
  foreach ($settings as $s) {
    $settingsByKey[$s->setting_key] = $s;
  }

  // Collect any settings not assigned to a section
  $assignedKeys = [];
  foreach ($sections as $sec) {
    $assignedKeys = array_merge($assignedKeys, $sec['keys']);
  }
  $unassigned = [];
  foreach ($settings as $s) {
    if (!in_array($s->setting_key, $assignedKeys)) {
      $unassigned[] = $s;
    }
  }

  // Friendly labels for setting keys
  $friendlyLabels = [
    'registry_name' => __('Registry Name'),
    'default_country' => __('Default Country'),
    'featured_count' => __('Featured Items Count'),
    'allow_self_registration' => __('Allow Self-Registration'),
    'moderation_enabled' => __('Require Admin Approval for Registrations'),
    'blog_require_approval' => __('Require Blog Post Approval'),
    'discussion_require_approval' => __('Require Discussion Approval'),
    'nav_show_community' => __('Show Community'),
    'nav_show_user_groups' => __('Show User Groups'),
    'nav_show_blog' => __('Show Blog'),
    'nav_show_newsletters' => __('Show Newsletters'),
    'nav_show_map' => __('Show Map'),
    'nav_show_search' => __('Show Search'),
    'nav_show_standards' => __('Show Standards'),
    'map_default_lat' => __('Default Latitude'),
    'map_default_lng' => __('Default Longitude'),
    'map_default_zoom' => __('Default Zoom Level'),
    'heartbeat_interval_hours' => __('Expected Heartbeat Interval (hours)'),
    'heartbeat_offline_threshold_days' => __('Offline Threshold (days)'),
    'max_upload_size_mb' => __('Max Software Upload Size (MB)'),
    'allowed_upload_extensions' => __('Allowed Upload Extensions'),
    'max_attachment_size_mb' => __('Max Attachment Size (MB)'),
    'allowed_attachment_types' => __('Allowed Attachment Types'),
    'max_logo_size_mb' => __('Max Logo Size (MB)'),
    'allowed_logo_types' => __('Allowed Logo Types'),
    'oauth_google_enabled' => __('Google OAuth Enabled'),
    'oauth_google_client_id' => __('Google Client ID'),
    'oauth_google_client_secret' => __('Google Client Secret'),
    'oauth_facebook_enabled' => __('Facebook OAuth Enabled'),
    'oauth_facebook_app_id' => __('Facebook App ID'),
    'oauth_facebook_app_secret' => __('Facebook App Secret'),
    'oauth_github_enabled' => __('GitHub OAuth Enabled'),
    'oauth_github_client_id' => __('GitHub Client ID'),
    'oauth_github_client_secret' => __('GitHub Client Secret'),
    'smtp_enabled' => __('SMTP Enabled'),
    'smtp_host' => __('SMTP Host'),
    'smtp_port' => __('SMTP Port'),
    'smtp_encryption' => __('Encryption'),
    'smtp_username' => __('Username'),
    'smtp_password' => __('Password'),
    'smtp_from_email' => __('From Email'),
    'smtp_from_name' => __('From Name'),
    'footer_description' => __('Footer Description'),
    'footer_copyright' => __('Copyright Text'),
    'footer_columns' => __('Footer Link Columns (JSON)'),
  ];
?>

<form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminSettings']); ?>">

  <!-- Section navigation -->
  <div class="card mb-4">
    <div class="card-body py-2">
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($sections as $sKey => $sec): ?>
          <a href="#section-<?php echo $sKey; ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas <?php echo $sec['icon']; ?> me-1"></i><?php echo $sec['label']; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <?php foreach ($sections as $sKey => $sec): ?>
  <div class="card mb-4" id="section-<?php echo $sKey; ?>">
    <div class="card-header fw-semibold">
      <i class="fas <?php echo $sec['icon']; ?> me-2 <?php echo $sec['color']; ?>"></i><?php echo $sec['label']; ?>
    </div>
    <div class="card-body">
      <?php $first = true; foreach ($sec['keys'] as $key):
        $setting = $settingsByKey[$key] ?? null;
        if (!$setting) continue;
        if (!$first): ?><hr class="my-3"><?php endif; $first = false;
      ?>
        <?php echo _render_setting_field($setting, $friendlyLabels, $key); ?>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if (!empty($unassigned)): ?>
  <div class="card mb-4" id="section-other">
    <div class="card-header fw-semibold">
      <i class="fas fa-ellipsis-h me-2 text-muted"></i><?php echo __('Other Settings'); ?>
    </div>
    <div class="card-body">
      <?php $first = true; foreach ($unassigned as $setting):
        if (!$first): ?><hr class="my-3"><?php endif; $first = false;
        echo _render_setting_field($setting, $friendlyLabels, $setting->setting_key);
      endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Sticky save bar -->
  <div class="card border-primary">
    <div class="card-body d-flex justify-content-between align-items-center py-2">
      <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminDashboard']); ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Admin'); ?>
      </a>
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-1"></i> <?php echo __('Save All Settings'); ?>
      </button>
    </div>
  </div>

</form>

<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-cog fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No settings configured'); ?></h5>
  <p class="text-muted"><?php echo __('Registry settings will appear here once configured.'); ?></p>
</div>
<?php endif; ?>

<?php
/**
 * Render a single setting field (defined as a local function for the template).
 */
function _render_setting_field($setting, $friendlyLabels, $key) {
  $fieldName = 'setting_' . $setting->setting_key;
  $fieldId = 'setting_' . htmlspecialchars($setting->setting_key, ENT_QUOTES, 'UTF-8');
  $settingType = $setting->setting_type ?? 'text';
  $label = $friendlyLabels[$key] ?? ucwords(str_replace('_', ' ', $setting->setting_key));

  ob_start();
  ?>
  <div class="mb-0">
    <?php if ('boolean' === $settingType): ?>
      <div class="form-check form-switch">
        <input type="hidden" name="<?php echo $fieldName; ?>" value="0">
        <input class="form-check-input" type="checkbox" id="<?php echo $fieldId; ?>" name="<?php echo $fieldName; ?>" value="1"<?php echo !empty($setting->setting_value) && '0' !== $setting->setting_value ? ' checked' : ''; ?>>
        <label class="form-check-label fw-semibold" for="<?php echo $fieldId; ?>"><?php echo $label; ?></label>
      </div>
    <?php else: ?>
      <label for="<?php echo $fieldId; ?>" class="form-label fw-semibold"><?php echo $label; ?></label>
    <?php endif; ?>

    <?php if (!empty($setting->description)): ?>
      <div class="form-text mb-1" style="margin-top: -2px;"><?php echo htmlspecialchars($setting->description, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ('boolean' === $settingType): ?>
      <!-- already rendered above -->
    <?php elseif ('number' === $settingType): ?>
      <input type="number" class="form-control" id="<?php echo $fieldId; ?>" name="<?php echo $fieldName; ?>" value="<?php echo htmlspecialchars($setting->setting_value ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="max-width: 200px;">
    <?php elseif ('json' === $settingType): ?>
      <textarea class="form-control font-monospace" id="<?php echo $fieldId; ?>" name="<?php echo $fieldName; ?>" rows="4"><?php echo htmlspecialchars($setting->setting_value ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    <?php elseif (strpos($setting->setting_key, 'secret') !== false || strpos($setting->setting_key, 'password') !== false): ?>
      <div class="input-group" style="max-width: 500px;">
        <input type="password" class="form-control" id="<?php echo $fieldId; ?>" name="<?php echo $fieldName; ?>" value="<?php echo htmlspecialchars($setting->setting_value ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <button class="btn btn-outline-secondary" type="button" onclick="var i=document.getElementById('<?php echo $fieldId; ?>'); i.type = i.type==='password' ? 'text' : 'password';"><i class="fas fa-eye"></i></button>
      </div>
    <?php else: ?>
      <input type="text" class="form-control" id="<?php echo $fieldId; ?>" name="<?php echo $fieldName; ?>" value="<?php echo htmlspecialchars($setting->setting_value ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
  </div>
  <?php
  return ob_get_clean();
}
?>

<?php end_slot(); ?>

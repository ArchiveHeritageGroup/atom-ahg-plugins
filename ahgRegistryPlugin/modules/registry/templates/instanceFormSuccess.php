<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo $instance ? __('Edit Instance') : __('Add Instance'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Institution'), 'url' => url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard'])],
  ['label' => __('Instances'), 'url' => url_for(['module' => 'registry', 'action' => 'myInstitutionInstances'])],
  ['label' => $instance ? __('Edit') : __('Add')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-10 col-xl-9">

    <h1 class="h3 mb-4"><?php echo $instance ? __('Edit Instance') : __('Add Instance'); ?></h1>

    <?php if (isset($errors) && count($errors) > 0): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php $f = $instance; ?>

    <form method="post">

      <!-- Basic instance info -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-server me-2 text-primary"></i><?php echo __('Instance Details'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="if-name" class="form-label"><?php echo __('Instance Name'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="if-name" name="name" value="<?php echo htmlspecialchars($f->name ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="<?php echo __('e.g., Production Server'); ?>">
            </div>
            <div class="col-md-6">
              <label for="if-url" class="form-label"><?php echo __('URL'); ?></label>
              <input type="url" class="form-control" id="if-url" name="url" value="<?php echo htmlspecialchars($f->url ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://archives.example.org">
            </div>
            <div class="col-md-4">
              <label for="if-type" class="form-label"><?php echo __('Instance Type'); ?></label>
              <select class="form-select" id="if-type" name="instance_type">
                <?php
                  $instTypes = ['production' => __('Production'), 'staging' => __('Staging'), 'dev' => __('Development'), 'demo' => __('Demo'), 'offline' => __('Offline / Air-gapped')];
                  $selType = $f->instance_type ?? 'production';
                  foreach ($instTypes as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selType === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="if-software" class="form-label"><?php echo __('Software'); ?></label>
              <?php $currentSw = $f->software ?? 'heratio'; ?>
              <select class="form-select" id="if-software" name="software">
                <?php if (isset($allSoftware) && count($allSoftware) > 0):
                  foreach ($allSoftware as $_sw): ?>
                    <option value="<?php echo htmlspecialchars($_sw->name, ENT_QUOTES, 'UTF-8'); ?>"<?php echo (strcasecmp($currentSw, $_sw->name) === 0 || strcasecmp($currentSw, $_sw->slug) === 0) ? ' selected' : ''; ?>><?php echo htmlspecialchars($_sw->name, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; endif; ?>
                <?php
                  // If current value doesn't match any software in directory, show it as custom option
                  $matched = false;
                  if (isset($allSoftware)) {
                    foreach ($allSoftware as $_sw) {
                      if (strcasecmp($currentSw, $_sw->name) === 0 || strcasecmp($currentSw, $_sw->slug) === 0) { $matched = true; break; }
                    }
                  }
                  if (!$matched && !empty($currentSw)): ?>
                    <option value="<?php echo htmlspecialchars($currentSw, ENT_QUOTES, 'UTF-8'); ?>" selected><?php echo htmlspecialchars($currentSw, ENT_QUOTES, 'UTF-8'); ?> (<?php echo __('custom'); ?>)</option>
                <?php endif; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="if-version" class="form-label"><?php echo __('Software Version'); ?></label>
              <input type="text" class="form-control" id="if-version" name="software_version" value="<?php echo htmlspecialchars($f->software_version ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="2.8.2">
            </div>
          </div>
        </div>
      </div>

      <!-- Hosting & environment -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-cloud me-2 text-info"></i><?php echo __('Hosting & Environment'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label for="if-hosting" class="form-label"><?php echo __('Hosting Type'); ?></label>
              <select class="form-select" id="if-hosting" name="hosting">
                <option value=""><?php echo __('-- Select --'); ?></option>
                <?php
                  $hostTypes = ['self_hosted' => __('Self-Hosted'), 'cloud' => __('Cloud'), 'vendor_hosted' => __('Vendor Hosted'), 'saas' => __('SaaS')];
                  $selHost = $f->hosting ?? '';
                  foreach ($hostTypes as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selHost === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="if-hosting-vendor" class="form-label"><?php echo __('Hosting Vendor'); ?></label>
              <select class="form-select" id="if-hosting-vendor" name="hosting_vendor_id">
                <option value=""><?php echo __('-- None --'); ?></option>
                <?php if (!empty($vendors)):
                  foreach ($vendors as $v): ?>
                    <option value="<?php echo (int) $v->id; ?>"<?php echo (isset($f->hosting_vendor_id) && (int) $f->hosting_vendor_id === (int) $v->id) ? ' selected' : ''; ?>><?php echo htmlspecialchars($v->name, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; endif; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="if-maintained" class="form-label"><?php echo __('Maintained by Vendor'); ?></label>
              <select class="form-select" id="if-maintained" name="maintained_by_vendor_id">
                <option value=""><?php echo __('-- None / Self --'); ?></option>
                <?php if (!empty($vendors)):
                  foreach ($vendors as $v): ?>
                    <option value="<?php echo (int) $v->id; ?>"<?php echo (isset($f->maintained_by_vendor_id) && (int) $f->maintained_by_vendor_id === (int) $v->id) ? ' selected' : ''; ?>><?php echo htmlspecialchars($v->name, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; endif; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label for="if-os" class="form-label"><?php echo __('OS Environment'); ?></label>
              <input type="text" class="form-control" id="if-os" name="os_environment" value="<?php echo htmlspecialchars($f->os_environment ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('e.g., Ubuntu 22.04, RHEL 9, Windows Server 2022'); ?>">
            </div>
            <div class="col-md-6">
              <label for="if-languages" class="form-label"><?php echo __('Languages'); ?></label>
              <?php
                $langDisplay = '';
                $rawLangs = $f->languages ?? '';
                if (!empty($rawLangs)) {
                  $rawLangs = sfOutputEscaper::unescape($rawLangs);
                  if (is_string($rawLangs)) {
                    $decoded = json_decode($rawLangs, true);
                    $langDisplay = is_array($decoded) ? implode(', ', $decoded) : $rawLangs;
                  } elseif (is_array($rawLangs)) {
                    $langDisplay = implode(', ', $rawLangs);
                  }
                }
              ?>
              <input type="text" class="form-control" id="if-languages" name="languages" value="<?php echo htmlspecialchars($langDisplay, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Comma-separated: English, French, Afrikaans'); ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Metrics -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-chart-bar me-2 text-success"></i><?php echo __('Metrics'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <label for="if-record-count" class="form-label"><?php echo __('Record Count'); ?></label>
              <input type="number" class="form-control" id="if-record-count" name="record_count" value="<?php echo htmlspecialchars($f->record_count ?? '', ENT_QUOTES, 'UTF-8'); ?>" min="0">
            </div>
            <div class="col-md-3">
              <label for="if-do-count" class="form-label"><?php echo __('Digital Object Count'); ?></label>
              <input type="number" class="form-control" id="if-do-count" name="digital_object_count" value="<?php echo htmlspecialchars($f->digital_object_count ?? '', ENT_QUOTES, 'UTF-8'); ?>" min="0">
            </div>
            <div class="col-md-3">
              <label for="if-storage" class="form-label"><?php echo __('Storage (GB)'); ?></label>
              <input type="number" class="form-control" id="if-storage" name="storage_gb" value="<?php echo htmlspecialchars($f->storage_gb ?? '', ENT_QUOTES, 'UTF-8'); ?>" min="0" step="0.1">
            </div>
            <div class="col-md-3">
              <label for="if-standard" class="form-label"><?php echo __('Descriptive Standard'); ?></label>
              <select class="form-select" id="if-standard" name="descriptive_standard">
                <option value=""><?php echo __('-- Select --'); ?></option>
                <?php
                  $stds = ['RAD' => 'RAD', 'ISAD(G)' => 'ISAD(G)', 'DACS' => 'DACS', 'Dublin Core' => 'Dublin Core', 'MODS' => 'MODS', 'Other' => __('Other')];
                  $selStd = $f->descriptive_standard ?? '';
                  foreach ($stds as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selStd === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Feature/Module usage -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-puzzle-piece me-2 text-warning"></i><?php echo __('Feature / Module Usage'); ?></div>
        <div class="card-body">
          <p class="text-muted small mb-3"><?php echo __('Indicate which features and modules are in use on this instance.'); ?></p>
          <?php
            $features = [
              'accession_records' => __('Accession records'),
              'archival_descriptions' => __('Archival descriptions'),
              'authority_records' => __('Authority records'),
              'digital_objects' => __('Digital objects'),
              'physical_storage' => __('Physical storage'),
              'rights_management' => __('Rights management'),
              'taxonomies' => __('Taxonomies'),
              'finding_aids' => __('Finding aids'),
              'import_export' => __('Import / Export'),
              'iiif_viewer' => __('IIIF Viewer'),
              'preservation' => __('Digital preservation'),
              'ai_processing' => __('AI Processing (NER, OCR, etc.)'),
              'multi_language' => __('Multi-language support'),
              'public_access' => __('Public access interface'),
              'research_services' => __('Research services'),
              'audit_trail' => __('Audit trail'),
              'backup' => __('Backup management'),
            ];
            $currentFeatures = [];
            if (!empty($f->feature_usage)) {
              $rawFeatureUsage = sfOutputEscaper::unescape($f->feature_usage);
              $currentFeatures = is_string($rawFeatureUsage) ? json_decode($rawFeatureUsage, true) : (array) $rawFeatureUsage;
              if (!is_array($currentFeatures)) { $currentFeatures = []; }
            }
          ?>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead class="table-light">
                <tr>
                  <th style="width: 40%;"><?php echo __('Feature'); ?></th>
                  <th style="width: 15%;" class="text-center"><?php echo __('In Use?'); ?></th>
                  <th><?php echo __('Comments'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($features as $key => $label): ?>
                <tr>
                  <td><?php echo $label; ?></td>
                  <td class="text-center">
                    <input class="form-check-input" type="checkbox" name="features[<?php echo $key; ?>][enabled]" value="1"<?php echo (!empty($currentFeatures[$key]['enabled']) || (is_array($currentFeatures) && in_array($key, $currentFeatures))) ? ' checked' : ''; ?>>
                  </td>
                  <td>
                    <input type="text" class="form-control form-control-sm" name="features[<?php echo $key; ?>][comment]" value="<?php echo htmlspecialchars($currentFeatures[$key]['comment'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Optional notes...'); ?>">
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Settings -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-cog me-2 text-secondary"></i><?php echo __('Settings'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="if-sync" name="sync_enabled" value="1"<?php echo !empty($f->sync_enabled) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="if-sync"><?php echo __('Enable sync (heartbeat reporting)'); ?></label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="if-public" name="is_public" value="1"<?php echo (!$instance || !empty($f->is_public)) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="if-public"><?php echo __('Publicly visible in directory'); ?></label>
              </div>
            </div>
            <div class="col-12">
              <label for="if-desc" class="form-label"><?php echo __('Description'); ?></label>
              <textarea class="form-control" id="if-desc" name="description" rows="3"><?php echo htmlspecialchars($f->description ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <div>
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionInstances']); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
          <?php if ($instance): ?>
            <button type="button" class="btn btn-outline-danger ms-2" onclick="if(confirm('<?php echo __('Are you sure you want to delete this instance?'); ?>')) { document.getElementById('delete-instance-form').submit(); }">
              <i class="fas fa-trash me-1"></i> <?php echo __('Delete'); ?>
            </button>
          <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> <?php echo $instance ? __('Save Changes') : __('Add Instance'); ?></button>
      </div>

    </form>

    <?php if ($instance): ?>
    <form id="delete-instance-form" method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionInstanceDelete', 'id' => (int) $instance->id]); ?>" style="display: none;">
    </form>
    <?php endif; ?>

  </div>
</div>

<?php end_slot(); ?>

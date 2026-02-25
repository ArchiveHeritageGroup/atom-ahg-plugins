<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo $software ? __('Edit Software') : __('Add Software'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Vendor Dashboard'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorDashboard'])],
  ['label' => __('Software'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorSoftware'])],
  ['label' => $software ? __('Edit') : __('Add')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-9">

    <h1 class="h3 mb-4"><?php echo $software ? __('Edit Software Product') : __('Add Software Product'); ?></h1>

    <?php if (isset($errors) && count($errors) > 0): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php $f = $software; ?>

    <form method="post" enctype="multipart/form-data">

      <!-- Basic info -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-info-circle me-2 text-primary"></i><?php echo __('Basic Information'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label for="sf-name" class="form-label"><?php echo __('Software Name'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="sf-name" name="name" value="<?php echo htmlspecialchars($f->name ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="col-md-4">
              <label for="sf-cat" class="form-label"><?php echo __('Category'); ?></label>
              <select class="form-select" id="sf-cat" name="category">
                <?php
                  $cats = [
                    'ams' => __('Archival Management System'), 'cms' => __('Collection Management System'),
                    'dam' => __('Digital Asset Management'), 'dams' => __('DAMS'),
                    'ims' => __('Information Management System'), 'glam' => __('GLAM / DAM'),
                    'preservation' => __('Digital Preservation'),
                    'discovery' => __('Discovery / Access'), 'digitization' => __('Digitization'),
                    'integration' => __('Integration / Middleware'), 'plugin' => __('Plugin / Extension'),
                    'theme' => __('Theme / Template'), 'utility' => __('Utility / Tool'),
                    'other' => __('Other'),
                  ];
                  $selCat = $f->category ?? 'other';
                  foreach ($cats as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selCat === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label for="sf-short-desc" class="form-label"><?php echo __('Short Description'); ?></label>
              <input type="text" class="form-control" id="sf-short-desc" name="short_description" value="<?php echo htmlspecialchars($f->short_description ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="255">
            </div>
            <div class="col-12">
              <label for="sf-desc" class="form-label"><?php echo __('Description'); ?></label>
              <textarea class="form-control" id="sf-desc" name="description" rows="5"><?php echo htmlspecialchars($f->description ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div class="col-md-6">
              <label for="sf-website" class="form-label"><?php echo __('Website'); ?></label>
              <input type="url" class="form-control" id="sf-website" name="website" value="<?php echo htmlspecialchars($f->website ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-6">
              <label for="sf-docs" class="form-label"><?php echo __('Documentation URL'); ?></label>
              <input type="url" class="form-control" id="sf-docs" name="documentation_url" value="<?php echo htmlspecialchars($f->documentation_url ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-6">
              <label for="sf-install" class="form-label"><?php echo __('Install / Download URL'); ?></label>
              <input type="url" class="form-control" id="sf-install" name="install_url" value="<?php echo htmlspecialchars($f->install_url ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('e.g., https://www.accesstomemory.org/en/download/'); ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Git section -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fab fa-git-alt me-2 text-danger"></i><?php echo __('Source Code Repository'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label for="sf-git-prov" class="form-label"><?php echo __('Git Provider'); ?></label>
              <select class="form-select" id="sf-git-prov" name="git_provider">
                <?php
                  $providers = ['none' => __('None'), 'github' => 'GitHub', 'gitlab' => 'GitLab', 'bitbucket' => 'Bitbucket', 'self_hosted' => __('Self-hosted')];
                  $selProv = $f->git_provider ?? 'none';
                  foreach ($providers as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selProv === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-8">
              <label for="sf-git-url" class="form-label"><?php echo __('Repository URL'); ?></label>
              <input type="url" class="form-control" id="sf-git-url" name="git_url" value="<?php echo htmlspecialchars($f->git_url ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://github.com/org/repo">
            </div>
            <div class="col-md-4">
              <label for="sf-git-branch" class="form-label"><?php echo __('Default Branch'); ?></label>
              <input type="text" class="form-control" id="sf-git-branch" name="git_default_branch" value="<?php echo htmlspecialchars($f->git_default_branch ?? 'main', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="sf-git-public" name="git_is_public" value="1"<?php echo (!$software || !empty($f->git_is_public)) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="sf-git-public"><?php echo __('Public repository'); ?></label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Licensing & pricing -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-balance-scale me-2 text-success"></i><?php echo __('Licensing & Pricing'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label for="sf-license" class="form-label"><?php echo __('License'); ?></label>
              <select class="form-select" id="sf-license" name="license">
                <option value=""><?php echo __('-- Select --'); ?></option>
                <?php
                  $licenses = ['AGPL-3.0' => 'AGPL-3.0', 'GPL-3.0' => 'GPL-3.0', 'GPL-2.0' => 'GPL-2.0', 'MIT' => 'MIT', 'Apache-2.0' => 'Apache 2.0', 'BSD-2-Clause' => 'BSD 2-Clause', 'BSD-3-Clause' => 'BSD 3-Clause', 'MPL-2.0' => 'MPL 2.0', 'LGPL-3.0' => 'LGPL-3.0', 'CC-BY-4.0' => 'CC BY 4.0', 'CC-BY-SA-4.0' => 'CC BY-SA 4.0', 'Unlicense' => 'Unlicense', 'Proprietary' => __('Proprietary'), 'Other' => __('Other')];
                  $selLic = $f->license ?? '';
                  foreach ($licenses as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selLic === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="sf-pricing" class="form-label"><?php echo __('Pricing Model'); ?></label>
              <select class="form-select" id="sf-pricing" name="pricing_model">
                <?php
                  $prModels = ['open_source' => __('Open Source / Free'), 'freemium' => __('Freemium'), 'subscription' => __('Subscription'), 'one_time' => __('One-time License'), 'per_user' => __('Per User'), 'custom' => __('Custom / Contact')];
                  $selPr = $f->pricing_model ?? 'open_source';
                  foreach ($prModels as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selPr === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="sf-pricing-details" class="form-label"><?php echo __('Pricing Details'); ?></label>
              <input type="text" class="form-control" id="sf-pricing-details" name="pricing_details" value="<?php echo htmlspecialchars($f->pricing_details ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('e.g., Free, $99/year'); ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- GLAM Sectors -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-landmark me-2 text-warning"></i><?php echo __('GLAM Sectors'); ?></div>
        <div class="card-body">
          <p class="text-muted small mb-2"><?php echo __('Select all GLAM sectors this software supports.'); ?></p>
          <?php
            $glamOptions = [
              'archive' => __('Archive'),
              'library' => __('Library'),
              'museum' => __('Museum'),
              'gallery' => __('Gallery'),
              'dam' => __('Digital Asset Management'),
              'heritage' => __('Heritage'),
              'research' => __('Research Centre'),
              'government' => __('Government'),
            ];
            $currentSectors = [];
            if (!empty($f->glam_sectors)) {
              $rawGlamSectors = sfOutputEscaper::unescape($f->glam_sectors);
              $currentSectors = is_string($rawGlamSectors) ? json_decode($rawGlamSectors, true) : (array) $rawGlamSectors;
              if (!is_array($currentSectors)) { $currentSectors = []; }
            }
          ?>
          <div class="row g-2">
            <?php foreach ($glamOptions as $val => $label): ?>
            <div class="col-md-3 col-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="sf-glam-<?php echo $val; ?>" name="glam_sectors[]" value="<?php echo $val; ?>"<?php echo in_array($val, $currentSectors) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="sf-glam-<?php echo $val; ?>"><?php echo $label; ?></label>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Logo upload -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-image me-2 text-info"></i><?php echo __('Logo'); ?></div>
        <div class="card-body">
          <?php if (!empty($f->logo_path)): ?>
            <div class="mb-2">
              <img src="<?php echo htmlspecialchars($f->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded border" style="max-height: 60px;">
            </div>
          <?php endif; ?>
          <div class="border rounded p-3 text-center position-relative" id="sf-logo-drop" style="min-height: 80px; cursor: pointer;">
            <div id="sf-logo-preview">
              <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-1"></i>
              <p class="mb-0 small"><?php echo __('Drag and drop, or click to upload.'); ?> <span class="text-muted"><?php echo __('PNG, JPG, SVG'); ?></span></p>
            </div>
            <input type="file" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" id="sf-logo" name="logo" accept="image/png,image/jpeg,image/svg+xml" style="cursor: pointer;">
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorSoftware']); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> <?php echo $software ? __('Save Changes') : __('Add Software'); ?></button>
      </div>

    </form>

  </div>
</div>

<script <?php echo $na; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var inp = document.getElementById('sf-logo'), prev = document.getElementById('sf-logo-preview'), drop = document.getElementById('sf-logo-drop');
  if (inp) { inp.addEventListener('change', function(e) { if (e.target.files && e.target.files[0]) { var r = new FileReader(); r.onload = function(ev) { prev.innerHTML = '<img src="'+ev.target.result+'" alt="Preview" style="max-height:60px;" class="mb-1"><br><small class="text-muted">'+e.target.files[0].name+'</small>'; }; r.readAsDataURL(e.target.files[0]); } }); }
  if (drop) { ['dragenter','dragover'].forEach(function(ev){drop.addEventListener(ev,function(e){e.preventDefault();drop.classList.add('border-primary');});}); ['dragleave','drop'].forEach(function(ev){drop.addEventListener(ev,function(e){e.preventDefault();drop.classList.remove('border-primary');});}); drop.addEventListener('drop',function(e){if(e.dataTransfer.files.length){inp.files=e.dataTransfer.files;inp.dispatchEvent(new Event('change'));}}); }
});
</script>

<?php end_slot(); ?>

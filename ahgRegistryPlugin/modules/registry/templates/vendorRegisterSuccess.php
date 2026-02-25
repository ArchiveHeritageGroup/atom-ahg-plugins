<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Register as Vendor'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Register as Vendor')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-10 col-xl-9">

    <h1 class="h3 mb-2"><?php echo __('Register as Vendor'); ?></h1>
    <p class="text-muted mb-4"><?php echo __('List your services and software in the global AtoM vendor directory. Fields marked with * are required.'); ?></p>

    <?php if (isset($errors) && count($errors) > 0): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php $f = isset($formData) ? $formData : new stdClass(); ?>

    <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'vendorRegister']); ?>" enctype="multipart/form-data">

      <!-- Basic info -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-info-circle me-2 text-primary"></i><?php echo __('Basic Information'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label for="vr-name" class="form-label"><?php echo __('Company / Vendor Name'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="vr-name" name="name" value="<?php echo htmlspecialchars($f->name ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Vendor Type'); ?></label>
              <?php
                $vTypes = ['developer' => __('Developer'), 'hosting_provider' => __('Hosting Provider'), 'consultant' => __('Consultant'), 'digitization' => __('Digitization Service'), 'training' => __('Training Provider'), 'integrator' => __('Systems Integrator'), 'service_provider' => __('Service Provider'), 'hosting' => __('Hosting'), 'reseller' => __('Reseller'), 'other' => __('Other')];
                $rawVt = sfOutputEscaper::unescape($f->vendor_type ?? '[]');
                $selTypes = is_array($rawVt) ? $rawVt : (is_string($rawVt) ? (json_decode($rawVt, true) ?: []) : []);
                foreach ($vTypes as $val => $label): ?>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="vendor_type[]" value="<?php echo $val; ?>" id="vr-type-<?php echo $val; ?>"<?php echo in_array($val, $selTypes) ? ' checked' : ''; ?>>
                    <label class="form-check-label" for="vr-type-<?php echo $val; ?>"><?php echo $label; ?></label>
                  </div>
              <?php endforeach; ?>
            </div>
            <div class="col-12">
              <label for="vr-short-desc" class="form-label"><?php echo __('Short Description'); ?></label>
              <input type="text" class="form-control" id="vr-short-desc" name="short_description" value="<?php echo htmlspecialchars($f->short_description ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="255">
            </div>
            <div class="col-12">
              <label for="vr-desc" class="form-label"><?php echo __('Description'); ?></label>
              <textarea class="form-control" id="vr-desc" name="description" rows="4"><?php echo htmlspecialchars($f->description ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div class="col-12">
              <label for="vr-logo" class="form-label"><?php echo __('Logo'); ?></label>
              <div class="border rounded p-3 text-center position-relative" id="vr-logo-drop" style="min-height: 120px; cursor: pointer;">
                <div id="vr-logo-preview">
                  <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                  <p class="mb-1"><?php echo __('Drag and drop your logo here, or click to browse'); ?></p>
                  <small class="text-muted"><?php echo __('PNG, JPG, SVG. Max 2MB. Recommended: 200x200px.'); ?></small>
                </div>
                <input type="file" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" id="vr-logo" name="logo" accept="image/png,image/jpeg,image/svg+xml" style="cursor: pointer;">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Contact -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-globe me-2 text-info"></i><?php echo __('Contact & Online Presence'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label for="vr-website" class="form-label"><?php echo __('Website'); ?></label>
              <input type="url" class="form-control" id="vr-website" name="website" value="<?php echo htmlspecialchars($f->website ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://www.example.com">
            </div>
            <div class="col-md-4">
              <label for="vr-email" class="form-label"><?php echo __('Email'); ?></label>
              <input type="email" class="form-control" id="vr-email" name="email" value="<?php echo htmlspecialchars($f->email ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="vr-phone" class="form-label"><?php echo __('Phone'); ?></label>
              <input type="tel" class="form-control" id="vr-phone" name="phone" value="<?php echo htmlspecialchars($f->phone ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Address -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-map-marker-alt me-2 text-danger"></i><?php echo __('Address'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12">
              <label for="vr-street" class="form-label"><?php echo __('Street Address'); ?></label>
              <input type="text" class="form-control" id="vr-street" name="street_address" value="<?php echo htmlspecialchars($f->street_address ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="vr-city" class="form-label"><?php echo __('City'); ?></label>
              <input type="text" class="form-control" id="vr-city" name="city" value="<?php echo htmlspecialchars($f->city ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="vr-province" class="form-label"><?php echo __('Province / State'); ?></label>
              <input type="text" class="form-control" id="vr-province" name="province_state" value="<?php echo htmlspecialchars($f->province_state ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="vr-postal" class="form-label"><?php echo __('Postal Code'); ?></label>
              <input type="text" class="form-control" id="vr-postal" name="postal_code" value="<?php echo htmlspecialchars($f->postal_code ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="vr-country" class="form-label"><?php echo __('Country'); ?></label>
              <input type="text" class="form-control" id="vr-country" name="country" value="<?php echo htmlspecialchars($f->country ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="South Africa">
            </div>
          </div>
        </div>
      </div>

      <!-- Company details -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-building me-2 text-success"></i><?php echo __('Company Details'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label for="vr-reg" class="form-label"><?php echo __('Company Registration'); ?></label>
              <input type="text" class="form-control" id="vr-reg" name="company_registration" value="<?php echo htmlspecialchars($f->company_registration ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="vr-vat" class="form-label"><?php echo __('VAT Number'); ?></label>
              <input type="text" class="form-control" id="vr-vat" name="vat_number" value="<?php echo htmlspecialchars($f->vat_number ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="vr-established" class="form-label"><?php echo __('Established Year'); ?></label>
              <input type="number" class="form-control" id="vr-established" name="established_year" value="<?php echo htmlspecialchars($f->established_year ?? '', ENT_QUOTES, 'UTF-8'); ?>" min="1900" max="2099">
            </div>
            <div class="col-md-4">
              <label for="vr-team" class="form-label"><?php echo __('Team Size'); ?></label>
              <select class="form-select" id="vr-team" name="team_size">
                <option value=""><?php echo __('-- Select --'); ?></option>
                <?php
                  $teamSizes = ['solo' => __('Solo'), '2-5' => '2-5', '6-20' => '6-20', '21-50' => '21-50', '50+' => '50+'];
                  $selTeam = $f->team_size ?? '';
                  foreach ($teamSizes as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selTeam === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="vr-regions" class="form-label"><?php echo __('Service Regions'); ?></label>
              <input type="text" class="form-control" id="vr-regions" name="service_regions" value="<?php echo htmlspecialchars($f->service_regions ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Comma-separated: Southern Africa, Europe'); ?>">
            </div>
            <div class="col-md-4">
              <label for="vr-langs" class="form-label"><?php echo __('Languages'); ?></label>
              <input type="text" class="form-control" id="vr-langs" name="languages" value="<?php echo htmlspecialchars($f->languages ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('English, Afrikaans'); ?>">
            </div>
            <div class="col-12">
              <label for="vr-certs" class="form-label"><?php echo __('Certifications'); ?></label>
              <input type="text" class="form-control" id="vr-certs" name="certifications" value="<?php echo htmlspecialchars($f->certifications ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Comma-separated: ISO 27001, TOGAF'); ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Social / Git links -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fab fa-github me-2 text-dark"></i><?php echo __('Online Profiles'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label for="vr-github" class="form-label"><i class="fab fa-github me-1"></i> <?php echo __('GitHub URL'); ?></label>
              <input type="url" class="form-control" id="vr-github" name="github_url" value="<?php echo htmlspecialchars($f->github_url ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://github.com/org">
            </div>
            <div class="col-md-4">
              <label for="vr-gitlab" class="form-label"><i class="fab fa-gitlab me-1"></i> <?php echo __('GitLab URL'); ?></label>
              <input type="url" class="form-control" id="vr-gitlab" name="gitlab_url" value="<?php echo htmlspecialchars($f->gitlab_url ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="vr-linkedin" class="form-label"><i class="fab fa-linkedin me-1"></i> <?php echo __('LinkedIn URL'); ?></label>
              <input type="url" class="form-control" id="vr-linkedin" name="linkedin_url" value="<?php echo htmlspecialchars($f->linkedin_url ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'index']); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i> <?php echo __('Register as Vendor'); ?></button>
      </div>

    </form>

  </div>
</div>

<script <?php echo $na; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var inp = document.getElementById('vr-logo');
  var prev = document.getElementById('vr-logo-preview');
  var drop = document.getElementById('vr-logo-drop');
  if (inp) {
    inp.addEventListener('change', function(e) {
      if (e.target.files && e.target.files[0]) {
        var r = new FileReader();
        r.onload = function(ev) { prev.innerHTML = '<img src="' + ev.target.result + '" alt="Preview" style="max-height: 100px; max-width: 200px;" class="mb-2"><br><small class="text-muted">' + e.target.files[0].name + '</small>'; };
        r.readAsDataURL(e.target.files[0]);
      }
    });
  }
  if (drop) {
    ['dragenter','dragover'].forEach(function(evt) { drop.addEventListener(evt, function(e) { e.preventDefault(); drop.classList.add('border-primary'); }); });
    ['dragleave','drop'].forEach(function(evt) { drop.addEventListener(evt, function(e) { e.preventDefault(); drop.classList.remove('border-primary'); }); });
    drop.addEventListener('drop', function(e) { if (e.dataTransfer.files.length) { inp.files = e.dataTransfer.files; inp.dispatchEvent(new Event('change')); } });
  }
});
</script>

<?php end_slot(); ?>

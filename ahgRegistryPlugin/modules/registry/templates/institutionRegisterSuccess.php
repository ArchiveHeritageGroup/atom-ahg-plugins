<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Register Institution'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Register Institution')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-10 col-xl-9">

    <h1 class="h3 mb-2"><?php echo __('Register Your Institution'); ?></h1>
    <p class="text-muted mb-4"><?php echo __('Add your institution to the global AtoM directory. Fields marked with * are required.'); ?></p>

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

    <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'institutionRegister']); ?>" enctype="multipart/form-data">

      <!-- Section 1: Basic Info -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-info-circle me-2 text-primary"></i><?php echo __('Basic Information'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label for="reg-name" class="form-label"><?php echo __('Institution Name'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="reg-name" name="name" value="<?php echo htmlspecialchars($f->name ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="col-md-4">
              <label for="reg-type" class="form-label"><?php echo __('Institution Type'); ?></label>
              <select class="form-select" id="reg-type" name="institution_type">
                <?php
                  $types = \AhgRegistry\Services\DropdownService::getOptions('institution_type');
                  $selType = $f->institution_type ?? 'archive';
                  foreach ($types as $val => $label): ?>
                    <option value="<?php echo htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $selType === $val ? ' selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label for="reg-short-desc" class="form-label"><?php echo __('Short Description'); ?></label>
              <input type="text" class="form-control" id="reg-short-desc" name="short_description" value="<?php echo htmlspecialchars($f->short_description ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="255" placeholder="<?php echo __('Brief one-line description'); ?>">
            </div>
            <div class="col-12">
              <label for="reg-desc" class="form-label"><?php echo __('Description'); ?></label>
              <textarea class="form-control" id="reg-desc" name="description" rows="4" placeholder="<?php echo __('Full description of your institution, its mission, and scope...'); ?>"><?php echo htmlspecialchars($f->description ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div class="col-12">
              <label for="reg-logo" class="form-label"><?php echo __('Logo'); ?></label>
              <div class="border rounded p-3 text-center position-relative" id="logo-drop-zone" style="min-height: 120px; cursor: pointer;">
                <div id="logo-preview-area">
                  <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                  <p class="mb-1"><?php echo __('Drag and drop your logo here, or click to browse'); ?></p>
                  <small class="text-muted"><?php echo __('PNG, JPG, SVG. Max 2MB. Recommended: 200x200px.'); ?></small>
                </div>
                <input type="file" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" id="reg-logo" name="logo" accept="image/png,image/jpeg,image/svg+xml" style="cursor: pointer;">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 2: Location -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-map-marker-alt me-2 text-danger"></i><?php echo __('Location'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12">
              <label for="reg-street" class="form-label"><?php echo __('Street Address'); ?></label>
              <input type="text" class="form-control" id="reg-street" name="street_address" value="<?php echo htmlspecialchars($f->street_address ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="reg-city" class="form-label"><?php echo __('City'); ?></label>
              <input type="text" class="form-control" id="reg-city" name="city" value="<?php echo htmlspecialchars($f->city ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="reg-province" class="form-label"><?php echo __('Province / State'); ?></label>
              <input type="text" class="form-control" id="reg-province" name="province_state" value="<?php echo htmlspecialchars($f->province_state ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="reg-postal" class="form-label"><?php echo __('Postal Code'); ?></label>
              <input type="text" class="form-control" id="reg-postal" name="postal_code" value="<?php echo htmlspecialchars($f->postal_code ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="reg-country" class="form-label"><?php echo __('Country'); ?></label>
              <input type="text" class="form-control" id="reg-country" name="country" value="<?php echo htmlspecialchars($f->country ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="South Africa">
            </div>
            <div class="col-md-4">
              <label for="reg-lat" class="form-label"><?php echo __('Latitude'); ?></label>
              <input type="number" step="any" class="form-control" id="reg-lat" name="latitude" value="<?php echo htmlspecialchars($f->latitude ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="-33.9249">
            </div>
            <div class="col-md-4">
              <label for="reg-lng" class="form-label"><?php echo __('Longitude'); ?></label>
              <input type="number" step="any" class="form-control" id="reg-lng" name="longitude" value="<?php echo htmlspecialchars($f->longitude ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="18.4241">
            </div>
          </div>
        </div>
      </div>

      <!-- Section 3: Online Presence -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-globe me-2 text-info"></i><?php echo __('Online Presence'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="reg-website" class="form-label"><?php echo __('Website URL'); ?></label>
              <input type="url" class="form-control" id="reg-website" name="website" value="<?php echo htmlspecialchars($f->website ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://www.example.org">
            </div>
            <div class="col-md-6">
              <label for="reg-inst-url" class="form-label"><?php echo __('AtoM Instance URL'); ?></label>
              <input type="url" class="form-control" id="reg-inst-url" name="institution_url" value="<?php echo htmlspecialchars($f->institution_url ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://archives.example.org">
              <div class="form-text"><?php echo __('Direct URL to your AtoM instance, if applicable.'); ?></div>
            </div>
            <div class="col-md-4">
              <label for="reg-email" class="form-label"><?php echo __('Email'); ?></label>
              <input type="email" class="form-control" id="reg-email" name="email" value="<?php echo htmlspecialchars($f->email ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="reg-phone" class="form-label"><?php echo __('Phone'); ?></label>
              <input type="tel" class="form-control" id="reg-phone" name="phone" value="<?php echo htmlspecialchars($f->phone ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="reg-fax" class="form-label"><?php echo __('Fax'); ?></label>
              <input type="tel" class="form-control" id="reg-fax" name="fax" value="<?php echo htmlspecialchars($f->fax ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="reg-open-public" name="open_to_public" value="1"<?php echo !empty($f->open_to_public) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="reg-open-public"><?php echo __('Open to the public'); ?></label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 4: Organization -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-sitemap me-2 text-success"></i><?php echo __('Organization'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label for="reg-size" class="form-label"><?php echo __('Size'); ?></label>
              <select class="form-select" id="reg-size" name="size">
                <option value=""><?php echo __('-- Select --'); ?></option>
                <?php
                  $sizes = ['small' => __('Small'), 'medium' => __('Medium'), 'large' => __('Large'), 'national' => __('National')];
                  $selSize = $f->size ?? '';
                  foreach ($sizes as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selSize === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="reg-governance" class="form-label"><?php echo __('Governance'); ?></label>
              <select class="form-select" id="reg-governance" name="governance">
                <option value=""><?php echo __('-- Select --'); ?></option>
                <?php
                  $govTypes = ['public' => __('Public'), 'private' => __('Private'), 'ngo' => __('NGO'), 'academic' => __('Academic'), 'government' => __('Government'), 'tribal' => __('Tribal'), 'community' => __('Community')];
                  $selGov = $f->governance ?? '';
                  foreach ($govTypes as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selGov === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="reg-established" class="form-label"><?php echo __('Established Year'); ?></label>
              <input type="number" class="form-control" id="reg-established" name="established_year" value="<?php echo htmlspecialchars($f->established_year ?? '', ENT_QUOTES, 'UTF-8'); ?>" min="1000" max="2099" placeholder="1990">
            </div>
            <div class="col-md-6">
              <label for="reg-parent" class="form-label"><?php echo __('Parent Body'); ?></label>
              <input type="text" class="form-control" id="reg-parent" name="parent_body" value="<?php echo htmlspecialchars($f->parent_body ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('e.g., City of Vancouver, University of Cape Town'); ?>">
            </div>
            <div class="col-md-6">
              <label for="reg-accreditation" class="form-label"><?php echo __('Accreditation'); ?></label>
              <input type="text" class="form-control" id="reg-accreditation" name="accreditation" value="<?php echo htmlspecialchars($f->accreditation ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('e.g., NARSSA, CAN/CGSB-72.34'); ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Section 5: Collections -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-archive me-2 text-warning"></i><?php echo __('Collections'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12">
              <label for="reg-coll-summary" class="form-label"><?php echo __('Collection Summary'); ?></label>
              <textarea class="form-control" id="reg-coll-summary" name="collection_summary" rows="3" placeholder="<?php echo __('Brief summary of your collections, scope, and focus areas...'); ?>"><?php echo htmlspecialchars($f->collection_summary ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div class="col-md-6">
              <label for="reg-strengths" class="form-label"><?php echo __('Collection Strengths'); ?></label>
              <input type="text" class="form-control" id="reg-strengths" name="collection_strengths" value="<?php echo htmlspecialchars($f->collection_strengths ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Comma-separated tags: photographs, maps, government records...'); ?>">
              <div class="form-text"><?php echo __('Enter comma-separated values.'); ?></div>
            </div>
            <div class="col-md-6">
              <label for="reg-holdings-analog" class="form-label"><?php echo __('Analog Holdings'); ?></label>
              <textarea class="form-control" id="reg-holdings-analog" name="holdings_analog" rows="4" placeholder="<?php echo __('e.g., 500 linear metres of records, 12,000 photographs, 8,000 books, 200 maps'); ?>"><?php echo htmlspecialchars($f->holdings_analog ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
              <div class="form-text"><?php echo __('Physical holdings — free-form. Use any units.'); ?></div>
            </div>
            <div class="col-md-6">
              <label for="reg-holdings-digital" class="form-label"><?php echo __('Digital Holdings'); ?></label>
              <textarea class="form-control" id="reg-holdings-digital" name="holdings_digital" rows="4" placeholder="<?php echo __('e.g., 2 TB images, 500 GB audio/video, 80,000 digital files'); ?>"><?php echo htmlspecialchars($f->holdings_digital ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
              <div class="form-text"><?php echo __('Born-digital and digitised holdings — free-form.'); ?></div>
            </div>
            <div class="col-md-3">
              <label for="reg-digitization" class="form-label"><?php echo __('Digitization %'); ?></label>
              <div class="input-group">
                <input type="number" class="form-control" id="reg-digitization" name="digitization_percentage" value="<?php echo htmlspecialchars($f->digitization_percentage ?? '', ENT_QUOTES, 'UTF-8'); ?>" min="0" max="100">
                <span class="input-group-text">%</span>
              </div>
            </div>
            <div class="col-md-6">
              <label for="reg-mgmt-system" class="form-label"><?php echo __('Collection Management System'); ?></label>
              <input type="text" class="form-control" id="reg-mgmt-system" name="management_system" value="<?php echo htmlspecialchars($f->management_system ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('e.g., AtoM, ArchivesSpace, Koha'); ?>">
            </div>
            <div class="col-md-6">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="reg-heratio" name="uses_atom" value="1"<?php echo !empty($f->uses_atom) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="reg-heratio"><?php echo __('Uses AtoM'); ?></label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 6: Standards & Systems -->
      <div class="card mb-4">
        <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
          <span><i class="fas fa-list-check me-2 text-secondary"></i><?php echo __('Standards & Systems'); ?></span>
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'standardBrowse']); ?>" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="fas fa-external-link-alt me-1"></i><?php echo __('Browse Standards'); ?></a>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-3"><?php echo __('Select all standards used by your institution. Click a standard name to view its details.'); ?></p>
          <?php
            $selectedStandards = [];
            if (!empty($f->descriptive_standards)) {
              $rawDescriptiveStandards = sfOutputEscaper::unescape($f->descriptive_standards);
              $selectedStandards = is_string($rawDescriptiveStandards) ? json_decode($rawDescriptiveStandards, true) : (array) $rawDescriptiveStandards;
              if (!is_array($selectedStandards)) { $selectedStandards = []; }
            }

            $grouped = [];
            if (isset($dbStandards)) {
              foreach ($dbStandards as $std) {
                $cat = $std->category ?? 'other';
                $grouped[$cat][] = $std;
              }
            }

            $catLabels = [
              'descriptive' => __('Descriptive Standards'),
              'metadata' => __('Metadata Standards'),
              'interchange' => __('Interchange Formats'),
              'preservation' => __('Digital Preservation'),
              'rights' => __('Rights & Licensing'),
              'sector' => __('Sector Standards'),
              'compliance' => __('Compliance & Regulatory'),
              'accounting' => __('Heritage Accounting'),
            ];
            $catIcons = [
              'descriptive' => 'fa-file-alt', 'metadata' => 'fa-tags', 'interchange' => 'fa-exchange-alt',
              'preservation' => 'fa-shield-alt', 'rights' => 'fa-balance-scale', 'sector' => 'fa-industry',
              'compliance' => 'fa-gavel', 'accounting' => 'fa-calculator',
            ];
          ?>
          <div class="accordion" id="standards-accordion">
            <?php $idx = 0; foreach ($grouped as $cat => $stds): $idx++; ?>
            <div class="accordion-item">
              <h2 class="accordion-header" id="reg-std-head-<?php echo $idx; ?>">
                <button class="accordion-button<?php echo $idx > 1 ? ' collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#reg-std-body-<?php echo $idx; ?>" aria-expanded="<?php echo $idx === 1 ? 'true' : 'false'; ?>">
                  <i class="fas <?php echo $catIcons[$cat] ?? 'fa-list'; ?> me-2 text-secondary"></i>
                  <?php echo $catLabels[$cat] ?? ucfirst($cat); ?>
                  <span class="badge bg-secondary ms-2"><?php echo count($stds); ?></span>
                </button>
              </h2>
              <div id="reg-std-body-<?php echo $idx; ?>" class="accordion-collapse collapse<?php echo $idx === 1 ? ' show' : ''; ?>" data-bs-parent="#standards-accordion">
                <div class="accordion-body">
                  <div class="row">
                    <?php foreach ($stds as $std):
                      $matchVal = $std->acronym ?: $std->name;
                      $isChecked = is_array($selectedStandards) && (in_array($matchVal, $selectedStandards) || in_array($std->name, $selectedStandards) || in_array($std->acronym, $selectedStandards));
                      $cbId = 'reg-std-' . $std->id;
                    ?>
                    <div class="col-md-6">
                      <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="<?php echo $cbId; ?>" name="descriptive_standards[]" value="<?php echo htmlspecialchars($matchVal, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isChecked ? ' checked' : ''; ?>>
                        <label class="form-check-label" for="<?php echo $cbId; ?>">
                          <?php if ($std->acronym): ?>
                            <strong><?php echo htmlspecialchars($std->acronym, ENT_QUOTES, 'UTF-8'); ?></strong> —
                          <?php endif; ?>
                          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'standardView', 'slug' => $std->slug]); ?>" target="_blank" class="text-decoration-none"><?php echo htmlspecialchars($std->name, ENT_QUOTES, 'UTF-8'); ?></a>
                        </label>
                      </div>
                    </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'index']); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> <?php echo __('Register Institution'); ?></button>
      </div>

    </form>

  </div>
</div>

<!-- Logo preview script -->
<script <?php echo $na; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var logoInput = document.getElementById('reg-logo');
  var previewArea = document.getElementById('logo-preview-area');
  var dropZone = document.getElementById('logo-drop-zone');

  if (logoInput) {
    logoInput.addEventListener('change', function(e) {
      if (e.target.files && e.target.files[0]) {
        var reader = new FileReader();
        reader.onload = function(ev) {
          previewArea.innerHTML = '<img src="' + ev.target.result + '" alt="Logo preview" style="max-height: 100px; max-width: 200px;" class="mb-2"><br><small class="text-muted">' + e.target.files[0].name + '</small>';
        };
        reader.readAsDataURL(e.target.files[0]);
      }
    });
  }

  if (dropZone) {
    ['dragenter', 'dragover'].forEach(function(evt) {
      dropZone.addEventListener(evt, function(e) { e.preventDefault(); dropZone.classList.add('border-primary'); });
    });
    ['dragleave', 'drop'].forEach(function(evt) {
      dropZone.addEventListener(evt, function(e) { e.preventDefault(); dropZone.classList.remove('border-primary'); });
    });
    dropZone.addEventListener('drop', function(e) {
      if (e.dataTransfer.files.length) {
        logoInput.files = e.dataTransfer.files;
        logoInput.dispatchEvent(new Event('change'));
      }
    });
  }
});
</script>

<?php end_slot(); ?>

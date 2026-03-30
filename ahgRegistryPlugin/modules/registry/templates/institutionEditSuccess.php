<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Edit Institution'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Institution'), 'url' => url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard'])],
  ['label' => __('Edit')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-10 col-xl-9">

    <h1 class="h3 mb-4"><?php echo __('Edit Institution Profile'); ?></h1>

    <?php if (isset($errors) && count($errors) > 0): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php $f = $institution; ?>

    <form method="post" action="<?php echo url_for(array_merge(['module' => 'registry', 'action' => 'institutionEdit'], isset($sf_request) && $sf_request->getParameter('id') ? ['id' => (int) $sf_request->getParameter('id')] : [])); ?>" enctype="multipart/form-data">

      <!-- Section 1: Basic Info -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-info-circle me-2 text-primary"></i><?php echo __('Basic Information'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label for="edit-name" class="form-label"><?php echo __('Institution Name'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="edit-name" name="name" value="<?php echo htmlspecialchars($f->name ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="col-md-4">
              <label for="edit-type" class="form-label"><?php echo __('Institution Type'); ?></label>
              <select class="form-select" id="edit-type" name="institution_type">
                <?php
                  $types = \AhgRegistry\Services\DropdownService::getOptions('institution_type');
                  $selType = $f->institution_type ?? 'archive';
                  foreach ($types as $val => $label): ?>
                    <option value="<?php echo htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $selType === $val ? ' selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label for="edit-short-desc" class="form-label"><?php echo __('Short Description'); ?></label>
              <input type="text" class="form-control" id="edit-short-desc" name="short_description" value="<?php echo htmlspecialchars($f->short_description ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="255">
            </div>
            <div class="col-12">
              <label for="edit-desc" class="form-label"><?php echo __('Description'); ?></label>
              <textarea class="form-control" id="edit-desc" name="description" rows="4"><?php echo htmlspecialchars($f->description ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div class="col-12">
              <label for="edit-logo" class="form-label"><?php echo __('Logo'); ?></label>
              <?php if (!empty($f->logo_path)): ?>
                <div class="mb-2">
                  <img src="<?php echo htmlspecialchars($f->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo __('Current logo'); ?>" class="rounded border" style="max-height: 80px; max-width: 200px;">
                  <small class="text-muted d-block mt-1"><?php echo __('Current logo. Upload a new file to replace.'); ?></small>
                </div>
              <?php endif; ?>
              <div class="border rounded p-3 text-center position-relative" id="logo-drop-zone" style="min-height: 100px; cursor: pointer;">
                <div id="logo-preview-area">
                  <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                  <p class="mb-0 small"><?php echo __('Drag and drop a new logo, or click to browse'); ?></p>
                  <small class="text-muted"><?php echo __('PNG, JPG, SVG. Max 2MB.'); ?></small>
                </div>
                <input type="file" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" id="edit-logo" name="logo" accept="image/png,image/jpeg,image/svg+xml" style="cursor: pointer;">
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
              <label for="edit-street" class="form-label"><?php echo __('Street Address'); ?></label>
              <input type="text" class="form-control" id="edit-street" name="street_address" value="<?php echo htmlspecialchars($f->street_address ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="edit-city" class="form-label"><?php echo __('City'); ?></label>
              <input type="text" class="form-control" id="edit-city" name="city" value="<?php echo htmlspecialchars($f->city ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="edit-province" class="form-label"><?php echo __('Province / State'); ?></label>
              <input type="text" class="form-control" id="edit-province" name="province_state" value="<?php echo htmlspecialchars($f->province_state ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="edit-postal" class="form-label"><?php echo __('Postal Code'); ?></label>
              <input type="text" class="form-control" id="edit-postal" name="postal_code" value="<?php echo htmlspecialchars($f->postal_code ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="edit-country" class="form-label"><?php echo __('Country'); ?></label>
              <input type="text" class="form-control" id="edit-country" name="country" value="<?php echo htmlspecialchars($f->country ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="edit-lat" class="form-label"><?php echo __('Latitude'); ?></label>
              <input type="number" step="any" class="form-control" id="edit-lat" name="latitude" value="<?php echo htmlspecialchars($f->latitude ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="edit-lng" class="form-label"><?php echo __('Longitude'); ?></label>
              <input type="number" step="any" class="form-control" id="edit-lng" name="longitude" value="<?php echo htmlspecialchars($f->longitude ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Section 3: Online Presence -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-globe me-2 text-info"></i><?php echo __('Online Presence'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label for="edit-website" class="form-label"><?php echo __('Institution Website URL'); ?></label>
              <input type="url" class="form-control" id="edit-website" name="website" value="<?php echo htmlspecialchars($f->website ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://www.example.org">
            </div>
            <div class="col-md-4">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="edit-open-public" name="open_to_public" value="1"<?php echo !empty($f->open_to_public) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="edit-open-public"><?php echo __('Open to the public'); ?></label>
              </div>
            </div>
          </div>
          <div class="form-text text-muted mt-2"><?php echo __('Contact details (email, phone) should be added in the Contacts section for specific people.'); ?></div>
        </div>
      </div>

      <!-- Section 4: Organization -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-sitemap me-2 text-success"></i><?php echo __('Organization'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label for="edit-size" class="form-label"><?php echo __('Size'); ?></label>
              <select class="form-select" id="edit-size" name="size">
                <option value=""><?php echo __('-- Select --'); ?></option>
                <?php $sizes = ['small' => __('Small (1–5 staff)'), 'medium' => __('Medium (6–20 staff)'), 'large' => __('Large (21+ staff)')]; $selSize = $f->size ?? '';
                  foreach ($sizes as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selSize === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="edit-governance" class="form-label"><?php echo __('Governance'); ?></label>
              <select class="form-select" id="edit-governance" name="governance">
                <option value=""><?php echo __('-- Select --'); ?></option>
                <?php $govTypes = ['public' => __('Public / Government'), 'private' => __('Private'), 'ngo' => __('NGO / Non-Profit'), 'academic' => __('Academic'), 'indigenous' => __('Indigenous'), 'community' => __('Community')]; $selGov = $f->governance ?? '';
                  foreach ($govTypes as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selGov === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="edit-established" class="form-label"><?php echo __('Established Year'); ?></label>
              <input type="number" class="form-control" id="edit-established" name="established_year" value="<?php echo htmlspecialchars($f->established_year ?? '', ENT_QUOTES, 'UTF-8'); ?>" min="1000" max="2099">
            </div>
            <div class="col-md-6">
              <label for="edit-parent" class="form-label"><?php echo __('Parent Body'); ?></label>
              <input type="text" class="form-control" id="edit-parent" name="parent_body" value="<?php echo htmlspecialchars($f->parent_body ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-6">
              <label for="edit-accreditation" class="form-label"><?php echo __('Accreditation'); ?></label>
              <input type="text" class="form-control" id="edit-accreditation" name="accreditation" value="<?php echo htmlspecialchars($f->accreditation ?? '', ENT_QUOTES, 'UTF-8'); ?>">
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
              <label for="edit-coll-summary" class="form-label"><?php echo __('Collection Summary'); ?></label>
              <textarea class="form-control" id="edit-coll-summary" name="collection_summary" rows="3"><?php echo htmlspecialchars($f->collection_summary ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div class="col-md-6">
              <label for="edit-strengths" class="form-label"><?php echo __('Collection Strengths'); ?></label>
              <?php
                $strengthsVal = '';
                if (!empty($f->collection_strengths)) {
                  $rawStr = sfOutputEscaper::unescape($f->collection_strengths);
                  $decoded = is_string($rawStr) ? json_decode($rawStr, true) : (array) $rawStr;
                  $strengthsVal = is_array($decoded) ? implode(', ', $decoded) : ($rawStr ?? '');
                }
              ?>
              <input type="text" class="form-control" id="edit-strengths" name="collection_strengths" value="<?php echo htmlspecialchars($strengthsVal, ENT_QUOTES, 'UTF-8'); ?>">
              <div class="form-text"><?php echo __('Comma-separated tags.'); ?></div>
            </div>
            <div class="col-md-3">
              <label for="edit-holdings" class="form-label"><?php echo __('Total Holdings'); ?></label>
              <input type="text" class="form-control" id="edit-holdings" name="total_holdings" value="<?php echo htmlspecialchars($f->total_holdings ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-3">
              <label for="edit-digitization" class="form-label"><?php echo __('Digitization %'); ?></label>
              <div class="input-group">
                <input type="number" class="form-control" id="edit-digitization" name="digitization_percentage" value="<?php echo htmlspecialchars($f->digitization_percentage ?? '', ENT_QUOTES, 'UTF-8'); ?>" min="0" max="100">
                <span class="input-group-text">%</span>
              </div>
            </div>
            <div class="col-md-6">
              <label for="edit-mgmt-system" class="form-label"><?php echo __('Collection Management System'); ?></label>
              <input type="text" class="form-control" id="edit-mgmt-system" name="management_system" value="<?php echo htmlspecialchars($f->management_system ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-6">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="edit-heratio" name="uses_atom" value="1"<?php echo !empty($f->uses_atom) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="edit-heratio"><?php echo __('Uses AtoM'); ?></label>
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
              $rawStd = sfOutputEscaper::unescape($f->descriptive_standards);
              $selectedStandards = is_string($rawStd) ? json_decode($rawStd, true) : (array) $rawStd;
              if (!is_array($selectedStandards)) { $selectedStandards = []; }
            }

            // Group standards by category
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
              <h2 class="accordion-header" id="std-head-<?php echo $idx; ?>">
                <button class="accordion-button<?php echo $idx > 1 ? ' collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#std-body-<?php echo $idx; ?>" aria-expanded="<?php echo $idx === 1 ? 'true' : 'false'; ?>">
                  <i class="fas <?php echo $catIcons[$cat] ?? 'fa-list'; ?> me-2 text-secondary"></i>
                  <?php echo $catLabels[$cat] ?? ucfirst($cat); ?>
                  <span class="badge bg-secondary ms-2"><?php echo count($stds); ?></span>
                </button>
              </h2>
              <div id="std-body-<?php echo $idx; ?>" class="accordion-collapse collapse<?php echo $idx === 1 ? ' show' : ''; ?>" data-bs-parent="#standards-accordion">
                <div class="accordion-body">
                  <div class="row">
                    <?php foreach ($stds as $std):
                      // Match by acronym or name
                      $matchVal = $std->acronym ?: $std->name;
                      $isChecked = in_array($matchVal, $selectedStandards) || in_array($std->name, $selectedStandards) || in_array($std->acronym, $selectedStandards);
                      $cbId = 'edit-std-' . $std->id;
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

      <!-- Tags -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-tags me-2 text-secondary"></i><?php echo __('Tags'); ?></div>
        <div class="card-body">
          <label for="ie-tags" class="form-label"><?php echo __('Tags (comma-separated)'); ?></label>
          <?php
            $currentTags = [];
            if (!empty($institutionTags)) {
              foreach ($institutionTags as $t) { $currentTags[] = $t->tag; }
            }
          ?>
          <input type="text" class="form-control" id="ie-tags" name="tags" value="<?php echo htmlspecialchars(implode(', ', $currentTags), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('e.g., archive, heritage, photographs, manuscripts'); ?>">
          <small class="form-text text-muted"><?php echo __('Enter tags separated by commas. Used for discoverability.'); ?></small>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard']); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> <?php echo __('Save Changes'); ?></button>
      </div>

    </form>

  </div>
</div>

<!-- Logo preview script -->
<script <?php echo $na; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var logoInput = document.getElementById('edit-logo');
  var previewArea = document.getElementById('logo-preview-area');
  var dropZone = document.getElementById('logo-drop-zone');
  if (logoInput) {
    logoInput.addEventListener('change', function(e) {
      if (e.target.files && e.target.files[0]) {
        var reader = new FileReader();
        reader.onload = function(ev) {
          previewArea.innerHTML = '<img src="' + ev.target.result + '" alt="Preview" style="max-height: 80px; max-width: 200px;" class="mb-1"><br><small class="text-muted">' + e.target.files[0].name + '</small>';
        };
        reader.readAsDataURL(e.target.files[0]);
      }
    });
  }
  if (dropZone) {
    ['dragenter','dragover'].forEach(function(evt) { dropZone.addEventListener(evt, function(e) { e.preventDefault(); dropZone.classList.add('border-primary'); }); });
    ['dragleave','drop'].forEach(function(evt) { dropZone.addEventListener(evt, function(e) { e.preventDefault(); dropZone.classList.remove('border-primary'); }); });
    dropZone.addEventListener('drop', function(e) { if (e.dataTransfer.files.length) { logoInput.files = e.dataTransfer.files; logoInput.dispatchEvent(new Event('change')); } });
  }
});
</script>

<?php end_slot(); ?>

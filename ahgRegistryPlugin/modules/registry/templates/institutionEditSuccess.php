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
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="edit-open-public" name="open_to_public" value="1"<?php echo !empty($f->open_to_public) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="edit-open-public"><?php echo __('Open to the public'); ?></label>
                <div class="form-text"><?php echo __('Collections are accessible to researchers and the general public, whether online or in-person.'); ?></div>
              </div>
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
          <p class="text-muted small mb-3"><?php echo __('Add every URL relevant to this institution — main website, AtoM instance, digital repository, online catalogue, social profiles, etc. Contact details (email, phone) belong in the Contacts section.'); ?></p>
          <?php
            $urlTypes = $entityUrlTypes ?? [];
            $existing = isset($entityUrls) && is_array($entityUrls) ? $entityUrls : [];
            // Always render at least one empty row for new entries
            if (empty($existing)) {
              $existing = [(object) ['link_type' => 'website', 'url' => $f->website ?? '', 'label' => '']];
            }
          ?>
          <div id="url-list">
            <?php foreach ($existing as $i => $row):
              $rowType = is_object($row) ? ($row->link_type ?? 'website') : ($row['link_type'] ?? 'website');
              $rowUrl = is_object($row) ? ($row->url ?? '') : ($row['url'] ?? '');
              $rowLabel = is_object($row) ? ($row->label ?? '') : ($row['label'] ?? '');
            ?>
              <div class="row g-2 mb-2 url-row">
                <div class="col-md-3">
                  <select class="form-select" name="url_types[]">
                    <?php foreach ($urlTypes as $tVal => $tLabel): ?>
                      <option value="<?php echo htmlspecialchars($tVal, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $rowType === $tVal ? ' selected' : ''; ?>><?php echo htmlspecialchars(__($tLabel), ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <input type="url" class="form-control" name="urls[]" value="<?php echo htmlspecialchars($rowUrl, ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://">
                </div>
                <div class="col-md-2">
                  <input type="text" class="form-control" name="url_labels[]" value="<?php echo htmlspecialchars($rowLabel, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Label (optional)'); ?>">
                </div>
                <div class="col-md-1 d-grid">
                  <button type="button" class="btn btn-outline-danger btn-sm url-remove" title="<?php echo __('Remove'); ?>"><i class="fas fa-times"></i></button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn btn-sm btn-outline-primary" id="url-add"><i class="fas fa-plus me-1"></i><?php echo __('Add URL'); ?></button>
        </div>
      </div>

      <script <?php echo $na; ?>>
      document.addEventListener('DOMContentLoaded', function() {
        var list = document.getElementById('url-list');
        var addBtn = document.getElementById('url-add');
        if (!list || !addBtn) return;
        function bindRemove(row) {
          var btn = row.querySelector('.url-remove');
          if (btn) {
            btn.addEventListener('click', function() {
              if (list.querySelectorAll('.url-row').length > 1) {
                row.remove();
              } else {
                row.querySelectorAll('input').forEach(function(i) { i.value = ''; });
              }
            });
          }
        }
        list.querySelectorAll('.url-row').forEach(bindRemove);
        addBtn.addEventListener('click', function() {
          var first = list.querySelector('.url-row');
          if (!first) return;
          var clone = first.cloneNode(true);
          clone.querySelectorAll('input').forEach(function(i) { i.value = ''; });
          var sel = clone.querySelector('select');
          if (sel) sel.selectedIndex = 0;
          list.appendChild(clone);
          bindRemove(clone);
        });
      });
      </script>

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
              <label for="edit-coll-summary" class="form-label"><?php echo __('Collections Mandate'); ?></label>
              <textarea class="form-control" id="edit-coll-summary" name="collection_summary" rows="3" placeholder="<?php echo __('What this institution collects, why, and for whom — scope, geographic focus, time periods, donor relationships.'); ?>"><?php echo htmlspecialchars($f->collection_summary ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
              <div class="form-text"><?php echo __('A short statement of collection scope and purpose. Shown on your public profile.'); ?></div>
            </div>
            <div class="col-12">
              <label for="edit-strengths" class="form-label"><?php echo __('Collection Strengths'); ?></label>
              <?php
                $strengthsVal = '';
                if (!empty($f->collection_strengths)) {
                  $rawStr = sfOutputEscaper::unescape($f->collection_strengths);
                  $decoded = is_string($rawStr) ? json_decode($rawStr, true) : (array) $rawStr;
                  $strengthsVal = is_array($decoded) ? implode(', ', $decoded) : ($rawStr ?? '');
                }
              ?>
              <input type="text" class="form-control" id="edit-strengths" name="collection_strengths" value="<?php echo htmlspecialchars($strengthsVal, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('e.g., photographs, oral history, indigenous heritage, maps'); ?>">
              <div class="form-text"><?php echo __('Comma-separated keywords describing subject areas, formats, or themes your collection is known for. Used for discovery and helping researchers find relevant repositories.'); ?></div>
            </div>
            <div class="col-md-6">
              <label for="edit-holdings-analog" class="form-label"><?php echo __('Analog Holdings'); ?></label>
              <textarea class="form-control" id="edit-holdings-analog" name="holdings_analog" rows="4" placeholder="<?php echo __('e.g., 500 linear metres of records, 12,000 photographs, 8,000 books, 200 maps'); ?>"><?php echo htmlspecialchars($f->holdings_analog ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
              <div class="form-text"><?php echo __('Physical holdings — free-form. Use any units (linear metres, cubic feet, items, boxes, volumes).'); ?></div>
            </div>
            <div class="col-md-6">
              <label for="edit-holdings-digital" class="form-label"><?php echo __('Digital Holdings'); ?></label>
              <textarea class="form-control" id="edit-holdings-digital" name="holdings_digital" rows="4" placeholder="<?php echo __('e.g., 2 TB images, 500 GB audio/video, 80,000 digital files, 1.2 million records'); ?>"><?php echo htmlspecialchars($f->holdings_digital ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
              <div class="form-text"><?php echo __('Born-digital and digitised holdings — free-form. Use any units (TB, GB, files, records).'); ?></div>
            </div>
            <div class="col-md-3">
              <label for="edit-digitization" class="form-label"><?php echo __('Digitization %'); ?></label>
              <div class="input-group">
                <input type="number" class="form-control" id="edit-digitization" name="digitization_percentage" value="<?php echo htmlspecialchars($f->digitization_percentage ?? '', ENT_QUOTES, 'UTF-8'); ?>" min="0" max="100">
                <span class="input-group-text">%</span>
              </div>
              <div class="form-text"><?php echo __('Rough estimate. Leave blank if unknown.'); ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 6: Standards & Systems -->
      <div class="card mb-4">
        <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
          <span><i class="fas fa-list-check me-2 text-secondary"></i><?php echo __('Standards & Systems'); ?></span>
          <div>
            <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#newStandardModal"><i class="fas fa-plus me-1"></i><?php echo __('Create new'); ?></button>
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'standardBrowse']); ?>" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="fas fa-external-link-alt me-1"></i><?php echo __('Browse Standards'); ?></a>
          </div>
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
          <small class="form-text text-muted"><?php echo __('Free-form keywords that help users find your institution in the registry directory. Tags appear as clickable filters on the browse page — use broad terms (region, era, theme, collection type) that users might search for.'); ?></small>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard']); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> <?php echo __('Save Changes'); ?></button>
      </div>

    </form>

    <!-- New Standard modal (submits outside the main form) -->
    <div class="modal fade" id="newStandardModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'standardSubmit']); ?>">
          <input type="hidden" name="return" value="<?php echo url_for(array_merge(['module' => 'registry', 'action' => 'institutionEdit'], isset($sf_request) && $sf_request->getParameter('id') ? ['id' => (int) $sf_request->getParameter('id')] : [])); ?>">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><?php echo __('Add a New Standard'); ?></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <p class="text-muted small"><?php echo __('Add a standard or compliance instrument not yet listed — e.g., regional legislation, niche sector standards. Once saved, it will appear in the list above and be available to all institutions.'); ?></p>
              <div class="mb-3">
                <label class="form-label"><?php echo __('Name'); ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" required placeholder="<?php echo __('e.g., BC Freedom of Information and Protection of Privacy Act'); ?>">
              </div>
              <div class="row g-2">
                <div class="col-md-4">
                  <label class="form-label"><?php echo __('Acronym'); ?></label>
                  <input type="text" class="form-control" name="acronym" placeholder="FIPPA">
                </div>
                <div class="col-md-8">
                  <label class="form-label"><?php echo __('Category'); ?></label>
                  <select class="form-select" name="category">
                    <option value="descriptive"><?php echo __('Descriptive'); ?></option>
                    <option value="metadata"><?php echo __('Metadata'); ?></option>
                    <option value="interchange"><?php echo __('Interchange'); ?></option>
                    <option value="preservation"><?php echo __('Preservation'); ?></option>
                    <option value="rights"><?php echo __('Rights & Licensing'); ?></option>
                    <option value="sector"><?php echo __('Sector'); ?></option>
                    <option value="compliance" selected><?php echo __('Compliance & Regulatory'); ?></option>
                    <option value="accounting"><?php echo __('Heritage Accounting'); ?></option>
                  </select>
                </div>
              </div>
              <div class="mb-3 mt-3">
                <label class="form-label"><?php echo __('Short Description'); ?></label>
                <textarea class="form-control" name="short_description" rows="2" placeholder="<?php echo __('One-line summary of the standard or instrument'); ?>"></textarea>
              </div>
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label"><?php echo __('Issuing Body'); ?></label>
                  <input type="text" class="form-control" name="issuing_body" placeholder="<?php echo __('e.g., Province of British Columbia'); ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label"><?php echo __('Website URL'); ?></label>
                  <input type="url" class="form-control" name="website_url" placeholder="https://">
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
              <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Save Standard'); ?></button>
            </div>
          </div>
        </form>
      </div>
    </div>

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

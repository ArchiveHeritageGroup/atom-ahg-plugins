<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php $detail = $institution['institution']; ?>

<?php slot('title'); ?><?php echo htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8'); ?> - <?php echo __('Institution'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Institutions'), 'url' => url_for(['module' => 'registry', 'action' => 'institutionBrowse'])],
  ['label' => htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8')],
]]); ?>

<!-- Banner -->
<?php if (!empty($detail->banner_path)): ?>
<div class="mb-4 rounded-3 overflow-hidden" style="max-height: 250px;">
  <img src="<?php echo htmlspecialchars($detail->banner_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="w-100" style="object-fit: cover; max-height: 250px;">
</div>
<?php endif; ?>

<div class="row">
  <!-- Main content -->
  <div class="col-lg-8">

    <div class="d-flex align-items-start mb-4">
      <?php if (!empty($detail->logo_path)): ?>
      <img src="<?php echo htmlspecialchars($detail->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-3" style="width: 80px; height: 80px; object-fit: contain;">
      <?php else: ?>
      <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
        <i class="fas fa-university fa-2x text-muted"></i>
      </div>
      <?php endif; ?>
      <div class="flex-grow-1">
        <div class="d-flex justify-content-between align-items-start">
          <h1 class="h3 mb-1">
            <?php echo htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8'); ?>
            <?php if (!empty($detail->is_verified)): ?>
              <i class="fas fa-check-circle text-primary ms-1" title="<?php echo __('Verified'); ?>"></i>
            <?php endif; ?>
          </h1>
          <?php $canEdit = (!empty($isAdmin) || (!empty($currentUserId) && isset($detail->created_by) && (int) $detail->created_by === (int) $currentUserId)); ?>
          <div class="d-flex gap-1 ms-2">
            <?php if ($sf_user->isAuthenticated()): ?>
            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'favoriteToggle']); ?>" class="d-inline">
              <input type="hidden" name="entity_type" value="institution">
              <input type="hidden" name="entity_id" value="<?php echo (int) $detail->id; ?>">
              <input type="hidden" name="return" value="<?php echo url_for(['module' => 'registry', 'action' => 'institutionView', 'slug' => $detail->slug]); ?>">
              <button type="submit" class="btn btn-sm <?php echo !empty($isFavorited) ? 'btn-warning' : 'btn-outline-warning'; ?>" title="<?php echo !empty($isFavorited) ? __('Remove from favorites') : __('Add to favorites'); ?>">
                <i class="fas fa-star"></i>
              </button>
            </form>
            <?php endif; ?>
            <?php if (!empty($detail->is_featured)): ?>
              <span class="btn btn-sm btn-outline-success disabled"><i class="fas fa-award me-1"></i><?php echo __('Featured'); ?></span>
            <?php endif; ?>
            <?php if ($canEdit): ?>
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionEdit', 'id' => (int) $detail->id]); ?>" class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-edit me-1"></i> <?php echo __('Edit'); ?>
            </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="mb-1">
          <span class="badge bg-primary"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $detail->institution_type ?? '')), ENT_QUOTES, 'UTF-8'); ?></span>
          <?php if (!empty($detail->size)): ?>
            <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($detail->size), ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
          <?php if (!empty($detail->governance)): ?>
            <span class="badge bg-outline-secondary border"><?php echo htmlspecialchars(ucfirst($detail->governance), ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
          <?php if (!empty($detail->uses_atom)): ?>
            <span class="badge bg-success"><?php echo __('Uses AtoM'); ?></span>
          <?php endif; ?>
        </div>
        <?php if (!empty($detail->city) || !empty($detail->country)): ?>
        <small class="text-muted">
          <i class="fas fa-map-marker-alt me-1"></i>
          <?php echo htmlspecialchars(implode(', ', array_filter([$detail->city ?? '', $detail->province_state ?? '', $detail->country ?? ''])), ENT_QUOTES, 'UTF-8'); ?>
        </small>
        <?php endif; ?>
      </div>
    </div>

    <!-- Description -->
    <?php if (!empty($detail->description)): ?>
    <div class="mb-4">
      <h2 class="h5"><?php echo __('About'); ?></h2>
      <div><?php echo nl2br(htmlspecialchars($detail->description, ENT_QUOTES, 'UTF-8')); ?></div>
    </div>
    <?php endif; ?>

    <!-- Collection section -->
    <?php if (!empty($detail->collection_summary) || !empty($detail->total_holdings)): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Collection'); ?></div>
      <div class="card-body">
        <?php if (!empty($detail->collection_summary)): ?>
        <p><?php echo nl2br(htmlspecialchars($detail->collection_summary, ENT_QUOTES, 'UTF-8')); ?></p>
        <?php endif; ?>
        <div class="row g-3">
          <?php if (!empty($detail->total_holdings)): ?>
          <div class="col-sm-4">
            <strong><?php echo __('Total Holdings'); ?></strong><br>
            <?php echo htmlspecialchars($detail->total_holdings, ENT_QUOTES, 'UTF-8'); ?>
          </div>
          <?php endif; ?>
          <?php if (isset($detail->digitization_percentage) && $detail->digitization_percentage !== null): ?>
          <div class="col-sm-4">
            <strong><?php echo __('Digitization'); ?></strong><br>
            <?php echo (int) $detail->digitization_percentage; ?>%
            <div class="progress mt-1" style="height: 6px;">
              <div class="progress-bar" style="width: <?php echo (int) $detail->digitization_percentage; ?>%"></div>
            </div>
          </div>
          <?php endif; ?>
          <?php if (!empty($detail->collection_strengths)): ?>
          <div class="col-sm-4">
            <strong><?php echo __('Strengths'); ?></strong><br>
            <?php
              $rawCollectionStrengths = sfOutputEscaper::unescape($detail->collection_strengths);
              $strengths = is_string($rawCollectionStrengths) ? json_decode($rawCollectionStrengths, true) : (array) $rawCollectionStrengths;
              if (is_array($strengths)):
                foreach ($strengths as $s): ?>
                  <span class="badge bg-light text-dark border me-1"><?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endforeach; endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Standards & systems -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Standards & Systems'); ?></div>
      <div class="card-body">
        <div class="row g-3">
          <?php if (!empty($detail->descriptive_standards)): ?>
          <div class="col-sm-6">
            <strong><?php echo __('Descriptive Standards'); ?></strong><br>
            <?php
              $rawDescriptiveStandards = sfOutputEscaper::unescape($detail->descriptive_standards);
              $standards = is_string($rawDescriptiveStandards) ? json_decode($rawDescriptiveStandards, true) : (array) $rawDescriptiveStandards;
              if (is_array($standards)):
                foreach ($standards as $st): ?>
                  <span class="badge bg-info text-dark me-1"><?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endforeach; endif; ?>
          </div>
          <?php endif; ?>
          <?php if (!empty($detail->management_system)): ?>
          <div class="col-sm-6">
            <strong><?php echo __('Management System'); ?></strong><br>
            <?php echo htmlspecialchars($detail->management_system, ENT_QUOTES, 'UTF-8'); ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Map -->
    <?php if (!empty($detail->latitude) && !empty($detail->longitude)): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Location'); ?></div>
      <div class="card-body p-0">
        <div id="institution-map" style="height: 300px;"></div>
      </div>
    </div>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" <?php echo $na; ?>></script>
    <script <?php echo $na; ?>>
    document.addEventListener('DOMContentLoaded', function() {
      var lat = <?php echo (float) $detail->latitude; ?>;
      var lng = <?php echo (float) $detail->longitude; ?>;
      var map = L.map('institution-map').setView([lat, lng], 14);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);
      L.marker([lat, lng]).addTo(map)
        .bindPopup(<?php echo json_encode(htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8')); ?>)
        .openPopup();
    });
    </script>
    <?php endif; ?>

    <!-- Contacts -->
    <div class="card mb-4">
      <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <?php echo __('Contacts'); ?>
        <?php if ($canEdit): ?>
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionContactAdd']); ?>?inst=<?php echo (int) $detail->id; ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-plus me-1"></i><?php echo __('Add'); ?>
          </a>
        <?php endif; ?>
      </div>
      <?php if (!empty($institution['contacts'])): ?>
      <div class="card-body">
        <?php include_partial('registry/contactList', ['contacts' => $institution['contacts'], 'canEdit' => $canEdit ?? false, 'entityType' => 'institution']); ?>
      </div>
      <?php else: ?>
      <div class="card-body text-muted"><?php echo __('No contacts added yet.'); ?></div>
      <?php endif; ?>
    </div>

  </div>

  <!-- Sidebar -->
  <div class="col-lg-4">

    <!-- Quick info card -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Details'); ?></div>
      <ul class="list-group list-group-flush">
        <?php if (!empty($detail->website)): ?>
        <li class="list-group-item">
          <i class="fas fa-globe me-2 text-muted"></i>
          <a href="<?php echo htmlspecialchars($detail->website, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars(preg_replace('#^https?://#', '', $detail->website), ENT_QUOTES, 'UTF-8'); ?></a>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->email)): ?>
        <li class="list-group-item">
          <i class="fas fa-envelope me-2 text-muted"></i>
          <a href="mailto:<?php echo htmlspecialchars($detail->email, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($detail->email, ENT_QUOTES, 'UTF-8'); ?></a>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->phone)): ?>
        <li class="list-group-item">
          <i class="fas fa-phone me-2 text-muted"></i>
          <?php echo htmlspecialchars($detail->phone, ENT_QUOTES, 'UTF-8'); ?>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->established_year)): ?>
        <li class="list-group-item">
          <i class="fas fa-calendar me-2 text-muted"></i>
          <?php echo __('Established %1%', ['%1%' => (int) $detail->established_year]); ?>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->parent_body)): ?>
        <li class="list-group-item">
          <i class="fas fa-sitemap me-2 text-muted"></i>
          <?php echo htmlspecialchars($detail->parent_body, ENT_QUOTES, 'UTF-8'); ?>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->accreditation)): ?>
        <li class="list-group-item">
          <i class="fas fa-award me-2 text-muted"></i>
          <?php echo htmlspecialchars($detail->accreditation, ENT_QUOTES, 'UTF-8'); ?>
        </li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Instances -->
    <div class="card mb-4">
      <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <?php echo __('System Instances'); ?>
        <?php if ($canEdit): ?>
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionInstanceAdd']); ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-plus me-1"></i><?php echo __('Add'); ?>
          </a>
        <?php endif; ?>
      </div>
      <?php if (!empty($institution['instances'])): ?>
      <div class="card-body">
        <?php include_partial('registry/instanceList', ['instances' => $institution['instances'], 'canEdit' => $canEdit ?? false]); ?>
      </div>
      <?php else: ?>
      <div class="card-body text-muted"><?php echo __('No instances registered yet.'); ?></div>
      <?php endif; ?>
    </div>

    <!-- Software used -->
    <div class="card mb-4">
      <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <?php echo __('Software Used'); ?>
        <?php if ($canEdit): ?>
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionSoftware']); ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-cog me-1"></i><?php echo __('Manage'); ?>
          </a>
        <?php endif; ?>
      </div>
    <?php if (!empty($institution['software'])): ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($institution['software'] as $sw): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span>
            <?php if (!empty($sw->slug)): ?>
              <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $sw->slug]); ?>"><?php echo htmlspecialchars($sw->name ?? '', ENT_QUOTES, 'UTF-8'); ?></a>
            <?php else: ?>
              <?php echo htmlspecialchars($sw->name ?? '', ENT_QUOTES, 'UTF-8'); ?>
            <?php endif; ?>
          </span>
          <?php if (!empty($sw->version_in_use)): ?>
          <span class="badge bg-secondary"><?php echo htmlspecialchars($sw->version_in_use, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <div class="card-body text-muted"><?php echo __('No software registered.'); ?></div>
    <?php endif; ?>
    </div>

    <!-- Vendor relationships -->
    <div class="card mb-4">
      <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <?php echo __('Service Providers'); ?>
        <?php if ($canEdit): ?>
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionVendors']); ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-cog me-1"></i><?php echo __('Manage'); ?>
          </a>
        <?php endif; ?>
      </div>
    <?php if (!empty($institution['vendors'])): ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($institution['vendors'] as $rel): ?>
        <li class="list-group-item">
          <?php if (!empty($rel->vendor_slug)): ?>
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'vendorView', 'slug' => $rel->vendor_slug]); ?>"><?php echo htmlspecialchars($rel->vendor_name ?? '', ENT_QUOTES, 'UTF-8'); ?></a>
          <?php else: ?>
            <?php echo htmlspecialchars($rel->vendor_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
          <?php endif; ?>
          <br><small class="text-muted"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $rel->relationship_type ?? '')), ENT_QUOTES, 'UTF-8'); ?></small>
        </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <div class="card-body text-muted"><?php echo __('No service providers listed.'); ?></div>
    <?php endif; ?>
    </div>

    <!-- Tags -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Tags'); ?></div>
      <div class="card-body">
        <?php if (!empty($institution['tags'])): ?>
          <?php foreach ($institution['tags'] as $tag): ?>
            <span class="badge bg-light text-dark border me-1 mb-1"><?php echo htmlspecialchars($tag->tag ?? $tag, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endforeach; ?>
        <?php else: ?>
          <span class="text-muted"><?php echo __('No tags.'); ?></span>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php end_slot(); ?>

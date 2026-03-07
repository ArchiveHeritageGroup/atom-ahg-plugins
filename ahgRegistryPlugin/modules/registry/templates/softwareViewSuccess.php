<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php $detail = $software['software']; ?>

<?php slot('title'); ?><?php echo htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8'); ?> - <?php echo __('Software'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Software'), 'url' => url_for(['module' => 'registry', 'action' => 'softwareBrowse'])],
  ['label' => htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8')],
]]); ?>

<div class="row">
  <!-- Main content -->
  <div class="col-lg-8">

    <div class="d-flex align-items-start mb-4">
      <?php if (!empty($detail->logo_path)): ?>
      <img src="<?php echo htmlspecialchars($detail->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-3" style="width: 80px; height: 80px; object-fit: contain;">
      <?php else: ?>
      <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
        <i class="fas fa-box-open fa-2x text-muted"></i>
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
        <?php $canEdit = (!empty($isAdmin) || (!empty($currentUserId) && isset($detail->vendor_id) && isset($detail->created_by) && (int) $detail->created_by === (int) $currentUserId)); ?>
        <div class="d-flex gap-1 ms-2">
          <?php if ($sf_user->isAuthenticated()): ?>
          <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'favoriteToggle']); ?>" class="d-inline">
            <input type="hidden" name="entity_type" value="software">
            <input type="hidden" name="entity_id" value="<?php echo (int) $detail->id; ?>">
            <input type="hidden" name="return" value="<?php echo url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $detail->slug]); ?>">
            <button type="submit" class="btn btn-sm <?php echo !empty($isFavorited) ? 'btn-warning' : 'btn-outline-warning'; ?>" title="<?php echo !empty($isFavorited) ? __('Remove from favorites') : __('Add to favorites'); ?>">
              <i class="fas fa-star"></i>
            </button>
          </form>
          <?php endif; ?>
          <?php if (!empty($detail->is_featured)): ?>
            <span class="btn btn-sm btn-outline-success disabled"><i class="fas fa-award me-1"></i><?php echo __('Featured'); ?></span>
          <?php endif; ?>
          <?php if ($canEdit): ?>
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorSoftwareEdit', 'id' => (int) $detail->id]); ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-edit me-1"></i> <?php echo __('Edit'); ?>
          </a>
          <?php endif; ?>
        </div>
        </div>
        <div class="mb-1">
          <?php
            $categoryLabels = [
              'ams' => 'Archival Management System', 'ims' => 'Information Management System',
              'dam' => 'Digital Asset Management', 'dams' => 'DAMS',
              'cms' => 'CMS', 'glam' => 'GLAM / DAM', 'preservation' => 'Digital Preservation',
              'digitization' => 'Digitization', 'discovery' => 'Discovery',
              'utility' => 'Utility', 'plugin' => 'Plugin/Extension',
              'theme' => 'Theme', 'integration' => 'Integration', 'other' => 'Other',
            ];
            $catLabel = $categoryLabels[$detail->category ?? ''] ?? ucfirst($detail->category ?? '');
          ?>
          <span class="badge bg-info text-dark"><?php echo htmlspecialchars($catLabel, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php if (!empty($detail->license)): ?>
            <span class="badge bg-secondary"><?php echo htmlspecialchars($detail->license, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
          <?php if (!empty($detail->pricing_model)): ?>
            <span class="badge bg-success"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $detail->pricing_model)), ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
          <?php if (!empty($detail->latest_version)): ?>
            <span class="badge bg-primary">v<?php echo htmlspecialchars($detail->latest_version, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
        </div>
        <?php if (!empty($detail->short_description)): ?>
        <p class="text-muted mb-0"><?php echo htmlspecialchars($detail->short_description, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Description -->
    <?php if (!empty($detail->description)): ?>
    <div class="mb-4">
      <h2 class="h5"><?php echo __('Description'); ?></h2>
      <div><?php echo nl2br(htmlspecialchars($detail->description, ENT_QUOTES, 'UTF-8')); ?></div>
    </div>
    <?php endif; ?>

    <!-- Screenshot -->
    <?php if (!empty($detail->screenshot_path)): ?>
    <div class="mb-4">
      <img src="<?php echo htmlspecialchars($detail->screenshot_path, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo __('Screenshot'); ?>" class="img-fluid rounded border">
    </div>
    <?php endif; ?>

    <!-- Git repo -->
    <?php if (!empty($detail->git_url) && $detail->git_provider !== 'none'): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Source Code'); ?></div>
      <div class="card-body">
        <div class="d-flex align-items-center">
          <?php
            $providerIcons = ['github' => 'fab fa-github', 'gitlab' => 'fab fa-gitlab', 'bitbucket' => 'fab fa-bitbucket', 'self_hosted' => 'fas fa-code-branch'];
            $icon = $providerIcons[$detail->git_provider] ?? 'fas fa-code-branch';
          ?>
          <i class="<?php echo $icon; ?> fa-2x me-3"></i>
          <div>
            <a href="<?php echo htmlspecialchars($detail->git_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="fw-bold">
              <?php echo htmlspecialchars($detail->git_url, ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php if (!empty($detail->git_default_branch)): ?>
              <br><small class="text-muted"><?php echo __('Default branch: %1%', ['%1%' => htmlspecialchars($detail->git_default_branch, ENT_QUOTES, 'UTF-8')]); ?></small>
            <?php endif; ?>
            <?php if (!empty($detail->git_is_public)): ?>
              <span class="badge bg-success ms-2"><?php echo __('Public'); ?></span>
            <?php else: ?>
              <span class="badge bg-warning text-dark ms-2"><?php echo __('Private'); ?></span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Technical requirements -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Technical Details'); ?></div>
      <div class="card-body">
        <div class="row g-3">
          <?php if (!empty($detail->min_php_version)): ?>
          <div class="col-sm-4">
            <strong><?php echo __('PHP'); ?></strong><br>
            &ge; <?php echo htmlspecialchars($detail->min_php_version, ENT_QUOTES, 'UTF-8'); ?>
          </div>
          <?php endif; ?>
          <?php if (!empty($detail->min_mysql_version)): ?>
          <div class="col-sm-4">
            <strong><?php echo __('MySQL'); ?></strong><br>
            &ge; <?php echo htmlspecialchars($detail->min_mysql_version, ENT_QUOTES, 'UTF-8'); ?>
          </div>
          <?php endif; ?>
          <?php if (!empty($detail->supported_platforms)): ?>
          <div class="col-sm-4">
            <strong><?php echo __('Platforms'); ?></strong><br>
            <?php
              $rawSupportedPlatforms = sfOutputEscaper::unescape($detail->supported_platforms);
              $platforms = is_string($rawSupportedPlatforms) ? json_decode($rawSupportedPlatforms, true) : (array) $rawSupportedPlatforms;
              echo is_array($platforms) ? htmlspecialchars(implode(', ', $platforms), ENT_QUOTES, 'UTF-8') : '';
            ?>
          </div>
          <?php endif; ?>
        </div>
        <?php if (!empty($detail->requirements)): ?>
        <div class="mt-3">
          <strong><?php echo __('Requirements'); ?></strong><br>
          <small><?php echo nl2br(htmlspecialchars($detail->requirements, ENT_QUOTES, 'UTF-8')); ?></small>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- GLAM sectors, standards, languages -->
    <div class="row g-3 mb-4">
      <?php if (!empty($detail->glam_sectors)): ?>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-header py-2 fw-semibold small"><?php echo __('GLAM Sectors'); ?></div>
          <div class="card-body py-2">
            <?php
              $rawGlamSectors = sfOutputEscaper::unescape($detail->glam_sectors);
              $sectors = is_string($rawGlamSectors) ? json_decode($rawGlamSectors, true) : (array) $rawGlamSectors;
              if (is_array($sectors)):
                foreach ($sectors as $s): ?>
                  <span class="badge bg-primary me-1 mb-1"><?php echo htmlspecialchars(ucfirst($s), ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
      <?php if (!empty($detail->standards_supported)): ?>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-header py-2 fw-semibold small"><?php echo __('Standards'); ?></div>
          <div class="card-body py-2">
            <?php
              $rawStandardsSupported = sfOutputEscaper::unescape($detail->standards_supported);
              $stds = is_string($rawStandardsSupported) ? json_decode($rawStandardsSupported, true) : (array) $rawStandardsSupported;
              if (is_array($stds)):
                foreach ($stds as $s): ?>
                  <span class="badge bg-info text-dark me-1 mb-1"><?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
      <?php if (!empty($detail->languages)): ?>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-header py-2 fw-semibold small"><?php echo __('Languages'); ?></div>
          <div class="card-body py-2">
            <?php
              $rawLanguages = sfOutputEscaper::unescape($detail->languages);
              $langs = is_string($rawLanguages) ? json_decode($rawLanguages, true) : (array) $rawLanguages;
              if (is_array($langs)):
                foreach ($langs as $l): ?>
                  <span class="badge bg-secondary me-1 mb-1"><?php echo htmlspecialchars($l, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Releases (latest 5) -->
    <?php if (!empty($software['releases'])): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <?php echo __('Recent Releases'); ?>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareReleases', 'slug' => $detail->slug]); ?>" class="btn btn-sm btn-outline-primary"><?php echo __('View All'); ?></a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Version'); ?></th>
              <th><?php echo __('Type'); ?></th>
              <th><?php echo __('Date'); ?></th>
              <th><?php echo __('Tag'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php $count = 0; foreach ($software['releases'] as $rel): if ($count++ >= 5) break; ?>
            <tr>
              <td>
                <strong><?php echo htmlspecialchars($rel->version, ENT_QUOTES, 'UTF-8'); ?></strong>
                <?php if (!empty($rel->is_stable)): ?><span class="badge bg-success ms-1"><?php echo __('Stable'); ?></span><?php endif; ?>
              </td>
              <td><span class="badge bg-secondary"><?php echo htmlspecialchars($rel->release_type ?? '', ENT_QUOTES, 'UTF-8'); ?></span></td>
              <td><?php echo !empty($rel->released_at) ? date('M j, Y', strtotime($rel->released_at)) : '-'; ?></td>
              <td><?php echo !empty($rel->git_tag) ? htmlspecialchars($rel->git_tag, ENT_QUOTES, 'UTF-8') : '-'; ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Components / Plugins -->
    <?php if (!empty($components)): ?>
    <?php
      // Group components by category
      $grouped = [];
      foreach ($components as $comp) {
        $cat = $comp->category ?: __('Other');
        $grouped[$cat][] = $comp;
      }
    ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="fas fa-puzzle-piece me-2"></i><?php echo __('Components & Plugins'); ?> <span class="badge bg-secondary ms-1"><?php echo count($components); ?></span></span>
        <?php if (!empty($canEdit)): ?>
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareComponents', 'id' => (int) $detail->id]); ?>" class="btn btn-sm btn-outline-primary"><?php echo __('Manage'); ?></a>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php foreach ($grouped as $catName => $catComponents): ?>
          <h6 class="text-muted text-uppercase small fw-bold mt-3 mb-2 <?php echo $catName === array_key_first($grouped) ? 'mt-0' : ''; ?>">
            <?php echo htmlspecialchars($catName, ENT_QUOTES, 'UTF-8'); ?>
            <span class="badge bg-light text-dark ms-1"><?php echo count($catComponents); ?></span>
          </h6>
          <div class="row g-2 mb-2">
            <?php foreach ($catComponents as $comp): ?>
            <div class="col-md-6 col-lg-4">
              <div class="border rounded p-2 h-100 <?php echo !empty($comp->is_required) ? 'border-primary bg-primary bg-opacity-10' : ''; ?>">
                <div class="d-flex align-items-center">
                  <?php if (!empty($comp->icon_class)): ?>
                    <i class="<?php echo htmlspecialchars($comp->icon_class, ENT_QUOTES, 'UTF-8'); ?> me-2 text-muted"></i>
                  <?php else: ?>
                    <i class="fas fa-plug me-2 text-muted"></i>
                  <?php endif; ?>
                  <div class="min-width-0">
                    <strong class="small d-block text-truncate"><?php echo htmlspecialchars($comp->name, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <?php if (!empty($comp->is_required)): ?>
                      <span class="badge bg-primary" style="font-size: 0.65em;"><?php echo __('Required'); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($comp->version)): ?>
                      <span class="badge bg-secondary" style="font-size: 0.65em;">v<?php echo htmlspecialchars($comp->version, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                  </div>
                </div>
                <?php if (!empty($comp->short_description)): ?>
                  <small class="text-muted d-block mt-1" style="font-size: 0.75em;"><?php echo htmlspecialchars($comp->short_description, ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Standards Conformance -->
    <?php if (!empty($standardsConformance)): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold">
        <i class="fas fa-balance-scale me-2"></i><?php echo __('Standards Conformance'); ?>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Standard'); ?></th>
              <th><?php echo __('Level'); ?></th>
              <th><?php echo __('Notes'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($standardsConformance as $sc): ?>
            <tr>
              <td>
                <a href="<?php echo url_for(['module' => 'registry', 'action' => 'standardView', 'slug' => $sc->slug]); ?>">
                  <?php if (!empty($sc->acronym)): ?>
                    <strong><?php echo htmlspecialchars($sc->acronym, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <small class="text-muted ms-1"><?php echo htmlspecialchars($sc->name, ENT_QUOTES, 'UTF-8'); ?></small>
                  <?php else: ?>
                    <strong><?php echo htmlspecialchars($sc->name, ENT_QUOTES, 'UTF-8'); ?></strong>
                  <?php endif; ?>
                </a>
              </td>
              <td>
                <?php
                  $lvlColors = ['full' => 'success', 'partial' => 'warning', 'extended' => 'primary', 'planned' => 'secondary'];
                  $lvlColor = $lvlColors[$sc->conformance_level] ?? 'secondary';
                ?>
                <span class="badge bg-<?php echo $lvlColor; ?>"><?php echo htmlspecialchars(ucfirst($sc->conformance_level), ENT_QUOTES, 'UTF-8'); ?></span>
              </td>
              <td><small><?php echo htmlspecialchars($sc->notes ?? '', ENT_QUOTES, 'UTF-8'); ?></small></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Institutions using this -->
    <?php if (!empty($software['institutions'])): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Institutions Using This Software'); ?></div>
      <ul class="list-group list-group-flush">
        <?php foreach ($software['institutions'] as $inst): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <?php if (!empty($inst->slug)): ?>
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionView', 'slug' => $inst->slug]); ?>"><?php echo htmlspecialchars($inst->name ?? '', ENT_QUOTES, 'UTF-8'); ?></a>
          <?php else: ?>
            <?php echo htmlspecialchars($inst->name ?? '', ENT_QUOTES, 'UTF-8'); ?>
          <?php endif; ?>
          <?php if (!empty($inst->version_in_use)): ?>
            <span class="badge bg-secondary">v<?php echo htmlspecialchars($inst->version_in_use, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <!-- Reviews -->
    <?php if (!empty($software['reviews'])): ?>
    <div class="mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0"><?php echo __('Reviews'); ?></h2>
        <?php if (!empty($detail->average_rating)): ?>
        <div>
          <?php include_partial('registry/ratingStars', ['rating' => $detail->average_rating]); ?>
          <span class="text-muted ms-1">(<?php echo (int) $detail->rating_count; ?>)</span>
        </div>
        <?php endif; ?>
      </div>
      <?php foreach ($software['reviews'] as $review): ?>
      <div class="card mb-2">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <div>
              <strong><?php echo htmlspecialchars($review->reviewer_name ?? __('Anonymous'), ENT_QUOTES, 'UTF-8'); ?></strong>
              <small class="text-muted ms-2"><?php echo date('M j, Y', strtotime($review->created_at)); ?></small>
            </div>
            <?php include_partial('registry/ratingStars', ['rating' => (int) $review->rating]); ?>
          </div>
          <?php if (!empty($review->title)): ?>
            <h6 class="mb-1"><?php echo htmlspecialchars($review->title, ENT_QUOTES, 'UTF-8'); ?></h6>
          <?php endif; ?>
          <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($review->comment ?? '', ENT_QUOTES, 'UTF-8')); ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>

  <!-- Sidebar -->
  <div class="col-lg-4">

    <!-- Quick info -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Details'); ?></div>
      <ul class="list-group list-group-flush">
        <?php if (!empty($detail->website)): ?>
        <li class="list-group-item">
          <i class="fas fa-globe me-2 text-muted"></i>
          <a href="<?php echo htmlspecialchars($detail->website, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo __('Website'); ?></a>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->documentation_url)): ?>
        <li class="list-group-item">
          <i class="fas fa-book me-2 text-muted"></i>
          <a href="<?php echo htmlspecialchars($detail->documentation_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo __('Documentation'); ?></a>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->install_url)): ?>
        <li class="list-group-item">
          <i class="fas fa-download me-2 text-muted"></i>
          <a href="<?php echo htmlspecialchars($detail->install_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="fw-bold"><?php echo __('Install / Download'); ?></a>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->download_count)): ?>
        <li class="list-group-item">
          <i class="fas fa-download me-2 text-muted"></i>
          <?php echo __('%1% downloads', ['%1%' => number_format($detail->download_count)]); ?>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->institution_count)): ?>
        <li class="list-group-item">
          <i class="fas fa-university me-2 text-muted"></i>
          <?php echo __('%1% institutions', ['%1%' => (int) $detail->institution_count]); ?>
        </li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Setup Guides -->
    <?php if (!empty($setupGuideCount) && $setupGuideCount > 0): ?>
    <div class="card mb-4">
      <div class="card-body text-center">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'setupGuideBrowse', 'slug' => $detail->slug]); ?>" class="btn btn-outline-primary btn-sm">
          <i class="fas fa-book-open me-1"></i> <?php echo __('Setup Guides'); ?>
          <span class="badge bg-primary ms-1"><?php echo (int) $setupGuideCount; ?></span>
        </a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Pricing details -->
    <?php if (!empty($detail->pricing_details)): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Pricing'); ?></div>
      <div class="card-body">
        <span class="badge bg-success mb-2"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $detail->pricing_model ?? '')), ENT_QUOTES, 'UTF-8'); ?></span>
        <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($detail->pricing_details, ENT_QUOTES, 'UTF-8')); ?></p>
      </div>
    </div>
    <?php endif; ?>

    <!-- Vendor info -->
    <?php if (!empty($software['vendor'])): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Vendor'); ?></div>
      <div class="card-body">
        <div class="d-flex align-items-center">
          <?php if (!empty($software['vendor']->logo_path)): ?>
          <img src="<?php echo htmlspecialchars($software['vendor']->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-2" style="width: 40px; height: 40px; object-fit: contain;">
          <?php endif; ?>
          <div>
            <?php if (!empty($software['vendor']->slug)): ?>
              <a href="<?php echo url_for(['module' => 'registry', 'action' => 'vendorView', 'slug' => $software['vendor']->slug]); ?>" class="fw-bold"><?php echo htmlspecialchars($software['vendor']->name ?? '', ENT_QUOTES, 'UTF-8'); ?></a>
            <?php else: ?>
              <strong><?php echo htmlspecialchars($software['vendor']->name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php endif; ?>
            <?php if (!empty($software['vendor']->is_verified)): ?>
              <i class="fas fa-check-circle text-primary ms-1"></i>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Write a review -->
    <?php if ($sf_user->isAuthenticated()): ?>
    <div class="card mb-4">
      <div class="card-body text-center">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionReview', 'type' => 'software', 'id' => (int) $detail->id]); ?>" class="btn btn-outline-primary btn-sm">
          <i class="fas fa-star me-1"></i> <?php echo __('Write a Review'); ?>
        </a>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php end_slot(); ?>

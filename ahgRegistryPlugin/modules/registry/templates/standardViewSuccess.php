<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php
  $_raw = sfOutputEscaper::unescape($standard);
  $detail = $_raw['standard'];
  $extensions = $_raw['extensions'] ?? [];
  $conformance = $_raw['conformance'] ?? [];
  $tags = $_raw['tags'] ?? [];
?>

<?php slot('title'); ?><?php echo htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8'); ?> - <?php echo __('Standard'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Standards'), 'url' => url_for(['module' => 'registry', 'action' => 'standardBrowse'])],
  ['label' => htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8')],
]]); ?>

<?php $_isAdmin = sfContext::getInstance()->getUser() && sfContext::getInstance()->getUser()->isAuthenticated() && sfContext::getInstance()->getUser()->hasCredential('administrator'); ?>

<?php
  $catBg = [
    'descriptive' => 'bg-primary',
    'preservation' => 'bg-success',
    'rights' => 'bg-warning text-dark',
    'accounting' => 'bg-info text-dark',
    'compliance' => 'bg-danger',
    'metadata' => 'bg-secondary',
    'interchange' => 'bg-dark',
    'sector' => 'bg-primary',
  ];
  $cat = $detail->category ?? '';
  $catClass = $catBg[strtolower($cat)] ?? 'bg-secondary';

  $extTypeBg = [
    'addition' => 'bg-success',
    'deviation' => 'bg-warning text-dark',
    'implementation_note' => 'bg-info text-dark',
    'api_binding' => 'bg-primary',
  ];

  $confLevelBg = [
    'full' => 'bg-success',
    'partial' => 'bg-warning text-dark',
    'extended' => 'bg-primary',
    'planned' => 'bg-secondary',
  ];
?>

<div class="row">
  <!-- Main content -->
  <div class="col-lg-8">

    <!-- Header -->
    <div class="mb-4">
      <div class="d-flex justify-content-between align-items-start">
        <h1 class="h3 mb-1">
          <?php echo htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8'); ?>
          <?php if (!empty($detail->acronym)): ?>
            <span class="badge bg-light text-dark border ms-2" style="font-size: 0.5em; vertical-align: middle;"><?php echo htmlspecialchars($detail->acronym, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
        </h1>
        <?php if ($_isAdmin): ?>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminStandardEdit', 'id' => $detail->id]); ?>" class="btn btn-outline-secondary btn-sm flex-shrink-0">
          <i class="fas fa-wrench me-1"></i><?php echo __('Maintenance'); ?>
        </a>
        <?php endif; ?>
      </div>
      <div class="mb-2">
        <span class="badge <?php echo $catClass; ?>"><?php echo htmlspecialchars(ucfirst($cat), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php if (!empty($detail->is_featured)): ?>
          <span class="badge bg-warning text-dark"><i class="fas fa-award me-1"></i><?php echo __('Featured'); ?></span>
        <?php endif; ?>
      </div>
      <?php if (!empty($detail->short_description)): ?>
      <p class="text-muted mb-0"><?php echo htmlspecialchars($detail->short_description, ENT_QUOTES, 'UTF-8'); ?></p>
      <?php endif; ?>
    </div>

    <!-- Full description -->
    <?php if (!empty($detail->description)): ?>
    <div class="mb-4">
      <h2 class="h5"><?php echo __('Description'); ?></h2>
      <div><?php echo nl2br(htmlspecialchars($detail->description, ENT_QUOTES, 'UTF-8')); ?></div>
    </div>
    <?php endif; ?>

    <!-- Heratio Extensions -->
    <div class="card mb-4">
      <div class="card-header fw-semibold">
        <i class="fas fa-puzzle-piece me-2"></i><?php echo __('Heratio Extensions'); ?>
        <?php if (!empty($extensions)): ?>
          <span class="badge bg-secondary ms-1"><?php echo count($extensions); ?></span>
        <?php endif; ?>
      </div>
      <?php if (!empty($extensions)): ?>
      <div class="card-body p-0">
        <?php foreach ($extensions as $idx => $ext): ?>
        <div class="p-3 <?php echo $idx > 0 ? 'border-top' : ''; ?>">
          <div class="d-flex align-items-start">
            <div class="flex-grow-1">
              <div class="d-flex align-items-center mb-1">
                <?php
                  $extType = $ext->extension_type ?? 'addition';
                  $extBgClass = $extTypeBg[strtolower($extType)] ?? 'bg-secondary';
                ?>
                <span class="badge <?php echo $extBgClass; ?> me-2"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $extType)), ENT_QUOTES, 'UTF-8'); ?></span>
                <strong><?php echo htmlspecialchars($ext->title ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
              </div>
              <p class="mb-1 small"><?php echo htmlspecialchars($ext->description ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
              <div class="d-flex flex-wrap gap-2 align-items-center">
                <?php if (!empty($ext->plugin_name)): ?>
                  <span class="badge bg-light text-dark border"><i class="fas fa-plug me-1"></i><?php echo htmlspecialchars($ext->plugin_name, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
                <?php if (!empty($ext->db_tables)): ?>
                  <small class="text-muted"><i class="fas fa-database me-1"></i><?php echo htmlspecialchars($ext->db_tables, ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
              </div>
              <?php if (!empty($ext->rationale)): ?>
                <?php $collapseId = 'rationale_' . ($ext->id ?? $idx); ?>
                <div class="mt-2">
                  <a class="small text-decoration-none" data-bs-toggle="collapse" href="#<?php echo $collapseId; ?>" role="button" aria-expanded="false">
                    <i class="fas fa-chevron-right me-1"></i><?php echo __('Rationale'); ?>
                  </a>
                  <div class="collapse" id="<?php echo $collapseId; ?>">
                    <div class="mt-1 p-2 bg-light rounded small"><?php echo htmlspecialchars($ext->rationale, ENT_QUOTES, 'UTF-8'); ?></div>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="card-body">
        <p class="text-muted mb-0"><i class="fas fa-check-circle text-success me-2"></i><?php echo __('This standard is implemented without modifications.'); ?></p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Notes -->
    <?php include_partial('registry/notes', [
      'entityType' => 'standard',
      'entityId' => $detail->id,
      'returnUrl' => $sf_request->getUri(),
    ]); ?>

    <!-- Software Conformance (only shown when data exists) -->
    <?php if (!empty($conformance)): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold">
        <i class="fas fa-check-double me-2"></i><?php echo __('Software Conformance'); ?>
        <span class="badge bg-secondary ms-1"><?php echo count($conformance); ?></span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Software'); ?></th>
              <th><?php echo __('Conformance Level'); ?></th>
              <th><?php echo __('Notes'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($conformance as $conf): ?>
            <tr>
              <td>
                <?php if (!empty($conf->software_slug)): ?>
                  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $conf->software_slug]); ?>">
                    <?php echo htmlspecialchars($conf->software_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
                  </a>
                <?php else: ?>
                  <?php echo htmlspecialchars($conf->software_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
              </td>
              <td>
                <?php
                  $confLevel = $conf->conformance_level ?? 'partial';
                  $confBgClass = $confLevelBg[strtolower($confLevel)] ?? 'bg-secondary';
                ?>
                <span class="badge <?php echo $confBgClass; ?>"><?php echo htmlspecialchars(ucfirst($confLevel), ENT_QUOTES, 'UTF-8'); ?></span>
              </td>
              <td class="small"><?php echo htmlspecialchars($conf->notes ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Sidebar -->
  <div class="col-lg-4">

    <!-- Details card -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Details'); ?></div>
      <ul class="list-group list-group-flush">
        <?php if (!empty($detail->website_url)): ?>
        <li class="list-group-item">
          <i class="fas fa-globe me-2 text-muted"></i>
          <a href="<?php echo htmlspecialchars($detail->website_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
            <?php echo __('Official Website'); ?> <i class="fas fa-external-link-alt ms-1" style="font-size: 0.7em;"></i>
          </a>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->issuing_body)): ?>
        <li class="list-group-item">
          <i class="fas fa-building me-2 text-muted"></i>
          <?php echo htmlspecialchars($detail->issuing_body, ENT_QUOTES, 'UTF-8'); ?>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->current_version)): ?>
        <li class="list-group-item">
          <i class="fas fa-tag me-2 text-muted"></i>
          <?php echo __('Version: %1%', ['%1%' => htmlspecialchars($detail->current_version, ENT_QUOTES, 'UTF-8')]); ?>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->publication_year)): ?>
        <li class="list-group-item">
          <i class="fas fa-calendar me-2 text-muted"></i>
          <?php echo __('Published: %1%', ['%1%' => (int) $detail->publication_year]); ?>
        </li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Sector Applicability card -->
    <?php
      $rawSectors = sfOutputEscaper::unescape($detail->sector_applicability ?? '');
      $sectors = is_string($rawSectors) ? json_decode($rawSectors, true) : (is_array($rawSectors) ? $rawSectors : []);
      if (!is_array($sectors)) {
        $sectors = [];
      }
    ?>
    <?php if (!empty($sectors)): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Sector Applicability'); ?></div>
      <div class="card-body">
        <?php
          $sectorIcons = [
            'archive' => 'fas fa-archive',
            'library' => 'fas fa-book',
            'museum' => 'fas fa-landmark',
            'gallery' => 'fas fa-images',
            'dam' => 'fas fa-photo-video',
          ];
        ?>
        <?php foreach ($sectors as $s): ?>
          <?php $sIcon = $sectorIcons[strtolower($s)] ?? 'fas fa-tag'; ?>
          <span class="badge bg-primary me-1 mb-1"><i class="<?php echo $sIcon; ?> me-1"></i><?php echo htmlspecialchars(ucfirst($s), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Related Standards -->
    <?php if (!empty($relatedStandards)): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Related Standards'); ?></div>
      <ul class="list-group list-group-flush">
        <?php foreach ($relatedStandards as $rel): ?>
        <li class="list-group-item">
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'standardView', 'slug' => $rel->slug]); ?>" class="text-decoration-none">
            <?php if (!empty($rel->acronym)): ?>
              <strong><?php echo htmlspecialchars($rel->acronym, ENT_QUOTES, 'UTF-8'); ?></strong>
              <span class="text-muted ms-1"><?php echo htmlspecialchars($rel->name, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php else: ?>
              <strong><?php echo htmlspecialchars($rel->name, ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php endif; ?>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php end_slot(); ?>

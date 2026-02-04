<?php
/**
 * Embedded GLAM Browse - for landing page AJAX loading
 * Returns just facets + results without full page layout
 */
use_helper('Date');

// Get values from Symfony request object
$limit = (int) $sf_request->getParameter('limit', 10);
if ($limit < 10) $limit = 10;
if ($limit > 100) $limit = 100;

$page = (int) $sf_request->getParameter('page', 1);
$sort = $sf_request->getParameter('sort', 'date');
$sortDir = $sf_request->getParameter('dir', 'desc');
$viewMode = $sf_request->getParameter('view', 'card');
$typeFilter = $sf_request->getParameter('type');
$creatorFilter = $sf_request->getParameter('creator');
$placeFilter = $sf_request->getParameter('place');
$subjectFilter = $sf_request->getParameter('subject');
$genreFilter = $sf_request->getParameter('genre');
$levelFilter = $sf_request->getParameter('level');
$mediaFilter = $sf_request->getParameter('media');
$repoFilter = $sf_request->getParameter('repo');
$hasDigital = $sf_request->getParameter('hasDigital');
$parentId = $sf_request->getParameter('parent');

// Get data from action
$total = $sf_data->getRaw('total') ?: 0;
$totalPages = $sf_data->getRaw('totalPages') ?: 1;
$parent = $sf_data->getRaw('parent');
$digitalObjectCount = $sf_data->getRaw('digitalObjectCount') ?: 0;
$objects = $sf_data->getRaw('objects') ?: [];
$types = $sf_data->getRaw('types') ?: [];
$creators = $sf_data->getRaw('creators') ?: [];
$places = $sf_data->getRaw('places') ?: [];
$subjects = $sf_data->getRaw('subjects') ?: [];
$genres = $sf_data->getRaw('genres') ?: [];
$levels = $sf_data->getRaw('levels') ?: [];
$mediaTypes = $sf_data->getRaw('mediaTypes') ?: [];
$repositories = $sf_data->getRaw('repositories') ?: [];
$showSidebar = $sf_data->getRaw('showSidebar') !== false;

// Build filter params for URLs
$fp = [
    'type' => $typeFilter,
    'parent' => $parentId,
    'creator' => $creatorFilter,
    'subject' => $subjectFilter,
    'place' => $placeFilter,
    'genre' => $genreFilter,
    'level' => $levelFilter,
    'media' => $mediaFilter,
    'repo' => $repoFilter,
    'hasDigital' => $hasDigital,
    'view' => $viewMode,
    'limit' => $limit,
    'sort' => $sort,
    'dir' => $sortDir,
];

$typeConfig = [
  'archive' => ['icon' => 'fa-archive', 'color' => 'success', 'label' => 'Archive'],
  'museum' => ['icon' => 'fa-landmark', 'color' => 'warning', 'label' => 'Museum'],
  'gallery' => ['icon' => 'fa-palette', 'color' => 'info', 'label' => 'Gallery'],
  'library' => ['icon' => 'fa-book', 'color' => 'primary', 'label' => 'Library'],
  'dam' => ['icon' => 'fa-images', 'color' => 'danger', 'label' => 'Photo/DAM'],
];
$limitOptions = [10, 25, 50, 100];
$sortLabels = [
  'date' => 'Date modified',
  'title' => 'Title',
  'identifier' => 'Identifier',
  'refcode' => 'Reference code',
  'startdate' => 'Start date',
  'enddate' => 'End date',
];

function buildEmbeddedUrl($fp, $add = [], $remove = [], $keepPage = false) {
    $params = array_merge(['module' => 'display', 'action' => 'browse'], array_filter($fp), $add);
    foreach ($remove as $key) { unset($params[$key]); }
    if (!$keepPage && !isset($add['page'])) { unset($params['page']); }
    return url_for($params);
}

function getEmbeddedItemUrl($obj) {
    switch ($obj->object_type) {
        case 'library':
            return url_for(['module' => 'library', 'action' => 'index', 'slug' => $obj->slug]);
        case 'museum':
            return url_for(['module' => 'museum', 'action' => 'index', 'slug' => $obj->slug]);
        default:
            return url_for('@slug?slug=' . $obj->slug);
    }
}
?>
<div class="glam-browse-embedded">
  <div class="row">
    <?php if ($showSidebar): ?>
    <!-- Facets Sidebar -->
    <div class="col-lg-3 col-md-4">
      <div class="card mb-3" style="background-color: #1d6a52;">
        <div class="card-body py-2 text-white text-center">
          <i class="fas fa-filter"></i> <?php echo __('Filter by:'); ?>
        </div>
      </div>

      <!-- GLAM Type Facet -->
      <?php if (!empty($types)): ?>
      <div class="card mb-2">
        <div class="card-header bg-light py-2" data-bs-toggle="collapse" data-bs-target="#embFacetType" style="cursor:pointer">
          <strong><?php echo __('GLAM Type'); ?></strong> <i class="fas fa-chevron-down float-end"></i>
        </div>
        <div class="collapse show" id="embFacetType">
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo !$typeFilter ? 'active' : '' ?>">
              <a href="<?php echo buildEmbeddedUrl($fp, [], ['type']) ?>" class="text-decoration-none small <?php echo !$typeFilter ? 'text-white' : '' ?>">
                <?php echo __('All'); ?>
              </a>
            </li>
            <?php foreach ($types as $type): ?>
              <?php $cfg = $typeConfig[$type->object_type] ?? ['icon' => 'fa-question', 'color' => 'secondary', 'label' => ucfirst($type->object_type)]; ?>
              <?php $isActive = $typeFilter === $type->object_type; ?>
              <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo $isActive ? 'active' : '' ?>">
                <a href="<?php echo $isActive ? buildEmbeddedUrl($fp, [], ['type']) : buildEmbeddedUrl($fp, ['type' => $type->object_type]) ?>" class="text-decoration-none small <?php echo $isActive ? 'text-white' : '' ?>">
                  <i class="fas <?php echo $cfg['icon'] ?> text-<?php echo $isActive ? 'white' : $cfg['color'] ?>"></i>
                  <?php echo $cfg['label'] ?>
                </a>
                <span class="badge bg-<?php echo $isActive ? 'light text-dark' : 'secondary' ?> rounded-pill"><?php echo $type->count ?></span>
              </li>
            <?php endforeach ?>
          </ul>
        </div>
      </div>
      <?php endif ?>

      <!-- Repository Facet -->
      <?php if (!empty($repositories)): ?>
      <div class="card mb-2">
        <div class="card-header bg-light py-2" data-bs-toggle="collapse" data-bs-target="#embFacetRepo" style="cursor:pointer">
          <strong><?php echo __('Repository'); ?></strong> <i class="fas fa-chevron-down float-end"></i>
        </div>
        <div class="collapse" id="embFacetRepo">
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo !$repoFilter ? 'active' : '' ?>">
              <a href="<?php echo buildEmbeddedUrl($fp, [], ['repo']) ?>" class="text-decoration-none small <?php echo !$repoFilter ? 'text-white' : '' ?>">
                <?php echo __('All'); ?>
              </a>
            </li>
            <?php foreach ($repositories as $repo): ?>
              <?php $isActive = $repoFilter == $repo->id; ?>
              <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo $isActive ? 'active' : '' ?>">
                <a href="<?php echo $isActive ? buildEmbeddedUrl($fp, [], ['repo']) : buildEmbeddedUrl($fp, ['repo' => $repo->id]) ?>" class="text-decoration-none small text-truncate <?php echo $isActive ? 'text-white' : '' ?>" style="max-width:180px">
                  <?php echo esc_entities($repo->name) ?>
                </a>
                <span class="badge bg-<?php echo $isActive ? 'light text-dark' : 'secondary' ?> rounded-pill"><?php echo $repo->count ?></span>
              </li>
            <?php endforeach ?>
          </ul>
        </div>
      </div>
      <?php endif ?>

      <!-- Subject Facet -->
      <?php if (!empty($subjects)): ?>
      <div class="card mb-2">
        <div class="card-header bg-light py-2" data-bs-toggle="collapse" data-bs-target="#embFacetSubject" style="cursor:pointer">
          <strong><?php echo __('Subject'); ?></strong> <i class="fas fa-chevron-down float-end"></i>
        </div>
        <div class="collapse" id="embFacetSubject">
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo !$subjectFilter ? 'active' : '' ?>">
              <a href="<?php echo buildEmbeddedUrl($fp, [], ['subject']) ?>" class="text-decoration-none small <?php echo !$subjectFilter ? 'text-white' : '' ?>">
                <?php echo __('All'); ?>
              </a>
            </li>
            <?php foreach ($subjects as $subject): ?>
              <?php $isActive = $subjectFilter == $subject->id; ?>
              <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo $isActive ? 'active' : '' ?>">
                <a href="<?php echo $isActive ? buildEmbeddedUrl($fp, [], ['subject']) : buildEmbeddedUrl($fp, ['subject' => $subject->id]) ?>" class="text-decoration-none small text-truncate <?php echo $isActive ? 'text-white' : '' ?>" style="max-width:180px">
                  <?php echo esc_entities($subject->name) ?>
                </a>
                <span class="badge bg-<?php echo $isActive ? 'light text-dark' : 'secondary' ?> rounded-pill"><?php echo $subject->count ?></span>
              </li>
            <?php endforeach ?>
          </ul>
        </div>
      </div>
      <?php endif ?>

      <!-- Level Facet -->
      <?php if (!empty($levels)): ?>
      <div class="card mb-2">
        <div class="card-header bg-light py-2" data-bs-toggle="collapse" data-bs-target="#embFacetLevel" style="cursor:pointer">
          <strong><?php echo __('Level'); ?></strong> <i class="fas fa-chevron-down float-end"></i>
        </div>
        <div class="collapse" id="embFacetLevel">
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo !$levelFilter ? 'active' : '' ?>">
              <a href="<?php echo buildEmbeddedUrl($fp, [], ['level']) ?>" class="text-decoration-none small <?php echo !$levelFilter ? 'text-white' : '' ?>">
                <?php echo __('All'); ?>
              </a>
            </li>
            <?php foreach ($levels as $level): ?>
              <?php $isActive = $levelFilter == $level->id; ?>
              <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo $isActive ? 'active' : '' ?>">
                <a href="<?php echo $isActive ? buildEmbeddedUrl($fp, [], ['level']) : buildEmbeddedUrl($fp, ['level' => $level->id]) ?>" class="text-decoration-none small <?php echo $isActive ? 'text-white' : '' ?>">
                  <?php echo esc_entities($level->name) ?>
                </a>
                <span class="badge bg-<?php echo $isActive ? 'light text-dark' : 'secondary' ?> rounded-pill"><?php echo $level->count ?></span>
              </li>
            <?php endforeach ?>
          </ul>
        </div>
      </div>
      <?php endif ?>

      <!-- Creator Facet -->
      <?php if (!empty($creators)): ?>
      <div class="card mb-2">
        <div class="card-header bg-light py-2" data-bs-toggle="collapse" data-bs-target="#embFacetCreator" style="cursor:pointer">
          <strong><?php echo __('Creator'); ?></strong> <i class="fas fa-chevron-down float-end"></i>
        </div>
        <div class="collapse" id="embFacetCreator">
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo !$creatorFilter ? 'active' : '' ?>">
              <a href="<?php echo buildEmbeddedUrl($fp, [], ['creator']) ?>" class="text-decoration-none small <?php echo !$creatorFilter ? 'text-white' : '' ?>">
                <?php echo __('All'); ?>
              </a>
            </li>
            <?php foreach ($creators as $creator): ?>
              <?php $isActive = $creatorFilter == $creator->id; ?>
              <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo $isActive ? 'active' : '' ?>">
                <a href="<?php echo $isActive ? buildEmbeddedUrl($fp, [], ['creator']) : buildEmbeddedUrl($fp, ['creator' => $creator->id]) ?>" class="text-decoration-none small text-truncate <?php echo $isActive ? 'text-white' : '' ?>" style="max-width:180px">
                  <?php echo esc_entities($creator->name) ?>
                </a>
                <span class="badge bg-<?php echo $isActive ? 'light text-dark' : 'secondary' ?> rounded-pill"><?php echo $creator->count ?></span>
              </li>
            <?php endforeach ?>
          </ul>
        </div>
      </div>
      <?php endif ?>

      <!-- Place Facet -->
      <?php if (!empty($places)): ?>
      <div class="card mb-2">
        <div class="card-header bg-light py-2" data-bs-toggle="collapse" data-bs-target="#embFacetPlace" style="cursor:pointer">
          <strong><?php echo __('Place'); ?></strong> <i class="fas fa-chevron-down float-end"></i>
        </div>
        <div class="collapse" id="embFacetPlace">
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo !$placeFilter ? 'active' : '' ?>">
              <a href="<?php echo buildEmbeddedUrl($fp, [], ['place']) ?>" class="text-decoration-none small <?php echo !$placeFilter ? 'text-white' : '' ?>">
                <?php echo __('All'); ?>
              </a>
            </li>
            <?php foreach ($places as $place): ?>
              <?php $isActive = $placeFilter == $place->id; ?>
              <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo $isActive ? 'active' : '' ?>">
                <a href="<?php echo $isActive ? buildEmbeddedUrl($fp, [], ['place']) : buildEmbeddedUrl($fp, ['place' => $place->id]) ?>" class="text-decoration-none small text-truncate <?php echo $isActive ? 'text-white' : '' ?>" style="max-width:180px">
                  <?php echo esc_entities($place->name) ?>
                </a>
                <span class="badge bg-<?php echo $isActive ? 'light text-dark' : 'secondary' ?> rounded-pill"><?php echo $place->count ?></span>
              </li>
            <?php endforeach ?>
          </ul>
        </div>
      </div>
      <?php endif ?>

      <!-- Media Type Facet -->
      <?php if (!empty($mediaTypes)): ?>
      <div class="card mb-2">
        <div class="card-header bg-light py-2" data-bs-toggle="collapse" data-bs-target="#embFacetMedia" style="cursor:pointer">
          <strong><?php echo __('Media type'); ?></strong> <i class="fas fa-chevron-down float-end"></i>
        </div>
        <div class="collapse" id="embFacetMedia">
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo !$mediaFilter ? 'active' : '' ?>">
              <a href="<?php echo buildEmbeddedUrl($fp, [], ['media']) ?>" class="text-decoration-none small <?php echo !$mediaFilter ? 'text-white' : '' ?>">
                <?php echo __('All'); ?>
              </a>
            </li>
            <?php foreach ($mediaTypes as $media): ?>
              <?php $isActive = $mediaFilter === $media->media_type; ?>
              <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo $isActive ? 'active' : '' ?>">
                <a href="<?php echo $isActive ? buildEmbeddedUrl($fp, [], ['media']) : buildEmbeddedUrl($fp, ['media' => $media->media_type]) ?>" class="text-decoration-none small <?php echo $isActive ? 'text-white' : '' ?>">
                  <i class="fas fa-<?php echo match($media->media_type) { 'image' => 'image', 'video' => 'video', 'audio' => 'music', 'application' => 'file-alt', default => 'file' } ?>"></i>
                  <?php echo ucfirst($media->media_type) ?>
                </a>
                <span class="badge bg-<?php echo $isActive ? 'light text-dark' : 'secondary' ?> rounded-pill"><?php echo $media->count ?></span>
              </li>
            <?php endforeach ?>
          </ul>
        </div>
      </div>
      <?php endif ?>

      <a href="<?php echo url_for(['module' => 'display', 'action' => 'browse']) ?>" class="btn btn-outline-success btn-sm w-100 mt-2">
        <i class="fas fa-expand-arrows-alt me-1"></i> <?php echo __('Full Browse Page'); ?>
      </a>
    </div>
    <?php endif ?>

    <!-- Results Column -->
    <div class="<?php echo $showSidebar ? 'col-lg-9 col-md-8' : 'col-12' ?>">
      <!-- Header -->
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0 text-success">
          <i class="fas fa-folder-open me-2"></i>
          <?php echo __('Showing %1% results', ['%1%' => number_format($total)]); ?>
        </h4>
        <a href="<?php echo url_for(['module' => 'display', 'action' => 'browse']) ?>" class="btn btn-success btn-sm">
          <i class="fas fa-search me-1"></i> <?php echo __('Advanced Search'); ?>
        </a>
      </div>

      <!-- Active Filters -->
      <?php if ($typeFilter || $repoFilter || $levelFilter || $creatorFilter || $subjectFilter || $hasDigital): ?>
      <div class="d-flex flex-wrap gap-2 mb-3">
        <?php if ($typeFilter): $cfg = $typeConfig[$typeFilter] ?? ['icon' => 'fa-tag', 'color' => 'secondary', 'label' => ucfirst($typeFilter)]; ?>
          <a href="<?php echo buildEmbeddedUrl($fp, [], ['type']) ?>" class="badge bg-<?php echo $cfg['color'] ?> p-2 text-decoration-none text-white">
            <i class="fas <?php echo $cfg['icon'] ?>"></i> <?php echo $cfg['label'] ?> <i class="fas fa-times ms-1"></i>
          </a>
        <?php endif ?>
        <?php if ($repoFilter): $name = ''; foreach($repositories as $r) if($r->id == $repoFilter) $name = $r->name; ?>
          <a href="<?php echo buildEmbeddedUrl($fp, [], ['repo']) ?>" class="badge bg-dark p-2 text-decoration-none text-white">
            <?php echo __('Repository'); ?>: <?php echo esc_entities($name) ?> <i class="fas fa-times ms-1"></i>
          </a>
        <?php endif ?>
        <?php if ($levelFilter): $name = ''; foreach($levels as $l) if($l->id == $levelFilter) $name = $l->name; ?>
          <a href="<?php echo buildEmbeddedUrl($fp, [], ['level']) ?>" class="badge bg-secondary p-2 text-decoration-none text-white">
            <?php echo __('Level'); ?>: <?php echo esc_entities($name) ?> <i class="fas fa-times ms-1"></i>
          </a>
        <?php endif ?>
        <?php if ($hasDigital): ?>
          <a href="<?php echo buildEmbeddedUrl($fp, [], ['hasDigital']) ?>" class="badge bg-info p-2 text-decoration-none text-white">
            <?php echo __('With digital objects'); ?> <i class="fas fa-times ms-1"></i>
          </a>
        <?php endif ?>
      </div>
      <?php endif ?>

      <!-- Toolbar -->
      <div class="d-flex flex-wrap gap-2 mb-3 small">
        <a href="<?php echo buildEmbeddedUrl($fp, ['view' => 'card']) ?>" class="btn btn-sm <?php echo $viewMode === 'card' ? 'btn-success' : 'btn-outline-success' ?>"><i class="fas fa-th-large"></i></a>
        <a href="<?php echo buildEmbeddedUrl($fp, ['view' => 'grid']) ?>" class="btn btn-sm <?php echo $viewMode === 'grid' ? 'btn-success' : 'btn-outline-success' ?>"><i class="fas fa-th"></i></a>
        <a href="<?php echo buildEmbeddedUrl($fp, ['view' => 'table']) ?>" class="btn btn-sm <?php echo $viewMode === 'table' ? 'btn-success' : 'btn-outline-success' ?>"><i class="fas fa-list"></i></a>

        <div class="dropdown">
          <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><?php echo $limit ?>/<?php echo __('page'); ?></button>
          <ul class="dropdown-menu">
            <?php foreach ($limitOptions as $opt): ?>
              <li><a class="dropdown-item <?php echo $limit == $opt ? 'active' : '' ?>" href="<?php echo buildEmbeddedUrl($fp, ['limit' => $opt]) ?>"><?php echo $opt ?></a></li>
            <?php endforeach ?>
          </ul>
        </div>

        <div class="dropdown ms-auto">
          <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><?php echo __('Sort'); ?>: <?php echo $sortLabels[$sort] ?? 'Title' ?></button>
          <ul class="dropdown-menu">
            <?php foreach ($sortLabels as $sortKey => $sortLabel): ?>
              <li><a class="dropdown-item <?php echo $sort === $sortKey ? 'active' : '' ?>" href="<?php echo buildEmbeddedUrl($fp, ['sort' => $sortKey]) ?>"><?php echo $sortLabel ?></a></li>
            <?php endforeach ?>
          </ul>
        </div>

        <div class="dropdown">
          <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><?php echo $sortDir === 'asc' ? __('Asc') : __('Desc') ?></button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item <?php echo $sortDir === 'asc' ? 'active' : '' ?>" href="<?php echo buildEmbeddedUrl($fp, ['dir' => 'asc']) ?>"><?php echo __('Ascending'); ?></a></li>
            <li><a class="dropdown-item <?php echo $sortDir === 'desc' ? 'active' : '' ?>" href="<?php echo buildEmbeddedUrl($fp, ['dir' => 'desc']) ?>"><?php echo __('Descending'); ?></a></li>
          </ul>
        </div>
      </div>

      <!-- Results Info -->
      <div class="mb-3 text-muted small">
        <?php echo __('Results %1% to %2% of %3%', ['%1%' => min((($page - 1) * $limit) + 1, $total), '%2%' => min($page * $limit, $total), '%3%' => $total]); ?>
      </div>

      <!-- Results -->
      <?php if ($viewMode === 'grid'): ?>
        <!-- GRID VIEW -->
        <?php if (empty($objects)): ?>
          <div class="text-center text-muted py-5"><i class="fas fa-inbox fa-4x mb-3"></i><h4><?php echo __('No results'); ?></h4></div>
        <?php else: ?>
          <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
            <?php foreach ($objects as $obj): $cfg = $typeConfig[$obj->object_type] ?? ['icon' => 'fa-file', 'color' => 'secondary', 'label' => 'Unknown']; ?>
              <div class="col">
                <div class="card h-100 shadow-sm">
                  <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:120px;overflow:hidden;">
                    <?php if ($obj->thumbnail): ?>
                      <a href="<?php echo getEmbeddedItemUrl($obj) ?>"><img src="<?php echo $obj->thumbnail ?>" alt="" class="img-fluid" style="max-height:120px;object-fit:cover;"></a>
                    <?php else: ?>
                      <a href="<?php echo getEmbeddedItemUrl($obj) ?>"><i class="fas <?php echo $cfg['icon'] ?> fa-3x text-<?php echo $cfg['color'] ?>"></i></a>
                    <?php endif ?>
                  </div>
                  <div class="card-body p-2">
                    <a href="<?php echo getEmbeddedItemUrl($obj) ?>" class="text-success text-decoration-none small d-block text-truncate"><?php echo esc_entities($obj->title ?: '[Untitled]') ?></a>
                  </div>
                </div>
              </div>
            <?php endforeach ?>
          </div>
        <?php endif ?>

      <?php elseif ($viewMode === 'table'): ?>
        <!-- TABLE VIEW -->
        <div class="table-responsive">
          <table class="table table-hover table-sm">
            <thead class="table-light">
              <tr>
                <th style="width:60px"></th>
                <th><?php echo __('Title'); ?></th>
                <th style="width:100px"><?php echo __('Level'); ?></th>
                <th style="width:100px"><?php echo __('Type'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($objects)): ?>
                <tr><td colspan="4" class="text-center text-muted py-5"><i class="fas fa-inbox fa-3x mb-3"></i><br><?php echo __('No results'); ?></td></tr>
              <?php else: ?>
                <?php foreach ($objects as $obj): $cfg = $typeConfig[$obj->object_type] ?? ['icon' => 'fa-file', 'color' => 'secondary']; ?>
                  <tr>
                    <td class="text-center">
                      <?php if ($obj->thumbnail): ?>
                        <img src="<?php echo $obj->thumbnail ?>" alt="" class="rounded" style="width:50px;height:50px;object-fit:cover;">
                      <?php else: ?>
                        <i class="fas <?php echo $cfg['icon'] ?> fa-2x text-<?php echo $cfg['color'] ?>"></i>
                      <?php endif ?>
                    </td>
                    <td>
                      <a href="<?php echo getEmbeddedItemUrl($obj) ?>" class="text-success text-decoration-none"><?php echo esc_entities($obj->title ?: '[Untitled]') ?></a>
                      <?php if ($obj->identifier): ?><br><small class="text-muted"><?php echo esc_entities($obj->identifier) ?></small><?php endif ?>
                    </td>
                    <td><span class="badge bg-light text-dark"><?php echo esc_entities($obj->level_name ?: '-') ?></span></td>
                    <td><span class="badge bg-<?php echo $cfg['color'] ?>"><?php echo ucfirst($obj->object_type ?: '?') ?></span></td>
                  </tr>
                <?php endforeach ?>
              <?php endif ?>
            </tbody>
          </table>
        </div>

      <?php else: ?>
        <!-- CARD VIEW (default) -->
        <?php if (empty($objects)): ?>
          <div class="text-center text-muted py-5"><i class="fas fa-inbox fa-4x mb-3"></i><h4><?php echo __('No results'); ?></h4></div>
        <?php else: ?>
          <?php foreach ($objects as $obj): $cfg = $typeConfig[$obj->object_type] ?? ['icon' => 'fa-file', 'color' => 'secondary', 'label' => 'Unknown']; ?>
            <div class="card mb-2 shadow-sm">
              <div class="row g-0">
                <div class="col-md-2 d-flex align-items-center justify-content-center p-2" style="background:#f8f9fa;">
                  <?php if ($obj->thumbnail): ?>
                    <a href="<?php echo getEmbeddedItemUrl($obj) ?>"><img src="<?php echo $obj->thumbnail ?>" alt="" class="img-fluid rounded" style="max-height:100px;object-fit:contain;"></a>
                  <?php else: ?>
                    <a href="<?php echo getEmbeddedItemUrl($obj) ?>"><i class="fas <?php echo $cfg['icon'] ?> fa-3x text-<?php echo $cfg['color'] ?>"></i></a>
                  <?php endif ?>
                </div>
                <div class="col-md-10">
                  <div class="card-body py-2">
                    <h6 class="card-title mb-1">
                      <a href="<?php echo getEmbeddedItemUrl($obj) ?>" class="text-success text-decoration-none"><?php echo esc_entities($obj->title ?: '[Untitled]') ?></a>
                    </h6>
                    <p class="card-text mb-1 small">
                      <span class="text-success"><?php echo esc_entities($obj->identifier ?: '') ?></span>
                      <?php if ($obj->level_name): ?><span class="mx-1">&middot;</span><?php echo esc_entities($obj->level_name) ?><?php endif ?>
                      <?php if ($obj->child_count > 0): ?><span class="mx-1">&middot;</span><i class="fas fa-folder text-muted"></i> <?php echo $obj->child_count ?><?php endif ?>
                    </p>
                    <?php if ($obj->scope_and_content): ?>
                      <p class="card-text text-muted small mb-1"><?php echo esc_entities(mb_substr($obj->scope_and_content, 0, 120)) ?>...</p>
                    <?php endif ?>
                    <span class="badge bg-<?php echo $cfg['color'] ?>"><?php echo $cfg['label'] ?></span>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach ?>
        <?php endif ?>
      <?php endif ?>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
          <ul class="pagination justify-content-center">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?php echo buildEmbeddedUrl($fp, ['page' => $page - 1], [], true) ?>"><?php echo __('Previous'); ?></a></li>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
              <li class="page-item <?php echo $i == $page ? 'active' : '' ?>"><a class="page-link" href="<?php echo buildEmbeddedUrl($fp, ['page' => $i], [], true) ?>"><?php echo $i ?></a></li>
            <?php endfor ?>
            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="<?php echo buildEmbeddedUrl($fp, ['page' => $page + 1], [], true) ?>"><?php echo __('Next'); ?></a></li>
          </ul>
        </nav>
      <?php endif ?>
    </div>
  </div>
</div>

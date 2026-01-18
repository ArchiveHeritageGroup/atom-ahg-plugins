<?php decorate_with($sf_request->getParameter('view') === 'full' ? 'layout_1col' : 'layout_2col'); ?>
<?php use_helper('Date') ?>
<style>
/* Resizable table columns */
.table-resizable th {
  position: relative;
  min-width: 50px;
}
.table-resizable th .resize-handle {
  position: absolute;
  right: 0;
  top: 0;
  bottom: 0;
  width: 8px;
  cursor: col-resize;
  background: transparent;
  z-index: 10;
}
.table-resizable th .resize-handle:hover,
.table-resizable th .resize-handle.resizing {
  background: rgba(29, 106, 82, 0.3);
}
.table-resizable th.resizing {
  user-select: none;
}
/* Remove fixed widths to allow resizing */
.table-resizable th[style*="width"] {
  width: auto !important;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const tables = document.querySelectorAll('.table-resizable');
  
  tables.forEach(function(table) {
    const headers = table.querySelectorAll('th');
    
    headers.forEach(function(th) {
      // Create resize handle
      const handle = document.createElement('div');
      handle.className = 'resize-handle';
      th.appendChild(handle);
      
      let startX, startWidth;
      
      handle.addEventListener('mousedown', function(e) {
        startX = e.pageX;
        startWidth = th.offsetWidth;
        th.classList.add('resizing');
        handle.classList.add('resizing');
        
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
        e.preventDefault();
      });
      
      function onMouseMove(e) {
        const diff = e.pageX - startX;
        th.style.width = (startWidth + diff) + 'px';
        th.style.minWidth = (startWidth + diff) + 'px';
      }
      
      function onMouseUp() {
        th.classList.remove('resizing');
        handle.classList.remove('resizing');
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
      }
    });
  });
});
</script>
<?php
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

function buildUrl($fp, $add = [], $remove = [], $keepPage = false) {
    $params = array_merge(['module' => 'ahgDisplay', 'action' => 'browse'], array_filter($fp), $add);
    foreach ($remove as $key) { unset($params[$key]); }
    if (!$keepPage && !isset($add['page'])) { unset($params['page']); }
    return url_for($params);
}

function getItemUrl($obj) {
    switch ($obj->object_type) {
        case 'library':
            return url_for(['module' => 'ahgLibraryPlugin', 'action' => 'index', 'slug' => $obj->slug]);
        case 'museum':
            return url_for(['module' => 'ahgMuseumPlugin', 'action' => 'index', 'slug' => $obj->slug]);
        default:
            return url_for('@slug?slug=' . $obj->slug);
    }
}
?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-folder-open fa-2x text-muted me-3"></i>
    <div>
      <h1 class="mb-0 text-success"><?php echo __('Showing %1% results', ['%1%' => $total]); ?></h1>
      <span class="small text-muted">
        <?php if ($parent): ?><?php echo __('in'); ?> <?php echo esc_entities($parent->title ?: '[Untitled]') ?><?php else: ?><?php echo __('GLAM Browser'); ?><?php endif ?>
      </span>
    </div>
  </div>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
  <div class="card mb-3" style="background-color: #1d6a52;">
    <div class="card-body py-2 text-white text-center">
      <i class="fas fa-filter"></i> <?php echo __('Narrow your results by:'); ?>
    </div>
  </div>

  <!-- GLAM Type Facet - OPEN by default -->
  <?php if (!empty($types)): ?>
  <div class="card mb-2">
    <div class="card-header bg-light py-2" data-bs-toggle="collapse" data-bs-target="#facetType" style="cursor:pointer">
      <strong><?php echo __('GLAM Type'); ?></strong> <i class="fas fa-chevron-down float-end"></i>
    </div>
    <div class="collapse show" id="facetType">
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo !$typeFilter ? 'active' : '' ?>">
          <a href="<?php echo buildUrl($fp, [], ['type']) ?>" class="text-decoration-none small <?php echo !$typeFilter ? 'text-white' : '' ?>">
            <?php echo __('All'); ?>
          </a>
        </li>
        <?php foreach ($types as $type): ?>
          <?php $cfg = $typeConfig[$type->object_type] ?? ['icon' => 'fa-question', 'color' => 'secondary', 'label' => ucfirst($type->object_type)]; ?>
          <?php $isActive = $typeFilter === $type->object_type; ?>
          <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo $isActive ? 'active' : '' ?>">
            <a href="<?php echo $isActive ? buildUrl($fp, [], ['type']) : buildUrl($fp, ['type' => $type->object_type]) ?>" class="text-decoration-none small <?php echo $isActive ? 'text-white' : '' ?>">
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

  <!-- Creator Facet - CLOSED by default -->
  <?php if (!empty($creators)): ?>
  <div class="card mb-2">
    <div class="card-header bg-light py-2" data-bs-toggle="collapse" data-bs-target="#facetCreator" style="cursor:pointer">
      <strong><?php echo __('Creator'); ?></strong> <i class="fas fa-chevron-down float-end"></i>
    </div>
    <div class="collapse" id="facetCreator">
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo !$creatorFilter ? 'active' : '' ?>">
          <a href="<?php echo buildUrl($fp, [], ['creator']) ?>" class="text-decoration-none small <?php echo !$creatorFilter ? 'text-white' : '' ?>">
            <?php echo __('All'); ?>
          </a>
        </li>
        <?php foreach ($creators as $creator): ?>
          <?php $isActive = $creatorFilter == $creator->id; ?>
          <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo $isActive ? 'active' : '' ?>">
            <a href="<?php echo $isActive ? buildUrl($fp, [], ['creator']) : buildUrl($fp, ['creator' => $creator->id]) ?>" class="text-decoration-none small text-truncate <?php echo $isActive ? 'text-white' : '' ?>" style="max-width:180px">
              <?php echo esc_entities($creator->name) ?>
            </a>
            <span class="badge bg-<?php echo $isActive ? 'light text-dark' : 'secondary' ?> rounded-pill"><?php echo $creator->count ?></span>
          </li>
        <?php endforeach ?>
      </ul>
    </div>
  </div>
  <?php endif ?>

  <!-- Place Facet - CLOSED by default -->
  <?php if (!empty($places)): ?>
  <div class="card mb-2">
    <div class="card-header bg-light py-2" data-bs-toggle="collapse" data-bs-target="#facetPlace" style="cursor:pointer">
      <strong><?php echo __('Place'); ?></strong> <i class="fas fa-chevron-down float-end"></i>
    </div>
    <div class="collapse" id="facetPlace">
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo !$placeFilter ? 'active' : '' ?>">
          <a href="<?php echo buildUrl($fp, [], ['place']) ?>" class="text-decoration-none small <?php echo !$placeFilter ? 'text-white' : '' ?>">
            <?php echo __('All'); ?>
          </a>
        </li>
        <?php foreach ($places as $place): ?>
          <?php $isActive = $placeFilter == $place->id; ?>
          <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo $isActive ? 'active' : '' ?>">
            <a href="<?php echo $isActive ? buildUrl($fp, [], ['place']) : buildUrl($fp, ['place' => $place->id]) ?>" class="text-decoration-none small text-truncate <?php echo $isActive ? 'text-white' : '' ?>" style="max-width:180px">
              <?php echo esc_entities($place->name) ?>
            </a>
            <span class="badge bg-<?php echo $isActive ? 'light text-dark' : 'secondary' ?> rounded-pill"><?php echo $place->count ?></span>
          </li>
        <?php endforeach ?>
      </ul>
    </div>
  </div>
  <?php endif ?>

  <!-- Subject Facet - CLOSED by default -->
  <?php if (!empty($subjects)): ?>
  <div class="card mb-2">
    <div class="card-header bg-light py-2" data-bs-toggle="collapse" data-bs-target="#facetSubject" style="cursor:pointer">
      <strong><?php echo __('Subject'); ?></strong> <i class="fas fa-chevron-down float-end"></i>
    </div>
    <div class="collapse" id="facetSubject">
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo !$subjectFilter ? 'active' : '' ?>">
          <a href="<?php echo buildUrl($fp, [], ['subject']) ?>" class="text-decoration-none small <?php echo !$subjectFilter ? 'text-white' : '' ?>">
            <?php echo __('All'); ?>
          </a>
        </li>
        <?php foreach ($subjects as $subject): ?>
          <?php $isActive = $subjectFilter == $subject->id; ?>
          <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo $isActive ? 'active' : '' ?>">
            <a href="<?php echo $isActive ? buildUrl($fp, [], ['subject']) : buildUrl($fp, ['subject' => $subject->id]) ?>" class="text-decoration-none small text-truncate <?php echo $isActive ? 'text-white' : '' ?>" style="max-width:180px">
              <?php echo esc_entities($subject->name) ?>
            </a>
            <span class="badge bg-<?php echo $isActive ? 'light text-dark' : 'secondary' ?> rounded-pill"><?php echo $subject->count ?></span>
          </li>
        <?php endforeach ?>
      </ul>
    </div>
  </div>
  <?php endif ?>

  <!-- Genre Facet - CLOSED by default -->
  <?php if (!empty($genres)): ?>
  <div class="card mb-2">
    <div class="card-header bg-light py-2" data-bs-toggle="collapse" data-bs-target="#facetGenre" style="cursor:pointer">
      <strong><?php echo __('Genre'); ?></strong> <i class="fas fa-chevron-down float-end"></i>
    </div>
    <div class="collapse" id="facetGenre">
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo !$genreFilter ? 'active' : '' ?>">
          <a href="<?php echo buildUrl($fp, [], ['genre']) ?>" class="text-decoration-none small <?php echo !$genreFilter ? 'text-white' : '' ?>">
            <?php echo __('All'); ?>
          </a>
        </li>
        <?php foreach ($genres as $genre): ?>
          <?php $isActive = $genreFilter == $genre->id; ?>
          <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo $isActive ? 'active' : '' ?>">
            <a href="<?php echo $isActive ? buildUrl($fp, [], ['genre']) : buildUrl($fp, ['genre' => $genre->id]) ?>" class="text-decoration-none small text-truncate <?php echo $isActive ? 'text-white' : '' ?>" style="max-width:180px">
              <?php echo esc_entities($genre->name) ?>
            </a>
            <span class="badge bg-<?php echo $isActive ? 'light text-dark' : 'secondary' ?> rounded-pill"><?php echo $genre->count ?></span>
          </li>
        <?php endforeach ?>
      </ul>
    </div>
  </div>
  <?php endif ?>

  <!-- Level Facet - CLOSED by default -->
  <?php if (!empty($levels)): ?>
  <div class="card mb-2">
    <div class="card-header bg-light py-2" data-bs-toggle="collapse" data-bs-target="#facetLevel" style="cursor:pointer">
      <strong><?php echo __('Level of description'); ?></strong> <i class="fas fa-chevron-down float-end"></i>
    </div>
    <div class="collapse" id="facetLevel">
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo !$levelFilter ? 'active' : '' ?>">
          <a href="<?php echo buildUrl($fp, [], ['level']) ?>" class="text-decoration-none small <?php echo !$levelFilter ? 'text-white' : '' ?>">
            <?php echo __('All'); ?>
          </a>
        </li>
        <?php foreach ($levels as $level): ?>
          <?php $isActive = $levelFilter == $level->id; ?>
          <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo $isActive ? 'active' : '' ?>">
            <a href="<?php echo $isActive ? buildUrl($fp, [], ['level']) : buildUrl($fp, ['level' => $level->id]) ?>" class="text-decoration-none small <?php echo $isActive ? 'text-white' : '' ?>">
              <?php echo esc_entities($level->name) ?>
            </a>
            <span class="badge bg-<?php echo $isActive ? 'light text-dark' : 'secondary' ?> rounded-pill"><?php echo $level->count ?></span>
          </li>
        <?php endforeach ?>
      </ul>
    </div>
  </div>
  <?php endif ?>

  <!-- Media Type Facet - CLOSED by default -->
  <?php if (!empty($mediaTypes)): ?>
  <div class="card mb-2">
    <div class="card-header bg-light py-2" data-bs-toggle="collapse" data-bs-target="#facetMedia" style="cursor:pointer">
      <strong><?php echo __('Media type'); ?></strong> <i class="fas fa-chevron-down float-end"></i>
    </div>
    <div class="collapse" id="facetMedia">
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo !$mediaFilter ? 'active' : '' ?>">
          <a href="<?php echo buildUrl($fp, [], ['media']) ?>" class="text-decoration-none small <?php echo !$mediaFilter ? 'text-white' : '' ?>">
            <?php echo __('All'); ?>
          </a>
        </li>
        <?php foreach ($mediaTypes as $media): ?>
          <?php $isActive = $mediaFilter === $media->media_type; ?>
          <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo $isActive ? 'active' : '' ?>">
            <a href="<?php echo $isActive ? buildUrl($fp, [], ['media']) : buildUrl($fp, ['media' => $media->media_type]) ?>" class="text-decoration-none small <?php echo $isActive ? 'text-white' : '' ?>">
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

  <!-- Repository Facet - CLOSED by default -->
  <?php if (!empty($repositories)): ?>
  <div class="card mb-2">
    <div class="card-header bg-light py-2" data-bs-toggle="collapse" data-bs-target="#facetRepo" style="cursor:pointer">
      <strong><?php echo __('Repository'); ?></strong> <i class="fas fa-chevron-down float-end"></i>
    </div>
    <div class="collapse" id="facetRepo">
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo !$repoFilter ? 'active' : '' ?>">
          <a href="<?php echo buildUrl($fp, [], ['repo']) ?>" class="text-decoration-none small <?php echo !$repoFilter ? 'text-white' : '' ?>">
            <?php echo __('All'); ?>
          </a>
        </li>
        <?php foreach ($repositories as $repo): ?>
          <?php $isActive = $repoFilter == $repo->id; ?>
          <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo $isActive ? 'active' : '' ?>">
            <a href="<?php echo $isActive ? buildUrl($fp, [], ['repo']) : buildUrl($fp, ['repo' => $repo->id]) ?>" class="text-decoration-none small text-truncate <?php echo $isActive ? 'text-white' : '' ?>" style="max-width:180px">
              <?php echo esc_entities($repo->name) ?>
            </a>
            <span class="badge bg-<?php echo $isActive ? 'light text-dark' : 'secondary' ?> rounded-pill"><?php echo $repo->count ?></span>
          </li>
        <?php endforeach ?>
      </ul>
    </div>
  </div>
  <?php endif ?>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <!-- Active Filters -->
  <div class="d-flex flex-wrap gap-2 mb-3">
    <?php if ($topLevelOnly): ?>
      <a href="<?php echo buildUrl($fp, ['topLevel' => '0'], []) ?>" class="badge bg-primary p-2 text-decoration-none text-white">
        <?php echo __("Only top-level descriptions"); ?> <i class="fas fa-times ms-1"></i>
      </a>
    <?php endif ?>
    <?php if ($typeFilter): $cfg = $typeConfig[$typeFilter] ?? ['icon' => 'fa-tag', 'color' => 'secondary', 'label' => ucfirst($typeFilter)]; ?>
      <a href="<?php echo buildUrl($fp, [], ['type']) ?>" class="badge bg-<?php echo $cfg['color'] ?> p-2 text-decoration-none text-white">
        <i class="fas <?php echo $cfg['icon'] ?>"></i> <?php echo $cfg['label'] ?> <i class="fas fa-times ms-1"></i>
      </a>
    <?php endif ?>
    <?php if ($creatorFilter): $name = ''; foreach($creators as $c) if($c->id == $creatorFilter) $name = $c->name; ?>
      <a href="<?php echo buildUrl($fp, [], ['creator']) ?>" class="badge bg-info p-2 text-decoration-none text-white">
        <?php echo __('Creator'); ?>: <?php echo esc_entities($name) ?> <i class="fas fa-times ms-1"></i>
      </a>
    <?php endif ?>
    <?php if ($levelFilter): $name = ''; foreach($levels as $l) if($l->id == $levelFilter) $name = $l->name; ?>
      <a href="<?php echo buildUrl($fp, [], ['level']) ?>" class="badge bg-secondary p-2 text-decoration-none text-white">
        <?php echo __('Level'); ?>: <?php echo esc_entities($name) ?> <i class="fas fa-times ms-1"></i>
      </a>
    <?php endif ?>
    <?php if ($repoFilter): $name = ''; foreach($repositories as $r) if($r->id == $repoFilter) $name = $r->name; ?>
      <a href="<?php echo buildUrl($fp, [], ['repo']) ?>" class="badge bg-dark p-2 text-decoration-none text-white">
        <?php echo __('Repository'); ?>: <?php echo esc_entities($name) ?> <i class="fas fa-times ms-1"></i>
      </a>
    <?php endif ?>
    <?php if ($hasDigital): ?>
      <a href="<?php echo buildUrl($fp, [], ['hasDigital']) ?>" class="badge bg-info p-2 text-decoration-none text-white">
        <?php echo __('With digital objects'); ?> <i class="fas fa-times ms-1"></i>
      </a>
    <?php endif ?>
    <?php if ($parent): ?>
      <a href="<?php echo buildUrl($fp, [], ['parent']) ?>" class="badge bg-dark p-2 text-decoration-none text-white">
        <i class="fas fa-folder"></i> <?php echo esc_entities($parent->title ?: '[Untitled]') ?> <i class="fas fa-times ms-1"></i>
      </a>
    <?php endif ?>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <?php include_partial("ahgDisplaySearch/glamAdvancedSearchEnhancements"); ?>
  <?php include_partial("ahgDisplaySearch/glamAdvancedSearch"); ?>
  <!-- Toolbar -->
  <div class="d-flex flex-wrap gap-2 mb-3">
    <a href="<?php echo url_for(array_merge(['module' => 'ahgDisplay', 'action' => 'print'], array_filter($fp))) ?>" target="_blank" class="btn btn-success btn-sm"><i class="fas fa-print"></i> <?php echo __('Print'); ?></a>
    <a href="<?php echo url_for(array_merge(['module' => 'ahgDisplay', 'action' => 'exportCsv'], array_filter($fp))) ?>" class="btn btn-success btn-sm"><i class="fas fa-download"></i> <?php echo __('CSV'); ?></a>
    <a href="<?php echo buildUrl($fp, ['view' => 'card']) ?>" class="btn btn-sm <?php echo $viewMode === 'card' ? 'btn-success' : 'btn-outline-success' ?>" title="<?php echo __('Card view'); ?>"><i class="fas fa-th-large"></i></a>
    <a href="<?php echo buildUrl($fp, ['view' => 'grid']) ?>" class="btn btn-sm <?php echo $viewMode === 'grid' ? 'btn-success' : 'btn-outline-success' ?>" title="<?php echo __('Grid view'); ?>"><i class="fas fa-th"></i></a>
    <a href="<?php echo buildUrl($fp, ['view' => 'table']) ?>" class="btn btn-sm <?php echo $viewMode === 'table' ? 'btn-success' : 'btn-outline-success' ?>"><i class="fas fa-list"></i></a>
    <a href="<?php echo buildUrl($fp, ['view' => 'full']) ?>" class="btn btn-sm <?php echo $viewMode === 'full' ? 'btn-success' : 'btn-outline-success' ?>" title="<?php echo __('Full width'); ?>"><i class="fas fa-bars"></i></a>
    
    <div class="dropdown">
      <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><?php echo $limit ?>/<?php echo __('page'); ?></button>
      <ul class="dropdown-menu">
        <?php foreach ($limitOptions as $opt): ?>
          <li><a class="dropdown-item <?php echo $limit == $opt ? 'active' : '' ?>" href="<?php echo buildUrl($fp, ['limit' => $opt]) ?>"><?php echo $opt ?></a></li>
        <?php endforeach ?>
      </ul>
    </div>
    
    <div class="dropdown ms-auto">
      <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><?php echo __('Sort'); ?>: <?php echo $sortLabels[$sort] ?? 'Title' ?></button>
      <ul class="dropdown-menu">
        <?php foreach ($sortLabels as $sortKey => $sortLabel): ?>
          <li><a class="dropdown-item <?php echo $sort === $sortKey ? 'active' : '' ?>" href="<?php echo buildUrl($fp, ['sort' => $sortKey]) ?>"><?php echo $sortLabel ?></a></li>
        <?php endforeach ?>
      </ul>
    </div>
    
    <div class="dropdown">
      <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><?php echo $sortDir === 'asc' ? __('Asc') : __('Desc') ?></button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item <?php echo $sortDir === 'asc' ? 'active' : '' ?>" href="<?php echo buildUrl($fp, ['dir' => 'asc']) ?>"><?php echo __('Ascending'); ?></a></li>
        <li><a class="dropdown-item <?php echo $sortDir === 'desc' ? 'active' : '' ?>" href="<?php echo buildUrl($fp, ['dir' => 'desc']) ?>"><?php echo __('Descending'); ?></a></li>
      </ul>
    </div>
  </div>

  <!-- Digital Objects Info -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <small class="text-muted"><?php echo $digitalObjectCount ?> <?php echo __('with digital objects'); ?></small>
    <?php if (!$hasDigital): ?>
      <a href="<?php echo buildUrl($fp, ['hasDigital' => 1]) ?>" class="btn btn-outline-success btn-sm"><i class="fas fa-image"></i> <?php echo __('With digital objects'); ?></a>
    <?php endif ?>
  </div>

  <!-- Results Info -->
  <div class="mb-3 text-muted small">
    <?php echo __('Results %1% to %2% of %3%', ['%1%' => min((($page - 1) * $limit) + 1, $total), '%2%' => min($page * $limit, $total), '%3%' => $total]); ?>
  </div>

  <!-- Results -->
  <?php if ($viewMode === 'table'): ?>
    <div class="table-responsive">
      <table class="table table-hover table-resizable "style="font-size: 0.95rem;">
        <thead class="table-light">
          <tr>
            <th style="width:420px"><?php echo __('Image'); ?></th>
            <th><?php echo __('Title'); ?></th>
            <th style="width:120px"><?php echo __('Identifier'); ?></th>
            <th style="width:140px"><?php echo __('Level'); ?></th>
            <th style="width:140px"><?php echo __('Type'); ?></th>
            <th style="width:80px"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($objects)): ?>
            <tr><td colspan="6" class="text-center text-muted py-5"><i class="fas fa-inbox fa-3x mb-3"></i><br><?php echo __('No results'); ?></td></tr>
          <?php else: ?>
            <?php foreach ($objects as $obj): $cfg = $typeConfig[$obj->object_type] ?? ['icon' => 'fa-file', 'color' => 'secondary']; ?>
              <tr>
                <td class="text-center p-2">
                  <?php if ($obj->thumbnail): ?>
                    <a href="<?php echo getItemUrl($obj) ?>"><img src="<?php echo $obj->thumbnail ?>" alt="" class="rounded img-thumbnail" style="width:400px;height:400px;object-fit:cover;"></a>
                  <?php else: ?>
                    <a href="<?php echo getItemUrl($obj) ?>"><div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:400px;height:400px;"><i class="fas <?php echo $cfg['icon'] ?> fa-5x text-<?php echo $cfg['color'] ?>"></i></div></a>
                  <?php endif ?>
                </td>
                <td class="align-middle">
                  <a href="<?php echo getItemUrl($obj) ?>" class="text-success text-decoration-none fw-semibold"><?php echo esc_entities($obj->title ?: '[Untitled]') ?></a>
                  <?php if ($obj->scope_and_content): ?>
                    <p class="text-muted mb-1 small"><?php echo esc_entities(mb_substr($obj->scope_and_content, 0, 200)) ?>...</p>
                  <?php endif ?>
                  <?php if ($obj->child_count > 0): ?>
                    <a href="<?php echo buildUrl($fp, ['parent' => $obj->id]) ?>" class="text-muted small"><i class="fas fa-folder"></i> <?php echo $obj->child_count ?></a>
                  <?php endif ?>
                </td>
                <td class="align-middle small"><?php echo esc_entities($obj->identifier ?: '-') ?></td>
                <td class="align-middle"><span class="badge bg-light text-dark small"><?php echo esc_entities($obj->level_name ?: '-') ?></span></td>
                <td class="align-middle"><span class="badge bg-<?php echo $cfg['color'] ?> small"><?php echo ucfirst($obj->object_type ?: '?') ?></span></td>
                <td class="align-middle">
                  <div class="d-flex flex-column gap-1">
                    <a href="<?php echo getItemUrl($obj) ?>" class="btn btn-outline-success btn-sm"><i class="fas fa-eye"></i></a>
                    <button class="btn btn-outline-success btn-sm clipboard" data-clipboard-slug="<?php echo $obj->slug; ?>" data-clipboard-type="informationObject" data-tooltip="true" data-title="Add to clipboard" data-alt-title="Remove from clipboard"><i class="fas fa-paperclip"></i></button>
                  </div>
                </td>
              </tr>
            <?php endforeach ?>
          <?php endif ?>
        </tbody>
      </table>
    </div>
  <?php elseif ($viewMode === 'grid'): ?>
    <!-- GRID VIEW -->
    <?php if (empty($objects)): ?>
      <div class="text-center text-muted py-5"><i class="fas fa-inbox fa-4x mb-3"></i><h4><?php echo __('No results'); ?></h4></div>
    <?php else: ?>
      <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-3">
        <?php foreach ($objects as $obj): $cfg = $typeConfig[$obj->object_type] ?? ['icon' => 'fa-file', 'color' => 'secondary', 'label' => 'Unknown']; ?>
          <div class="col">
            <div class="card h-100 shadow-sm">
              <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:120px;overflow:hidden;">
                <?php if ($obj->thumbnail): ?>
                  <a href="<?php echo getItemUrl($obj) ?>"><img src="<?php echo $obj->thumbnail ?>" alt="" class="img-fluid" style="max-height:120px;object-fit:cover;"></a>
                <?php else: ?>
                  <a href="<?php echo getItemUrl($obj) ?>"><i class="fas <?php echo $cfg['icon'] ?> fa-3x text-<?php echo $cfg['color'] ?>"></i></a>
                <?php endif ?>
              </div>
              <div class="card-body p-2">
                <a href="<?php echo getItemUrl($obj) ?>" class="text-success text-decoration-none small d-block text-truncate"><?php echo esc_entities($obj->title ?: '[Untitled]') ?></a>
              </div>
            </div>
          </div>
        <?php endforeach ?>
      </div>
    <?php endif ?>
  <?php elseif ($viewMode === 'full'): ?>
    <!-- FULL WIDTH VIEW -->
    <?php if (empty($objects)): ?>
      <div class="text-center text-muted py-5"><i class="fas fa-inbox fa-4x mb-3"></i><h4><?php echo __('No results'); ?></h4></div>
    <?php else: ?>
      <?php foreach ($objects as $obj): $cfg = $typeConfig[$obj->object_type] ?? ['icon' => 'fa-file', 'color' => 'secondary', 'label' => 'Unknown']; ?>
        <div class="card mb-3 shadow-sm">
          <div class="row g-0">
            <div class="col-12 col-md-6 position-relative overflow-hidden" style="background:#f8f9fa;min-height:400px;">
              <?php if ($obj->thumbnail): ?>
                <a href="<?php echo getItemUrl($obj) ?>" class="d-block w-100 h-100"><img src="<?php echo $obj->thumbnail ?>" alt="" class="rounded" style="width:100%;height:100%;object-fit:contain;position:absolute;top:0;left:0;"></a>
              <?php else: ?>
                <a href="<?php echo getItemUrl($obj) ?>" class="d-flex align-items-center justify-content-center w-100 h-100"><i class="fas <?php echo $cfg['icon'] ?> fa-5x text-<?php echo $cfg['color'] ?>"></i></a>
              <?php endif ?>
            </div>
            <div class="col-12 col-md-6">
              <div class="card-body">
                <h4 class="card-title"><a href="<?php echo getItemUrl($obj) ?>" class="text-success text-decoration-none"><?php echo esc_entities($obj->title ?: '[Untitled]') ?></a></h4>
                <p class="card-text mb-2"><span class="badge bg-<?php echo $cfg['color'] ?>"><?php echo $cfg['label'] ?></span> <span class="badge bg-light text-dark"><?php echo esc_entities($obj->level_name ?: '-') ?></span></p>
                <?php if ($obj->identifier): ?><p class="card-text"><strong><?php echo __('Identifier'); ?>:</strong> <?php echo esc_entities($obj->identifier) ?></p><?php endif ?>
                <?php if ($obj->scope_and_content): ?><p class="card-text text-muted"><?php echo esc_entities(mb_substr($obj->scope_and_content, 0, 500)) ?>...</p><?php endif ?>
                <a href="<?php echo getItemUrl($obj) ?>" class="btn btn-success"><?php echo __('View'); ?></a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach ?>
    <?php endif ?>
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
                <a href="<?php echo getItemUrl($obj) ?>"><img src="<?php echo $obj->thumbnail ?>" alt="" class="img-fluid rounded" style="max-height:150px;object-fit:contain;"></a>
              <?php else: ?>
                <a href="<?php echo getItemUrl($obj) ?>"><i class="fas <?php echo $cfg['icon'] ?> fa-4x text-<?php echo $cfg['color'] ?>"></i></a>
              <?php endif ?>
            </div>
            <div class="col-md-9">
              <div class="card-body py-2">
                <h6 class="card-title mb-1">
                  <a href="<?php echo getItemUrl($obj) ?>" class="text-success text-decoration-none"><?php echo esc_entities($obj->title ?: '[Untitled]') ?></a>
                </h6>
                <p class="card-text mb-1 small">
                  <span class="text-success"><?php echo esc_entities($obj->identifier ?: '') ?></span>
                  <?php if ($obj->level_name): ?><span class="mx-1">&middot;</span><?php echo esc_entities($obj->level_name) ?><?php endif ?>
                  <?php if ($obj->child_count > 0): ?><span class="mx-1">&middot;</span><a href="<?php echo buildUrl($fp, ['parent' => $obj->id]) ?>"><i class="fas fa-folder"></i> <?php echo $obj->child_count ?></a><?php endif ?>
                </p>
                <?php if ($obj->scope_and_content): ?>
                  <p class="card-text text-muted small mb-1"><?php echo esc_entities(mb_substr($obj->scope_and_content, 0, 150)) ?>...</p>
                <?php endif ?>
                <span class="badge bg-<?php echo $cfg['color'] ?>"><?php echo $cfg['label'] ?></span>
              </div>
            </div>
            <div class="col-md-1 d-flex flex-column align-items-center justify-content-center border-start gap-1">
              <a href="<?php echo getItemUrl($obj) ?>" class="btn btn-outline-success btn-sm"><i class="fas fa-eye"></i></a><button class="btn btn-outline-success btn-sm clipboard" data-clipboard-slug="<?php echo $obj->slug; ?>" data-clipboard-type="informationObject" data-tooltip="true" data-title="Add to clipboard" data-alt-title="Remove from clipboard"><i class="fas fa-paperclip"></i></button>
            </div>
          </div>
        </div>
      <?php endforeach ?>
    <?php endif ?>
  <?php endif ?>

<?php end_slot(); ?>

<?php slot('after-content'); ?>
  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <nav class="mt-4">
      <ul class="pagination justify-content-center">
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?php echo buildUrl($fp, ['page' => $page - 1], [], true) ?>"><?php echo __('Previous'); ?></a></li>
        <?php for ($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++): ?>
          <li class="page-item <?php echo $i == $page ? 'active' : '' ?>"><a class="page-link" href="<?php echo buildUrl($fp, ['page' => $i], [], true) ?>"><?php echo $i ?></a></li>
        <?php endfor ?>
        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="<?php echo buildUrl($fp, ['page' => $page + 1], [], true) ?>"><?php echo __('Next'); ?></a></li>
      </ul>
    </nav>
  <?php endif ?>
<?php end_slot(); ?>

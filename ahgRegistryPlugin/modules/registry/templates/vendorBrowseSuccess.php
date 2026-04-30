<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Vendors Directory'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php
  $viewMode = $sf_request->getParameter('view', 'grid');
  if (!in_array($viewMode, ['grid', 'list'], true)) { $viewMode = 'grid'; }

  $sortKey = $sf_request->getParameter('sort', 'name');
  $sortDir = $sf_request->getParameter('dir', 'asc');
  $sortKey = in_array($sortKey, ['name', 'created_at', 'client_count', 'average_rating', 'country'], true) ? $sortKey : 'name';
  $sortDir = 'desc' === $sortDir ? 'desc' : 'asc';

  $baseParams = [
    'module' => 'registry',
    'action' => 'vendorBrowse',
    'q' => $sf_request->getParameter('q', ''),
    'type' => $sf_request->getParameter('type', ''),
    'country' => $sf_request->getParameter('country', ''),
    'specialization' => $sf_request->getParameter('specialization', ''),
    'sort' => $sortKey,
    'dir' => $sortDir,
    'view' => $viewMode,
  ];

  $sortOptions = [
    'name|asc' => __('Name (A–Z)'),
    'name|desc' => __('Name (Z–A)'),
    'created_at|desc' => __('Newest first'),
    'created_at|asc' => __('Oldest first'),
    'client_count|desc' => __('Most clients'),
    'average_rating|desc' => __('Highest rated'),
    'country|asc' => __('Country (A–Z)'),
  ];
  $sortCurrent = $sortKey . '|' . $sortDir;
  $sortLabel = $sortOptions[$sortCurrent] ?? __('Sort');
?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Vendors')],
]]); ?>

<div class="row mb-4 align-items-center">
  <div class="col">
    <h1 class="h3 mb-1"><?php echo __('Vendors Directory'); ?></h1>
    <p class="text-muted mb-0"><?php echo __('%1% vendors registered', ['%1%' => number_format($result['total'] ?? 0)]); ?></p>
  </div>
  <div class="col-auto d-flex align-items-center gap-2 flex-wrap">
    <div class="dropdown">
      <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-sort me-1"></i> <?php echo htmlspecialchars($sortLabel, ENT_QUOTES, 'UTF-8'); ?>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <?php foreach ($sortOptions as $key => $label): ?>
          <?php list($k, $d) = explode('|', $key); ?>
          <li>
            <a class="dropdown-item<?php echo $key === $sortCurrent ? ' active' : ''; ?>"
               href="<?php echo url_for(array_merge($baseParams, ['sort' => $k, 'dir' => $d, 'page' => 1])); ?>">
              <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="btn-group btn-group-sm" role="group" aria-label="<?php echo __('View mode'); ?>">
      <a href="<?php echo url_for(array_merge($baseParams, ['view' => 'grid'])); ?>"
         class="btn btn-outline-secondary<?php echo 'grid' === $viewMode ? ' active' : ''; ?>"
         title="<?php echo __('Grid view'); ?>"><i class="fas fa-th"></i></a>
      <a href="<?php echo url_for(array_merge($baseParams, ['view' => 'list'])); ?>"
         class="btn btn-outline-secondary<?php echo 'list' === $viewMode ? ' active' : ''; ?>"
         title="<?php echo __('List view'); ?>"><i class="fas fa-list"></i></a>
    </div>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'vendorRegister']); ?>" class="btn btn-primary btn-sm">
      <i class="fas fa-plus me-1"></i> <?php echo __('Register as Vendor'); ?>
    </a>
  </div>
</div>

<!-- Search bar -->
<div class="mb-4">
  <form method="get" action="<?php echo url_for(['module' => 'registry', 'action' => 'vendorBrowse']); ?>">
    <div class="input-group">
      <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($sf_request->getParameter('q', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Search vendors...'); ?>">
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-search"></i>
      </button>
    </div>
  </form>
</div>

<div class="row">

  <!-- Filter sidebar -->
  <div class="col-lg-3 mb-4">
    <div class="d-lg-none mb-3">
      <button class="btn btn-outline-secondary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#filterSidebar" aria-expanded="false">
        <i class="fas fa-filter me-1"></i> <?php echo __('Filters'); ?>
      </button>
    </div>
    <div class="collapse d-lg-block" id="filterSidebar">
      <?php include_partial('registry/filterSidebar', [
        'action' => 'vendorBrowse',
        'filters' => [
          [
            'label' => __('Vendor Type'),
            'name' => 'type',
            'current' => $sf_request->getParameter('type', ''),
            'options' => [
              '' => __('All Types'),
              'developer' => __('Developer'),
              'integrator' => __('Integrator'),
              'consultant' => __('Consultant'),
              'service_provider' => __('Service Provider'),
              'hosting' => __('Hosting'),
              'digitization' => __('Digitization'),
              'training' => __('Training'),
              'other' => __('Other'),
            ],
          ],
          [
            'label' => __('Specialization'),
            'name' => 'specialization',
            'current' => $sf_request->getParameter('specialization', ''),
            'options' => [
              '' => __('All'),
              'archives' => __('Archives'),
              'libraries' => __('Libraries'),
              'museums' => __('Museums'),
              'galleries' => __('Galleries'),
              'dam' => __('Digital Asset Management'),
              'preservation' => __('Digital Preservation'),
            ],
          ],
        ],
        'country' => true,
        'country_current' => $sf_request->getParameter('country', ''),
      ]); ?>
    </div>
  </div>

  <!-- Results grid -->
  <div class="col-lg-9">
    <?php if (!empty($result['items'])): ?>
      <?php if ('list' === $viewMode): ?>
        <div class="list-group">
          <?php foreach ($result['items'] as $v): ?>
            <?php include_partial('registry/vendorListItem', ['item' => $v]); ?>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3">
          <?php foreach ($result['items'] as $v): ?>
            <?php include_partial('registry/vendorCard', ['item' => $v]); ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Pagination -->
      <?php $page = (int) ($result['page'] ?? 1); $total = (int) ($result['total'] ?? 0); $limit = 24; ?>
      <?php if ($total > $limit): ?>
        <?php $totalPages = (int) ceil($total / $limit); ?>
        <nav aria-label="<?php echo __('Page navigation'); ?>" class="mt-4">
          <ul class="pagination justify-content-center">
            <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
              <a class="page-link" href="<?php echo url_for(array_merge($baseParams, ['page' => $page - 1])); ?>">&laquo;</a>
            </li>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
              <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
                <a class="page-link" href="<?php echo url_for(array_merge($baseParams, ['page' => $i])); ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
              <a class="page-link" href="<?php echo url_for(array_merge($baseParams, ['page' => $page + 1])); ?>">&raquo;</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    <?php else: ?>
      <div class="text-center py-5">
        <i class="fas fa-handshake fa-3x text-muted mb-3"></i>
        <h5><?php echo __('No vendors found'); ?></h5>
        <p class="text-muted"><?php echo __('Try adjusting your filters or search terms.'); ?></p>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'vendorBrowse']); ?>" class="btn btn-primary"><?php echo __('Clear Filters'); ?></a>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php end_slot(); ?>

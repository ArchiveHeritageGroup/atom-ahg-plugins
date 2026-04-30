<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Software Directory'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php
  $viewMode = $sf_request->getParameter('view', 'grid');
  if (!in_array($viewMode, ['grid', 'list'], true)) { $viewMode = 'grid'; }

  $sortKey = $sf_request->getParameter('sort', 'name');
  $sortDir = $sf_request->getParameter('dir', 'asc');
  $sortKey = in_array($sortKey, ['name', 'created_at', 'download_count', 'institution_count', 'average_rating', 'latest_version'], true) ? $sortKey : 'name';
  $sortDir = 'desc' === $sortDir ? 'desc' : 'asc';

  $baseParams = [
    'module' => 'registry',
    'action' => 'softwareBrowse',
    'q' => $sf_request->getParameter('q', ''),
    'category' => $sf_request->getParameter('category', ''),
    'license' => $sf_request->getParameter('license', ''),
    'pricing' => $sf_request->getParameter('pricing', ''),
    'sector' => $sf_request->getParameter('sector', ''),
    'sort' => $sortKey,
    'dir' => $sortDir,
    'view' => $viewMode,
  ];

  $sortOptions = [
    'name|asc' => __('Name (A–Z)'),
    'name|desc' => __('Name (Z–A)'),
    'created_at|desc' => __('Newest first'),
    'created_at|asc' => __('Oldest first'),
    'download_count|desc' => __('Most downloads'),
    'institution_count|desc' => __('Most institutions'),
    'average_rating|desc' => __('Highest rated'),
  ];
  $sortCurrent = $sortKey . '|' . $sortDir;
  $sortLabel = $sortOptions[$sortCurrent] ?? __('Sort');
?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Software')],
]]); ?>

<div class="row mb-4 align-items-center">
  <div class="col">
    <h1 class="h3 mb-1"><?php echo __('Software Directory'); ?></h1>
    <p class="text-muted mb-0"><?php echo __('%1% software products listed', ['%1%' => number_format($result['total'] ?? 0)]); ?></p>
  </div>
  <div class="col-auto d-flex align-items-center gap-2">
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
         title="<?php echo __('Grid view'); ?>" aria-label="<?php echo __('Grid view'); ?>">
        <i class="fas fa-th"></i>
      </a>
      <a href="<?php echo url_for(array_merge($baseParams, ['view' => 'list'])); ?>"
         class="btn btn-outline-secondary<?php echo 'list' === $viewMode ? ' active' : ''; ?>"
         title="<?php echo __('List view'); ?>" aria-label="<?php echo __('List view'); ?>">
        <i class="fas fa-list"></i>
      </a>
    </div>
    <?php if ($sf_user->isAuthenticated()): ?>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorSoftwareAdd']); ?>" class="btn btn-primary btn-sm">
      <i class="fas fa-plus me-1"></i> <?php echo __('Add Software'); ?>
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- Search bar -->
<div class="mb-4">
  <form method="get" action="<?php echo url_for(['module' => 'registry', 'action' => 'softwareBrowse']); ?>">
    <div class="input-group">
      <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($sf_request->getParameter('q', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Search software...'); ?>">
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
        'action' => 'softwareBrowse',
        'filters' => [
          [
            'label' => __('Category'),
            'name' => 'category',
            'current' => $sf_request->getParameter('category', ''),
            'options' => [
              '' => __('All Categories'),
              'ams' => __('AMS (Archival Management System)'),
              'ims' => __('IMS (Information Management)'),
              'dam' => __('DAM (Digital Asset Management)'),
              'dams' => __('DAMS'),
              'cms' => __('CMS'),
              'preservation' => __('Digital Preservation'),
              'digitization' => __('Digitization'),
              'discovery' => __('Discovery'),
              'utility' => __('Utility'),
              'plugin' => __('Plugin/Extension'),
              'theme' => __('Theme'),
              'integration' => __('Integration'),
              'other' => __('Other'),
            ],
          ],
          [
            'label' => __('License'),
            'name' => 'license',
            'current' => $sf_request->getParameter('license', ''),
            'options' => [
              '' => __('All Licenses'),
              'GPL-3.0' => 'GPL-3.0',
              'GPL-2.0' => 'GPL-2.0',
              'MIT' => 'MIT',
              'Apache-2.0' => 'Apache 2.0',
              'BSD-3-Clause' => 'BSD 3-Clause',
              'AGPL-3.0' => 'AGPL-3.0',
              'proprietary' => __('Proprietary'),
            ],
          ],
          [
            'label' => __('Pricing'),
            'name' => 'pricing',
            'current' => $sf_request->getParameter('pricing', ''),
            'options' => [
              '' => __('All'),
              'free' => __('Free'),
              'open_source' => __('Open Source'),
              'freemium' => __('Freemium'),
              'subscription' => __('Subscription'),
              'one_time' => __('One-Time License'),
              'contact' => __('Contact for Pricing'),
            ],
          ],
          [
            'label' => __('GLAM Sector'),
            'name' => 'sector',
            'current' => $sf_request->getParameter('sector', ''),
            'options' => [
              '' => __('All Sectors'),
              'archive' => __('Archive'),
              'library' => __('Library'),
              'museum' => __('Museum'),
              'gallery' => __('Gallery'),
              'dam' => __('DAM'),
            ],
          ],
        ],
      ]); ?>
    </div>
  </div>

  <!-- Results grid -->
  <div class="col-lg-9">
    <?php if (!empty($result['items'])): ?>
      <?php if ('list' === $viewMode): ?>
        <div class="list-group">
          <?php foreach ($result['items'] as $sw): ?>
            <?php include_partial('registry/softwareListItem', ['item' => $sw]); ?>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3">
          <?php foreach ($result['items'] as $sw): ?>
            <?php include_partial('registry/softwareCard', ['item' => $sw]); ?>
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
        <i class="fas fa-code fa-3x text-muted mb-3"></i>
        <h5><?php echo __('No software found'); ?></h5>
        <p class="text-muted"><?php echo __('Try adjusting your filters or search terms.'); ?></p>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareBrowse']); ?>" class="btn btn-primary"><?php echo __('Clear Filters'); ?></a>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php end_slot(); ?>

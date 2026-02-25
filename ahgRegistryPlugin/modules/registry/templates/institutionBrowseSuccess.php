<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Institutions Directory'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Institutions')],
]]); ?>

<div class="row mb-4 align-items-center">
  <div class="col">
    <h1 class="h3 mb-1"><?php echo __('Institutions Directory'); ?></h1>
    <p class="text-muted mb-0"><?php echo __('%1% institutions registered', ['%1%' => number_format($result['total'] ?? 0)]); ?></p>
  </div>
  <div class="col-auto">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'map']); ?>" class="btn btn-outline-secondary btn-sm me-1">
      <i class="fas fa-map me-1"></i> <?php echo __('Map View'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionRegister']); ?>" class="btn btn-primary btn-sm">
      <i class="fas fa-plus me-1"></i> <?php echo __('Register'); ?>
    </a>
  </div>
</div>

<!-- Search bar -->
<div class="mb-4">
  <form method="get" action="<?php echo url_for(['module' => 'registry', 'action' => 'institutionBrowse']); ?>">
    <div class="input-group">
      <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($sf_request->getParameter('q', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Search institutions...'); ?>">
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
        'action' => 'institutionBrowse',
        'filters' => [
          [
            'label' => __('Type'),
            'name' => 'type',
            'current' => $sf_request->getParameter('type', ''),
            'options' => [
              '' => __('All Types'),
              'archive' => __('Archive'),
              'library' => __('Library'),
              'museum' => __('Museum'),
              'gallery' => __('Gallery'),
              'dam' => __('Digital Asset Management'),
              'heritage_site' => __('Heritage Site'),
              'research_centre' => __('Research Centre'),
              'government' => __('Government'),
              'university' => __('University'),
              'other' => __('Other'),
            ],
          ],
          [
            'label' => __('Size'),
            'name' => 'size',
            'current' => $sf_request->getParameter('size', ''),
            'options' => [
              '' => __('All Sizes'),
              'small' => __('Small'),
              'medium' => __('Medium'),
              'large' => __('Large'),
              'national' => __('National'),
            ],
          ],
          [
            'label' => __('Governance'),
            'name' => 'governance',
            'current' => $sf_request->getParameter('governance', ''),
            'options' => [
              '' => __('All'),
              'public' => __('Public'),
              'private' => __('Private'),
              'ngo' => __('NGO'),
              'academic' => __('Academic'),
              'government' => __('Government'),
              'tribal' => __('Tribal'),
              'community' => __('Community'),
            ],
          ],
          [
            'label' => __('Uses AtoM'),
            'name' => 'uses_atom',
            'current' => $sf_request->getParameter('uses_atom', ''),
            'options' => [
              '' => __('All'),
              '1' => __('Yes'),
              '0' => __('No'),
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
      <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3">
        <?php foreach ($result['items'] as $inst): ?>
          <?php include_partial('registry/institutionCard', ['item' => $inst]); ?>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php $page = (int) ($result['page'] ?? 1); $total = (int) ($result['total'] ?? 0); $limit = 24; ?>
      <?php if ($total > $limit): ?>
        <?php $totalPages = (int) ceil($total / $limit); ?>
        <nav aria-label="<?php echo __('Page navigation'); ?>" class="mt-4">
          <ul class="pagination justify-content-center">
            <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
              <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionBrowse', 'page' => $page - 1, 'q' => $sf_request->getParameter('q', ''), 'type' => $sf_request->getParameter('type', ''), 'country' => $sf_request->getParameter('country', ''), 'size' => $sf_request->getParameter('size', ''), 'governance' => $sf_request->getParameter('governance', '')]); ?>">&laquo;</a>
            </li>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
              <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
                <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionBrowse', 'page' => $i, 'q' => $sf_request->getParameter('q', ''), 'type' => $sf_request->getParameter('type', ''), 'country' => $sf_request->getParameter('country', ''), 'size' => $sf_request->getParameter('size', ''), 'governance' => $sf_request->getParameter('governance', '')]); ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
              <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionBrowse', 'page' => $page + 1, 'q' => $sf_request->getParameter('q', ''), 'type' => $sf_request->getParameter('type', ''), 'country' => $sf_request->getParameter('country', ''), 'size' => $sf_request->getParameter('size', ''), 'governance' => $sf_request->getParameter('governance', '')]); ?>">&raquo;</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    <?php else: ?>
      <div class="text-center py-5">
        <i class="fas fa-university fa-3x text-muted mb-3"></i>
        <h5><?php echo __('No institutions found'); ?></h5>
        <p class="text-muted"><?php echo __('Try adjusting your filters or search terms.'); ?></p>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionBrowse']); ?>" class="btn btn-primary"><?php echo __('Clear Filters'); ?></a>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php end_slot(); ?>

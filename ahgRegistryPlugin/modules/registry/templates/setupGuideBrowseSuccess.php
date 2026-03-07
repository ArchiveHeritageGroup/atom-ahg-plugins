<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Setup Guides'); ?> - <?php echo htmlspecialchars($software->name ?? '', ENT_QUOTES, 'UTF-8'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Software'), 'url' => url_for(['module' => 'registry', 'action' => 'softwareBrowse'])],
  ['label' => htmlspecialchars($software->name ?? '', ENT_QUOTES, 'UTF-8'), 'url' => url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $software->slug ?? ''])],
  ['label' => __('Setup Guides')],
]]); ?>

<div class="row mb-4 align-items-center">
  <div class="col">
    <h1 class="h3 mb-1"><?php echo __('Setup Guides for %1%', ['%1%' => htmlspecialchars($software->name ?? '', ENT_QUOTES, 'UTF-8')]); ?></h1>
    <p class="text-muted mb-0"><?php echo __('%1% guides available', ['%1%' => number_format($result['total'] ?? 0)]); ?></p>
  </div>
  <div class="col-auto">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $software->slug ?? '']); ?>" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Software'); ?>
    </a>
  </div>
</div>

<?php
  $guideCatBg = [
    'security' => 'bg-danger',
    'deployment' => 'bg-primary',
    'configuration' => 'bg-info text-dark',
    'optimization' => 'bg-success',
    'troubleshooting' => 'bg-warning text-dark',
    'integration' => 'bg-dark',
  ];
?>

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
        'action' => 'setupGuideBrowse',
        'extraParams' => ['slug' => $software->slug ?? ''],
        'filters' => [
          [
            'label' => __('Category'),
            'name' => 'category',
            'current' => $sf_request->getParameter('category', ''),
            'options' => [
              '' => __('All Categories'),
              'security' => __('Security'),
              'deployment' => __('Deployment'),
              'configuration' => __('Configuration'),
              'optimization' => __('Optimization'),
              'troubleshooting' => __('Troubleshooting'),
              'integration' => __('Integration'),
            ],
          ],
        ],
      ]); ?>
    </div>
  </div>

  <!-- Results -->
  <div class="col-lg-9">
    <?php if (!empty($result['items'])): ?>
      <div class="row row-cols-1 row-cols-md-2 g-3">
        <?php foreach ($result['items'] as $guide): ?>
        <div class="col">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="card-title mb-0">
                  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'setupGuideView', 'slug' => $software->slug ?? '', 'guide_slug' => $guide->slug ?? '']); ?>" class="text-decoration-none stretched-link">
                    <?php echo htmlspecialchars($guide->title ?? '', ENT_QUOTES, 'UTF-8'); ?>
                  </a>
                </h6>
                <?php if (!empty($guide->is_featured)): ?>
                  <span class="badge bg-warning text-dark flex-shrink-0 ms-2"><i class="fas fa-award"></i></span>
                <?php endif; ?>
              </div>
              <?php
                $gCat = $guide->category ?? '';
                $gCatClass = $guideCatBg[strtolower($gCat)] ?? 'bg-secondary';
              ?>
              <div class="mb-2">
                <span class="badge <?php echo $gCatClass; ?>"><?php echo htmlspecialchars(ucfirst($gCat), ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <?php if (!empty($guide->short_description)): ?>
              <p class="card-text small text-muted mb-2">
                <?php echo htmlspecialchars(mb_strimwidth(strip_tags($guide->short_description), 0, 150, '...'), ENT_QUOTES, 'UTF-8'); ?>
              </p>
              <?php endif; ?>
              <div class="d-flex justify-content-between align-items-center small text-muted">
                <div>
                  <?php if (!empty($guide->author_name)): ?>
                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($guide->author_name, ENT_QUOTES, 'UTF-8'); ?>
                  <?php endif; ?>
                </div>
                <div>
                  <?php if (!empty($guide->view_count)): ?>
                    <i class="fas fa-eye me-1"></i><?php echo number_format((int) $guide->view_count); ?>
                  <?php endif; ?>
                  <?php if (!empty($guide->updated_at)): ?>
                    <span class="ms-2"><i class="fas fa-clock me-1"></i><?php echo date('M j, Y', strtotime($guide->updated_at)); ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php $page = (int) ($result['page'] ?? 1); $total = (int) ($result['total'] ?? 0); $limit = 20; ?>
      <?php if ($total > $limit): ?>
        <?php $totalPages = (int) ceil($total / $limit); ?>
        <nav aria-label="<?php echo __('Page navigation'); ?>" class="mt-4">
          <ul class="pagination justify-content-center">
            <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
              <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'setupGuideBrowse', 'slug' => $software->slug ?? '', 'page' => $page - 1, 'category' => $sf_request->getParameter('category', '')]); ?>">&laquo;</a>
            </li>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
              <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
                <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'setupGuideBrowse', 'slug' => $software->slug ?? '', 'page' => $i, 'category' => $sf_request->getParameter('category', '')]); ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
              <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'setupGuideBrowse', 'slug' => $software->slug ?? '', 'page' => $page + 1, 'category' => $sf_request->getParameter('category', '')]); ?>">&raquo;</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    <?php else: ?>
      <div class="text-center py-5">
        <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
        <h5><?php echo __('No setup guides found'); ?></h5>
        <p class="text-muted"><?php echo __('No guides are available for this software yet.'); ?></p>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'setupGuideBrowse', 'slug' => $software->slug ?? '']); ?>" class="btn btn-primary"><?php echo __('Clear Filters'); ?></a>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php end_slot(); ?>

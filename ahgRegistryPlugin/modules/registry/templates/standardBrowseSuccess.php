<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Standards Directory'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Standards')],
]]); ?>

<div class="row mb-4 align-items-center">
  <div class="col">
    <h1 class="h3 mb-1"><?php echo __('Standards Directory'); ?></h1>
    <p class="text-muted mb-0"><?php echo __('%1% standards listed', ['%1%' => number_format($result['total'] ?? 0)]); ?></p>
  </div>
  <div class="col-auto">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'erdBrowse']); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-project-diagram me-1"></i><?php echo __('Schema & ERD'); ?>
    </a>
  </div>
</div>

<!-- Search bar -->
<div class="mb-4">
  <form method="get" action="<?php echo url_for(['module' => 'registry', 'action' => 'standardBrowse']); ?>">
    <div class="input-group">
      <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($sf_request->getParameter('q', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Search standards...'); ?>">
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
        'action' => 'standardBrowse',
        'filters' => [
          [
            'label' => __('Category'),
            'name' => 'category',
            'current' => $sf_request->getParameter('category', ''),
            'options' => [
              '' => __('All Categories'),
              'descriptive' => __('Descriptive'),
              'preservation' => __('Preservation'),
              'rights' => __('Rights'),
              'accounting' => __('Accounting'),
              'compliance' => __('Compliance'),
              'metadata' => __('Metadata'),
              'interchange' => __('Interchange'),
              'sector' => __('Sector'),
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
      <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3">
        <?php foreach ($result['items'] as $item): ?>
          <?php include_partial('registry/standardCard', ['item' => $item]); ?>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php $page = (int) ($result['page'] ?? 1); $total = (int) ($result['total'] ?? 0); $limit = 20; ?>
      <?php if ($total > $limit): ?>
        <?php $totalPages = (int) ceil($total / $limit); ?>
        <nav aria-label="<?php echo __('Page navigation'); ?>" class="mt-4">
          <ul class="pagination justify-content-center">
            <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
              <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'standardBrowse', 'page' => $page - 1, 'q' => $sf_request->getParameter('q', ''), 'category' => $sf_request->getParameter('category', ''), 'sector' => $sf_request->getParameter('sector', '')]); ?>">&laquo;</a>
            </li>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
              <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
                <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'standardBrowse', 'page' => $i, 'q' => $sf_request->getParameter('q', ''), 'category' => $sf_request->getParameter('category', ''), 'sector' => $sf_request->getParameter('sector', '')]); ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
              <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'standardBrowse', 'page' => $page + 1, 'q' => $sf_request->getParameter('q', ''), 'category' => $sf_request->getParameter('category', ''), 'sector' => $sf_request->getParameter('sector', '')]); ?>">&raquo;</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    <?php else: ?>
      <div class="text-center py-5">
        <i class="fas fa-balance-scale fa-3x text-muted mb-3"></i>
        <h5><?php echo __('No standards found'); ?></h5>
        <p class="text-muted"><?php echo __('Try adjusting your filters or search terms.'); ?></p>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'standardBrowse']); ?>" class="btn btn-primary"><?php echo __('Clear Filters'); ?></a>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php end_slot(); ?>

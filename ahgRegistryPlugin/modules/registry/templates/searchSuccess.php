<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Search Registry'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Search')],
]]); ?>

<!-- Search form -->
<div class="row justify-content-center mb-4">
  <div class="col-lg-8">
    <form method="get" action="<?php echo url_for(['module' => 'registry', 'action' => 'search']); ?>">
      <div class="input-group input-group-lg">
        <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Search institutions, vendors, software, groups, discussions, blog...'); ?>" autofocus>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-search me-1"></i> <?php echo __('Search'); ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Type filter -->
<div class="text-center mb-4">
  <?php
    $types = ['' => __('All'), 'institutions' => __('Institutions'), 'vendors' => __('Vendors'), 'software' => __('Software'), 'groups' => __('Groups'), 'discussions' => __('Discussions'), 'blog' => __('Blog')];
  ?>
  <div class="btn-group btn-group-sm flex-wrap" role="group">
    <?php foreach ($types as $val => $label): ?>
      <a href="<?php echo url_for(['module' => 'registry', 'action' => 'search', 'q' => $query, 'type' => $val]); ?>" class="btn btn-outline-secondary<?php echo $type === $val ? ' active' : ''; ?>">
        <?php echo $label; ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- Results count -->
<?php if (!empty($query)): ?>
<p class="text-muted mb-3">
  <?php echo __('%1% results for "%2%"', ['%1%' => number_format($total), '%2%' => htmlspecialchars($query, ENT_QUOTES, 'UTF-8')]); ?>
  <?php if (!empty($type)): ?>
    <?php echo __('in %1%', ['%1%' => htmlspecialchars($types[$type] ?? $type, ENT_QUOTES, 'UTF-8')]); ?>
  <?php endif; ?>
</p>
<?php endif; ?>

<!-- Results list -->
<?php if (!empty($results)): ?>
<div class="list-group">
  <?php foreach ($results as $item): ?>
  <div class="list-group-item">
    <div class="d-flex align-items-start">
      <div class="me-3" style="min-width: 40px; text-align: center;">
        <?php
          $entityType = $item->entity_type ?? '';
          $icons = [
            'institution' => 'fas fa-university text-primary',
            'vendor' => 'fas fa-building text-success',
            'software' => 'fas fa-box-open text-info',
            'group' => 'fas fa-users text-warning',
            'discussion' => 'fas fa-comments text-secondary',
            'blog' => 'fas fa-newspaper text-danger',
          ];
          $icon = $icons[$entityType] ?? 'fas fa-circle text-muted';
        ?>
        <i class="<?php echo $icon; ?> fa-lg"></i>
      </div>
      <div class="flex-grow-1">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h6 class="mb-1">
              <?php
                // Build proper URL based on entity type
                $entityType = $item->entity_type ?? '';
                $itemUrl = '';
                $meta = isset($item->meta) ? (is_array($item->meta) ? $item->meta : (array) $item->meta) : [];
                switch ($entityType) {
                  case 'institution':
                    $itemUrl = url_for(['module' => 'registry', 'action' => 'institutionView', 'slug' => $meta['slug'] ?? $item->id]);
                    break;
                  case 'vendor':
                    $itemUrl = url_for(['module' => 'registry', 'action' => 'vendorView', 'slug' => $meta['slug'] ?? $item->id]);
                    break;
                  case 'software':
                    $itemUrl = url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $meta['slug'] ?? $item->id]);
                    break;
                  case 'user_group':
                    $itemUrl = url_for(['module' => 'registry', 'action' => 'groupView', 'slug' => $meta['slug'] ?? $item->id]);
                    break;
                  case 'discussion':
                    $itemUrl = url_for(['module' => 'registry', 'action' => 'discussionView', 'id' => $item->id, 'slug' => $meta['group_slug'] ?? '']);
                    break;
                  case 'blog_post':
                    $itemUrl = url_for(['module' => 'registry', 'action' => 'blogView', 'slug' => $meta['slug'] ?? $item->id]);
                    break;
                }
              ?>
              <?php if ($itemUrl): ?>
                <a href="<?php echo $itemUrl; ?>" class="text-decoration-none"><?php echo htmlspecialchars($item->title ?? '', ENT_QUOTES, 'UTF-8'); ?></a>
              <?php else: ?>
                <?php echo htmlspecialchars($item->title ?? '', ENT_QUOTES, 'UTF-8'); ?>
              <?php endif; ?>
            </h6>
            <?php if (!empty($item->excerpt)): ?>
            <p class="mb-1 small text-muted"><?php echo htmlspecialchars($item->excerpt, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
          </div>
          <span class="badge bg-<?php
            $typeColors = ['institution' => 'primary', 'vendor' => 'success', 'software' => 'info', 'group' => 'warning', 'discussion' => 'secondary', 'blog' => 'danger'];
            echo $typeColors[$entityType] ?? 'secondary';
          ?> ms-2"><?php echo htmlspecialchars(ucfirst($entityType), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php $limit = 20; ?>
<?php if ($total > $limit): ?>
  <?php $totalPages = (int) ceil($total / $limit); ?>
  <nav aria-label="<?php echo __('Page navigation'); ?>" class="mt-4">
    <ul class="pagination justify-content-center">
      <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'search', 'q' => $query, 'type' => $type, 'page' => $page - 1]); ?>">&laquo;</a>
      </li>
      <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'search', 'q' => $query, 'type' => $type, 'page' => $i]); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'search', 'q' => $query, 'type' => $type, 'page' => $page + 1]); ?>">&raquo;</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<?php elseif (!empty($query)): ?>
<div class="text-center py-5">
  <i class="fas fa-search fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No results found'); ?></h5>
  <p class="text-muted"><?php echo __('No results match "%1%". Try different keywords or broaden your search.', ['%1%' => htmlspecialchars($query, ENT_QUOTES, 'UTF-8')]); ?></p>
  <div class="mt-3">
    <p class="small text-muted mb-2"><?php echo __('Suggestions:'); ?></p>
    <ul class="list-unstyled small text-muted">
      <li><?php echo __('Check for typos or use more general terms'); ?></li>
      <li><?php echo __('Try searching a different type (institutions, vendors, software)'); ?></li>
      <li><?php echo __('Browse the directory instead'); ?></li>
    </ul>
  </div>
  <div class="mt-3">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionBrowse']); ?>" class="btn btn-outline-primary btn-sm me-1"><?php echo __('Browse Institutions'); ?></a>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'vendorBrowse']); ?>" class="btn btn-outline-success btn-sm me-1"><?php echo __('Browse Vendors'); ?></a>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareBrowse']); ?>" class="btn btn-outline-info btn-sm"><?php echo __('Browse Software'); ?></a>
  </div>
</div>
<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-search fa-3x text-muted mb-3"></i>
  <h5><?php echo __('Search the Registry'); ?></h5>
  <p class="text-muted"><?php echo __('Enter a search term above to find institutions, vendors, software, groups, discussions, and blog posts.'); ?></p>
</div>
<?php endif; ?>

<?php end_slot(); ?>

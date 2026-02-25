<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Manage Blog Posts'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Blog Posts')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Manage Blog Posts'); ?></h1>
  <span class="badge bg-secondary fs-6"><?php echo number_format($result['total'] ?? 0); ?> <?php echo __('total'); ?></span>
</div>

<!-- Search -->
<div class="mb-4">
  <form method="get" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminBlog']); ?>">
    <div class="input-group">
      <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($sf_request->getParameter('q', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Search blog posts...'); ?>">
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-search"></i>
      </button>
    </div>
  </form>
</div>

<?php if (!empty($result['items'])): ?>
<div class="table-responsive">
  <table class="table table-hover table-striped align-middle">
    <thead class="table-light">
      <tr>
        <th><?php echo __('Title'); ?></th>
        <th><?php echo __('Author'); ?></th>
        <th><?php echo __('Category'); ?></th>
        <th class="text-center"><?php echo __('Status'); ?></th>
        <th class="text-center"><?php echo __('Views'); ?></th>
        <th><?php echo __('Published'); ?></th>
        <th class="text-end"><?php echo __('Actions'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($result['items'] as $item): ?>
      <tr>
        <td>
          <span class="fw-semibold"><?php echo htmlspecialchars($item->title ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
          <?php if (!empty($item->is_pinned)): ?>
            <span class="badge bg-info text-dark ms-1"><i class="fas fa-thumbtack"></i></span>
          <?php endif; ?>
          <?php if (!empty($item->is_featured)): ?>
            <span class="badge bg-primary ms-1"><i class="fas fa-star"></i></span>
          <?php endif; ?>
        </td>
        <td>
          <?php
            $authorTypeBg = [
              'admin' => 'bg-danger',
              'vendor' => 'bg-primary',
              'institution' => 'bg-success',
              'user_group' => 'bg-purple',
            ];
            $at = $item->author_type ?? '';
            $atClass = $authorTypeBg[$at] ?? 'bg-secondary';
          ?>
          <small>
            <?php echo htmlspecialchars($item->author_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
            <?php if ($at): ?>
              <span class="badge <?php echo $atClass; ?>" style="<?php echo 'user_group' === $at ? 'background-color:#6f42c1!important;' : ''; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $at)), ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
          </small>
        </td>
        <td>
          <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($item->category ?? 'news'), ENT_QUOTES, 'UTF-8'); ?></span>
        </td>
        <td class="text-center">
          <?php
            $statusBg = [
              'published' => 'bg-success',
              'draft' => 'bg-secondary',
              'pending_review' => 'bg-warning text-dark',
              'archived' => 'bg-dark',
            ];
            $st = $item->status ?? 'draft';
            $stClass = $statusBg[$st] ?? 'bg-secondary';
          ?>
          <span class="badge <?php echo $stClass; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $st)), ENT_QUOTES, 'UTF-8'); ?></span>
        </td>
        <td class="text-center">
          <small class="text-muted"><?php echo number_format((int) ($item->view_count ?? 0)); ?></small>
        </td>
        <td>
          <small class="text-muted">
            <?php if (!empty($item->published_at)): ?>
              <?php echo date('Y-m-d', strtotime($item->published_at)); ?>
            <?php else: ?>
              -
            <?php endif; ?>
          </small>
        </td>
        <td class="text-end">
          <div class="btn-group btn-group-sm">
            <?php if (($item->status ?? 'draft') !== 'published'): ?>
            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminBlog']); ?>" class="d-inline">
              <input type="hidden" name="form_action" value="publish">
              <input type="hidden" name="post_id" value="<?php echo (int) $item->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-success" title="<?php echo __('Publish'); ?>">
                <i class="fas fa-check"></i>
              </button>
            </form>
            <?php endif; ?>

            <?php if (($item->status ?? 'draft') !== 'archived'): ?>
            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminBlog']); ?>" class="d-inline">
              <input type="hidden" name="form_action" value="archive">
              <input type="hidden" name="post_id" value="<?php echo (int) $item->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?php echo __('Archive'); ?>">
                <i class="fas fa-archive"></i>
              </button>
            </form>
            <?php endif; ?>

            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminBlog']); ?>" class="d-inline">
              <input type="hidden" name="form_action" value="feature">
              <input type="hidden" name="post_id" value="<?php echo (int) $item->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-primary" title="<?php echo !empty($item->is_featured) ? __('Unfeature') : __('Feature'); ?>">
                <i class="fas fa-star"></i>
              </button>
            </form>

            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminBlog']); ?>" class="d-inline">
              <input type="hidden" name="form_action" value="pin">
              <input type="hidden" name="post_id" value="<?php echo (int) $item->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-info" title="<?php echo !empty($item->is_pinned) ? __('Unpin') : __('Pin'); ?>">
                <i class="fas fa-thumbtack"></i>
              </button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Pagination -->
<?php $page = (int) ($result['page'] ?? 1); $total = (int) ($result['total'] ?? 0); $limit = 50; ?>
<?php if ($total > $limit): ?>
  <?php $totalPages = (int) ceil($total / $limit); ?>
  <nav aria-label="<?php echo __('Page navigation'); ?>" class="mt-3">
    <ul class="pagination justify-content-center">
      <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminBlog', 'page' => $page - 1, 'q' => $sf_request->getParameter('q', '')]); ?>">&laquo;</a>
      </li>
      <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminBlog', 'page' => $i, 'q' => $sf_request->getParameter('q', '')]); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminBlog', 'page' => $page + 1, 'q' => $sf_request->getParameter('q', '')]); ?>">&raquo;</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-blog fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No blog posts found'); ?></h5>
  <p class="text-muted"><?php echo __('Try adjusting your search terms.'); ?></p>
</div>
<?php endif; ?>

<?php end_slot(); ?>

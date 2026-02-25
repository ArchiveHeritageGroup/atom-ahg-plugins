<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Manage Institutions'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Institutions')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Manage Institutions'); ?></h1>
  <span class="badge bg-secondary fs-6"><?php echo number_format($result['total'] ?? 0); ?> <?php echo __('total'); ?></span>
</div>

<!-- Search -->
<div class="mb-4">
  <form method="get" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminInstitutions']); ?>">
    <div class="input-group">
      <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($sf_request->getParameter('q', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Search institutions...'); ?>">
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
        <th><?php echo __('Name'); ?></th>
        <th><?php echo __('Type'); ?></th>
        <th><?php echo __('Country'); ?></th>
        <th class="text-center"><?php echo __('Verified'); ?></th>
        <th class="text-center"><?php echo __('Featured'); ?></th>
        <th class="text-center"><?php echo __('Active'); ?></th>
        <th><?php echo __('Created'); ?></th>
        <th class="text-end"><?php echo __('Actions'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($result['items'] as $item): ?>
      <tr>
        <td>
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionView', 'slug' => $item->slug ?? '']); ?>" class="fw-semibold text-decoration-none">
            <?php echo htmlspecialchars($item->name ?? '', ENT_QUOTES, 'UTF-8'); ?>
          </a>
        </td>
        <td>
          <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $item->institution_type ?? '')), ENT_QUOTES, 'UTF-8'); ?></span>
        </td>
        <td><?php echo htmlspecialchars($item->country ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="text-center">
          <?php if (!empty($item->is_verified)): ?>
            <span class="badge bg-success"><i class="fas fa-check"></i></span>
          <?php else: ?>
            <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i></span>
          <?php endif; ?>
        </td>
        <td class="text-center">
          <?php if (!empty($item->is_featured)): ?>
            <span class="badge bg-primary"><i class="fas fa-star"></i></span>
          <?php else: ?>
            <span class="text-muted">-</span>
          <?php endif; ?>
        </td>
        <td class="text-center">
          <?php if (!isset($item->is_active) || $item->is_active): ?>
            <span class="badge bg-success"><?php echo __('Active'); ?></span>
          <?php else: ?>
            <span class="badge bg-danger"><?php echo __('Suspended'); ?></span>
          <?php endif; ?>
        </td>
        <td>
          <small class="text-muted"><?php echo !empty($item->created_at) ? date('Y-m-d', strtotime($item->created_at)) : '-'; ?></small>
        </td>
        <td class="text-end">
          <div class="btn-group btn-group-sm">
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionEdit', 'id' => (int) $item->id]); ?>" class="btn btn-sm btn-outline-secondary" title="<?php echo __('Edit'); ?>">
              <i class="fas fa-edit"></i>
            </a>

            <?php if (empty($item->is_verified)): ?>
            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminInstitutionVerify']); ?>" class="d-inline">
              <input type="hidden" name="form_action" value="verify">
              <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-success" title="<?php echo __('Verify'); ?>">
                <i class="fas fa-check"></i>
              </button>
            </form>
            <?php else: ?>
            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminInstitutionVerify']); ?>" class="d-inline">
              <input type="hidden" name="form_action" value="unverify">
              <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?php echo __('Unverify'); ?>">
                <i class="fas fa-times"></i>
              </button>
            </form>
            <?php endif; ?>

            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminInstitutionVerify']); ?>" class="d-inline">
              <input type="hidden" name="form_action" value="feature">
              <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-primary" title="<?php echo !empty($item->is_featured) ? __('Unfeature') : __('Feature'); ?>">
                <i class="fas fa-star"></i>
              </button>
            </form>

            <?php if (!isset($item->is_active) || $item->is_active): ?>
            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminInstitutionVerify']); ?>" class="d-inline">
              <input type="hidden" name="form_action" value="suspend">
              <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Suspend'); ?>">
                <i class="fas fa-ban"></i>
              </button>
            </form>
            <?php else: ?>
            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminInstitutionVerify']); ?>" class="d-inline">
              <input type="hidden" name="form_action" value="activate">
              <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-success" title="<?php echo __('Activate'); ?>">
                <i class="fas fa-play"></i>
              </button>
            </form>
            <?php endif; ?>

            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminInstitutionVerify']); ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this institution? This cannot be undone.');">
              <input type="hidden" name="form_action" value="delete">
              <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Delete'); ?>">
                <i class="fas fa-trash"></i>
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
        <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminInstitutions', 'page' => $page - 1, 'q' => $sf_request->getParameter('q', '')]); ?>">&laquo;</a>
      </li>
      <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminInstitutions', 'page' => $i, 'q' => $sf_request->getParameter('q', '')]); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminInstitutions', 'page' => $page + 1, 'q' => $sf_request->getParameter('q', '')]); ?>">&raquo;</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-university fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No institutions found'); ?></h5>
  <p class="text-muted"><?php echo __('Try adjusting your search terms.'); ?></p>
</div>
<?php endif; ?>

<?php end_slot(); ?>

<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Manage Reviews'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Reviews')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Manage Reviews'); ?></h1>
  <span class="badge bg-secondary fs-6"><?php echo number_format($total ?? 0); ?> <?php echo __('total'); ?></span>
</div>

<?php if (!empty($reviews) && count($reviews) > 0): ?>
<div class="table-responsive">
  <table class="table table-hover table-striped align-middle">
    <thead class="table-light">
      <tr>
        <th><?php echo __('Entity Type'); ?></th>
        <th><?php echo __('Entity ID'); ?></th>
        <th><?php echo __('Reviewer'); ?></th>
        <th class="text-center"><?php echo __('Rating'); ?></th>
        <th><?php echo __('Title'); ?></th>
        <th class="text-center"><?php echo __('Visible'); ?></th>
        <th class="text-center"><?php echo __('Verified'); ?></th>
        <th><?php echo __('Date'); ?></th>
        <th class="text-end"><?php echo __('Actions'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($reviews as $item): ?>
      <tr>
        <td>
          <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $item->entity_type ?? '')), ENT_QUOTES, 'UTF-8'); ?></span>
        </td>
        <td>
          <small class="text-muted">#<?php echo (int) ($item->entity_id ?? 0); ?></small>
        </td>
        <td>
          <small><?php echo htmlspecialchars($item->reviewer_name ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
        </td>
        <td class="text-center">
          <?php include_partial('registry/ratingStars', ['rating' => (float) ($item->rating ?? 0), 'count' => 0]); ?>
        </td>
        <td>
          <?php echo htmlspecialchars($item->title ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </td>
        <td class="text-center">
          <?php if (!isset($item->is_visible) || $item->is_visible): ?>
            <span class="badge bg-success"><i class="fas fa-eye"></i></span>
          <?php else: ?>
            <span class="badge bg-secondary"><i class="fas fa-eye-slash"></i></span>
          <?php endif; ?>
        </td>
        <td class="text-center">
          <?php if (!empty($item->is_verified_purchase)): ?>
            <span class="badge bg-success"><i class="fas fa-check"></i></span>
          <?php else: ?>
            <span class="text-muted">-</span>
          <?php endif; ?>
        </td>
        <td>
          <small class="text-muted"><?php echo !empty($item->created_at) ? date('Y-m-d', strtotime($item->created_at)) : '-'; ?></small>
        </td>
        <td class="text-end">
          <div class="btn-group btn-group-sm">
            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminReviews']); ?>" class="d-inline">
              <input type="hidden" name="form_action" value="toggle_visibility">
              <input type="hidden" name="review_id" value="<?php echo (int) $item->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?php echo (!isset($item->is_visible) || $item->is_visible) ? __('Hide') : __('Show'); ?>">
                <i class="fas fa-<?php echo (!isset($item->is_visible) || $item->is_visible) ? 'eye-slash' : 'eye'; ?>"></i>
              </button>
            </form>

            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminReviews']); ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Are you sure you want to delete this review?'); ?>');">
              <input type="hidden" name="form_action" value="delete">
              <input type="hidden" name="review_id" value="<?php echo (int) $item->id; ?>">
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
<?php $limit = 50; ?>
<?php if (($total ?? 0) > $limit): ?>
  <?php $totalPages = (int) ceil($total / $limit); ?>
  <nav aria-label="<?php echo __('Page navigation'); ?>" class="mt-3">
    <ul class="pagination justify-content-center">
      <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminReviews', 'page' => $page - 1]); ?>">&laquo;</a>
      </li>
      <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminReviews', 'page' => $i]); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminReviews', 'page' => $page + 1]); ?>">&raquo;</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-star fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No reviews found'); ?></h5>
  <p class="text-muted"><?php echo __('Reviews will appear here once users submit them.'); ?></p>
</div>
<?php endif; ?>

<?php end_slot(); ?>

<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Moderate Discussions'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Discussions')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Moderate Discussions'); ?></h1>
  <span class="badge bg-secondary fs-6"><?php echo number_format($total ?? 0); ?> <?php echo __('total'); ?></span>
</div>

<!-- Status filter tabs -->
<?php $currentStatus = $sf_request->getParameter('status', ''); ?>
<ul class="nav nav-tabs mb-4">
  <li class="nav-item">
    <a class="nav-link<?php echo '' === $currentStatus ? ' active' : ''; ?>" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminDiscussions']); ?>">
      <?php echo __('All'); ?>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link<?php echo 'active' === $currentStatus ? ' active' : ''; ?>" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminDiscussions', 'status' => 'active']); ?>">
      <?php echo __('Active'); ?>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link<?php echo 'hidden' === $currentStatus ? ' active' : ''; ?>" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminDiscussions', 'status' => 'hidden']); ?>">
      <?php echo __('Hidden'); ?>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link<?php echo 'spam' === $currentStatus ? ' active' : ''; ?>" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminDiscussions', 'status' => 'spam']); ?>">
      <?php echo __('Spam'); ?>
    </a>
  </li>
</ul>

<?php if (!empty($discussions) && count($discussions) > 0): ?>
<div class="table-responsive">
  <table class="table table-hover table-striped align-middle">
    <thead class="table-light">
      <tr>
        <th><?php echo __('Title'); ?></th>
        <th><?php echo __('Group'); ?></th>
        <th><?php echo __('Author'); ?></th>
        <th class="text-center"><?php echo __('Replies'); ?></th>
        <th class="text-center"><?php echo __('Status'); ?></th>
        <th><?php echo __('Created'); ?></th>
        <th class="text-end"><?php echo __('Actions'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($discussions as $item): ?>
      <tr>
        <td>
          <span class="fw-semibold"><?php echo htmlspecialchars($item->title ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
          <?php if (!empty($item->is_pinned)): ?>
            <span class="badge bg-info text-dark ms-1"><i class="fas fa-thumbtack"></i></span>
          <?php endif; ?>
          <?php if (!empty($item->is_locked)): ?>
            <span class="badge bg-secondary ms-1"><i class="fas fa-lock"></i></span>
          <?php endif; ?>
        </td>
        <td>
          <small><?php echo htmlspecialchars($item->group_name ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
        </td>
        <td>
          <small><?php echo htmlspecialchars($item->author_name ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
        </td>
        <td class="text-center">
          <span class="badge bg-light text-dark border"><?php echo (int) ($item->reply_count ?? 0); ?></span>
        </td>
        <td class="text-center">
          <?php
            $statusBg = [
              'active' => 'bg-success',
              'hidden' => 'bg-warning text-dark',
              'spam' => 'bg-danger',
              'locked' => 'bg-secondary',
            ];
            $st = $item->status ?? 'active';
            $stClass = $statusBg[$st] ?? 'bg-secondary';
          ?>
          <span class="badge <?php echo $stClass; ?>"><?php echo htmlspecialchars(ucfirst($st), ENT_QUOTES, 'UTF-8'); ?></span>
        </td>
        <td>
          <small class="text-muted"><?php echo !empty($item->created_at) ? date('Y-m-d H:i', strtotime($item->created_at)) : '-'; ?></small>
        </td>
        <td class="text-end">
          <div class="btn-group btn-group-sm">
            <?php if (($item->status ?? 'active') !== 'hidden'): ?>
            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminDiscussions']); ?>" class="d-inline">
              <input type="hidden" name="form_action" value="hide">
              <input type="hidden" name="discussion_id" value="<?php echo (int) $item->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-warning" title="<?php echo __('Hide'); ?>">
                <i class="fas fa-eye-slash"></i>
              </button>
            </form>
            <?php endif; ?>

            <?php if (($item->status ?? 'active') !== 'spam'): ?>
            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminDiscussions']); ?>" class="d-inline">
              <input type="hidden" name="form_action" value="spam">
              <input type="hidden" name="discussion_id" value="<?php echo (int) $item->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Mark as Spam'); ?>">
                <i class="fas fa-ban"></i>
              </button>
            </form>
            <?php endif; ?>

            <?php if (($item->status ?? 'active') !== 'active'): ?>
            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminDiscussions']); ?>" class="d-inline">
              <input type="hidden" name="form_action" value="activate">
              <input type="hidden" name="discussion_id" value="<?php echo (int) $item->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-success" title="<?php echo __('Activate'); ?>">
                <i class="fas fa-check"></i>
              </button>
            </form>
            <?php endif; ?>

            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminDiscussions']); ?>" class="d-inline">
              <input type="hidden" name="form_action" value="lock">
              <input type="hidden" name="discussion_id" value="<?php echo (int) $item->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?php echo !empty($item->is_locked) ? __('Unlock') : __('Lock'); ?>">
                <i class="fas fa-<?php echo !empty($item->is_locked) ? 'unlock' : 'lock'; ?>"></i>
              </button>
            </form>

            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminDiscussions']); ?>" class="d-inline">
              <input type="hidden" name="form_action" value="pin">
              <input type="hidden" name="discussion_id" value="<?php echo (int) $item->id; ?>">
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
<?php $limit = 50; ?>
<?php if (($total ?? 0) > $limit): ?>
  <?php $totalPages = (int) ceil($total / $limit); ?>
  <nav aria-label="<?php echo __('Page navigation'); ?>" class="mt-3">
    <ul class="pagination justify-content-center">
      <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminDiscussions', 'page' => $page - 1, 'status' => $currentStatus]); ?>">&laquo;</a>
      </li>
      <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminDiscussions', 'page' => $i, 'status' => $currentStatus]); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminDiscussions', 'page' => $page + 1, 'status' => $currentStatus]); ?>">&raquo;</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-comments fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No discussions found'); ?></h5>
  <p class="text-muted"><?php echo __('No discussions match the selected filter.'); ?></p>
</div>
<?php endif; ?>

<?php end_slot(); ?>

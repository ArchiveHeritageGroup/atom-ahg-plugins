<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Manage Users'); ?> — Admin<?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Manage Users')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Manage Users'); ?></h1>
  <a href="/registry/admin/users" class="btn btn-outline-warning btn-sm">
    <i class="fas fa-user-check me-1"></i> <?php echo __('User Approval'); ?>
  </a>
</div>

<!-- Search and filter -->
<div class="card mb-4">
  <div class="card-body">
    <form method="get" action="/registry/admin/users/manage" class="row g-2 align-items-end">
      <div class="col-md-5">
        <label class="form-label small"><?php echo __('Search'); ?></label>
        <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Name, email, or username...'); ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label small"><?php echo __('Filter'); ?></label>
        <select class="form-select" name="filter">
          <option value="all" <?php echo 'all' === $filter ? 'selected' : ''; ?>><?php echo __('All Users'); ?></option>
          <option value="active" <?php echo 'active' === $filter ? 'selected' : ''; ?>><?php echo __('Active'); ?></option>
          <option value="inactive" <?php echo 'inactive' === $filter ? 'selected' : ''; ?>><?php echo __('Inactive'); ?></option>
          <option value="admin" <?php echo 'admin' === $filter ? 'selected' : ''; ?>><?php echo __('Administrators'); ?></option>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">
          <i class="fas fa-search me-1"></i> <?php echo __('Search'); ?>
        </button>
      </div>
      <div class="col-md-2">
        <a href="/registry/admin/users/manage" class="btn btn-outline-secondary w-100"><?php echo __('Reset'); ?></a>
      </div>
    </form>
  </div>
</div>

<!-- Results -->
<div class="card">
  <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
    <span><i class="fas fa-users me-1"></i> <?php echo __('Users'); ?></span>
    <span class="badge bg-secondary"><?php echo number_format($total); ?> <?php echo __('total'); ?></span>
  </div>
  <?php
    $rawUsers = sfOutputEscaper::unescape($users);
    $rawUserGroups = sfOutputEscaper::unescape($userGroups);
  ?>
  <?php if (!empty($rawUsers)): ?>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th style="width: 60px;"><?php echo __('ID'); ?></th>
          <th><?php echo __('Name'); ?></th>
          <th><?php echo __('Username'); ?></th>
          <th><?php echo __('Email'); ?></th>
          <th><?php echo __('Groups'); ?></th>
          <th><?php echo __('Status'); ?></th>
          <th><?php echo __('Registered'); ?></th>
          <th class="text-end"><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rawUsers as $u): ?>
        <tr>
          <td class="text-muted"><?php echo (int) $u->id; ?></td>
          <td><?php echo htmlspecialchars($u->name ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
          <td><code class="small"><?php echo htmlspecialchars($u->username ?? '', ENT_QUOTES, 'UTF-8'); ?></code></td>
          <td><?php echo htmlspecialchars($u->email ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <?php
              $groups = $rawUserGroups[$u->id] ?? [];
              foreach ($groups as $g):
                $color = 'bg-secondary';
                if ((int) $g->group_id === 100) $color = 'bg-danger';
                elseif ((int) $g->group_id === 101) $color = 'bg-primary';
                elseif ((int) $g->group_id === 102) $color = 'bg-info';
                elseif ((int) $g->group_id === 103) $color = 'bg-warning text-dark';
            ?>
              <span class="badge <?php echo $color; ?> me-1"><?php echo htmlspecialchars(ucfirst($g->group_name ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endforeach; ?>
            <?php if (empty($groups)): ?>
              <span class="text-muted small">—</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($u->active): ?>
              <span class="badge bg-success"><?php echo __('Active'); ?></span>
            <?php else: ?>
              <span class="badge bg-warning text-dark"><?php echo __('Inactive'); ?></span>
            <?php endif; ?>
          </td>
          <td><small class="text-muted"><?php echo !empty($u->created_at) ? date('M j, Y', strtotime($u->created_at)) : '—'; ?></small></td>
          <td class="text-end">
            <div class="btn-group btn-group-sm">
              <a href="/registry/admin/users/<?php echo (int) $u->id; ?>/edit" class="btn btn-outline-primary" title="<?php echo __('Edit'); ?>">
                <i class="fas fa-pencil-alt"></i>
              </a>
              <form method="post" action="/registry/admin/users/manage" class="d-inline">
                <input type="hidden" name="user_id" value="<?php echo (int) $u->id; ?>">
                <input type="hidden" name="form_action" value="toggle_active">
                <button type="submit" class="btn <?php echo $u->active ? 'btn-outline-warning' : 'btn-outline-success'; ?>" title="<?php echo $u->active ? __('Deactivate') : __('Activate'); ?>">
                  <i class="fas <?php echo $u->active ? 'fa-ban' : 'fa-check'; ?>"></i>
                </button>
              </form>
              <form method="post" action="/registry/admin/users/manage" class="d-inline" onsubmit="return confirm('Permanently delete this user? This cannot be undone.');">
                <input type="hidden" name="user_id" value="<?php echo (int) $u->id; ?>">
                <input type="hidden" name="form_action" value="delete">
                <button type="submit" class="btn btn-outline-danger" title="<?php echo __('Delete'); ?>">
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
  <?php if ($totalPages > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <small class="text-muted">
      <?php echo __('Page %1% of %2%', ['%1%' => (int) $page, '%2%' => (int) $totalPages]); ?>
    </small>
    <nav>
      <ul class="pagination pagination-sm mb-0">
        <?php if ($page > 1): ?>
        <li class="page-item">
          <a class="page-link" href="/registry/admin/users/manage?page=<?php echo $page - 1; ?>&q=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>">&laquo;</a>
        </li>
        <?php endif; ?>
        <?php
          $start = max(1, $page - 2);
          $end = min($totalPages, $page + 2);
          for ($p = $start; $p <= $end; $p++):
        ?>
        <li class="page-item <?php echo $p === (int) $page ? 'active' : ''; ?>">
          <a class="page-link" href="/registry/admin/users/manage?page=<?php echo $p; ?>&q=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>"><?php echo $p; ?></a>
        </li>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <li class="page-item">
          <a class="page-link" href="/registry/admin/users/manage?page=<?php echo $page + 1; ?>&q=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>">&raquo;</a>
        </li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div class="card-body text-center text-muted py-4">
    <i class="fas fa-search fa-2x mb-2"></i>
    <p class="mb-0"><?php echo __('No users found.'); ?></p>
  </div>
  <?php endif; ?>
</div>

<?php end_slot(); ?>

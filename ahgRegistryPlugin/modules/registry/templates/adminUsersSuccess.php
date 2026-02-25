<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('User Approval'); ?> — Admin<?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('User Approval')],
]]); ?>

<h1 class="h3 mb-4"><?php echo __('User Approval'); ?></h1>

<!-- Pending users -->
<div class="card mb-4">
  <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
    <span><i class="fas fa-clock me-1 text-warning"></i> <?php echo __('Pending Approval'); ?></span>
    <span class="badge bg-warning text-dark"><?php echo count($pendingUsers); ?></span>
  </div>
  <?php if (!empty($pendingUsers)): ?>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Name'); ?></th>
          <th><?php echo __('Email'); ?></th>
          <th><?php echo __('Registered'); ?></th>
          <th class="text-end"><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pendingUsers as $u): ?>
        <tr>
          <td><?php echo htmlspecialchars($u->name ?? $u->username ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($u->email ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          <td><small class="text-muted"><?php echo !empty($u->created_at) ? date('M j, Y H:i', strtotime($u->created_at)) : '—'; ?></small></td>
          <td class="text-end">
            <form method="post" action="/registry/admin/users" class="d-inline">
              <input type="hidden" name="user_id" value="<?php echo (int) $u->id; ?>">
              <input type="hidden" name="form_action" value="approve">
              <button type="submit" class="btn btn-sm btn-success me-1" title="<?php echo __('Approve'); ?>">
                <i class="fas fa-check"></i> <?php echo __('Approve'); ?>
              </button>
            </form>
            <form method="post" action="/registry/admin/users" class="d-inline" onsubmit="return confirm('Reject and delete this user account?');">
              <input type="hidden" name="user_id" value="<?php echo (int) $u->id; ?>">
              <input type="hidden" name="form_action" value="reject">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Reject'); ?>">
                <i class="fas fa-times"></i> <?php echo __('Reject'); ?>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="card-body text-center text-muted py-4">
    <i class="fas fa-check-circle fa-2x mb-2"></i>
    <p class="mb-0"><?php echo __('No pending registrations.'); ?></p>
  </div>
  <?php endif; ?>
</div>

<!-- Recently active users -->
<div class="card">
  <div class="card-header fw-semibold">
    <i class="fas fa-users me-1 text-success"></i> <?php echo __('Recent Active Users'); ?>
  </div>
  <?php if (!empty($activeUsers)): ?>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('ID'); ?></th>
          <th><?php echo __('Name'); ?></th>
          <th><?php echo __('Email'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($activeUsers as $u): ?>
        <tr>
          <td><?php echo (int) $u->id; ?></td>
          <td><?php echo htmlspecialchars($u->name ?? $u->username ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($u->email ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="card-body text-center text-muted py-4">
    <p class="mb-0"><?php echo __('No active users found.'); ?></p>
  </div>
  <?php endif; ?>
</div>

<?php end_slot(); ?>

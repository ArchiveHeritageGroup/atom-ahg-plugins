<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Edit User'); ?> — Admin<?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Manage Users'), 'url' => '/registry/admin/users/manage'],
  ['label' => __('Edit User')],
]]); ?>

<?php
  $eu = sfOutputEscaper::unescape($editUser);
  $rawCurrentGroups = sfOutputEscaper::unescape($currentGroups);
  $rawAllGroups = sfOutputEscaper::unescape($allGroups);
  $rawRegistryGroups = sfOutputEscaper::unescape($registryGroups);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Edit User'); ?>: <?php echo htmlspecialchars($eu->name ?? $eu->username ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
  <a href="/registry/admin/users/manage" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Users'); ?>
  </a>
</div>

<?php if ($saved): ?>
<div class="alert alert-success alert-dismissible fade show">
  <i class="fas fa-check-circle me-1"></i> <?php echo __('User updated successfully.'); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($sf_user->hasFlash('error')): ?>
<div class="alert alert-danger alert-dismissible fade show">
  <i class="fas fa-exclamation-circle me-1"></i> <?php echo htmlspecialchars($sf_user->getFlash('error'), ENT_QUOTES, 'UTF-8'); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($sf_user->hasFlash('notice')): ?>
<div class="alert alert-success alert-dismissible fade show">
  <i class="fas fa-check-circle me-1"></i> <?php echo htmlspecialchars($sf_user->getFlash('notice'), ENT_QUOTES, 'UTF-8'); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
  <!-- User details form -->
  <div class="col-lg-8">
    <div class="card mb-4">
      <div class="card-header fw-semibold">
        <i class="fas fa-user-edit me-1"></i> <?php echo __('User Details'); ?>
      </div>
      <div class="card-body">
        <form method="post" action="/registry/admin/users/<?php echo (int) $eu->id; ?>/edit">
          <input type="hidden" name="form_action" value="save">

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Display Name'); ?></label>
              <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($eu->name ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Username'); ?></label>
              <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($eu->username ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Email'); ?></label>
              <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($eu->email ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Status'); ?></label>
              <select class="form-select" name="active">
                <option value="1" <?php echo $eu->active ? 'selected' : ''; ?>><?php echo __('Active'); ?></option>
                <option value="0" <?php echo !$eu->active ? 'selected' : ''; ?>><?php echo __('Inactive'); ?></option>
              </select>
            </div>
          </div>

          <!-- AtoM ACL Groups -->
          <div class="mb-3">
            <label class="form-label"><?php echo __('System Groups (ACL)'); ?></label>
            <div class="row">
              <?php foreach ($rawAllGroups as $g): ?>
              <div class="col-md-4 mb-2">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="groups[]" value="<?php echo (int) $g->id; ?>" id="group-<?php echo (int) $g->id; ?>"
                    <?php echo in_array((int) $g->id, $rawCurrentGroups) ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="group-<?php echo (int) $g->id; ?>">
                    <?php echo htmlspecialchars(ucfirst($g->name ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ((int) $g->id === 100): ?>
                      <span class="badge bg-danger ms-1"><?php echo __('Admin'); ?></span>
                    <?php endif; ?>
                  </label>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="text-end">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save me-1"></i> <?php echo __('Save Changes'); ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Sidebar -->
  <div class="col-lg-4">
    <!-- User info card -->
    <div class="card mb-4">
      <div class="card-header fw-semibold">
        <i class="fas fa-info-circle me-1"></i> <?php echo __('User Info'); ?>
      </div>
      <div class="card-body">
        <dl class="mb-0">
          <dt class="text-muted small"><?php echo __('User ID'); ?></dt>
          <dd><?php echo (int) $eu->id; ?></dd>
          <dt class="text-muted small"><?php echo __('Status'); ?></dt>
          <dd>
            <?php if ($eu->active): ?>
              <span class="badge bg-success"><?php echo __('Active'); ?></span>
            <?php else: ?>
              <span class="badge bg-warning text-dark"><?php echo __('Inactive'); ?></span>
            <?php endif; ?>
          </dd>
          <dt class="text-muted small"><?php echo __('Registered'); ?></dt>
          <dd><?php echo !empty($eu->created_at) ? date('F j, Y H:i', strtotime($eu->created_at)) : '—'; ?></dd>
        </dl>
      </div>
    </div>

    <!-- Registry groups -->
    <?php if (!empty($rawRegistryGroups)): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold">
        <i class="fas fa-users me-1"></i> <?php echo __('Registry Groups'); ?>
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($rawRegistryGroups as $rg): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <?php echo htmlspecialchars($rg->name ?? '', ENT_QUOTES, 'UTF-8'); ?>
          <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($rg->role ?? 'member'), ENT_QUOTES, 'UTF-8'); ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <!-- Password reset -->
    <div class="card">
      <div class="card-header fw-semibold">
        <i class="fas fa-key me-1"></i> <?php echo __('Reset Password'); ?>
      </div>
      <div class="card-body">
        <form method="post" action="/registry/admin/users/<?php echo (int) $eu->id; ?>/reset-password" onsubmit="return confirm('Reset password for this user?');">
          <div class="mb-3">
            <label class="form-label"><?php echo __('New Password'); ?></label>
            <input type="password" class="form-control" name="new_password" minlength="6" required placeholder="<?php echo __('Min. 6 characters'); ?>">
          </div>
          <button type="submit" class="btn btn-warning w-100">
            <i class="fas fa-key me-1"></i> <?php echo __('Reset Password'); ?>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php end_slot(); ?>

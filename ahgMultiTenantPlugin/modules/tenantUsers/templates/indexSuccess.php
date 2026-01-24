<?php echo get_component('default', 'updateCheck') ?>

<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">
      <?php if ($isAdmin): ?>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('tenant_admin') ?>">Tenant Administration</a></li>
            <li class="breadcrumb-item active"><?php echo esc_specialchars($repository->name ?: 'Repository ' . $repository->id) ?></li>
          </ol>
        </nav>
      <?php endif; ?>

      <h1 class="mb-4">
        <i class="fas fa-users me-2"></i>
        Users: <?php echo esc_specialchars($repository->name ?: $repository->identifier) ?>
      </h1>

      <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <?php echo $sf_user->getFlash('notice') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
          <?php echo $sf_user->getFlash('error') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <div class="row">
        <!-- Super Users (Read-only for non-admins) -->
        <div class="col-md-4 mb-4">
          <div class="card h-100">
            <div class="card-header bg-warning text-dark">
              <h5 class="mb-0">
                <i class="fas fa-star me-2"></i>
                Super Users
              </h5>
            </div>
            <div class="card-body p-0">
              <?php if (!empty($superUsers)): ?>
                <ul class="list-group list-group-flush">
                  <?php foreach ($superUsers as $user): ?>
                    <li class="list-group-item">
                      <div>
                        <strong><?php echo esc_specialchars($user->name ?: $user->username) ?></strong>
                        <br>
                        <small class="text-muted"><?php echo esc_specialchars($user->email) ?></small>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <div class="text-center text-muted py-4">
                  <p class="mb-0">No super users assigned.</p>
                </div>
              <?php endif; ?>
            </div>
            <?php if ($isAdmin): ?>
              <div class="card-footer">
                <a href="<?php echo url_for('tenant_admin_super_users', ['id' => $repository->id]) ?>" class="btn btn-sm btn-warning">
                  <i class="fas fa-cog me-1"></i> Manage Super Users
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Current Users -->
        <div class="col-md-4 mb-4">
          <div class="card h-100">
            <div class="card-header bg-info text-white">
              <h5 class="mb-0">
                <i class="fas fa-user me-2"></i>
                Assigned Users
              </h5>
            </div>
            <div class="card-body p-0">
              <?php if (!empty($users)): ?>
                <ul class="list-group list-group-flush">
                  <?php foreach ($users as $user): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <div>
                        <strong><?php echo esc_specialchars($user->name ?: $user->username) ?></strong>
                        <br>
                        <small class="text-muted"><?php echo esc_specialchars($user->email) ?></small>
                      </div>
                      <form action="<?php echo url_for('tenant_users_remove') ?>" method="post" class="d-inline">
                        <input type="hidden" name="repository_id" value="<?php echo $repository->id ?>">
                        <input type="hidden" name="user_id" value="<?php echo $user->id ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this user from the repository?')">
                          <i class="fas fa-times"></i>
                        </button>
                      </form>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <div class="text-center text-muted py-4">
                  <i class="fas fa-user-slash fa-2x mb-2"></i>
                  <p>No users assigned.</p>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Add User -->
        <div class="col-md-4 mb-4">
          <div class="card h-100">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0">
                <i class="fas fa-user-plus me-2"></i>
                Add User
              </h5>
            </div>
            <div class="card-body">
              <?php if (!empty($availableUsers)): ?>
                <form action="<?php echo url_for('tenant_users_assign') ?>" method="post">
                  <input type="hidden" name="repository_id" value="<?php echo $repository->id ?>">

                  <div class="mb-3">
                    <label class="form-label">Select User</label>
                    <select name="user_id" class="form-select" required>
                      <option value="">-- Select User --</option>
                      <?php foreach ($availableUsers as $user): ?>
                        <option value="<?php echo $user->id ?>">
                          <?php echo esc_specialchars($user->name ?: $user->username) ?>
                          (<?php echo esc_specialchars($user->email) ?>)
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-plus me-1"></i> Add User
                  </button>
                </form>
              <?php else: ?>
                <div class="text-center text-muted py-3">
                  <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                  <p>All available users are already assigned.</p>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-2">
        <?php if ($isAdmin): ?>
          <a href="<?php echo url_for('tenant_admin') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Tenant Administration
          </a>
        <?php endif; ?>
        <a href="<?php echo url_for('tenant_branding', ['id' => $repository->id]) ?>" class="btn btn-outline-secondary">
          <i class="fas fa-palette me-1"></i> Manage Branding
        </a>
      </div>
    </div>
  </div>
</div>

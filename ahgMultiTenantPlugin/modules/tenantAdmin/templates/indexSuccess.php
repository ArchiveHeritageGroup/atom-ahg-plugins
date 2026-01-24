<?php echo get_component('default', 'updateCheck') ?>

<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">
      <h1 class="mb-4">
        <i class="fas fa-building me-2"></i>
        Multi-Tenant Administration
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

      <div class="card">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">
            <i class="fas fa-archive me-2"></i>
            Repositories / Tenants
          </h5>
        </div>
        <div class="card-body p-0">
          <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Identifier</th>
                <th class="text-center">Super Users</th>
                <th class="text-center">Users</th>
                <th class="text-center">Branding</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($repositories as $repo): ?>
                <tr>
                  <td><?php echo $repo->id ?></td>
                  <td>
                    <?php if ($repo->slug): ?>
                      <a href="<?php echo url_for(['module' => 'repository', 'slug' => $repo->slug]) ?>">
                        <?php echo esc_specialchars($repo->name ?: '(unnamed)') ?>
                      </a>
                    <?php else: ?>
                      <?php echo esc_specialchars($repo->name ?: '(unnamed)') ?>
                    <?php endif; ?>
                  </td>
                  <td><?php echo esc_specialchars($repo->identifier ?: '-') ?></td>
                  <td class="text-center">
                    <span class="badge bg-warning text-dark">
                      <?php echo $repo->super_user_count ?>
                    </span>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-info">
                      <?php echo $repo->user_count ?>
                    </span>
                  </td>
                  <td class="text-center">
                    <?php if ($repo->has_branding): ?>
                      <span class="badge bg-success"><i class="fas fa-check"></i></span>
                    <?php else: ?>
                      <span class="badge bg-secondary"><i class="fas fa-minus"></i></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?php echo url_for('tenant_admin_super_users', ['id' => $repo->id]) ?>" class="btn btn-outline-warning" title="Manage Super Users">
                        <i class="fas fa-star"></i>
                      </a>
                      <a href="<?php echo url_for('tenant_users', ['id' => $repo->id]) ?>" class="btn btn-outline-info" title="Manage Users">
                        <i class="fas fa-users"></i>
                      </a>
                      <a href="<?php echo url_for('tenant_branding', ['id' => $repo->id]) ?>" class="btn btn-outline-secondary" title="Branding">
                        <i class="fas fa-palette"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if (empty($repositories)): ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">
                    No repositories found.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="mt-4">
        <div class="card bg-light">
          <div class="card-body">
            <h6><i class="fas fa-info-circle me-2"></i> About Multi-Tenancy</h6>
            <ul class="mb-0 small">
              <li><strong>Admin:</strong> Can see all repositories and assign Super Users</li>
              <li><strong>Super User:</strong> Assigned to specific repositories, can assign Users and manage branding</li>
              <li><strong>User:</strong> Assigned to specific repositories, can only see assigned repositories</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

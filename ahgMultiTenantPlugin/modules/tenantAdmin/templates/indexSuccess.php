<?php echo get_component('default', 'updateCheck') ?>

<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">
          <i class="fas fa-building me-2"></i>
          Multi-Tenant Administration
        </h1>
        <a href="<?php echo url_for('tenant_admin_create') ?>" class="btn btn-primary">
          <i class="fas fa-plus me-2"></i>Create Tenant
        </a>
      </div>

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

      <!-- Statistics Cards -->
      <div class="row mb-4">
        <div class="col-md-2">
          <div class="card bg-primary text-white">
            <div class="card-body text-center">
              <h3 class="mb-0"><?php echo $statistics['total'] ?></h3>
              <small>Total Tenants</small>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <div class="card bg-success text-white">
            <div class="card-body text-center">
              <h3 class="mb-0"><?php echo $statistics['active'] ?></h3>
              <small>Active</small>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <div class="card bg-info text-white">
            <div class="card-body text-center">
              <h3 class="mb-0"><?php echo $statistics['trial'] ?></h3>
              <small>Trial</small>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <div class="card bg-danger text-white">
            <div class="card-body text-center">
              <h3 class="mb-0"><?php echo $statistics['suspended'] ?></h3>
              <small>Suspended</small>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <div class="card bg-warning text-dark">
            <div class="card-body text-center">
              <h3 class="mb-0"><?php echo $statistics['trial_expiring_soon'] ?></h3>
              <small>Expiring Soon</small>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <div class="card bg-secondary text-white">
            <div class="card-body text-center">
              <h3 class="mb-0"><?php echo $statistics['trial_expired'] ?></h3>
              <small>Expired</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Tenants Table -->
      <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="fas fa-building me-2"></i>
            Tenants
          </h5>
          <form class="d-flex gap-2" method="get" action="<?php echo url_for('tenant_admin') ?>">
            <select name="status" class="form-select form-select-sm" style="width: 150px;">
              <option value="">All Status</option>
              <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
              <option value="trial" <?php echo $statusFilter === 'trial' ? 'selected' : '' ?>>Trial</option>
              <option value="suspended" <?php echo $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
            </select>
            <input type="text" name="search" class="form-control form-control-sm" style="width: 200px;" placeholder="Search..." value="<?php echo esc_specialchars($searchFilter) ?>">
            <button type="submit" class="btn btn-sm btn-light">
              <i class="fas fa-search"></i>
            </button>
          </form>
        </div>
        <div class="card-body p-0">
          <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Code</th>
                <th>Name</th>
                <th>Status</th>
                <th class="text-center">Users</th>
                <th>Repository</th>
                <th>Contact</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tenants as $tenant): ?>
                <tr>
                  <td><?php echo $tenant->id ?></td>
                  <td><code><?php echo esc_specialchars($tenant->code) ?></code></td>
                  <td>
                    <strong><?php echo esc_specialchars($tenant->name) ?></strong>
                    <?php if ($tenant->domain): ?>
                      <br><small class="text-muted"><?php echo esc_specialchars($tenant->domain) ?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($tenant->status === 'active'): ?>
                      <span class="badge bg-success">Active</span>
                    <?php elseif ($tenant->status === 'trial'): ?>
                      <span class="badge bg-info">Trial</span>
                      <?php if ($tenant->trialEndsAt): ?>
                        <br><small class="text-muted">Ends: <?php echo date('M j, Y', strtotime($tenant->trialEndsAt)) ?></small>
                      <?php endif; ?>
                      <?php if ($tenant->isTrialExpired()): ?>
                        <br><span class="badge bg-danger">Expired</span>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="badge bg-danger">Suspended</span>
                      <?php if ($tenant->suspendedReason): ?>
                        <br><small class="text-muted" title="<?php echo esc_specialchars($tenant->suspendedReason) ?>">
                          <?php echo esc_specialchars(substr($tenant->suspendedReason, 0, 30)) ?>...
                        </small>
                      <?php endif; ?>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-secondary"><?php echo $tenant->userCount ?></span>
                  </td>
                  <td>
                    <?php if ($tenant->repositoryId): ?>
                      <small class="text-muted">ID: <?php echo $tenant->repositoryId ?></small>
                    <?php else: ?>
                      <small class="text-muted">-</small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($tenant->contactEmail): ?>
                      <small><?php echo esc_specialchars($tenant->contactEmail) ?></small>
                    <?php else: ?>
                      <small class="text-muted">-</small>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?php echo url_for('tenant_admin_edit_tenant', ['id' => $tenant->id]) ?>" class="btn btn-outline-primary" title="Edit Tenant">
                        <i class="fas fa-edit"></i>
                      </a>
                      <?php if ($tenant->status === 'suspended'): ?>
                        <form action="<?php echo url_for('tenant_admin_activate', ['id' => $tenant->id]) ?>" method="post" class="d-inline">
                          <button type="submit" class="btn btn-outline-success" title="Activate" onclick="return confirm('Activate this tenant?')">
                            <i class="fas fa-check"></i>
                          </button>
                        </form>
                      <?php elseif ($tenant->status === 'trial'): ?>
                        <form action="<?php echo url_for('tenant_admin_activate', ['id' => $tenant->id]) ?>" method="post" class="d-inline">
                          <button type="submit" class="btn btn-outline-success" title="Activate (End Trial)" onclick="return confirm('Activate this tenant (end trial)?')">
                            <i class="fas fa-check"></i>
                          </button>
                        </form>
                        <button type="button" class="btn btn-outline-info" title="Extend Trial" data-bs-toggle="modal" data-bs-target="#extendTrialModal" data-tenant-id="<?php echo $tenant->id ?>" data-tenant-name="<?php echo esc_specialchars($tenant->name) ?>">
                          <i class="fas fa-clock"></i>
                        </button>
                      <?php endif; ?>
                      <?php if ($tenant->status !== 'suspended'): ?>
                        <button type="button" class="btn btn-outline-warning" title="Suspend" data-bs-toggle="modal" data-bs-target="#suspendModal" data-tenant-id="<?php echo $tenant->id ?>" data-tenant-name="<?php echo esc_specialchars($tenant->name) ?>">
                          <i class="fas fa-ban"></i>
                        </button>
                      <?php endif; ?>
                      <form action="<?php echo url_for('tenant_admin_delete', ['id' => $tenant->id]) ?>" method="post" class="d-inline">
                        <button type="submit" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Delete this tenant? This cannot be undone.')">
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if (empty($tenants)): ?>
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">
                    No tenants found. <a href="<?php echo url_for('tenant_admin_create') ?>">Create your first tenant</a>.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Legacy Repository View -->
      <div class="card">
        <div class="card-header bg-secondary text-white">
          <h5 class="mb-0">
            <i class="fas fa-archive me-2"></i>
            Repositories (Legacy View)
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
                <th>Tenant</th>
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
                  <td>
                    <?php if ($repo->tenant): ?>
                      <a href="<?php echo url_for('tenant_admin_edit_tenant', ['id' => $repo->tenant->id]) ?>">
                        <?php echo esc_specialchars($repo->tenant->name) ?>
                      </a>
                    <?php else: ?>
                      <small class="text-muted">Not linked</small>
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
                  <td colspan="8" class="text-center text-muted py-4">
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
              <li><strong>Tenant:</strong> An organization or customer with their own settings, users, and access controls</li>
              <li><strong>Status:</strong>
                <span class="badge bg-success">Active</span> Full access |
                <span class="badge bg-info">Trial</span> Limited time access |
                <span class="badge bg-danger">Suspended</span> No access
              </li>
              <li><strong>Roles:</strong> Owner > Super User > Editor > Contributor > Viewer</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Suspend Modal -->
<div class="modal fade" id="suspendModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="" id="suspendForm">
        <div class="modal-header">
          <h5 class="modal-title">Suspend Tenant</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to suspend <strong id="suspendTenantName"></strong>?</p>
          <div class="mb-3">
            <label for="suspendReason" class="form-label">Reason (optional)</label>
            <textarea class="form-control" id="suspendReason" name="reason" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Suspend Tenant</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Extend Trial Modal -->
<div class="modal fade" id="extendTrialModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="" id="extendTrialForm">
        <div class="modal-header">
          <h5 class="modal-title">Extend Trial</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Extend trial for <strong id="extendTrialTenantName"></strong>?</p>
          <div class="mb-3">
            <label for="extendDays" class="form-label">Additional Days</label>
            <input type="number" class="form-control" id="extendDays" name="days" value="14" min="1" max="365">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info">Extend Trial</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Handle suspend modal
  var suspendModal = document.getElementById('suspendModal');
  if (suspendModal) {
    suspendModal.addEventListener('show.bs.modal', function(event) {
      var button = event.relatedTarget;
      var tenantId = button.getAttribute('data-tenant-id');
      var tenantName = button.getAttribute('data-tenant-name');
      document.getElementById('suspendTenantName').textContent = tenantName;
      document.getElementById('suspendForm').action = '/admin/tenants/' + tenantId + '/suspend';
    });
  }

  // Handle extend trial modal
  var extendTrialModal = document.getElementById('extendTrialModal');
  if (extendTrialModal) {
    extendTrialModal.addEventListener('show.bs.modal', function(event) {
      var button = event.relatedTarget;
      var tenantId = button.getAttribute('data-tenant-id');
      var tenantName = button.getAttribute('data-tenant-name');
      document.getElementById('extendTrialTenantName').textContent = tenantName;
      document.getElementById('extendTrialForm').action = '/admin/tenants/' + tenantId + '/extend-trial';
    });
  }
});
</script>

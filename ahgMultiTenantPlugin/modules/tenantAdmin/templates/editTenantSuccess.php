<?php echo get_component('default', 'updateCheck') ?>

<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">
          <i class="fas fa-edit me-2"></i>
          Edit Tenant: <?php echo esc_specialchars($tenant->name) ?>
        </h1>
        <a href="<?php echo url_for('tenant_admin') ?>" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left me-2"></i>Back to List
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

      <div class="row">
        <!-- Left Column: Tenant Details -->
        <div class="col-lg-6">
          <form action="<?php echo url_for('tenant_admin_update_tenant', ['id' => $tenant->id]) ?>" method="post">
            <div class="card mb-4">
              <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-building me-2"></i>Tenant Information</h5>
                <span class="badge bg-<?php echo $tenant->status === 'active' ? 'success' : ($tenant->status === 'trial' ? 'info' : 'danger') ?>">
                  <?php echo ucfirst($tenant->status) ?>
                </span>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label for="name" class="form-label">Tenant Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="name" name="name" value="<?php echo esc_specialchars($tenant->name) ?>" required>
                </div>

                <div class="mb-3">
                  <label for="code" class="form-label">Code</label>
                  <input type="text" class="form-control" id="code" name="code" value="<?php echo esc_specialchars($tenant->code) ?>" pattern="[a-z0-9-]+" maxlength="50">
                  <small class="form-text text-muted">Lowercase letters, numbers, and hyphens only</small>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="domain" class="form-label">Domain</label>
                    <input type="text" class="form-control" id="domain" name="domain" value="<?php echo esc_specialchars($tenant->domain ?? '') ?>">
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="subdomain" class="form-label">Subdomain</label>
                    <input type="text" class="form-control" id="subdomain" name="subdomain" value="<?php echo esc_specialchars($tenant->subdomain ?? '') ?>">
                  </div>
                </div>

                <div class="mb-3">
                  <label for="repository_id" class="form-label">Link to Repository</label>
                  <select class="form-select" id="repository_id" name="repository_id">
                    <option value="">-- None --</option>
                    <?php foreach ($repositories as $repo): ?>
                      <option value="<?php echo $repo->id ?>" <?php echo $tenant->repositoryId == $repo->id ? 'selected' : '' ?>>
                        <?php echo esc_specialchars($repo->name ?: $repo->identifier ?: "Repository #{$repo->id}") ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="contact_name" class="form-label">Contact Name</label>
                    <input type="text" class="form-control" id="contact_name" name="contact_name" value="<?php echo esc_specialchars($tenant->contactName ?? '') ?>">
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="contact_email" class="form-label">Contact Email</label>
                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo esc_specialchars($tenant->contactEmail ?? '') ?>">
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Created</label>
                  <p class="form-control-plaintext"><?php echo $tenant->createdAt ? date('F j, Y g:i A', strtotime($tenant->createdAt)) : '-' ?></p>
                </div>

                <?php if ($tenant->status === 'trial' && $tenant->trialEndsAt): ?>
                  <div class="alert alert-info">
                    <i class="fas fa-clock me-2"></i>
                    Trial ends: <strong><?php echo date('F j, Y', strtotime($tenant->trialEndsAt)) ?></strong>
                    <?php if ($tenant->isTrialExpired()): ?>
                      <span class="badge bg-danger ms-2">Expired</span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>

                <?php if ($tenant->status === 'suspended'): ?>
                  <div class="alert alert-danger">
                    <i class="fas fa-ban me-2"></i>
                    Suspended: <?php echo $tenant->suspendedAt ? date('F j, Y', strtotime($tenant->suspendedAt)) : 'Unknown' ?>
                    <?php if ($tenant->suspendedReason): ?>
                      <br><small>Reason: <?php echo esc_specialchars($tenant->suspendedReason) ?></small>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
              <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save me-2"></i>Save Changes
                </button>
              </div>
            </div>
          </form>

          <!-- Status Actions -->
          <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
              <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Status Actions</h5>
            </div>
            <div class="card-body">
              <div class="d-flex flex-wrap gap-2">
                <?php if ($tenant->status !== 'active'): ?>
                  <form action="<?php echo url_for('tenant_admin_activate', ['id' => $tenant->id]) ?>" method="post" class="d-inline">
                    <button type="submit" class="btn btn-success" onclick="return confirm('Activate this tenant?')">
                      <i class="fas fa-check me-2"></i>Activate
                    </button>
                  </form>
                <?php endif; ?>

                <?php if ($tenant->status === 'trial'): ?>
                  <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#extendTrialModal">
                    <i class="fas fa-clock me-2"></i>Extend Trial
                  </button>
                <?php endif; ?>

                <?php if ($tenant->status !== 'suspended'): ?>
                  <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#suspendModal">
                    <i class="fas fa-ban me-2"></i>Suspend
                  </button>
                <?php endif; ?>

                <form action="<?php echo url_for('tenant_admin_delete', ['id' => $tenant->id]) ?>" method="post" class="d-inline">
                  <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this tenant? This cannot be undone.')">
                    <i class="fas fa-trash me-2"></i>Delete
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: User Management -->
        <div class="col-lg-6">
          <div class="card mb-4">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
              <h5 class="mb-0"><i class="fas fa-users me-2"></i>Tenant Users</h5>
              <span class="badge bg-light text-dark"><?php echo count($users) ?> users</span>
            </div>
            <div class="card-body p-0">
              <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users as $user): ?>
                    <tr>
                      <td>
                        <?php echo esc_specialchars($user->name ?: $user->username) ?>
                        <br><small class="text-muted"><?php echo esc_specialchars($user->email) ?></small>
                      </td>
                      <td>
                        <form action="<?php echo url_for('tenant_admin_update_user_role') ?>" method="post" class="d-inline update-role-form">
                          <input type="hidden" name="tenant_id" value="<?php echo $tenant->id ?>">
                          <input type="hidden" name="user_id" value="<?php echo $user->id ?>">
                          <select name="role" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                            <?php foreach ($roles as $roleValue => $roleLabel): ?>
                              <option value="<?php echo $roleValue ?>" <?php echo $user->role === $roleValue ? 'selected' : '' ?>>
                                <?php echo $roleLabel ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </form>
                      </td>
                      <td class="text-end">
                        <form action="<?php echo url_for('tenant_admin_remove_user') ?>" method="post" class="d-inline">
                          <input type="hidden" name="tenant_id" value="<?php echo $tenant->id ?>">
                          <input type="hidden" name="user_id" value="<?php echo $user->id ?>">
                          <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove this user from the tenant?')">
                            <i class="fas fa-times"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>

                  <?php if (empty($users)): ?>
                    <tr>
                      <td colspan="3" class="text-center text-muted py-3">No users assigned</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Add User Form -->
            <?php if (!empty($availableUsers)): ?>
              <div class="card-footer">
                <form action="<?php echo url_for('tenant_admin_assign_user') ?>" method="post" class="row g-2 align-items-end">
                  <input type="hidden" name="tenant_id" value="<?php echo $tenant->id ?>">
                  <div class="col-md-5">
                    <label for="add_user_id" class="form-label small">Add User</label>
                    <select class="form-select form-select-sm" id="add_user_id" name="user_id" required>
                      <option value="">-- Select User --</option>
                      <?php foreach ($availableUsers as $user): ?>
                        <option value="<?php echo $user->id ?>">
                          <?php echo esc_specialchars($user->name ?: $user->username) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label for="add_user_role" class="form-label small">Role</label>
                    <select class="form-select form-select-sm" id="add_user_role" name="role">
                      <?php foreach ($roles as $roleValue => $roleLabel): ?>
                        <option value="<?php echo $roleValue ?>" <?php echo $roleValue === 'editor' ? 'selected' : '' ?>>
                          <?php echo $roleLabel ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                      <i class="fas fa-plus me-1"></i>Add
                    </button>
                  </div>
                </form>
              </div>
            <?php endif; ?>
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
      <form action="<?php echo url_for('tenant_admin_suspend', ['id' => $tenant->id]) ?>" method="post">
        <div class="modal-header">
          <h5 class="modal-title">Suspend Tenant</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to suspend <strong><?php echo esc_specialchars($tenant->name) ?></strong>?</p>
          <p class="text-warning"><i class="fas fa-exclamation-triangle me-2"></i>Users will not be able to access this tenant.</p>
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
      <form action="<?php echo url_for('tenant_admin_extend_trial', ['id' => $tenant->id]) ?>" method="post">
        <div class="modal-header">
          <h5 class="modal-title">Extend Trial</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Current trial ends: <strong><?php echo $tenant->trialEndsAt ? date('F j, Y', strtotime($tenant->trialEndsAt)) : 'Not set' ?></strong></p>
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

<div class="container mt-4">
  <div class="row">
    <div class="col-12">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>">Home</a></li>
          <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'user', 'action' => 'index', 'slug' => $sf_user->user->slug]); ?>">My Profile</a></li>
          <li class="breadcrumb-item active">Manage Approvers</li>
        </ol>
      </nav>

      <?php if ($sf_user->hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <?php echo $sf_user->getFlash('success'); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
          <?php echo $sf_user->getFlash('error'); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <div class="row">
        <div class="col-lg-8">
          <div class="card mb-4">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Current Approvers</h5>
            </div>
            <div class="card-body p-0">
              <?php if (empty($approvers)): ?>
                <div class="p-4 text-center text-muted">
                  <p>No approvers configured.</p>
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-hover mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>User</th>
                        <th>Clearance</th>
                        <th>Can Approve</th>
                        <th>Email Notify</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($approvers as $approver): ?>
                        <tr>
                          <td>
                            <strong><?php echo htmlspecialchars($approver->username); ?></strong>
                            <br><small class="text-muted"><?php echo htmlspecialchars($approver->email); ?></small>
                          </td>
                          <td>
                            <span class="badge bg-info"><?php echo htmlspecialchars($approver->clearance_name ?? 'Unknown'); ?></span>
                          </td>
                          <td>
                            Level <?php echo $approver->min_classification_level; ?> - <?php echo $approver->max_classification_level; ?>
                          </td>
                          <td>
                            <?php if ($approver->email_notifications): ?>
                              <i class="fas fa-check text-success"></i>
                            <?php else: ?>
                              <i class="fas fa-times text-muted"></i>
                            <?php endif; ?>
                          </td>
                          <td>
                            <a href="<?php echo url_for('security/approvers/' . $approver->user_id . '/remove'); ?>" 
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Remove this approver?');">
                              <i class="fas fa-trash"></i>
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card">
            <div class="card-header bg-success text-white">
              <h6 class="mb-0"><i class="fas fa-plus me-2"></i>Add Approver</h6>
            </div>
            <div class="card-body">
              <form method="post" action="<?php echo url_for('security/approvers/add'); ?>">
                <div class="mb-3">
                  <label for="user_id" class="form-label">User</label>
                  <select class="form-select" id="user_id" name="user_id" required>
                    <option value="">-- Select User --</option>
                    <?php foreach ($users as $user): ?>
                      <option value="<?php echo $user->id; ?>"><?php echo htmlspecialchars($user->username); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="row mb-3">
                  <div class="col-6">
                    <label for="min_level" class="form-label">Min Level</label>
                    <select class="form-select" id="min_level" name="min_level">
                      <?php foreach ($classifications as $c): ?>
                        <option value="<?php echo $c->level; ?>"><?php echo $c->level; ?> - <?php echo esc_entities($c->name); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-6">
                    <label for="max_level" class="form-label">Max Level</label>
                    <select class="form-select" id="max_level" name="max_level">
                      <?php foreach ($classifications as $c): ?>
                        <option value="<?php echo $c->level; ?>" <?php echo $c->level == 5 ? 'selected' : ''; ?>><?php echo $c->level; ?> - <?php echo esc_entities($c->name); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="mb-3 form-check">
                  <input type="checkbox" class="form-check-input" id="email_notifications" name="email_notifications" value="1" checked>
                  <label class="form-check-label" for="email_notifications">Send email notifications</label>
                </div>
                <button type="submit" class="btn btn-success w-100">
                  <i class="fas fa-plus me-1"></i> Add Approver
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

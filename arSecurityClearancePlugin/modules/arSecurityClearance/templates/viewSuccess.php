<div class="container mt-4">
  <div class="row">
    <div class="col-lg-10 mx-auto">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>">Home</a></li>
          <li class="breadcrumb-item"><a href="<?php echo url_for('@security_clearances'); ?>">Security Clearances</a></li>
          <li class="breadcrumb-item active"><?php echo htmlspecialchars($targetUser->username); ?></li>
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
        <!-- User Info Card -->
        <div class="col-md-4 mb-4">
          <div class="card">
            <div class="card-header bg-dark text-white">
              <h5 class="mb-0"><i class="fas fa-user me-2"></i>User Profile</h5>
            </div>
            <div class="card-body text-center">
              <div class="mb-3">
                <i class="fas fa-user-circle fa-5x text-muted"></i>
              </div>
              <h4><?php echo htmlspecialchars($targetUser->username); ?></h4>
              <p class="text-muted"><?php echo htmlspecialchars($targetUser->email); ?></p>
              
              <?php if ($clearance): ?>
                <?php
                $levelClass = 'secondary';
                if ($clearance->level >= 4) $levelClass = 'danger';
                elseif ($clearance->level >= 2) $levelClass = 'warning';
                else $levelClass = 'success';
                ?>
                <span class="badge bg-<?php echo $levelClass; ?> fs-5 mb-2">
                  <?php echo htmlspecialchars($clearance->classification_name); ?>
                </span>
                <p class="small text-muted mb-0">Level <?php echo $clearance->level; ?></p>
              <?php else: ?>
                <span class="badge bg-secondary fs-5">No Clearance</span>
              <?php endif; ?>
            </div>
            <div class="card-footer">
              <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#grantModal">
                <i class="fas fa-key me-1"></i> 
                <?php echo $clearance ? 'Change Clearance' : 'Grant Clearance'; ?>
              </button>
            </div>
          </div>

          <!-- Current Clearance Details -->
          <?php if ($clearance): ?>
            <div class="card mt-3">
              <div class="card-header">
                <h6 class="mb-0">Clearance Details</h6>
              </div>
              <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                  <span>Granted:</span>
                  <strong><?php echo date('M j, Y', strtotime($clearance->granted_at)); ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                  <span>Expires:</span>
                  <strong><?php echo $clearance->expires_at ? date('M j, Y', strtotime($clearance->expires_at)) : 'Never'; ?></strong>
                </li>
                <?php if ($clearance->notes): ?>
                  <li class="list-group-item">
                    <small class="text-muted"><?php echo htmlspecialchars($clearance->notes); ?></small>
                  </li>
                <?php endif; ?>
              </ul>
            </div>
          <?php endif; ?>
        </div>

        <!-- Right Column -->
        <div class="col-md-8">
          <!-- Object Access Grants -->
          <div class="card mb-4">
            <div class="card-header bg-success text-white">
              <h5 class="mb-0"><i class="fas fa-folder-open me-2"></i>Object Access Grants</h5>
            </div>
            <div class="card-body p-0">
              <?php if (empty($accessGrants)): ?>
                <div class="p-4 text-center text-muted">
                  <p class="mb-0">No specific object access grants.</p>
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Object</th>
                        <th>Type</th>
                        <th>Scope</th>
                        <th>Access</th>
                        <th>Granted</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($accessGrants as $grant): ?>
                        <tr>
                          <td>
                            <strong><?php echo htmlspecialchars($grant->object_title ?? 'Unknown'); ?></strong>
                          </td>
                          <td>
                            <span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $grant->object_type)); ?></span>
                          </td>
                          <td>
                            <?php if ($grant->include_descendants): ?>
                              <span class="badge bg-info">+ Children</span>
                            <?php else: ?>
                              <span class="badge bg-light text-dark">Single</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <span class="badge bg-<?php echo $grant->access_level === 'edit' ? 'danger' : ($grant->access_level === 'download' ? 'warning' : 'success'); ?>">
                              <?php echo ucfirst($grant->access_level); ?>
                            </span>
                          </td>
                          <td><?php echo date('M j, Y', strtotime($grant->granted_at)); ?></td>
                          <td>
                            <a href="<?php echo url_for("security/access/{$grant->id}/revoke?user_id={$targetUser->id}"); ?>" 
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Revoke this access?');">
                              <i class="fas fa-times"></i>
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

          <!-- Clearance History -->
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0"><i class="fas fa-history me-2"></i>Clearance History</h5>
            </div>
            <div class="card-body p-0">
              <?php if (empty($history)): ?>
                <div class="p-4 text-center text-muted">
                  <p class="mb-0">No clearance history.</p>
                </div>
              <?php else: ?>
                <ul class="list-group list-group-flush">
                  <?php foreach ($history as $entry): ?>
                    <li class="list-group-item">
                      <div class="d-flex justify-content-between align-items-center">
                        <div>
                          <span class="badge bg-<?php echo $entry->action === 'granted' ? 'success' : ($entry->action === 'revoked' ? 'danger' : 'info'); ?> me-2">
                            <?php echo ucfirst($entry->action); ?>
                          </span>
                          <?php if ($entry->classification_name): ?>
                            <strong><?php echo htmlspecialchars($entry->classification_name); ?></strong>
                          <?php endif; ?>
                          <?php if ($entry->notes): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($entry->notes); ?></small>
                          <?php endif; ?>
                        </div>
                        <small class="text-muted">
                          <?php echo $entry->changed_by_name ?? 'System'; ?><br>
                          <?php echo date('M j, Y H:i', strtotime($entry->created_at)); ?>
                        </small>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Grant Modal -->
<div class="modal fade" id="grantModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?php echo url_for('@security_clearance_grant'); ?>">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="fas fa-key me-2"></i>Grant Security Clearance</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="user_id" value="<?php echo $targetUser->id; ?>">
          <p>Granting clearance to: <strong><?php echo htmlspecialchars($targetUser->username); ?></strong></p>
          
          <div class="mb-3">
            <label for="classification_id" class="form-label">Clearance Level</label>
            <select class="form-select" name="classification_id" required>
              <option value="0">-- Revoke Clearance --</option>
              <?php foreach ($classifications as $c): ?>
                <option value="<?php echo $c->id; ?>" <?php echo ($clearance && $clearance->classification_id == $c->id) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($c->name); ?> (Level <?php echo $c->level; ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="expires_at" class="form-label">Expires (optional)</label>
            <input type="date" class="form-control" name="expires_at" 
                   value="<?php echo ($clearance && $clearance->expires_at) ? date('Y-m-d', strtotime($clearance->expires_at)) : ''; ?>">
          </div>
          
          <div class="mb-3">
            <label for="notes" class="form-label">Notes</label>
            <textarea class="form-control" name="notes" rows="2" placeholder="Reason for change..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-check me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

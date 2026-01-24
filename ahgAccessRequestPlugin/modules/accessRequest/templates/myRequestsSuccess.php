<?php use_helper('Date'); ?>

<div class="container mt-4">
  <div class="row">
    <div class="col-12">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>">Home</a></li>
          <li class="breadcrumb-item active">My Access Requests</li>
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

      <!-- Current Status Card -->
      <div class="card mb-4">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>My Security Status</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <p class="mb-1"><strong>Current Clearance Level:</strong></p>
              <?php if ($currentClearance): ?>
                <span class="badge bg-<?php echo $currentClearance->level >= 4 ? 'danger' : ($currentClearance->level >= 2 ? 'warning' : 'success'); ?> fs-6">
                  <?php echo htmlspecialchars($currentClearance->classification_name); ?>
                </span>
                <small class="text-muted d-block mt-1">
                  Granted: <?php echo date('M j, Y', strtotime($currentClearance->granted_at)); ?>
                </small>
              <?php else: ?>
                <span class="badge bg-secondary fs-6">No Clearance</span>
              <?php endif; ?>
            </div>
            <div class="col-md-6 text-md-end">
              <a href="<?php echo url_for('security/request-access'); ?>" class="btn btn-primary">
                <i class="fas fa-arrow-up me-1"></i> Request Higher Clearance
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Object Access Grants -->
      <?php if (!empty($accessGrants)): ?>
        <div class="card mb-4">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-key me-2"></i>My Access Grants</h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Object</th>
                    <th>Type</th>
                    <th>Scope</th>
                    <th>Access</th>
                    <th>Granted</th>
                    <th>Expires</th>
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
                      <td>
                        <?php echo date('M j, Y', strtotime($grant->granted_at)); ?>
                        <br><small class="text-muted">by <?php echo htmlspecialchars($grant->granted_by_name ?? 'System'); ?></small>
                      </td>
                      <td>
                        <?php if ($grant->expires_at): ?>
                          <?php 
                          $expires = strtotime($grant->expires_at);
                          $isExpired = $expires < time();
                          ?>
                          <span class="<?php echo $isExpired ? 'text-danger' : ''; ?>">
                            <?php echo date('M j, Y', $expires); ?>
                          </span>
                        <?php else: ?>
                          <span class="text-muted">Never</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Request History -->
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-history me-2"></i>Request History</h5>
        </div>
        <div class="card-body p-0">
          <?php if (empty($requests)): ?>
            <div class="p-4 text-center text-muted">
              <i class="fas fa-inbox fa-3x mb-3"></i>
              <p>You haven't submitted any access requests yet.</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Type</th>
                    <th>Requested</th>
                    <th>Urgency</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($requests as $req): ?>
                    <tr>
                      <td>
                        <?php
                        $typeIcons = [
                            'clearance' => 'fa-user-shield',
                            'object' => 'fa-file-alt',
                            'repository' => 'fa-building',
                            'authority' => 'fa-user-tie'
                        ];
                        $icon = $typeIcons[$req->request_type] ?? 'fa-question';
                        ?>
                        <i class="fas <?php echo $icon; ?> me-1"></i>
                        <?php echo ucfirst($req->request_type); ?>
                      </td>
                      <td>
                        <?php if ($req->request_type === 'clearance'): ?>
                          <strong><?php echo htmlspecialchars($req->requested_classification ?? 'N/A'); ?></strong>
                          <br><small class="text-muted">From: <?php echo esc_entities($req->current_classification ?? 'None'); ?></small>
                        <?php elseif (!empty($req->scopes)): ?>
                          <?php foreach ($req->scopes as $scope): ?>
                            <strong><?php echo htmlspecialchars($scope->object_title ?? 'Unknown'); ?></strong>
                            <?php if ($scope->include_descendants): ?>
                              <span class="badge bg-info ms-1">+ children</span>
                            <?php endif; ?>
                            <br>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </td>
                      <td>
                        <span class="badge bg-<?php 
                          echo $req->urgency === 'critical' ? 'danger' : 
                               ($req->urgency === 'high' ? 'warning' : 
                               ($req->urgency === 'normal' ? 'info' : 'secondary')); 
                        ?>">
                          <?php echo ucfirst($req->urgency); ?>
                        </span>
                      </td>
                      <td>
                        <?php
                        $statusColors = [
                            'pending' => 'warning',
                            'approved' => 'success',
                            'denied' => 'danger',
                            'cancelled' => 'secondary',
                            'expired' => 'dark'
                        ];
                        ?>
                        <span class="badge bg-<?php echo $statusColors[$req->status] ?? 'secondary'; ?>">
                          <?php echo ucfirst($req->status); ?>
                        </span>
                      </td>
                      <td>
                        <?php echo date('M j, Y H:i', strtotime($req->created_at)); ?>
                      </td>
                      <td>
                        <a href="<?php echo url_for('security/request/' . $req->id); ?>" class="btn btn-sm btn-outline-primary">
                          <i class="fas fa-eye"></i>
                        </a>
                        <?php if ($req->status === 'pending'): ?>
                          <a href="<?php echo url_for('security/request/' . $req->id . '/cancel'); ?>" 
                             class="btn btn-sm btn-outline-danger"
                             onclick="return confirm('Cancel this request?');">
                            <i class="fas fa-times"></i>
                          </a>
                        <?php endif; ?>
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
  </div>
</div>

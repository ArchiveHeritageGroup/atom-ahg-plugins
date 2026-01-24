<?php use_helper('Date'); ?>

<div class="container mt-4">
  <div class="row">
    <div class="col-lg-8 mx-auto">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>">Home</a></li>
          <?php if ($isApprover): ?>
            <li class="breadcrumb-item"><a href="<?php echo url_for('security/access-requests'); ?>">Access Requests</a></li>
          <?php else: ?>
            <li class="breadcrumb-item"><a href="<?php echo url_for('security/my-requests'); ?>">My Requests</a></li>
          <?php endif; ?>
          <li class="breadcrumb-item active">Request #<?php echo $accessRequest->id; ?></li>
        </ol>
      </nav>

      <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
      <?php endif; ?>

      <?php
      $statusColors = [
          'pending' => 'warning',
          'approved' => 'success',
          'denied' => 'danger',
          'cancelled' => 'secondary',
          'expired' => 'dark'
      ];
      $statusColor = $statusColors[$accessRequest->status] ?? 'secondary';
      $typeIcons = [
          'clearance' => 'fa-user-shield',
          'object' => 'fa-file-alt',
          'repository' => 'fa-building',
          'authority' => 'fa-user-tie'
      ];
      $typeIcon = $typeIcons[$accessRequest->request_type] ?? 'fa-question';
      ?>

      <div class="card mb-4">
        <div class="card-header bg-<?php echo $statusColor; ?> <?php echo in_array($statusColor, ['warning', 'light']) ? 'text-dark' : 'text-white'; ?>">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
              <i class="fas <?php echo $typeIcon; ?> me-2"></i>
              <?php echo ucfirst($accessRequest->request_type); ?> Request #<?php echo $accessRequest->id; ?>
            </h5>
            <span class="badge bg-light text-dark"><?php echo ucfirst($accessRequest->status); ?></span>
          </div>
        </div>
        <div class="card-body">
          <!-- Requester Info -->
          <div class="row mb-3">
            <div class="col-md-6">
              <h6 class="text-muted">Requester</h6>
              <p class="mb-0">
                <strong><?php echo htmlspecialchars($accessRequest->username); ?></strong><br>
                <small class="text-muted"><?php echo htmlspecialchars($accessRequest->email); ?></small>
              </p>
            </div>
            <div class="col-md-6">
              <h6 class="text-muted">Submitted</h6>
              <p class="mb-0"><?php echo date('F j, Y \a\t H:i', strtotime($accessRequest->created_at)); ?></p>
            </div>
          </div>

          <!-- Request Details based on type -->
          <?php if ($accessRequest->request_type === 'clearance'): ?>
            <div class="row mb-3">
              <div class="col-md-6">
                <h6 class="text-muted">Current Clearance</h6>
                <span class="badge bg-secondary fs-6"><?php echo $accessRequest->current_classification ?? 'None'; ?></span>
              </div>
              <div class="col-md-6">
                <h6 class="text-muted">Requested Clearance</h6>
                <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($accessRequest->requested_classification); ?></span>
              </div>
            </div>
          <?php elseif (!empty($accessRequest->scopes)): ?>
            <div class="mb-3">
              <h6 class="text-muted">Requested Access To</h6>
              <div class="list-group">
                <?php foreach ($accessRequest->scopes as $scope): ?>
                  <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <strong><?php echo htmlspecialchars($scope->object_title ?? 'Unknown'); ?></strong>
                        <span class="badge bg-secondary ms-2"><?php echo ucfirst(str_replace('_', ' ', $scope->object_type)); ?></span>
                      </div>
                      <?php if ($scope->include_descendants): ?>
                        <span class="badge bg-info">Including all children</span>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <h6 class="text-muted">Urgency</h6>
            <span class="badge bg-<?php 
              echo $accessRequest->urgency === 'critical' ? 'danger' : 
                   ($accessRequest->urgency === 'high' ? 'warning text-dark' : 
                   ($accessRequest->urgency === 'normal' ? 'info' : 'secondary')); 
            ?> fs-6">
              <?php echo ucfirst($accessRequest->urgency); ?>
            </span>
          </div>

          <div class="mb-3">
            <h6 class="text-muted">Reason</h6>
            <p class="mb-0"><?php echo nl2br(htmlspecialchars($accessRequest->reason)); ?></p>
          </div>

          <?php if ($accessRequest->justification): ?>
            <div class="mb-3">
              <h6 class="text-muted">Business Justification</h6>
              <p class="mb-0"><?php echo nl2br(htmlspecialchars($accessRequest->justification)); ?></p>
            </div>
          <?php endif; ?>

          <?php if ($accessRequest->status !== 'pending'): ?>
            <hr>
            <div class="row">
              <div class="col-md-6">
                <h6 class="text-muted">Reviewed By</h6>
                <p class="mb-0"><?php echo htmlspecialchars($accessRequest->reviewer_name ?? 'Unknown'); ?></p>
              </div>
              <div class="col-md-6">
                <h6 class="text-muted">Reviewed At</h6>
                <p class="mb-0"><?php echo $accessRequest->reviewed_at ? date('F j, Y \a\t H:i', strtotime($accessRequest->reviewed_at)) : '-'; ?></p>
              </div>
            </div>
            <?php if ($accessRequest->review_notes): ?>
              <div class="mt-3">
                <h6 class="text-muted">Review Notes</h6>
                <div class="alert alert-<?php echo $accessRequest->status === 'approved' ? 'success' : 'danger'; ?>">
                  <?php echo nl2br(htmlspecialchars($accessRequest->review_notes)); ?>
                </div>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>

        <?php if ($canApprove && $accessRequest->status === 'pending'): ?>
          <div class="card-footer">
            <div class="row">
              <div class="col-md-6 mb-3 mb-md-0">
                <form method="post" action="<?php echo url_for('@access_request_approve?id=' . $accessRequest->id); ?>">
                  <div class="input-group">
                    <input type="text" name="notes" class="form-control" placeholder="Approval notes (optional)">
                    <input type="date" name="expires_at" class="form-control" title="Expiration date">
                    <button type="submit" class="btn btn-success" onclick="return confirm('Approve this request?');">
                      <i class="fas fa-check me-1"></i> Approve
                    </button>
                  </div>
                </form>
              </div>
              <div class="col-md-6">
                <form method="post" action="<?php echo url_for('@access_request_deny?id=' . $accessRequest->id); ?>">
                  <div class="input-group">
                    <input type="text" name="notes" class="form-control" placeholder="Denial reason (required)" required>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Deny this request?');">
                      <i class="fas fa-times me-1"></i> Deny
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Audit Log -->
      <?php if (!empty($log)): ?>
        <div class="card">
          <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-history me-2"></i>Activity Log</h6>
          </div>
          <div class="card-body p-0">
            <ul class="list-group list-group-flush">
              <?php foreach ($log as $entry): ?>
                <li class="list-group-item">
                  <div class="d-flex justify-content-between">
                    <div>
                      <span class="badge bg-<?php 
                        echo $entry->action === 'approved' ? 'success' : 
                             ($entry->action === 'denied' ? 'danger' : 
                             ($entry->action === 'created' ? 'primary' : 'secondary')); 
                      ?> me-2"><?php echo ucfirst($entry->action); ?></span>
                      <?php if ($entry->details): ?>
                        <span class="text-muted"><?php echo htmlspecialchars($entry->details); ?></span>
                      <?php endif; ?>
                    </div>
                    <small class="text-muted">
                      <?php echo esc_entities($entry->actor_name ?? 'System'); ?> - 
                      <?php echo date('M j, Y H:i', strtotime($entry->created_at)); ?>
                    </small>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

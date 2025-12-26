<?php use_helper('Date'); ?>

<div class="container mt-4">
  <div class="row">
    <div class="col-lg-8 mx-auto">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>">Home</a></li>
          <li class="breadcrumb-item"><a href="<?php echo url_for('security/my-requests'); ?>">My Requests</a></li>
          <li class="breadcrumb-item active">Request Access</li>
        </ol>
      </nav>

      <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0">
            <i class="fas fa-lock me-2"></i>
            Request Access to <?php echo ucfirst(str_replace('_', ' ', $objectType)); ?>
          </h4>
        </div>
        <div class="card-body">
          <?php if ($hasAccess): ?>
            <div class="alert alert-success">
              <i class="fas fa-check-circle me-2"></i>
              You already have access to this <?php echo str_replace('_', ' ', $objectType); ?>.
            </div>
          <?php elseif ($hasPendingRequest): ?>
            <div class="alert alert-warning">
              <i class="fas fa-clock me-2"></i>
              You already have a pending request for this <?php echo str_replace('_', ' ', $objectType); ?>.
              Please wait for it to be reviewed.
            </div>
          <?php else: ?>
            <!-- Object Details -->
            <div class="card bg-light mb-4">
              <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($objectTitle ?? 'Unknown'); ?></h5>
                
                <?php if (!empty($objectPath)): ?>
                  <p class="text-muted mb-2">
                    <small>
                      <i class="fas fa-sitemap me-1"></i>
                      <?php 
                      $pathArray = is_array($objectPath) ? $objectPath : iterator_to_array($objectPath); $pathArray = [];
                      if ($objectPath) {
                          foreach ($objectPath as $p) {
                              $pathArray[] = htmlspecialchars($p['title'] ?? 'Unknown');
                          }
                      }
                      echo implode(' → ', $pathArray);
                      ?>
                    </small>
                  </p>
                <?php endif; ?>
                
                <p class="mb-0">
                  <span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $objectType)); ?></span>
                  <?php if ($descendantCount > 0): ?>
                    <span class="badge bg-info"><?php echo $descendantCount; ?> child records</span>
                  <?php endif; ?>
                </p>
              </div>
            </div>

            <form method="post" action="<?php echo url_for('security/request-object/create'); ?>">
              <input type="hidden" name="object_type" value="<?php echo htmlspecialchars($objectType); ?>">
              <input type="hidden" name="object_id" value="<?php echo $objectId; ?>">

              <?php if ($descendantCount > 0): ?>
                <div class="mb-3">
                  <label class="form-label">Access Scope</label>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="include_descendants" id="scope_single" value="0" checked>
                    <label class="form-check-label" for="scope_single">
                      <strong>This record only</strong>
                      <br><small class="text-muted">Access only to "<?php echo htmlspecialchars($objectTitle ?? 'this record'); ?>"</small>
                    </label>
                  </div>
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="radio" name="include_descendants" id="scope_children" value="1">
                    <label class="form-check-label" for="scope_children">
                      <strong>Include all children</strong>
                      <br><small class="text-muted">Access to this record and all <?php echo $descendantCount; ?> child records</small>
                    </label>
                  </div>
                </div>
              <?php else: ?>
                <input type="hidden" name="include_descendants" value="0">
              <?php endif; ?>

              <div class="mb-3">
                <label for="access_level" class="form-label">Access Level Needed</label>
                <select class="form-select" id="access_level" name="access_level">
                  <option value="view">View - Read-only access</option>
                  <option value="download">Download - View and download files</option>
                  <option value="edit">Edit - Full access including modifications</option>
                </select>
              </div>

              <div class="mb-3">
                <label for="urgency" class="form-label">Urgency</label>
                <select class="form-select" id="urgency" name="urgency">
                  <option value="low">Low - No rush</option>
                  <option value="normal" selected>Normal - Standard processing</option>
                  <option value="high">High - Needed soon</option>
                  <option value="critical">Critical - Urgent business need</option>
                </select>
              </div>

              <div class="mb-3">
                <label for="reason" class="form-label">Reason for Request <span class="text-danger">*</span></label>
                <textarea class="form-control" id="reason" name="reason" rows="3" required
                          placeholder="Explain why you need access to this record..."></textarea>
              </div>

              <div class="mb-3">
                <label for="justification" class="form-label">Additional Justification</label>
                <textarea class="form-control" id="justification" name="justification" rows="3"
                          placeholder="Any additional context about your role, project, or research needs..."></textarea>
              </div>

              <div class="d-flex justify-content-between">
                <a href="javascript:history.back()" class="btn btn-secondary">
                  <i class="fas fa-arrow-left me-1"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-paper-plane me-1"></i> Submit Request
                </button>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

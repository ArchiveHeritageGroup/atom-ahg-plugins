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

      <div class="card">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0"><i class="fas fa-key me-2"></i>Request Security Clearance</h4>
        </div>
        <div class="card-body">
          <?php if ($sf_user->hasFlash('error')): ?>
            <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
          <?php endif; ?>

          <?php if ($pendingRequest): ?>
            <div class="alert alert-warning">
              <i class="fas fa-clock me-2"></i>
              You already have a pending request for <strong><?php echo $pendingRequest->requested_classification ?? 'elevated'; ?></strong> clearance.
              Please wait for it to be reviewed before submitting a new request.
            </div>
          <?php else: ?>
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              <strong>Current Clearance:</strong> 
              <?php echo $currentClearance ? htmlspecialchars($currentClearance->classification_name) : 'None'; ?>
            </div>

            <form method="post" action="<?php echo url_for('security/request-access/create'); ?>">
              <div class="mb-3">
                <label for="classification_id" class="form-label">Requested Clearance Level <span class="text-danger">*</span></label>
                <select class="form-select" id="classification_id" name="classification_id" required>
                  <option value="">-- Select Level --</option>
                  <?php foreach ($classifications as $c): ?>
                    <?php 
                    $currentLevel = $currentClearance ? $currentClearance->level : -1;
                    if ($c->level > $currentLevel): 
                    ?>
                      <option value="<?php echo $c->id; ?>"><?php echo htmlspecialchars($c->name); ?> (Level <?php echo $c->level; ?>)</option>
                    <?php endif; ?>
                  <?php endforeach; ?>
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
                          placeholder="Briefly explain why you need this access level..."></textarea>
              </div>

              <div class="mb-3">
                <label for="justification" class="form-label">Business Justification</label>
                <textarea class="form-control" id="justification" name="justification" rows="4"
                          placeholder="Provide additional details about your role, projects, or responsibilities that justify this access..."></textarea>
              </div>

              <div class="d-flex justify-content-between">
                <a href="<?php echo url_for('security/my-requests'); ?>" class="btn btn-secondary">
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

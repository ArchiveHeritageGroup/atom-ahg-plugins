<?php use_helper('Url'); ?>

<div class="container-fluid py-4">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo url_for('workflow/dashboard'); ?>">Workflow</a></li>
      <li class="breadcrumb-item active">Publish Readiness</li>
    </ol>
  </nav>

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 mb-1"><i class="fas fa-clipboard-check me-2"></i>Publish Readiness Check</h1>
      <?php if (!empty($object)): ?>
        <p class="text-muted mb-0">
          <?php echo htmlspecialchars($object->title ?? 'Untitled'); ?>
          <?php if (!empty($object->identifier)): ?>
            <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($object->identifier); ?></span>
          <?php endif; ?>
        </p>
      <?php endif; ?>
    </div>
    <div>
      <?php if ($canPublish): ?>
        <a href="<?php echo url_for("workflow/publish-execute/{$objectId}"); ?>" class="btn btn-success"
           onclick="return confirm('Are you sure you want to publish this record?')">
          <i class="fas fa-globe me-1"></i>Publish Now
        </a>
      <?php endif; ?>
      <a href="<?php echo url_for("workflow/publish-simulate/{$objectId}"); ?>" class="btn btn-outline-primary">
        <i class="fas fa-eye me-1"></i>Preview Public View
      </a>
    </div>
  </div>

  <?php if ($canPublish): ?>
    <div class="alert alert-success d-flex align-items-center">
      <i class="fas fa-check-circle fa-2x me-3"></i>
      <div>
        <strong>Ready to Publish</strong><br>
        All blocker checks have passed. This record can be published.
      </div>
    </div>
  <?php else: ?>
    <div class="alert alert-danger d-flex align-items-center">
      <i class="fas fa-exclamation-circle fa-2x me-3"></i>
      <div>
        <strong>Not Ready to Publish</strong><br>
        One or more blocker checks have failed. Please resolve the issues below before publishing.
      </div>
    </div>
  <?php endif; ?>

  <!-- Gate Results -->
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="card-title mb-0">Gate Check Results</h5>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 50px">Status</th>
            <th>Rule</th>
            <th>Type</th>
            <th>Severity</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($results)): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">No gate rules configured</td></tr>
          <?php else: ?>
            <?php foreach ($results as $r): ?>
              <tr>
                <td class="text-center">
                  <?php if ($r['status'] === 'passed'): ?>
                    <i class="fas fa-check-circle text-success" title="Passed"></i>
                  <?php elseif ($r['status'] === 'failed'): ?>
                    <i class="fas fa-times-circle text-danger" title="Failed"></i>
                  <?php elseif ($r['status'] === 'warning'): ?>
                    <i class="fas fa-exclamation-triangle text-warning" title="Warning"></i>
                  <?php else: ?>
                    <i class="fas fa-minus-circle text-muted" title="Skipped"></i>
                  <?php endif; ?>
                </td>
                <td>
                  <strong><?php echo htmlspecialchars($r['rule_name']); ?></strong>
                  <?php if ($r['status'] === 'failed' && $r['severity'] === 'blocker'): ?>
                    <br><small class="text-danger"><?php echo htmlspecialchars($r['error_message']); ?></small>
                  <?php endif; ?>
                </td>
                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($r['rule_type']); ?></span></td>
                <td>
                  <?php if ($r['severity'] === 'blocker'): ?>
                    <span class="badge bg-danger">Blocker</span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark">Warning</span>
                  <?php endif; ?>
                </td>
                <td><small class="text-muted"><?php echo htmlspecialchars($r['details'] ?? ''); ?></small></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Summary Stats -->
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card text-center border-success">
        <div class="card-body py-3">
          <h3 class="text-success mb-0"><?php echo $passedCount; ?></h3>
          <small class="text-muted">Passed</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center border-danger">
        <div class="card-body py-3">
          <h3 class="text-danger mb-0"><?php echo $failedCount; ?></h3>
          <small class="text-muted">Failed</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center border-warning">
        <div class="card-body py-3">
          <h3 class="text-warning mb-0"><?php echo $warningCount; ?></h3>
          <small class="text-muted">Warnings</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center border-secondary">
        <div class="card-body py-3">
          <h3 class="text-muted mb-0"><?php echo $skippedCount; ?></h3>
          <small class="text-muted">Skipped</small>
        </div>
      </div>
    </div>
  </div>

  <?php if ($isAdmin && !$canPublish): ?>
    <div class="card border-warning">
      <div class="card-body">
        <h5 class="card-title text-warning"><i class="fas fa-user-shield me-2"></i>Administrator Override</h5>
        <p class="text-muted">As an administrator, you can force-publish this record despite blocker failures. This will be logged in the audit trail.</p>
        <a href="<?php echo url_for("workflow/publish-execute/{$objectId}"); ?>?force=1"
           class="btn btn-warning"
           onclick="return confirm('WARNING: Force-publishing bypasses gate checks. This action will be logged. Continue?')">
          <i class="fas fa-exclamation-triangle me-1"></i>Force Publish (Override Gates)
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>

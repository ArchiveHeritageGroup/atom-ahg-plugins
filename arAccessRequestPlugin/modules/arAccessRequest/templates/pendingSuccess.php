<?php use_helper('Date'); ?>

<div class="container mt-4">
  <div class="row">
    <div class="col-12">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>">Home</a></li>
          <li class="breadcrumb-item active">Access Requests</li>
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

      <!-- Stats Cards -->
      <div class="row mb-4">
        <div class="col-md-3">
          <div class="card bg-warning text-dark">
            <div class="card-body text-center">
              <h2 class="mb-0"><?php echo $stats['pending']; ?></h2>
              <small>Pending</small>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card bg-success text-white">
            <div class="card-body text-center">
              <h2 class="mb-0"><?php echo $stats['approved_today']; ?></h2>
              <small>Approved Today</small>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card bg-danger text-white">
            <div class="card-body text-center">
              <h2 class="mb-0"><?php echo $stats['denied_today']; ?></h2>
              <small>Denied Today</small>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card bg-info text-white">
            <div class="card-body text-center">
              <h2 class="mb-0"><?php echo $stats['total_this_month']; ?></h2>
              <small>This Month</small>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Pending Access Requests</h5>
        </div>
        <div class="card-body p-0">
          <?php if (empty($requests)): ?>
            <div class="p-4 text-center text-muted">
              <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
              <p>No pending requests. All caught up!</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>User</th>
                    <th>Current → Requested</th>
                    <th>Urgency</th>
                    <th>Reason</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($requests as $req): ?>
                    <tr class="<?php echo $req->urgency === 'critical' ? 'table-danger' : ($req->urgency === 'high' ? 'table-warning' : ''); ?>">
                      <td>
                        <strong><?php echo htmlspecialchars($req->username); ?></strong>
                        <br><small class="text-muted"><?php echo htmlspecialchars($req->email); ?></small>
                      </td>
                      <td>
                        <span class="badge bg-secondary"><?php echo $req->current_classification ?? 'None'; ?></span>
                        <i class="fas fa-arrow-right mx-1"></i>
                        <span class="badge bg-primary"><?php echo htmlspecialchars($req->requested_classification); ?></span>
                      </td>
                      <td>
                        <span class="badge bg-<?php 
                          echo $req->urgency === 'critical' ? 'danger' : 
                               ($req->urgency === 'high' ? 'warning text-dark' : 
                               ($req->urgency === 'normal' ? 'info' : 'secondary')); 
                        ?>">
                          <?php echo ucfirst($req->urgency); ?>
                        </span>
                      </td>
                      <td>
                        <span title="<?php echo htmlspecialchars($req->reason); ?>">
                          <?php echo htmlspecialchars(substr($req->reason, 0, 50)); ?>
                          <?php if (strlen($req->reason) > 50): ?>...<?php endif; ?>
                        </span>
                      </td>
                      <td>
                        <?php echo date('M j, Y', strtotime($req->created_at)); ?>
                        <br><small class="text-muted"><?php echo date('H:i', strtotime($req->created_at)); ?></small>
                      </td>
                      <td>
                        <a href="<?php echo url_for('@security_request_view?id=' . $req->id); ?>" class="btn btn-sm btn-primary">
                          <i class="fas fa-eye me-1"></i> Review
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
  </div>
</div>

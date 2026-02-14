<?php $n = sfConfig::get('csp_nonce', ''); $nattr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<div class="container-fluid py-3">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1"><i class="bi bi-cloud-upload me-2"></i>Researcher Workspace</h4>
      <p class="text-muted mb-0">Upload collections, describe records, and submit for archivist review</p>
    </div>
    <div>
      <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'importExchange']) ?>" class="btn btn-outline-primary me-2">
        <i class="bi bi-file-earmark-arrow-up me-1"></i>Import Exchange
      </a>
      <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'newSubmission']) ?>" class="btn btn-success">
        <i class="bi bi-plus-lg me-1"></i>New Submission
      </a>
    </div>
  </div>

  <!-- Flash messages -->
  <?php if ($sf_user->hasFlash('notice')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('notice') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif ?>
  <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif ?>

  <!-- Stats Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3 col-xl-2">
      <div class="card text-center border-primary h-100">
        <div class="card-body py-3">
          <h3 class="mb-0 text-primary"><?php echo $stats['total'] ?></h3>
          <small class="text-muted">Total</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
      <div class="card text-center border-secondary h-100">
        <div class="card-body py-3">
          <h3 class="mb-0 text-secondary"><?php echo $stats['draft'] ?></h3>
          <small class="text-muted">Draft</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
      <div class="card text-center border-warning h-100">
        <div class="card-body py-3">
          <h3 class="mb-0 text-warning"><?php echo $stats['pending'] ?></h3>
          <small class="text-muted">Pending Review</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
      <div class="card text-center border-success h-100">
        <div class="card-body py-3">
          <h3 class="mb-0 text-success"><?php echo $stats['approved'] ?></h3>
          <small class="text-muted">Approved</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
      <div class="card text-center border-info h-100">
        <div class="card-body py-3">
          <h3 class="mb-0 text-info"><?php echo $stats['published'] ?></h3>
          <small class="text-muted">Published</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
      <div class="card text-center border-danger h-100">
        <div class="card-body py-3">
          <h3 class="mb-0 text-danger"><?php echo $stats['returned'] + $stats['rejected'] ?></h3>
          <small class="text-muted">Returned / Rejected</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Submissions -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Submissions</h6>
      <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'submissions']) ?>" class="btn btn-sm btn-outline-primary">
        View All
      </a>
    </div>
    <div class="card-body p-0">
      <?php if (empty($recent)): ?>
        <div class="text-center text-muted py-5">
          <i class="bi bi-inbox" style="font-size: 2rem;"></i>
          <p class="mt-2 mb-0">No submissions yet. Create your first submission to get started.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Title</th>
                <th>Source</th>
                <th>Items</th>
                <th>Files</th>
                <th>Status</th>
                <th>Updated</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent as $sub): ?>
              <tr class="cursor-pointer" onclick="window.location='<?php echo url_for(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $sub->id]) ?>'">
                <td>
                  <strong><?php echo htmlspecialchars($sub->title) ?></strong>
                  <?php if ($isAdmin && !empty($sub->user_name)): ?>
                    <br><small class="text-muted"><?php echo htmlspecialchars($sub->user_name) ?></small>
                  <?php endif ?>
                </td>
                <td>
                  <?php if ($sub->source_type === 'offline'): ?>
                    <span class="badge bg-secondary"><i class="bi bi-hdd me-1"></i>Offline</span>
                  <?php else: ?>
                    <span class="badge bg-primary"><i class="bi bi-cloud me-1"></i>Online</span>
                  <?php endif ?>
                </td>
                <td><?php echo $sub->total_items ?></td>
                <td><?php echo $sub->total_files ?></td>
                <td>
                  <?php
                    $statusColors = [
                      'draft' => 'secondary', 'submitted' => 'warning', 'under_review' => 'info',
                      'approved' => 'success', 'published' => 'primary', 'returned' => 'danger', 'rejected' => 'dark',
                    ];
                    $color = $statusColors[$sub->status] ?? 'secondary';
                  ?>
                  <span class="badge bg-<?php echo $color ?>"><?php echo ucfirst(str_replace('_', ' ', $sub->status)) ?></span>
                </td>
                <td><small class="text-muted"><?php echo date('d M Y', strtotime($sub->updated_at)) ?></small></td>
              </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      <?php endif ?>
    </div>
  </div>

</div>

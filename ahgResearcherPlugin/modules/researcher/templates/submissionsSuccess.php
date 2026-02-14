<?php $n = sfConfig::get('csp_nonce', ''); $nattr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<div class="container-fluid py-3">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1"><i class="bi bi-collection me-2"></i>My Submissions</h4>
      <p class="text-muted mb-0">All your collection submissions</p>
    </div>
    <div>
      <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'dashboard']) ?>" class="btn btn-outline-secondary me-2">
        <i class="bi bi-speedometer2 me-1"></i>Dashboard
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

  <!-- Status Filter -->
  <div class="mb-3">
    <?php
      $statuses = ['', 'draft', 'submitted', 'under_review', 'approved', 'published', 'returned', 'rejected'];
      $labels = ['All', 'Draft', 'Submitted', 'Under Review', 'Approved', 'Published', 'Returned', 'Rejected'];
    ?>
    <div class="btn-group" role="group">
      <?php foreach ($statuses as $i => $s): ?>
        <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'submissions', 'status' => $s]) ?>"
           class="btn btn-sm <?php echo $currentStatus === $s ? 'btn-primary' : 'btn-outline-primary' ?>">
          <?php echo $labels[$i] ?>
        </a>
      <?php endforeach ?>
    </div>
  </div>

  <!-- Submissions Table -->
  <div class="card">
    <div class="card-body p-0">
      <?php if (empty($submissions)): ?>
        <div class="text-center text-muted py-5">
          <i class="bi bi-inbox" style="font-size: 2rem;"></i>
          <p class="mt-2 mb-0">No submissions found.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Title</th>
                <?php if ($isAdmin): ?><th>Researcher</th><?php endif ?>
                <th>Source</th>
                <th>Items</th>
                <th>Files</th>
                <th>Status</th>
                <th>Created</th>
                <th>Updated</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($submissions as $sub): ?>
              <tr class="cursor-pointer" onclick="window.location='<?php echo url_for(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $sub->id]) ?>'">
                <td class="text-muted"><?php echo $sub->id ?></td>
                <td><strong><?php echo htmlspecialchars($sub->title) ?></strong></td>
                <?php if ($isAdmin): ?>
                  <td><small><?php echo htmlspecialchars($sub->user_name ?? 'Unknown') ?></small></td>
                <?php endif ?>
                <td>
                  <?php if ($sub->source_type === 'offline'): ?>
                    <span class="badge bg-secondary">Offline</span>
                  <?php else: ?>
                    <span class="badge bg-primary">Online</span>
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
                <td><small class="text-muted"><?php echo date('d M Y', strtotime($sub->created_at)) ?></small></td>
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

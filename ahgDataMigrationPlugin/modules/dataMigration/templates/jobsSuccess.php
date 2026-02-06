<?php use_helper('Date') ?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>
          <i class="bi bi-list-task me-2"></i>
          Migration Jobs
        </h4>
        <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'index']) ?>" class="btn btn-primary">
          <i class="bi bi-plus-circle me-1"></i> New Import
        </a>
      </div>

      <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <?php echo $sf_user->getFlash('notice') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif ?>

      <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
          <?php echo $sf_user->getFlash('error') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif ?>

      <!-- Jobs Table -->
      <div class="card">
        <div class="card-body p-0">
          <?php if (count($jobs) > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>File</th>
                  <th>Type</th>
                  <th>Mapping</th>
                  <th>Status</th>
                  <th>Progress</th>
                  <th>Records</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($jobs as $job): ?>
                <tr>
                  <td><?php echo $job->id ?></td>
                  <td>
                    <span title="<?php echo htmlspecialchars($job->name) ?>">
                      <?php echo htmlspecialchars(strlen($job->name) > 30 ? substr($job->name, 0, 30) . '...' : $job->name) ?>
                    </span>
                    <br><small class="text-muted"><?php echo strtoupper($job->source_format) ?></small>
                  </td>
                  <td><?php echo ucfirst($job->target_type) ?></td>
                  <td>
                    <?php 
                      if ($job->mapping_id && isset($mappings[$job->mapping_id])) {
                        echo htmlspecialchars($mappings[$job->mapping_id]);
                      } else {
                        echo '<span class="text-muted">Custom</span>';
                      }
                    ?>
                  </td>
                  <td>
                    <span class="badge bg-<?php 
                      echo match($job->status) {
                        'completed' => 'success',
                        'running' => 'primary',
                        'failed' => 'danger',
                        'cancelled' => 'warning',
                        default => 'secondary'
                      };
                    ?>"><?php echo ucfirst($job->status) ?></span>
                  </td>
                  <td style="min-width: 120px;">
                    <?php 
                      $percent = $job->total_records > 0 ? round(($job->processed_records / $job->total_records) * 100) : 0;
                    ?>
                    <div class="progress" style="height: 20px;">
                      <div class="progress-bar <?php echo $job->status === 'running' ? 'progress-bar-striped progress-bar-animated' : '' ?>" 
                           style="width: <?php echo $percent ?>%">
                        <?php echo $percent ?>%
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="text-success"><?php echo number_format($job->imported_records) ?></span> /
                    <span class="text-info"><?php echo number_format($job->total_records) ?></span>
                    <?php if ($job->error_count > 0): ?>
                      <br><small class="text-danger"><?php echo $job->error_count ?> errors</small>
                    <?php endif ?>
                  </td>
                  <td>
                    <small><?php echo $job->created_at ?></small>
                  </td>
                  <td>
                    <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'jobStatus', 'id' => $job->id]) ?>" 
                       class="btn btn-sm btn-outline-primary" title="View Details">
                      <i class="bi bi-eye"></i>
                    </a>
                    <?php if (in_array($job->status, ['pending', 'running'])): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger" 
                            onclick="cancelJob(<?php echo $job->id ?>)" title="Cancel">
                      <i class="bi bi-x"></i>
                    </button>
                    <?php endif ?>
                  </td>
                </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-2">No migration jobs yet</p>
            <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'index']) ?>" class="btn btn-primary">
              <i class="bi bi-plus-circle me-1"></i> Start New Import
            </a>
          </div>
          <?php endif ?>
        </div>
      </div>

    </div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function cancelJob(jobId) {
  if (!confirm('Are you sure you want to cancel this job?')) return;

  fetch('<?php echo url_for(['module' => 'dataMigration', 'action' => 'cancelJob']) ?>?id=' + jobId, {method: 'POST'})
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        location.reload();
      } else {
        alert('Failed to cancel: ' + (data.error || 'Unknown error'));
      }
    });
}
</script>

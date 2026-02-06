<?php use_helper('Date') ?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>
          <i class="bi bi-gear-wide-connected me-2"></i>
          Migration Job #<?php echo $job->id ?>
        </h4>
        <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'jobs']) ?>" class="btn btn-outline-secondary">
          <i class="bi bi-list me-1"></i> All Jobs
        </a>
      </div>

      <!-- Status Card -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="bi bi-info-circle me-2"></i>Job Status</span>
          <span id="statusBadge" class="badge bg-<?php 
            echo match($job->status) {
              'completed' => 'success',
              'running' => 'primary',
              'failed' => 'danger',
              'cancelled' => 'warning',
              default => 'secondary'
            };
          ?>"><?php echo ucfirst($job->status) ?></span>
        </div>
        <div class="card-body">
          
          <!-- Job Info -->
          <div class="row mb-3">
            <div class="col-md-6">
              <strong>File:</strong> <?php echo htmlspecialchars($job->name) ?>
            </div>
            <div class="col-md-6">
              <strong>Format:</strong> <?php echo strtoupper($job->source_format) ?>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <strong>Target:</strong> <?php echo ucfirst($job->target_type) ?>
            </div>
            <div class="col-md-6">
              <strong>Mapping:</strong> <?php echo $mappingName ? htmlspecialchars($mappingName) : 'Custom' ?>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <strong>Created:</strong> <?php echo $job->created_at ?>
            </div>
            <div class="col-md-6">
              <strong>Started:</strong> <span id="startedAt"><?php echo $job->started_at ?: 'Pending...' ?></span>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span id="progressMessage"><?php echo htmlspecialchars($job->progress_message ?: 'Waiting...') ?></span>
              <span id="progressPercent"><?php 
                $percent = $job->total_records > 0 ? round(($job->processed_records / $job->total_records) * 100) : 0;
                echo $percent;
              ?>%</span>
            </div>
            <div class="progress" style="height: 25px;">
              <div id="progressBar" class="progress-bar progress-bar-striped <?php echo $job->status === 'running' ? 'progress-bar-animated' : '' ?>" 
                   role="progressbar" style="width: <?php echo $percent ?>%">
              </div>
            </div>
          </div>

          <!-- Stats -->
          <div class="row text-center" id="statsRow">
            <div class="col">
              <div class="border rounded p-2">
                <h5 id="totalRecords" class="mb-0 text-primary"><?php echo number_format($job->total_records) ?></h5>
                <small class="text-muted">Total</small>
              </div>
            </div>
            <div class="col">
              <div class="border rounded p-2">
                <h5 id="processedRecords" class="mb-0 text-info"><?php echo number_format($job->processed_records) ?></h5>
                <small class="text-muted">Processed</small>
              </div>
            </div>
            <div class="col">
              <div class="border rounded p-2">
                <h5 id="importedRecords" class="mb-0 text-success"><?php echo number_format($job->imported_records) ?></h5>
                <small class="text-muted">Imported</small>
              </div>
            </div>
            <div class="col">
              <div class="border rounded p-2">
                <h5 id="updatedRecords" class="mb-0 text-warning"><?php echo number_format($job->updated_records) ?></h5>
                <small class="text-muted">Updated</small>
              </div>
            </div>
            <div class="col">
              <div class="border rounded p-2">
                <h5 id="errorCount" class="mb-0 text-danger"><?php echo number_format($job->error_count) ?></h5>
                <small class="text-muted">Errors</small>
              </div>
            </div>
          </div>

        </div>
        <div class="card-footer">
          <?php if (in_array($job->status, ['pending', 'running'])): ?>
            <button type="button" class="btn btn-danger" id="cancelBtn" onclick="cancelJob(<?php echo $job->id ?>)">
              <i class="bi bi-x-circle me-1"></i> Cancel Job
            </button>
          <?php endif ?>
          
          <?php if ($job->status === 'completed'): ?>
            <span class="text-success"><i class="bi bi-check-circle me-1"></i> Completed at <?php echo $job->completed_at ?></span>
          <?php elseif ($job->status === 'failed'): ?>
            <span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i> Failed at <?php echo $job->completed_at ?></span>
          <?php elseif ($job->status === 'cancelled'): ?>
            <span class="text-warning"><i class="bi bi-dash-circle me-1"></i> Cancelled at <?php echo $job->completed_at ?></span>
          <?php endif ?>
        </div>
      </div>

      <!-- Errors Card -->
      <?php if (!empty($errors)): ?>
      <div class="card border-danger">
        <div class="card-header bg-danger text-white">
          <i class="bi bi-exclamation-triangle me-2"></i>
          Errors (<?php echo count($errors) ?>)
        </div>
        <div class="card-body" style="max-height: 300px; overflow-y: auto;">
          <ul class="list-unstyled mb-0">
            <?php foreach (array_slice($errors, 0, 50) as $error): ?>
              <li class="text-danger small mb-1">
                <?php if (isset($error['row'])): ?>
                  <strong>Row <?php echo $error['row'] ?>:</strong>
                <?php endif ?>
                <?php echo htmlspecialchars($error['message'] ?? 'Unknown error') ?>
              </li>
            <?php endforeach ?>
            <?php if (count($errors) > 50): ?>
              <li class="text-muted">... and <?php echo count($errors) - 50 ?> more errors</li>
            <?php endif ?>
          </ul>
        </div>
      </div>
      <?php endif ?>

      <!-- Actions -->
      <div class="mt-4">
        <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'index']) ?>" class="btn btn-primary">
          <i class="bi bi-plus-circle me-1"></i> New Import
        </a>
        <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'jobs']) ?>" class="btn btn-outline-secondary">
          <i class="bi bi-list me-1"></i> All Jobs
        </a>
        <?php if ($job->status === 'completed' && $job->imported_records > 0): ?>
          <a href="<?php echo url_for(['module' => 'search', 'action' => 'index']) ?>" class="btn btn-success">
            <i class="bi bi-search me-1"></i> Search Records
          </a>
        <?php endif ?>
      </div>

    </div>
  </div>
</div>

<?php if (in_array($job->status, ['pending', 'running'])): ?>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
  const jobId = <?php echo $job->id ?>;
  let pollInterval;

  function updateProgress() {
    fetch('<?php echo url_for(['module' => 'dataMigration', 'action' => 'jobProgress']) ?>?id=' + jobId)
      .then(r => r.json())
      .then(data => {
        if (data.error) {
          console.error(data.error);
          return;
        }

        // Update stats
        document.getElementById('totalRecords').textContent = Number(data.total_records).toLocaleString();
        document.getElementById('processedRecords').textContent = Number(data.processed_records).toLocaleString();
        document.getElementById('importedRecords').textContent = Number(data.imported_records).toLocaleString();
        document.getElementById('updatedRecords').textContent = Number(data.updated_records).toLocaleString();
        document.getElementById('errorCount').textContent = Number(data.error_count).toLocaleString();

        // Update progress bar
        document.getElementById('progressBar').style.width = data.percent + '%';
        document.getElementById('progressPercent').textContent = data.percent + '%';
        document.getElementById('progressMessage').textContent = data.progress_message || 'Processing...';

        // Update started time
        if (data.started_at) {
          document.getElementById('startedAt').textContent = data.started_at;
        }

        // Update status badge
        const badge = document.getElementById('statusBadge');
        badge.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
        badge.className = 'badge bg-' + {
          'completed': 'success',
          'running': 'primary',
          'failed': 'danger',
          'cancelled': 'warning',
          'pending': 'secondary'
        }[data.status];

        // Stop polling if done
        if (['completed', 'failed', 'cancelled'].includes(data.status)) {
          clearInterval(pollInterval);
          document.getElementById('progressBar').classList.remove('progress-bar-animated');
          
          // Hide cancel button
          const cancelBtn = document.getElementById('cancelBtn');
          if (cancelBtn) cancelBtn.style.display = 'none';

          // Reload page to show final state
          setTimeout(() => location.reload(), 1000);
        }
      })
      .catch(err => console.error('Poll error:', err));
  }

  // Poll every 2 seconds
  pollInterval = setInterval(updateProgress, 2000);
  
  // Initial update
  updateProgress();
})();

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
<?php endif ?>

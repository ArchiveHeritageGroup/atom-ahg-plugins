<?php decorate_with('layout_1col.php'); ?>
<?php use_helper('Date'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Queue'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>

  <?php if ($sf_user->hasFlash('notice')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?php echo esc_specialchars($sf_user->getFlash('notice')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('Close'); ?>"></button>
    </div>
  <?php endif; ?>

  <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?php echo esc_specialchars($sf_user->getFlash('error')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('Close'); ?>"></button>
    </div>
  <?php endif; ?>

  <?php $rawStats = $sf_data->getRaw('stats'); ?>

  <!-- Stats cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
      <div class="card text-center h-100">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold"><?php echo (int) $rawStats['total']; ?></div>
          <div class="text-muted small"><?php echo __('Total'); ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card text-center h-100 border-secondary">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold text-secondary"><?php echo (int) $rawStats['pending']; ?></div>
          <div class="text-muted small"><?php echo __('Pending'); ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card text-center h-100 border-primary">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold text-primary"><?php echo (int) $rawStats['active']; ?></div>
          <div class="text-muted small"><?php echo __('Active'); ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card text-center h-100 border-success">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold text-success"><?php echo (int) $rawStats['completed']; ?></div>
          <div class="text-muted small"><?php echo __('Completed'); ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card text-center h-100 border-danger">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold text-danger"><?php echo (int) $rawStats['failed']; ?></div>
          <div class="text-muted small"><?php echo __('Failed'); ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card text-center h-100 border-warning">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold text-warning"><?php echo (int) $rawStats['archived_failed']; ?></div>
          <div class="text-muted small"><?php echo __('Archived Failed'); ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter row -->
  <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <!-- Status pills -->
    <ul class="nav nav-pills">
      <li class="nav-item">
        <a href="<?php echo url_for('@queue_browse'); ?>" class="nav-link<?php echo empty($currentStatus) ? ' active' : ''; ?>">
          <?php echo __('All'); ?>
        </a>
      </li>
      <?php foreach (['pending', 'running', 'completed', 'failed', 'cancelled'] as $s): ?>
        <li class="nav-item">
          <a href="<?php echo url_for('@queue_browse') . '?status=' . $s . ($currentQueue ? '&queue=' . esc_specialchars($currentQueue) : ''); ?>"
             class="nav-link<?php echo ($currentStatus === $s) ? ' active' : ''; ?>">
            <?php echo ucfirst($s); ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <!-- Queue filter -->
    <div class="ms-auto d-flex gap-2 align-items-center">
      <select id="queueFilter" class="form-select form-select-sm" style="width: auto;">
        <option value=""><?php echo __('All queues'); ?></option>
        <?php foreach ($sf_data->getRaw('queueNames') as $qKey => $qLabel): ?>
          <option value="<?php echo esc_specialchars($qKey); ?>" <?php echo ($currentQueue === $qKey) ? 'selected' : ''; ?>>
            <?php echo esc_specialchars($qLabel); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <a href="<?php echo url_for('@queue_batches'); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-layer-group"></i> <?php echo __('Batches'); ?>
      </a>
      <a href="<?php echo url_for('@queue_browse') . '?status=' . esc_specialchars($currentStatus); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-sync-alt"></i> <?php echo __('Refresh'); ?>
      </a>
    </div>
  </div>

<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php $rawJobs = $sf_data->getRaw('queueJobs'); ?>

  <?php if (empty($rawJobs)): ?>
    <div class="alert alert-info">
      <?php echo __('No queue jobs found.'); ?>
    </div>
  <?php else: ?>
    <div class="table-responsive mb-3">
      <table class="table table-bordered table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 60px;"><?php echo __('ID'); ?></th>
            <th><?php echo __('Job Type'); ?></th>
            <th><?php echo __('Queue'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th><?php echo __('Progress'); ?></th>
            <th><?php echo __('User'); ?></th>
            <th><?php echo __('Created'); ?></th>
            <th><?php echo __('Duration'); ?></th>
            <th style="width: 120px;"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rawJobs as $job): ?>
            <tr>
              <td>
                <a href="<?php echo url_for('@queue_detail?id=' . $job->id); ?>">#<?php echo (int) $job->id; ?></a>
              </td>
              <td>
                <code><?php echo esc_specialchars($job->job_type); ?></code>
                <?php if ($job->batch_id): ?>
                  <span class="badge bg-light text-dark ms-1" title="<?php echo __('Batch'); ?> #<?php echo (int) $job->batch_id; ?>">
                    B#<?php echo (int) $job->batch_id; ?>
                  </span>
                <?php endif; ?>
                <?php if ($job->chain_id): ?>
                  <span class="badge bg-light text-dark ms-1" title="<?php echo __('Chain order'); ?> <?php echo (int) $job->chain_order; ?>">
                    C<?php echo (int) $job->chain_order; ?>
                  </span>
                <?php endif; ?>
              </td>
              <td><span class="badge bg-secondary"><?php echo esc_specialchars($job->queue); ?></span></td>
              <td>
                <?php $badgeClass = \AtomFramework\Services\QueueService::statusBadge($job->status); ?>
                <span class="badge bg-<?php echo $badgeClass; ?>">
                  <?php echo esc_specialchars(ucfirst($job->status)); ?>
                </span>
                <?php if ($job->attempt_count > 1): ?>
                  <small class="text-muted">(<?php echo __('attempt %1%/%2%', ['%1%' => $job->attempt_count, '%2%' => $job->max_attempts]); ?>)</small>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($job->progress_total > 0): ?>
                  <?php $pct = round($job->progress_current / $job->progress_total * 100); ?>
                  <div class="progress" style="height: 18px;" title="<?php echo esc_specialchars($job->progress_message ?? ''); ?>">
                    <div class="progress-bar <?php echo ($job->status === 'failed') ? 'bg-danger' : ''; ?>"
                         role="progressbar" style="width: <?php echo $pct; ?>%"
                         aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100">
                      <?php echo $pct; ?>%
                    </div>
                  </div>
                <?php elseif (in_array($job->status, ['running', 'reserved'])): ?>
                  <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden"><?php echo __('Processing...'); ?></span>
                  </div>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td><?php echo esc_specialchars($job->user_name ?? __('System')); ?></td>
              <td>
                <span title="<?php echo esc_specialchars($job->created_at ?? ''); ?>">
                  <?php echo !empty($job->created_at) ? date('M j H:i', strtotime($job->created_at)) : ''; ?>
                </span>
              </td>
              <td>
                <?php if ($job->processing_time_ms): ?>
                  <?php
                    $ms = (int) $job->processing_time_ms;
                    if ($ms < 1000) {
                        $dur = $ms . 'ms';
                    } elseif ($ms < 60000) {
                        $dur = round($ms / 1000, 1) . 's';
                    } else {
                        $dur = round($ms / 60000, 1) . 'm';
                    }
                  ?>
                  <?php echo $dur; ?>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <a href="<?php echo url_for('@queue_detail?id=' . $job->id); ?>" class="btn btn-outline-primary btn-sm" title="<?php echo __('Detail'); ?>">
                    <i class="fas fa-file-alt"></i>
                  </a>
                  <?php if ($job->status === 'failed'): ?>
                    <form method="post" action="<?php echo url_for('@queue_retry'); ?>" class="d-inline">
                      <input type="hidden" name="job_id" value="<?php echo (int) $job->id; ?>">
                      <button type="submit" class="btn btn-outline-warning btn-sm" title="<?php echo __('Retry'); ?>">
                        <i class="fas fa-redo"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                  <?php if (in_array($job->status, ['pending', 'reserved', 'running'])): ?>
                    <form method="post" action="<?php echo url_for('@queue_cancel'); ?>" class="d-inline">
                      <input type="hidden" name="job_id" value="<?php echo (int) $job->id; ?>">
                      <button type="submit" class="btn btn-outline-danger btn-sm" title="<?php echo __('Cancel'); ?>"
                              onclick="return confirm('<?php echo __('Cancel this job?'); ?>')">
                        <i class="fas fa-times"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

<?php end_slot(); ?>

<?php slot('after-content'); ?>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
    <?php
      $baseUrl = url_for('@queue_browse') . '?';
      $params = [];
      if ($currentStatus) { $params[] = 'status=' . esc_specialchars($currentStatus); }
      if ($currentQueue) { $params[] = 'queue=' . esc_specialchars($currentQueue); }
      $params[] = 'limit=' . $limit;
      $baseParams = implode('&', $params);
    ?>
    <nav aria-label="<?php echo __('Queue pagination'); ?>">
      <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo $baseUrl . $baseParams . '&page=' . ($page - 1); ?>"><?php echo __('Previous'); ?></a>
          </li>
        <?php else: ?>
          <li class="page-item disabled"><span class="page-link"><?php echo __('Previous'); ?></span></li>
        <?php endif; ?>

        <?php
          $windowSize = 2;
          $startPage = max(1, $page - $windowSize);
          $endPage = min($pages, $page + $windowSize);
        ?>

        <?php if ($startPage > 1): ?>
          <li class="page-item"><a class="page-link" href="<?php echo $baseUrl . $baseParams . '&page=1'; ?>">1</a></li>
          <?php if ($startPage > 2): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
          <li class="page-item<?php echo ($i == $page) ? ' active' : ''; ?>">
            <a class="page-link" href="<?php echo $baseUrl . $baseParams . '&page=' . $i; ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>

        <?php if ($endPage < $pages): ?>
          <?php if ($endPage < $pages - 1): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php endif; ?>
          <li class="page-item"><a class="page-link" href="<?php echo $baseUrl . $baseParams . '&page=' . $pages; ?>"><?php echo $pages; ?></a></li>
        <?php endif; ?>

        <?php if ($page < $pages): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo $baseUrl . $baseParams . '&page=' . ($page + 1); ?>"><?php echo __('Next'); ?></a>
          </li>
        <?php else: ?>
          <li class="page-item disabled"><span class="page-link"><?php echo __('Next'); ?></span></li>
        <?php endif; ?>
      </ul>
    </nav>
  <?php endif; ?>

  <div class="text-muted small text-center mb-3">
    <?php echo __('Showing %1% of %2% job(s)', ['%1%' => count($rawJobs), '%2%' => $total]); ?>
    <?php if ($pages > 1): ?>
      &middot; <?php echo __('Page %1% of %2%', ['%1%' => $page, '%2%' => $pages]); ?>
    <?php endif; ?>
  </div>

  <!-- Queue filter JS -->
  <script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
    document.getElementById('queueFilter').addEventListener('change', function() {
      var params = new URLSearchParams(window.location.search);
      if (this.value) {
        params.set('queue', this.value);
      } else {
        params.delete('queue');
      }
      params.delete('page');
      window.location.href = '<?php echo url_for('@queue_browse'); ?>?' + params.toString();
    });
  </script>

  <!-- Auto-refresh for active jobs -->
  <?php if ($rawStats['active'] > 0): ?>
    <script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
      (function() {
        var timerId = setInterval(function() { window.location.reload(); }, 10000);
        document.addEventListener('visibilitychange', function() {
          if (document.hidden) { clearInterval(timerId); }
          else { timerId = setInterval(function() { window.location.reload(); }, 10000); }
        });
      })();
    </script>
  <?php endif; ?>

<?php end_slot(); ?>

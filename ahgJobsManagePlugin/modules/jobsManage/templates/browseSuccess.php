<?php decorate_with('layout_1col.php'); ?>
<?php use_helper('Date'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Jobs'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>

  <?php if ($sf_user->hasFlash('notice')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?php echo esc_specialchars($sf_user->getFlash('notice')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('Close'); ?>"></button>
    </div>
  <?php endif; ?>

  <!-- Stats cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card text-center h-100">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold"><?php echo (int) $stats['total']; ?></div>
          <div class="text-muted small"><?php echo __('Total'); ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card text-center h-100 border-primary">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold text-primary"><?php echo (int) $stats['active']; ?></div>
          <div class="text-muted small"><?php echo __('Active'); ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card text-center h-100 border-success">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold text-success"><?php echo (int) $stats['completed']; ?></div>
          <div class="text-muted small"><?php echo __('Completed'); ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card text-center h-100 border-danger">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold text-danger"><?php echo (int) $stats['failed']; ?></div>
          <div class="text-muted small"><?php echo __('Failed'); ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter buttons -->
  <div class="d-flex flex-wrap gap-2 mb-3">
    <ul class="nav nav-pills">
      <li class="nav-item">
        <?php $allClass = ('all' == $currentStatus || empty($currentStatus)) ? ' active' : ''; ?>
        <a href="<?php echo url_for('@jobs_browse'); ?>" class="nav-link<?php echo $allClass; ?>">
          <?php echo __('All'); ?>
        </a>
      </li>
      <li class="nav-item">
        <?php $activeClass = ('active' == $currentStatus) ? ' active' : ''; ?>
        <a href="<?php echo url_for('@jobs_browse') . '?status=active'; ?>" class="nav-link<?php echo $activeClass; ?>">
          <?php echo __('Active'); ?>
        </a>
      </li>
      <li class="nav-item">
        <?php $completedClass = ('completed' == $currentStatus) ? ' active' : ''; ?>
        <a href="<?php echo url_for('@jobs_browse') . '?status=completed'; ?>" class="nav-link<?php echo $completedClass; ?>">
          <?php echo __('Completed'); ?>
        </a>
      </li>
      <li class="nav-item">
        <?php $failedClass = ('failed' == $currentStatus) ? ' active' : ''; ?>
        <a href="<?php echo url_for('@jobs_browse') . '?status=failed'; ?>" class="nav-link<?php echo $failedClass; ?>">
          <?php echo __('Failed'); ?>
        </a>
      </li>
    </ul>

    <div class="d-flex flex-wrap gap-2 ms-auto">
      <a href="<?php echo url_for('@jobs_browse') . '?status=' . esc_specialchars($currentStatus); ?>" class="btn btn-outline-secondary btn-sm" title="<?php echo __('Refresh'); ?>">
        <i class="fas fa-sync-alt"></i> <?php echo __('Refresh'); ?>
      </a>
      <a href="<?php echo url_for('@jobs_export'); ?>" class="btn btn-outline-secondary btn-sm" title="<?php echo __('Export CSV'); ?>">
        <i class="fas fa-download"></i> <?php echo __('Export CSV'); ?>
      </a>
      <?php if ($stats['completed'] + $stats['failed'] > 0): ?>
        <a href="<?php echo url_for('@jobs_delete'); ?>" class="btn btn-outline-danger btn-sm" title="<?php echo __('Clear inactive jobs'); ?>">
          <i class="fas fa-trash-alt"></i> <?php echo __('Clear inactive'); ?>
        </a>
      <?php endif; ?>
    </div>
  </div>

<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php if (empty($jobs)): ?>
    <div class="alert alert-info">
      <?php echo __('No jobs found.'); ?>
    </div>
  <?php else: ?>
    <div class="table-responsive mb-3">
      <table class="table table-bordered table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Job name'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <?php if ($isAdmin): ?>
              <th><?php echo __('User'); ?></th>
            <?php endif; ?>
            <th><?php echo __('Created'); ?></th>
            <th><?php echo __('Completed'); ?></th>
            <th><?php echo __('Related object'); ?></th>
            <th><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sf_data->getRaw('jobs') as $job): ?>
            <tr>
              <td>
                <?php echo esc_specialchars($job->name); ?>
              </td>
              <td>
                <?php
                    $badgeClass = \AhgJobsManage\Services\JobsService::getStatusBadge($job->status_id);
                    $statusLabel = \AhgJobsManage\Services\JobsService::getStatusLabel($job->status_id);
                ?>
                <span class="badge bg-<?php echo $badgeClass; ?>">
                  <?php echo esc_specialchars($statusLabel); ?>
                </span>
              </td>
              <?php if ($isAdmin): ?>
                <td>
                  <?php echo esc_specialchars($job->user_name ?? __('System')); ?>
                </td>
              <?php endif; ?>
              <td>
                <?php echo !empty($job->created_at) ? format_date($job->created_at, 'f') : ''; ?>
              </td>
              <td>
                <?php echo !empty($job->completed_at) ? format_date($job->completed_at, 'f') : ''; ?>
              </td>
              <td>
                <?php if (!empty($job->object_slug)): ?>
                  <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $job->object_slug]); ?>">
                    <?php echo esc_specialchars($job->object_slug); ?>
                  </a>
                <?php elseif (!empty($job->object_id)): ?>
                  <span class="text-muted">#<?php echo (int) $job->object_id; ?></span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <a href="<?php echo url_for('@jobs_report?id=' . $job->id); ?>" class="btn btn-outline-primary btn-sm" title="<?php echo __('Report'); ?>">
                    <i class="fas fa-file-alt"></i>
                  </a>
                  <?php if ($job->status_id != \AhgJobsManage\Services\JobsService::STATUS_IN_PROGRESS): ?>
                    <a href="<?php echo url_for('@jobs_delete') . '?id=' . $job->id; ?>" class="btn btn-outline-danger btn-sm" title="<?php echo __('Delete'); ?>">
                      <i class="fas fa-trash-alt"></i>
                    </a>
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
    <nav aria-label="<?php echo __('Job pagination'); ?>">
      <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for('@jobs_browse') . '?status=' . esc_specialchars($currentStatus) . '&page=' . ($page - 1) . '&limit=' . $limit; ?>">
              <?php echo __('Previous'); ?>
            </a>
          </li>
        <?php else: ?>
          <li class="page-item disabled">
            <span class="page-link"><?php echo __('Previous'); ?></span>
          </li>
        <?php endif; ?>

        <?php
          // Show a window of pages around the current page
          $windowSize = 2;
          $startPage = max(1, $page - $windowSize);
          $endPage = min($pages, $page + $windowSize);
        ?>

        <?php if ($startPage > 1): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for('@jobs_browse') . '?status=' . esc_specialchars($currentStatus) . '&page=1&limit=' . $limit; ?>">1</a>
          </li>
          <?php if ($startPage > 2): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
          <li class="page-item<?php echo ($i == $page) ? ' active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for('@jobs_browse') . '?status=' . esc_specialchars($currentStatus) . '&page=' . $i . '&limit=' . $limit; ?>">
              <?php echo $i; ?>
            </a>
          </li>
        <?php endfor; ?>

        <?php if ($endPage < $pages): ?>
          <?php if ($endPage < $pages - 1): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php endif; ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for('@jobs_browse') . '?status=' . esc_specialchars($currentStatus) . '&page=' . $pages . '&limit=' . $limit; ?>"><?php echo $pages; ?></a>
          </li>
        <?php endif; ?>

        <?php if ($page < $pages): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for('@jobs_browse') . '?status=' . esc_specialchars($currentStatus) . '&page=' . ($page + 1) . '&limit=' . $limit; ?>">
              <?php echo __('Next'); ?>
            </a>
          </li>
        <?php else: ?>
          <li class="page-item disabled">
            <span class="page-link"><?php echo __('Next'); ?></span>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
  <?php endif; ?>

  <div class="text-muted small text-center mb-3">
    <?php echo __('Showing %1% of %2% job(s)', ['%1%' => count($sf_data->getRaw('jobs')), '%2%' => $total]); ?>
    <?php if ($pages > 1): ?>
      &middot; <?php echo __('Page %1% of %2%', ['%1%' => $page, '%2%' => $pages]); ?>
    <?php endif; ?>
  </div>

  <!-- Auto-refresh for active jobs -->
  <?php if ('active' == $currentStatus && $stats['active'] > 0): ?>
    <script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
      (function() {
        var refreshInterval = 15000; // 15 seconds
        var timerId = setInterval(function() {
          window.location.reload();
        }, refreshInterval);

        // Stop auto-refresh if the page becomes hidden
        document.addEventListener('visibilitychange', function() {
          if (document.hidden) {
            clearInterval(timerId);
          } else {
            timerId = setInterval(function() {
              window.location.reload();
            }, refreshInterval);
          }
        });
      })();
    </script>
  <?php endif; ?>

<?php end_slot(); ?>

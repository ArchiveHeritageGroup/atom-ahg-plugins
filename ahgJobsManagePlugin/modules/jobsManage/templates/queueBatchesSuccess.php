<?php decorate_with('layout_1col.php'); ?>
<?php use_helper('Date'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Queue Batches'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>

  <?php if ($sf_user->hasFlash('notice')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?php echo esc_specialchars($sf_user->getFlash('notice')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('Close'); ?>"></button>
    </div>
  <?php endif; ?>

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo url_for('@queue_browse'); ?>"><?php echo __('Queue'); ?></a></li>
      <li class="breadcrumb-item active"><?php echo __('Batches'); ?></li>
    </ol>
  </nav>

<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php $rawBatches = $sf_data->getRaw('batches'); ?>

  <?php if (empty($rawBatches)): ?>
    <div class="alert alert-info">
      <?php echo __('No batches found.'); ?>
    </div>
  <?php else: ?>
    <div class="table-responsive mb-3">
      <table class="table table-bordered table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 60px;"><?php echo __('ID'); ?></th>
            <th><?php echo __('Name'); ?></th>
            <th><?php echo __('Queue'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th><?php echo __('Progress'); ?></th>
            <th><?php echo __('Jobs'); ?></th>
            <th><?php echo __('User'); ?></th>
            <th><?php echo __('Created'); ?></th>
            <th style="width: 100px;"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rawBatches as $batch): ?>
            <tr>
              <td>#<?php echo (int) $batch->id; ?></td>
              <td><?php echo esc_specialchars($batch->name); ?></td>
              <td><span class="badge bg-secondary"><?php echo esc_specialchars($batch->queue); ?></span></td>
              <td>
                <?php $badgeClass = \AtomFramework\Services\QueueService::statusBadge($batch->status); ?>
                <span class="badge bg-<?php echo $badgeClass; ?>">
                  <?php echo ucfirst(esc_specialchars($batch->status)); ?>
                </span>
              </td>
              <td>
                <div class="progress" style="height: 18px; min-width: 120px;">
                  <?php $pct = (float) $batch->progress_percent; ?>
                  <div class="progress-bar <?php echo ($batch->status === 'failed') ? 'bg-danger' : ''; ?>"
                       role="progressbar" style="width: <?php echo $pct; ?>%">
                    <?php echo round($pct); ?>%
                  </div>
                </div>
              </td>
              <td>
                <span class="text-success"><?php echo (int) $batch->completed_jobs; ?></span>
                <?php if ($batch->failed_jobs > 0): ?>
                  / <span class="text-danger"><?php echo (int) $batch->failed_jobs; ?> failed</span>
                <?php endif; ?>
                / <?php echo (int) $batch->total_jobs; ?>
              </td>
              <td><?php echo esc_specialchars($batch->user_name ?? __('System')); ?></td>
              <td>
                <span title="<?php echo esc_specialchars($batch->created_at ?? ''); ?>">
                  <?php echo !empty($batch->created_at) ? date('M j H:i', strtotime($batch->created_at)) : ''; ?>
                </span>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <a href="<?php echo url_for('@queue_browse') . '?batch_id=' . (int) $batch->id; ?>"
                     class="btn btn-outline-primary btn-sm" title="<?php echo __('View Jobs'); ?>">
                    <i class="fas fa-list"></i>
                  </a>
                  <?php if (in_array($batch->status, ['pending', 'running', 'paused'])): ?>
                    <form method="post" action="<?php echo url_for('@queue_cancel'); ?>" class="d-inline">
                      <input type="hidden" name="batch_id" value="<?php echo (int) $batch->id; ?>">
                      <button type="submit" class="btn btn-outline-danger btn-sm" title="<?php echo __('Cancel'); ?>"
                              onclick="return confirm('<?php echo __('Cancel this batch?'); ?>')">
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
    <nav aria-label="<?php echo __('Batch pagination'); ?>">
      <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for('@queue_batches') . '?page=' . ($page - 1) . '&limit=' . $limit; ?>"><?php echo __('Previous'); ?></a>
          </li>
        <?php else: ?>
          <li class="page-item disabled"><span class="page-link"><?php echo __('Previous'); ?></span></li>
        <?php endif; ?>

        <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
          <li class="page-item<?php echo ($i == $page) ? ' active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for('@queue_batches') . '?page=' . $i . '&limit=' . $limit; ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>

        <?php if ($page < $pages): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for('@queue_batches') . '?page=' . ($page + 1) . '&limit=' . $limit; ?>"><?php echo __('Next'); ?></a>
          </li>
        <?php else: ?>
          <li class="page-item disabled"><span class="page-link"><?php echo __('Next'); ?></span></li>
        <?php endif; ?>
      </ul>
    </nav>
  <?php endif; ?>

  <div class="text-muted small text-center mb-3">
    <?php echo __('Showing %1% of %2% batch(es)', ['%1%' => count($rawBatches), '%2%' => $total]); ?>
  </div>

<?php end_slot(); ?>

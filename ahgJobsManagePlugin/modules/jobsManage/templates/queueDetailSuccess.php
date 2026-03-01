<?php decorate_with('layout_1col.php'); ?>
<?php use_helper('Date'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Queue Job #%1%', ['%1%' => (int) $queueJob->id]); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>

  <?php $rawJob = $sf_data->getRaw('queueJob'); ?>

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo url_for('@queue_browse'); ?>"><?php echo __('Queue'); ?></a></li>
      <li class="breadcrumb-item active">
        #<?php echo (int) $rawJob->id; ?> — <?php echo esc_specialchars($rawJob->job_type); ?>
      </li>
    </ol>
  </nav>

  <!-- Status + Actions bar -->
  <div class="d-flex align-items-center gap-2 mb-4">
    <?php $badgeClass = \AtomFramework\Services\QueueService::statusBadge($rawJob->status); ?>
    <span class="badge bg-<?php echo $badgeClass; ?> fs-6 py-2 px-3">
      <?php echo ucfirst(esc_specialchars($rawJob->status)); ?>
    </span>

    <?php if ($rawJob->status === 'failed'): ?>
      <form method="post" action="<?php echo url_for('@queue_retry'); ?>" class="d-inline">
        <input type="hidden" name="job_id" value="<?php echo (int) $rawJob->id; ?>">
        <button type="submit" class="btn btn-warning btn-sm">
          <i class="fas fa-redo"></i> <?php echo __('Retry'); ?>
        </button>
      </form>
    <?php endif; ?>

    <?php if (in_array($rawJob->status, ['pending', 'reserved', 'running'])): ?>
      <form method="post" action="<?php echo url_for('@queue_cancel'); ?>" class="d-inline">
        <input type="hidden" name="job_id" value="<?php echo (int) $rawJob->id; ?>">
        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('<?php echo __('Cancel this job?'); ?>')">
          <i class="fas fa-times"></i> <?php echo __('Cancel'); ?>
        </button>
      </form>
    <?php endif; ?>
  </div>

<?php end_slot(); ?>

<?php slot('content'); ?>

  <!-- Job details card -->
  <div class="card mb-4">
    <div class="card-header"><strong><?php echo __('Job Details'); ?></strong></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <table class="table table-sm mb-0">
            <tr><th class="w-50"><?php echo __('ID'); ?></th><td><?php echo (int) $rawJob->id; ?></td></tr>
            <tr><th><?php echo __('Job Type'); ?></th><td><code><?php echo esc_specialchars($rawJob->job_type); ?></code></td></tr>
            <tr><th><?php echo __('Queue'); ?></th><td><span class="badge bg-secondary"><?php echo esc_specialchars($rawJob->queue); ?></span></td></tr>
            <tr><th><?php echo __('Priority'); ?></th><td><?php echo (int) $rawJob->priority; ?></td></tr>
            <tr><th><?php echo __('User'); ?></th><td><?php echo esc_specialchars($rawJob->user_name ?? __('System')); ?></td></tr>
            <tr><th><?php echo __('Worker'); ?></th><td><?php echo esc_specialchars($rawJob->worker_id ?? '-'); ?></td></tr>
          </table>
        </div>
        <div class="col-md-6">
          <table class="table table-sm mb-0">
            <tr><th class="w-50"><?php echo __('Attempts'); ?></th><td><?php echo (int) $rawJob->attempt_count; ?> / <?php echo (int) $rawJob->max_attempts; ?></td></tr>
            <tr><th><?php echo __('Backoff'); ?></th><td><?php echo esc_specialchars($rawJob->backoff_strategy); ?></td></tr>
            <tr><th><?php echo __('Created'); ?></th><td><?php echo esc_specialchars($rawJob->created_at ?? ''); ?></td></tr>
            <tr><th><?php echo __('Started'); ?></th><td><?php echo esc_specialchars($rawJob->started_at ?? '-'); ?></td></tr>
            <tr><th><?php echo __('Completed'); ?></th><td><?php echo esc_specialchars($rawJob->completed_at ?? '-'); ?></td></tr>
            <tr>
              <th><?php echo __('Duration'); ?></th>
              <td>
                <?php if ($rawJob->processing_time_ms): ?>
                  <?php
                    $ms = (int) $rawJob->processing_time_ms;
                    if ($ms < 1000) { echo $ms . 'ms'; }
                    elseif ($ms < 60000) { echo round($ms / 1000, 1) . 's'; }
                    else { echo round($ms / 60000, 1) . 'm'; }
                  ?>
                <?php else: ?>-<?php endif; ?>
              </td>
            </tr>
          </table>
        </div>
      </div>

      <?php if ($rawJob->batch_id): ?>
        <div class="mt-3">
          <strong><?php echo __('Batch'); ?>:</strong>
          <a href="<?php echo url_for('@queue_browse') . '?batch_id=' . (int) $rawJob->batch_id; ?>">
            #<?php echo (int) $rawJob->batch_id; ?>
          </a>
        </div>
      <?php endif; ?>

      <?php if ($rawJob->chain_id): ?>
        <div class="mt-2">
          <strong><?php echo __('Chain'); ?>:</strong>
          Chain #<?php echo (int) $rawJob->chain_id; ?>, order <?php echo (int) $rawJob->chain_order; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Progress -->
  <?php if ($rawJob->progress_total > 0): ?>
    <div class="card mb-4">
      <div class="card-header"><strong><?php echo __('Progress'); ?></strong></div>
      <div class="card-body">
        <?php $pct = round($rawJob->progress_current / $rawJob->progress_total * 100); ?>
        <div class="progress mb-2" style="height: 24px;">
          <div class="progress-bar <?php echo ($rawJob->status === 'failed') ? 'bg-danger' : 'bg-success'; ?>"
               role="progressbar" style="width: <?php echo $pct; ?>%">
            <?php echo (int) $rawJob->progress_current; ?> / <?php echo (int) $rawJob->progress_total; ?> (<?php echo $pct; ?>%)
          </div>
        </div>
        <?php if ($rawJob->progress_message): ?>
          <p class="text-muted mb-0"><?php echo esc_specialchars($rawJob->progress_message); ?></p>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Error -->
  <?php if ($rawJob->error_message): ?>
    <div class="card mb-4 border-danger">
      <div class="card-header bg-danger text-white"><strong><?php echo __('Error'); ?></strong></div>
      <div class="card-body">
        <p class="mb-1"><strong><?php echo __('Message'); ?>:</strong> <?php echo esc_specialchars($rawJob->error_message); ?></p>
        <?php if ($rawJob->error_code): ?>
          <p class="mb-1"><strong><?php echo __('Code'); ?>:</strong> <?php echo esc_specialchars($rawJob->error_code); ?></p>
        <?php endif; ?>
        <?php if ($rawJob->error_trace): ?>
          <details class="mt-2">
            <summary><?php echo __('Stack trace'); ?></summary>
            <pre class="bg-light p-2 mt-1 small" style="max-height: 300px; overflow-y: auto;"><?php echo esc_specialchars($rawJob->error_trace); ?></pre>
          </details>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Payload -->
  <?php if ($rawJob->payload): ?>
    <div class="card mb-4">
      <div class="card-header"><strong><?php echo __('Payload'); ?></strong></div>
      <div class="card-body">
        <pre class="bg-light p-2 small mb-0" style="max-height: 200px; overflow-y: auto;"><?php echo esc_specialchars(json_encode(json_decode($rawJob->payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
      </div>
    </div>
  <?php endif; ?>

  <!-- Result data -->
  <?php if ($rawJob->result_data): ?>
    <div class="card mb-4">
      <div class="card-header"><strong><?php echo __('Result'); ?></strong></div>
      <div class="card-body">
        <pre class="bg-light p-2 small mb-0" style="max-height: 200px; overflow-y: auto;"><?php echo esc_specialchars(json_encode(json_decode($rawJob->result_data), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
      </div>
    </div>
  <?php endif; ?>

  <!-- Log timeline -->
  <?php $rawLogs = $sf_data->getRaw('logEvents'); ?>
  <?php if (!empty($rawLogs)): ?>
    <div class="card mb-4">
      <div class="card-header"><strong><?php echo __('Event Log'); ?></strong></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 160px;"><?php echo __('Time'); ?></th>
                <th style="width: 120px;"><?php echo __('Event'); ?></th>
                <th><?php echo __('Message'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rawLogs as $log): ?>
                <tr>
                  <td><small><?php echo esc_specialchars($log->created_at ?? ''); ?></small></td>
                  <td>
                    <?php
                      $eventBadges = [
                          'dispatched' => 'secondary',
                          'reserved' => 'info',
                          'started' => 'primary',
                          'completed' => 'success',
                          'failed' => 'danger',
                          'retried' => 'warning',
                          'cancelled' => 'warning',
                          'info' => 'light',
                      ];
                      $eBadge = $eventBadges[$log->event_type] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $eBadge; ?>"><?php echo esc_specialchars($log->event_type); ?></span>
                  </td>
                  <td><?php echo esc_specialchars($log->message ?? ''); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

<?php end_slot(); ?>

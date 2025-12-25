<?php use_helper('Date') ?>

<h1><?php echo __('Media Processing Queue') ?></h1>

<div class="row mb-4">
  <div class="col-md-3 col-sm-6 mb-3">
    <div class="card text-center">
      <div class="card-body">
        <h3 class="text-warning"><?php echo $stats['pending'] ?? 0 ?></h3>
        <small class="text-muted"><?php echo __('Pending') ?></small>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 mb-3">
    <div class="card text-center">
      <div class="card-body">
        <h3 class="text-primary"><?php echo $stats['processing'] ?? 0 ?></h3>
        <small class="text-muted"><?php echo __('Processing') ?></small>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 mb-3">
    <div class="card text-center">
      <div class="card-body">
        <h3 class="text-success"><?php echo $stats['completed'] ?? 0 ?></h3>
        <small class="text-muted"><?php echo __('Completed') ?></small>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 mb-3">
    <div class="card text-center">
      <div class="card-body">
        <h3 class="text-danger"><?php echo $stats['failed'] ?? 0 ?></h3>
        <small class="text-muted"><?php echo __('Failed') ?></small>
      </div>
    </div>
  </div>
</div>

<div class="mb-4">
  <a href="<?php echo url_for(['module' => 'arMediaSettings', 'action' => 'processQueue', 'limit' => 5]) ?>" class="btn btn-primary">
    <i class="fas fa-play me-1"></i>
    <?php echo __('Process Next 5 Items') ?>
  </a>
  <a href="<?php echo url_for(['module' => 'arMediaSettings', 'action' => 'clearQueue']) ?>" class="btn btn-outline-danger"
     onclick="return confirm('<?php echo __('Clear all completed and failed items?') ?>')">
    <i class="fas fa-trash me-1"></i>
    <?php echo __('Clear Completed') ?>
  </a>
  <a href="<?php echo url_for(['module' => 'arMediaSettings', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
    <i class="fas fa-cog me-1"></i>
    <?php echo __('Settings') ?>
  </a>
</div>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><?php echo __('Recent Queue Items') ?></h5>
  </div>
  <div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
      <thead>
        <tr>
          <th><?php echo __('ID') ?></th>
          <th><?php echo __('File') ?></th>
          <th><?php echo __('Task') ?></th>
          <th><?php echo __('Status') ?></th>
          <th><?php echo __('Created') ?></th>
          <th><?php echo __('Completed') ?></th>
          <th><?php echo __('Error') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr>
            <td colspan="7" class="text-center text-muted py-4">
              <?php echo __('No items in queue') ?>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($items as $item): ?>
            <tr>
              <td><?php echo $item->id ?></td>
              <td>
                <a href="<?php echo url_for(['module' => 'digitalobject', 'action' => 'edit', 'id' => $item->digital_object_id]) ?>">
                  <?php echo $item->filename ?? 'ID: ' . $item->digital_object_id ?>
                </a>
              </td>
              <td><?php echo $item->task_type ?></td>
              <td>
                <?php
                $statusClass = [
                    'pending' => 'warning',
                    'processing' => 'primary',
                    'completed' => 'success',
                    'failed' => 'danger',
                ][$item->status] ?? 'secondary';
                ?>
                <span class="badge bg-<?php echo $statusClass ?>">
                  <?php echo $item->status ?>
                </span>
              </td>
              <td><?php echo $item->created_at ?></td>
              <td><?php echo $item->completed_at ?: '-' ?></td>
              <td>
                <?php if ($item->error_message): ?>
                  <span class="text-danger" title="<?php echo htmlspecialchars($item->error_message) ?>">
                    <?php echo truncate_text($item->error_message, 50) ?>
                  </span>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card mt-4">
  <div class="card-header">
    <h5 class="mb-0"><?php echo __('Cron Setup') ?></h5>
  </div>
  <div class="card-body">
    <p class="text-muted"><?php echo __('To automatically process the queue, add this cron job:') ?></p>
    <pre class="bg-light p-3 rounded"><code>*/5 * * * * php <?php echo sfConfig::get('sf_root_dir') ?>/symfony arMediaSettings:processQueue --limit=10</code></pre>
    <p class="text-muted mb-0"><?php echo __('Or call the endpoint periodically:') ?></p>
    <pre class="bg-light p-3 rounded mb-0"><code>curl -X POST <?php echo sfConfig::get('app_siteBaseUrl') ?>/index.php/arMediaSettings/processQueue?limit=10</code></pre>
  </div>
</div>

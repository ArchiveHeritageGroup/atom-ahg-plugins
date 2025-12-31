<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fa fa-list-ol"></i> <?php echo __('Sync Queue Management'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'index']); ?>"><?php echo __('RIC Dashboard'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Sync Queue'); ?></li>
  </ol>
</nav>

<ul class="nav nav-tabs mb-3">
  <?php
    $statuses = ['all' => 'All', 'queued' => 'Queued', 'processing' => 'Processing', 'completed' => 'Completed', 'failed' => 'Failed'];
    $colors = ['all' => 'secondary', 'queued' => 'primary', 'processing' => 'warning', 'completed' => 'success', 'failed' => 'danger'];
  ?>
  <?php foreach ($statuses as $key => $label): ?>
    <li class="nav-item">
      <a class="nav-link <?php echo $currentStatus === $key ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'queue', 'status' => $key]); ?>">
        <?php echo __($label); ?>
        <span class="badge bg-<?php echo $colors[$key]; ?>"><?php echo $key === 'all' ? array_sum($statusCounts) : ($statusCounts[$key] ?? 0); ?></span>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="card">
  <div class="card-body">
    <?php if (empty($queueItems)): ?>
      <div class="alert alert-info mb-0"><i class="fa fa-info-circle"></i> <?php echo __('Queue is empty.'); ?></div>
    <?php else: ?>
      <table class="table table-sm table-hover">
        <thead>
          <tr>
            <th><?php echo __('Entity'); ?></th>
            <th><?php echo __('Operation'); ?></th>
            <th><?php echo __('Priority'); ?></th>
            <th><?php echo __('Scheduled'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($queueItems as $item): ?>
            <tr>
              <td><code><?php echo $item->entity_type; ?>/<?php echo $item->entity_id; ?></code></td>
              <td><span class="badge bg-secondary"><?php echo $item->operation; ?></span></td>
              <td><?php echo $item->priority; ?></td>
              <td class="small text-muted"><?php echo date('Y-m-d H:i', strtotime($item->scheduled_at)); ?></td>
              <td><span class="badge bg-<?php echo $colors[$item->status] ?? 'secondary'; ?>"><?php echo $item->status; ?></span></td>
              <td>
                <?php if ($item->status === 'failed'): ?>
                  <button class="btn btn-sm btn-outline-primary" onclick="queueAction(<?php echo $item->id; ?>, 'retry')"><i class="fa fa-redo"></i></button>
                <?php endif; ?>
                <?php if (in_array($item->status, ['queued', 'failed'])): ?>
                  <button class="btn btn-sm btn-outline-danger" onclick="queueAction(<?php echo $item->id; ?>, 'cancel')"><i class="fa fa-times"></i></button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<?php end_slot(); ?>

<?php slot('after-content'); ?>
<script <?php echo __(sfConfig::get('csp_nonce', '')); ?>>
function queueAction(id, action) {
  fetch('<?php echo url_for(['module' => 'ricDashboard', 'action' => 'ajaxClearQueueItem']); ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id=' + id + '&queue_action=' + action
  }).then(r => r.json()).then(data => {
    if (data.success) location.reload();
  });
}
</script>
<?php end_slot(); ?>

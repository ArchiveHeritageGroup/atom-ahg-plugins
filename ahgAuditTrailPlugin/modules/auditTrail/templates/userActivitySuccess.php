<?php decorate_with('layout_1col') ?>

<?php slot('title') ?>
  <h1><?php echo __('User Activity: %1%', ['%1%' => $targetUser->username]) ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="row">
  <div class="col-lg-8">
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Recent Activity') ?></h5></div>
      <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
          <thead><tr><th><?php echo __('Time') ?></th><th><?php echo __('Action') ?></th><th><?php echo __('Entity') ?></th><th><?php echo __('Title') ?></th></tr></thead>
          <tbody>
            <?php foreach ($activityLogs as $log): ?>
            <tr>
              <td><small><?php echo $log->created_at->format('Y-m-d H:i') ?></small></td>
              <td><span class="badge bg-<?php echo match($log->action) { 'create' => 'success', 'update' => 'primary', 'delete' => 'danger', default => 'secondary' } ?>"><?php echo $log->action_label ?></span></td>
              <td><?php echo $log->entity_type_label ?></td>
              <td><a href="<?php echo url_for(['module' => 'auditTrail', 'action' => 'view', 'uuid' => $log->uuid]) ?>"><?php echo htmlspecialchars(mb_substr($log->entity_title ?? '-', 0, 40)) ?></a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($activityLogs) === 0): ?>
            <tr><td colspan="4" class="text-center text-muted py-4"><?php echo __('No activity found') ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Authentication History') ?></h5></div>
      <ul class="list-group list-group-flush">
        <?php foreach ($authLogs as $auth): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span class="badge bg-<?php echo $auth->status === 'success' ? 'success' : 'danger' ?>"><?php echo ucfirst(str_replace('_', ' ', $auth->event_type)) ?></span>
          <small class="text-muted"><?php echo $auth->created_at->format('M j, H:i') ?></small>
        </li>
        <?php endforeach; ?>
        <?php if (count($authLogs) === 0): ?>
        <li class="list-group-item text-center text-muted"><?php echo __('No authentication history') ?></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>

<div class="mt-4">
  <a href="<?php echo url_for(['module' => 'auditTrail', 'action' => 'browse']) ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo __('Back to Audit Trail') ?></a>
  <a href="<?php echo url_for(['module' => 'auditTrail', 'action' => 'statistics']) ?>" class="btn btn-outline-primary"><?php echo __('View Statistics') ?></a>
</div>
<?php end_slot() ?>
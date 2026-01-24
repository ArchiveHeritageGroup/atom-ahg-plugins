<?php decorate_with('layout_1col') ?>

<?php slot('title') ?>
  <h1><?php echo __('Entity History') ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><?php echo $entityType ?> #<?php echo $entityId ?><?php if ($entity): ?> - <?php echo htmlspecialchars($entity->getTitle() ?? $entity->slug ?? '') ?><?php endif; ?></h5>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th><?php echo __('Date/Time') ?></th><th><?php echo __('User') ?></th><th><?php echo __('Action') ?></th><th><?php echo __('Changes') ?></th><th></th></tr></thead>
      <tbody>
        <?php foreach ($auditLogs as $log): ?>
        <tr>
          <td><?php echo $log->created_at->format('Y-m-d H:i:s') ?></td>
          <td><?php echo htmlspecialchars($log->username ?? 'System') ?></td>
          <td><span class="badge bg-<?php echo match($log->action) { 'create' => 'success', 'update' => 'primary', 'delete' => 'danger', default => 'secondary' } ?>"><?php echo $log->action_label ?></span></td>
          <td><?php if ($log->changed_fields): ?><small><?php echo implode(', ', array_slice($log->changed_fields, 0, 5)) ?></small><?php endif; ?></td>
          <td><a href="<?php echo url_for(['module' => 'auditTrail', 'action' => 'view', 'uuid' => $log->uuid]) ?>" class="btn btn-sm btn-outline-primary"><?php echo __('Details') ?></a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (count($auditLogs) === 0): ?>
        <tr><td colspan="5" class="text-center text-muted py-4"><?php echo __('No history found') ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="mt-4">
  <a href="<?php echo url_for(['module' => 'auditTrail', 'action' => 'browse']) ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo __('Back to Audit Trail') ?></a>
</div>
<?php end_slot() ?>
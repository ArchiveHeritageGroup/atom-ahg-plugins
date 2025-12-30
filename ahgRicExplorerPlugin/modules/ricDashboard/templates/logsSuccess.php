<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fa fa-list"></i> <?php echo __('Operation Logs'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'index']); ?>"><?php echo __('RIC Dashboard'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Logs'); ?></li>
  </ol>
</nav>

<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2">
      <div class="col-md-2">
        <select name="operation" class="form-select form-select-sm">
          <option value=""><?php echo __('All Operations'); ?></option>
          <?php foreach (['create', 'update', 'delete', 'move', 'resync', 'cleanup'] as $op): ?>
            <option value="<?php echo $op; ?>" <?php echo ($filters['operation'] ?? '') === $op ? 'selected' : ''; ?>><?php echo $op; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value=""><?php echo __('All Statuses'); ?></option>
          <?php foreach (['success', 'failure', 'partial'] as $st): ?>
            <option value="<?php echo $st; ?>" <?php echo ($filters['status'] ?? '') === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" name="date_from" class="form-control form-control-sm" placeholder="From" value="<?php echo $filters['dateFrom'] ?? ''; ?>">
      </div>
      <div class="col-md-2">
        <input type="date" name="date_to" class="form-control form-control-sm" placeholder="To" value="<?php echo $filters['dateTo'] ?? ''; ?>">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fa fa-filter"></i> <?php echo __('Filter'); ?></button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <table class="table table-sm table-hover">
      <thead>
        <tr>
          <th><?php echo __('Time'); ?></th>
          <th><?php echo __('Operation'); ?></th>
          <th><?php echo __('Entity'); ?></th>
          <th><?php echo __('Status'); ?></th>
          <th><?php echo __('Triggered By'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
          <tr>
            <td class="small text-muted"><?php echo date('Y-m-d H:i:s', strtotime($log->created_at)); ?></td>
            <td><span class="badge bg-secondary"><?php echo $log->operation; ?></span></td>
            <td><code><?php echo $log->entity_type; ?>/<?php echo $log->entity_id; ?></code></td>
            <td><span class="badge bg-<?php echo $log->status === 'success' ? 'success' : ($log->status === 'failure' ? 'danger' : 'warning'); ?>"><?php echo $log->status; ?></span></td>
            <td class="small"><?php echo $log->triggered_by; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php end_slot(); ?>

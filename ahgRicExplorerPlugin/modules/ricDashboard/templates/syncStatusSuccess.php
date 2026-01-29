<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fa fa-sync"></i> <?php echo __('Sync Status Details'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'index']); ?>"><?php echo __('RIC Dashboard'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Sync Status'); ?></li>
  </ol>
</nav>

<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2">
      <div class="col-md-3">
        <select name="entity_type" class="form-select form-select-sm">
          <option value=""><?php echo __('All Entity Types'); ?></option>
          <?php foreach ($entityTypes as $et): ?>
            <option value="<?php echo $et; ?>" <?php echo $entityType === $et ? 'selected' : ''; ?>><?php echo $et; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
          <option value=""><?php echo __('All Statuses'); ?></option>
          <?php foreach ($statuses as $st): ?>
            <option value="<?php echo $st; ?>" <?php echo $status === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
          <?php endforeach; ?>
        </select>
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
          <th><?php echo __('Entity'); ?></th>
          <th><?php echo __('RIC URI'); ?></th>
          <th><?php echo __('Status'); ?></th>
          <th><?php echo __('Last Synced'); ?></th>
          <th><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($records as $record): ?>
          <?php $key = $record->entity_type . '/' . $record->entity_id; ?>
          <tr>
            <td>
              <strong><?php echo htmlspecialchars($entityNames[$key] ?? '(unknown)'); ?></strong>
              <br><small class="text-muted"><?php echo $record->entity_type; ?> #<?php echo $record->entity_id; ?></small>
            </td>
            <td class="small"><code><?php echo htmlspecialchars(substr($record->ric_uri, -40)); ?></code></td>
            <td>
              <?php $colors = ['synced' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'deleted' => 'secondary', 'orphaned' => 'info']; ?>
              <span class="badge bg-<?php echo $colors[$record->sync_status] ?? 'secondary'; ?>"><?php echo $record->sync_status; ?></span>
            </td>
            <td class="small text-muted"><?php echo $record->last_synced_at ? date('Y-m-d H:i', strtotime($record->last_synced_at)) : '-'; ?></td>
            <td>
              <button class="btn btn-sm btn-outline-primary" onclick="resync('<?php echo $record->entity_type; ?>', <?php echo $record->entity_id; ?>)">
                <i class="fa fa-sync"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php end_slot(); ?>

<?php slot('after-content'); ?>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function resync(entityType, entityId) {
  fetch('<?php echo url_for(['module' => 'ricDashboard', 'action' => 'ajaxResync']); ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'entity_type=' + entityType + '&entity_id=' + entityId
  }).then(r => r.json()).then(data => {
    if (data.success) location.reload();
    else alert('Error: ' + data.error);
  });
}
</script>
<?php end_slot(); ?>

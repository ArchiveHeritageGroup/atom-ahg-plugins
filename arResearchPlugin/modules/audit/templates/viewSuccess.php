<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-history text-primary me-2"></i>Audit Entry #<?php echo $entry->id; ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="row">
  <div class="col-md-6">
    <div class="card mb-4">
      <div class="card-header"><i class="fas fa-info-circle me-2"></i>Entry Details</div>
      <div class="card-body">
        <table class="table table-bordered mb-0">
          <tr><th style="width:30%;">Date/Time</th><td><?php echo $entry->created_at; ?></td></tr>
          <tr><th>User</th><td><?php echo htmlspecialchars($entry->user_name ?? 'System'); ?></td></tr>
          <tr><th>Action</th><td><span class="badge bg-<?php echo match($entry->action) { 'create'=>'success', 'update'=>'warning', 'delete'=>'danger', default=>'secondary' }; ?>"><?php echo ucfirst($entry->action); ?></span></td></tr>
          <tr><th>Table</th><td><code><?php echo $entry->table_name; ?></code></td></tr>
          <tr><th>Record ID</th><td>#<?php echo $entry->record_id; ?></td></tr>
          <?php if ($entry->field_name): ?><tr><th>Field</th><td><code><?php echo $entry->field_name; ?></code></td></tr><?php endif; ?>
          <tr><th>IP Address</th><td><?php echo $entry->ip_address ?? 'N/A'; ?></td></tr>
          <tr><th>Description</th><td><?php echo htmlspecialchars($entry->action_description ?? 'N/A'); ?></td></tr>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <?php if ($entry->field_name): ?>
      <div class="card mb-4">
        <div class="card-header"><i class="fas fa-exchange-alt me-2"></i>Value Change</div>
        <div class="card-body">
          <div class="row">
            <div class="col-6">
              <label class="text-muted">Old Value</label>
              <div class="bg-light border rounded p-2"><pre class="mb-0 text-danger"><?php echo htmlspecialchars($entry->old_value ?? '(empty)'); ?></pre></div>
            </div>
            <div class="col-6">
              <label class="text-muted">New Value</label>
              <div class="bg-light border rounded p-2"><pre class="mb-0 text-success"><?php echo htmlspecialchars($entry->new_value ?? '(empty)'); ?></pre></div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
    <?php if (!empty($changes)): ?>
      <div class="card mb-4">
        <div class="card-header"><i class="fas fa-list me-2"></i>All Changes</div>
        <div class="card-body p-0">
          <table class="table table-striped mb-0">
            <thead><tr><th>Field</th><th>Old</th><th>New</th></tr></thead>
            <tbody>
              <?php foreach ($changes as $field => $change): ?>
                <tr>
                  <td><code><?php echo $field; ?></code></td>
                  <td class="text-danger small"><?php echo htmlspecialchars(substr((string)$change['old'], 0, 50)); ?></td>
                  <td class="text-success small"><?php echo htmlspecialchars(substr((string)$change['new'], 0, 50)); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
<a href="<?php echo url_for(['module' => 'audit', 'action' => 'index']); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Audit Log</a>
<?php end_slot() ?>

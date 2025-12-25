<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-user text-primary me-2"></i>User Activity: <?php echo htmlspecialchars($user->username); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="row mb-4">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Activity by Table</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($tableStats as $stat): ?>
          <li class="list-group-item d-flex justify-content-between"><?php echo $stat->table_name; ?><span class="badge bg-primary"><?php echo $stat->count; ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Activity by Action</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($actionStats as $stat): ?>
          <li class="list-group-item d-flex justify-content-between"><span class="badge bg-<?php echo match($stat->action) { 'create'=>'success', 'update'=>'warning', 'delete'=>'danger', default=>'secondary' }; ?>"><?php echo ucfirst($stat->action); ?></span><span class="badge bg-primary"><?php echo $stat->count; ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>
<div class="card">
  <div class="card-header">Recent Activity (<?php echo $totalCount; ?> total)</div>
  <div class="table-responsive">
    <table class="table table-striped mb-0">
      <thead><tr><th>Date</th><th>Action</th><th>Table</th><th>Record</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($activity as $entry): ?>
          <tr>
            <td class="small"><?php echo $entry->created_at; ?></td>
            <td><span class="badge bg-<?php echo match($entry->action) { 'create'=>'success', 'update'=>'warning', 'delete'=>'danger', default=>'secondary' }; ?>"><?php echo ucfirst($entry->action); ?></span></td>
            <td><code><?php echo $entry->table_name; ?></code></td>
            <td>#<?php echo $entry->record_id; ?></td>
            <td><a href="<?php echo url_for(['module' => 'audit', 'action' => 'view', 'id' => $entry->id]); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="mt-3"><a href="<?php echo url_for(['module' => 'audit', 'action' => 'index']); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a></div>
<?php end_slot() ?>

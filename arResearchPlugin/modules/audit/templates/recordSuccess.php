<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-history text-primary me-2"></i>History: <?php echo $tableName; ?> #<?php echo $recordId; ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if (empty($history)): ?>
  <div class="alert alert-info">No history found for this record.</div>
<?php else: ?>
  <?php foreach ($timeline as $date => $entries): ?>
    <div class="card mb-3">
      <div class="card-header bg-light"><i class="fas fa-calendar me-2"></i><?php echo $date; ?></div>
      <ul class="list-group list-group-flush">
        <?php foreach ($entries as $entry): ?>
          <li class="list-group-item">
            <span class="badge bg-<?php echo match($entry->action) { 'create'=>'success', 'update'=>'warning', 'delete'=>'danger', default=>'secondary' }; ?>"><?php echo ucfirst($entry->action); ?></span>
            <small class="text-muted ms-2"><?php echo date('H:i:s', strtotime($entry->created_at)); ?></small>
            <span class="ms-2">by <strong><?php echo htmlspecialchars($entry->user_name ?? 'System'); ?></strong></span>
            <?php if ($entry->field_name): ?>
              <br><small><code><?php echo $entry->field_name; ?></code>: <span class="text-danger"><?php echo htmlspecialchars(substr($entry->old_value ?? '', 0, 30)); ?></span> → <span class="text-success"><?php echo htmlspecialchars(substr($entry->new_value ?? '', 0, 30)); ?></span></small>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
<a href="<?php echo url_for(['module' => 'audit', 'action' => 'index']); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
<?php end_slot() ?>

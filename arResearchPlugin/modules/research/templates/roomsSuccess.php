<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-door-open text-primary me-2"></i><?php echo __('Reading Rooms'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><?php echo __('Manage Reading Rooms'); ?></span>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'editRoom']); ?>" class="btn btn-sm btn-primary">
      <i class="fas fa-plus me-1"></i><?php echo __('Add Room'); ?>
    </a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th><?php echo __('Name'); ?></th>
          <th><?php echo __('Code'); ?></th>
          <th><?php echo __('Location'); ?></th>
          <th><?php echo __('Capacity'); ?></th>
          <th><?php echo __('Hours'); ?></th>
          <th><?php echo __('Status'); ?></th>
          <th width="100"><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rooms)): ?>
        <tr>
          <td colspan="7" class="text-center text-muted py-4"><?php echo __('No reading rooms configured'); ?></td>
        </tr>
        <?php else: ?>
          <?php foreach ($rooms as $room): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($room->name); ?></strong></td>
            <td><code><?php echo htmlspecialchars($room->code ?? '-'); ?></code></td>
            <td><?php echo htmlspecialchars($room->location ?? '-'); ?></td>
            <td><?php echo $room->capacity ?? '-'; ?></td>
            <td>
              <small>
                <?php echo substr($room->opening_time ?? '09:00', 0, 5); ?> - 
                <?php echo substr($room->closing_time ?? '17:00', 0, 5); ?>
              </small>
            </td>
            <td>
              <?php if ($room->is_active): ?>
                <span class="badge bg-success"><?php echo __('Active'); ?></span>
              <?php else: ?>
                <span class="badge bg-secondary"><?php echo __('Inactive'); ?></span>
              <?php endif; ?>
            </td>
            <td>
              <a href="<?php echo url_for(['module' => 'research', 'action' => 'editRoom', 'id' => $room->id]); ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-edit"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php end_slot() ?>

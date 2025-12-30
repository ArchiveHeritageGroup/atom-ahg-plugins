<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
  <div class="sidebar-widget">
    <h3><?php echo __('Embargo Management'); ?></h3>
    <ul class="nav nav-pills nav-stacked">
      <li class="active"><a href="<?php echo url_for(['module' => 'embargo', 'action' => 'index']); ?>"><?php echo __('Dashboard'); ?></a></li>
    </ul>
  </div>
  
  <div class="sidebar-widget">
    <h3><?php echo __('Quick Stats'); ?></h3>
    <ul class="list-unstyled">
      <li><strong><?php echo count($activeEmbargoes); ?></strong> <?php echo __('Active Embargoes'); ?></li>
      <li><strong><?php echo count($expiringEmbargoes); ?></strong> <?php echo __('Expiring Soon'); ?></li>
    </ul>
  </div>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Embargo Management'); ?></h1>
<?php end_slot(); ?>

<!-- Expiring Soon Alert -->
<?php if (count($expiringEmbargoes) > 0): ?>
<div class="alert alert-warning">
  <h5><i class="fas fa-exclamation-triangle"></i> <?php echo __('Embargoes Expiring Within 30 Days'); ?></h5>
  <ul class="mb-0">
    <?php foreach ($expiringEmbargoes->take(5) as $embargo): ?>
      <li>
        <a href="<?php echo url_for(['module' => 'embargo', 'action' => 'view', 'id' => $embargo->id]); ?>">
          <?php echo __('Object #%1%', ['%1%' => $embargo->object_id]); ?>
        </a>
        - <?php echo __('Expires: %1%', ['%1%' => $embargo->end_date->format('Y-m-d')]); ?>
        (<?php echo $embargo->days_remaining; ?> <?php echo __('days'); ?>)
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<!-- Active Embargoes -->
<div class="card">
  <div class="card-header">
    <h4 class="mb-0"><?php echo __('Active Embargoes'); ?></h4>
  </div>
  <div class="card-body p-0">
    <?php if (count($activeEmbargoes) > 0): ?>
      <table class="table table-striped table-hover mb-0">
        <thead>
          <tr>
            <th><?php echo __('Object'); ?></th>
            <th><?php echo __('Type'); ?></th>
            <th><?php echo __('Start Date'); ?></th>
            <th><?php echo __('End Date'); ?></th>
            <th><?php echo __('Reason'); ?></th>
            <th><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($activeEmbargoes as $embargo): ?>
            <tr>
              <td>
                <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse', 'id' => $embargo->object_id]); ?>">
                  #<?php echo $embargo->object_id; ?>
                </a>
              </td>
              <td>
                <span class="badge bg-<?php echo $embargo->embargo_type === 'full' ? 'danger' : 'warning'; ?>">
                  <?php echo ucfirst(str_replace('_', ' ', $embargo->embargo_type)); ?>
                </span>
              </td>
              <td><?php echo $embargo->start_date->format('Y-m-d'); ?></td>
              <td>
                <?php if ($embargo->is_perpetual): ?>
                  <span class="text-danger"><?php echo __('Perpetual'); ?></span>
                <?php elseif ($embargo->end_date): ?>
                  <?php echo $embargo->end_date->format('Y-m-d'); ?>
                  <?php if ($embargo->days_remaining <= 30): ?>
                    <span class="badge bg-warning"><?php echo $embargo->days_remaining; ?>d</span>
                  <?php endif; ?>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
              <td><?php echo esc_entities($embargo->reason ?? '-'); ?></td>
              <td>
                <a href="<?php echo url_for(['module' => 'embargo', 'action' => 'view', 'id' => $embargo->id]); ?>" class="btn btn-sm btn-outline-primary">
                  <?php echo __('View'); ?>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="text-muted text-center py-4"><?php echo __('No active embargoes.'); ?></p>
    <?php endif; ?>
  </div>
</div>

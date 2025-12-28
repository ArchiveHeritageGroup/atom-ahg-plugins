<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-chart-line me-2"></i><?php echo __('Audit Dashboard'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="fas fa-filter me-1"></i><?php echo __('Time Period'); ?></span>
    <div>
      <a href="<?php echo url_for(['module' => 'ahgAuditTrailPlugin', 'action' => 'browse']); ?>" class="btn btn-sm btn-primary">
        <i class="fas fa-exchange-alt me-1"></i><?php echo __('View Record Changes'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'ahgAuditTrail', 'action' => 'index']); ?>" class="btn btn-sm btn-outline-primary">
        <i class="fas fa-list me-1"></i><?php echo __('View All Logs'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'ahgAuditTrailPlugin', 'action' => 'statistics']); ?>" class="btn btn-sm btn-outline-info">
        <i class="fas fa-chart-bar me-1"></i><?php echo __('Statistics'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'ahgAuditTrail', 'action' => 'settings']); ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-cog me-1"></i><?php echo __('Settings'); ?>
      </a>
    </div>
  </div>
  <div class="card-body">
    <form method="get" class="row g-3 align-items-end">
      <div class="col-auto">
        <label class="form-label"><?php echo __('Show data from last'); ?></label>
        <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="1" <?php echo $period == '1' ? 'selected' : ''; ?>>1 <?php echo __('day'); ?></option>
          <option value="7" <?php echo $period == '7' ? 'selected' : ''; ?>>7 <?php echo __('days'); ?></option>
          <option value="14" <?php echo $period == '14' ? 'selected' : ''; ?>>14 <?php echo __('days'); ?></option>
          <option value="30" <?php echo $period == '30' ? 'selected' : ''; ?>>30 <?php echo __('days'); ?></option>
          <option value="90" <?php echo $period == '90' ? 'selected' : ''; ?>>90 <?php echo __('days'); ?></option>
          <option value="365" <?php echo $period == '365' ? 'selected' : ''; ?>>1 <?php echo __('year'); ?></option>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-sync-alt"></i> <?php echo __('Refresh'); ?></button>
      </div>
    </form>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-3">
    <div class="card text-center h-100">
      <div class="card-body">
        <h2 class="text-primary"><?php echo number_format($totalActions); ?></h2>
        <p class="mb-0"><?php echo __('Total Actions'); ?></p>
        <small class="text-muted"><?php echo __('Last %1% days', ['%1%' => $period]); ?></small>
      </div>
    </div>
  </div>
  <div class="col-md-9">
    <div class="card h-100">
      <div class="card-header"><?php echo __('Actions by Type'); ?></div>
      <div class="card-body">
        <?php if (count($actionsByType) > 0): ?>
          <?php foreach ($actionsByType as $item): ?>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span>
              <span class="badge bg-<?php
                echo $item->action == 'delete' ? 'danger' :
                    ($item->action == 'create' ? 'success' :
                    ($item->action == 'login' ? 'info' :
                    ($item->action == 'logout' ? 'warning' : 'primary')));
              ?>"><?php echo $item->action; ?></span>
            </span>
            <span class="badge bg-secondary"><?php echo number_format($item->count); ?></span>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted mb-0"><?php echo __('No activity in this period'); ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header"><?php echo __('Top Users'); ?></div>
      <ul class="list-group list-group-flush">
        <?php if (count($actionsByUser) > 0): ?>
          <?php foreach ($actionsByUser as $item): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>
              <i class="fas fa-user me-2 text-muted"></i>
              <?php echo htmlspecialchars($item->username ?? 'anonymous'); ?>
            </span>
            <span class="badge bg-primary rounded-pill"><?php echo number_format($item->count); ?></span>
          </li>
          <?php endforeach; ?>
        <?php else: ?>
          <li class="list-group-item text-muted"><?php echo __('No activity in this period'); ?></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><?php echo __('Recent Activity'); ?></span>
        <a href="<?php echo url_for(['module' => 'ahgAuditTrail', 'action' => 'index']); ?>" class="btn btn-sm btn-link p-0"><?php echo __('View all'); ?></a>
      </div>
      <ul class="list-group list-group-flush">
        <?php if (count($recentActivity) > 0): ?>
          <?php foreach ($recentActivity as $log): ?>
          <li class="list-group-item py-2">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <strong><?php echo htmlspecialchars($log->username ?? 'anonymous'); ?></strong>
                <span class="badge bg-<?php
                  echo $log->action == 'delete' ? 'danger' :
                      ($log->action == 'create' ? 'success' :
                      ($log->action == 'login' ? 'info' :
                      ($log->action == 'logout' ? 'warning' : 'primary')));
                ?> ms-1"><?php echo $log->action; ?></span>
                <br>
                <small class="text-muted"><?php echo $log->entity_type; ?></small>
              </div>
              <small class="text-muted"><?php echo date('M j, H:i', strtotime($log->created_at)); ?></small>
            </div>
          </li>
          <?php endforeach; ?>
        <?php else: ?>
          <li class="list-group-item text-muted"><?php echo __('No recent activity'); ?></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>
<?php end_slot() ?>

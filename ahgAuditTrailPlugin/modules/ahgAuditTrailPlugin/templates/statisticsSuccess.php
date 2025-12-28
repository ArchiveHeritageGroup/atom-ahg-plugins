<?php decorate_with('layout_1col') ?>

<?php slot('title') ?>
  <h1><?php echo __('Audit Statistics') ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php 
  // Convert escaped arrays to raw arrays
  $activitySummaryRaw = $sf_data->getRaw('activitySummary');
  $userStatsRaw = $sf_data->getRaw('userStats');
  $failedActionsRaw = $sf_data->getRaw('failedActions');
  $dateRangeRaw = $sf_data->getRaw('dateRange');
?>

<div class="mb-4">
  <form method="get" class="d-inline-flex gap-2 align-items-center">
    <label class="form-label mb-0"><?php echo __('Time Period:') ?></label>
    <select name="days" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
      <option value="7" <?php echo $dateRangeRaw['days'] == 7 ? 'selected' : '' ?>><?php echo __('Last 7 days') ?></option>
      <option value="30" <?php echo $dateRangeRaw['days'] == 30 ? 'selected' : '' ?>><?php echo __('Last 30 days') ?></option>
      <option value="90" <?php echo $dateRangeRaw['days'] == 90 ? 'selected' : '' ?>><?php echo __('Last 90 days') ?></option>
    </select>
  </form>
</div>

<div class="row mb-4">
  <?php $totalActions = array_sum($activitySummaryRaw); ?>
  <div class="col-md-3 mb-3">
    <div class="card h-100 border-primary">
      <div class="card-body text-center">
        <h3 class="text-primary"><?php echo number_format($totalActions) ?></h3>
        <p class="mb-0 text-muted"><?php echo __('Total Actions') ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card h-100 border-success">
      <div class="card-body text-center">
        <h3 class="text-success"><?php echo number_format($activitySummaryRaw['create'] ?? 0) ?></h3>
        <p class="mb-0 text-muted"><?php echo __('Created') ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card h-100 border-info">
      <div class="card-body text-center">
        <h3 class="text-info"><?php echo number_format($activitySummaryRaw['update'] ?? 0) ?></h3>
        <p class="mb-0 text-muted"><?php echo __('Updated') ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card h-100 border-danger">
      <div class="card-body text-center">
        <h3 class="text-danger"><?php echo number_format($activitySummaryRaw['delete'] ?? 0) ?></h3>
        <p class="mb-0 text-muted"><?php echo __('Deleted') ?></p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-6 mb-4">
    <div class="card">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Most Active Users') ?></h5></div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr><th><?php echo __('User') ?></th><th class="text-end"><?php echo __('Actions') ?></th></tr></thead>
          <tbody>
            <?php foreach ($userStatsRaw as $user): ?>
            <tr>
              <td><a href="<?php echo url_for(['module' => 'ahgAuditTrailPlugin', 'action' => 'userActivity', 'user_id' => $user->user_id]) ?>"><?php echo htmlspecialchars($user->username ?? 'Unknown') ?></a></td>
              <td class="text-end"><?php echo number_format($user->action_count) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($userStatsRaw) === 0): ?>
            <tr><td colspan="2" class="text-center text-muted"><?php echo __('No activity') ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-6 mb-4">
    <div class="card">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Recent Failed Actions') ?></h5></div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr><th><?php echo __('Time') ?></th><th><?php echo __('User') ?></th><th><?php echo __('Action') ?></th></tr></thead>
          <tbody>
            <?php foreach ($failedActionsRaw as $log): ?>
            <tr>
              <td><small><?php echo $log->created_at->format('M j, H:i') ?></small></td>
              <td><?php echo htmlspecialchars($log->username ?? 'Anonymous') ?></td>
              <td><span class="badge bg-danger"><?php echo $log->action_label ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($failedActionsRaw) === 0): ?>
            <tr><td colspan="3" class="text-center text-muted"><?php echo __('No failed actions') ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="mt-4">
  <a href="<?php echo url_for(['module' => 'ahgAuditTrailPlugin', 'action' => 'browse']) ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo __('Back to Audit Trail') ?></a>
</div>
<?php end_slot() ?>
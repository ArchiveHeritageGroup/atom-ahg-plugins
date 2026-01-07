<?php decorate_with('layout_2col') ?>

<?php slot('title') ?>
  <h1><?php echo __('Audit Trail') ?></h1>
<?php end_slot() ?>

<?php slot('sidebar') ?>
<?php
  $actionTypesRaw = $sf_data->getRaw('actionTypes');
  $entityTypesRaw = $sf_data->getRaw('entityTypes');
  $currentFiltersRaw = $sf_data->getRaw('currentFilters');
?>
<section id="facets">
  <div class="sidebar-lowering">
    <h3><?php echo __('Filter Audit Logs') ?></h3>
    <form method="get" action="<?php echo url_for(['module' => 'ahgAuditTrailPlugin', 'action' => 'browse']) ?>">
      <div class="mb-3">
        <label class="form-label"><?php echo __('Action Type') ?></label>
        <select name="filter_action" class="form-select form-select-sm">
          <option value=""><?php echo __('All Actions') ?></option>
          <?php foreach ($actionTypesRaw as $value => $label): ?>
            <option value="<?php echo $value ?>" <?php echo ($currentFiltersRaw['action'] ?? '') === $value ? 'selected' : '' ?>><?php echo __($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label"><?php echo __('Entity Type') ?></label>
        <select name="entity_type" class="form-select form-select-sm">
          <option value=""><?php echo __('All Types') ?></option>
          <?php foreach ($entityTypesRaw as $value => $label): ?>
            <option value="<?php echo $value ?>" <?php echo ($currentFiltersRaw['entity_type'] ?? '') === $value ? 'selected' : '' ?>><?php echo __($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label"><?php echo __('Username') ?></label>
        <input type="text" name="username" class="form-control form-control-sm" value="<?php echo htmlspecialchars($currentFiltersRaw['username'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label"><?php echo __('From Date') ?></label>
        <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo $currentFiltersRaw['from_date'] ?? '' ?>">
      </div>
      <div class="mb-3">
        <label class="form-label"><?php echo __('To Date') ?></label>
        <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo $currentFiltersRaw['to_date'] ?? '' ?>">
      </div>
      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary btn-sm"><?php echo __('Apply Filters') ?></button>
        <a href="<?php echo url_for(['module' => 'ahgAuditTrailPlugin', 'action' => 'browse']) ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('Clear') ?></a>
      </div>
    </form>
    <hr class="my-4">
    <h4><?php echo __('Quick Links') ?></h4>
    <ul class="list-unstyled">
      <li><a href="<?php echo url_for(['module' => 'ahgAuditTrailPlugin', 'action' => 'authentication']) ?>"><?php echo __('Authentication Log') ?></a></li>
      <li><a href="<?php echo url_for(['module' => 'ahgAuditTrailPlugin', 'action' => 'securityAccess']) ?>"><?php echo __('Security Access Log') ?></a></li>
      <li><a href="<?php echo url_for(['module' => 'ahgAuditTrailPlugin', 'action' => 'statistics']) ?>"><?php echo __('Statistics Dashboard') ?></a></li>
      <li><a href="<?php echo url_for(['module' => 'ahgAuditTrailPlugin', 'action' => 'settings']) ?>"><?php echo __('Settings') ?></a></li>
    </ul>
  </div>
</section>
<?php end_slot() ?>

<?php slot('content') ?>
<?php $pagerRaw = $sf_data->getRaw('pager'); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <span class="text-muted"><?php echo __('Showing %1% to %2% of %3% results', ['%1%' => $pagerRaw['from'], '%2%' => $pagerRaw['to'], '%3%' => $pagerRaw['total']]) ?></span>
  <div class="btn-group btn-group-sm">
    <a href="<?php echo url_for(['module' => 'ahgAuditTrailPlugin', 'action' => 'export', 'format' => 'csv']) ?>" class="btn btn-outline-secondary"><?php echo __('Export CSV') ?></a>
    <a href="<?php echo url_for(['module' => 'ahgAuditTrailPlugin', 'action' => 'export', 'format' => 'json']) ?>" class="btn btn-outline-secondary"><?php echo __('Export JSON') ?></a>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-striped table-hover table-sm">
    <thead class="table-light">
      <tr>
        <th><?php echo __('Date/Time') ?></th>
        <th><?php echo __('User') ?></th>
        <th><?php echo __('Action') ?></th>
        <th><?php echo __('Entity') ?></th>
        <th><?php echo __('Title') ?></th>
        <th><?php echo __('IP') ?></th>
        <th class="text-end"><?php echo __('Actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($pagerRaw['data'] as $log): ?>
        <tr class="<?php echo $log->status !== 'success' ? 'table-warning' : '' ?>">
          <td><small><?php echo $log->created_at->format('Y-m-d H:i:s') ?></small></td>
          <td><?php echo htmlspecialchars($log->username ?? 'Anonymous') ?></td>
          <td><span class="badge bg-<?php echo match($log->action) { 'create' => 'success', 'update' => 'primary', 'delete' => 'danger', default => 'secondary' } ?>"><?php echo $log->action_label ?></span></td>
          <td><?php echo $log->entity_type_label ?></td>
          <td><?php echo htmlspecialchars(mb_substr($log->entity_title ?? $log->entity_slug ?? '-', 0, 40)) ?></td>
          <td><small><?php echo $log->ip_address ?? '-' ?></small></td>
          <td class="text-end">
            <div class="btn-group btn-group-sm">
              <?php if (($log->action === 'update' || $log->action === 'create') && ($log->old_values || $log->new_values)): ?>
                <button type="button" class="btn btn-outline-warning" onclick="showAuditCompare(<?php echo $log->id ?>)" title="<?php echo __('Compare Changes') ?>">
                  <i class="fas fa-exchange-alt"></i>
                </button>
              <?php endif; ?>
              <a href="<?php echo url_for(['module' => 'ahgAuditTrailPlugin', 'action' => 'view', 'uuid' => $log->uuid]) ?>" class="btn btn-outline-primary" title="<?php echo __('View Details') ?>">
                <i class="fas fa-eye"></i>
              </a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if ($pagerRaw['total'] === 0): ?>
        <tr><td colspan="7" class="text-center text-muted py-4"><?php echo __('No audit log entries found.') ?></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($pagerRaw['last_page'] > 1): ?>
<nav><ul class="pagination pagination-sm justify-content-center">
  <?php if ($pagerRaw['current_page'] > 1): ?>
    <li class="page-item">
      <a class="page-link" href="<?php echo url_for(['module' => 'ahgAuditTrailPlugin', 'action' => 'browse', 'page' => $pagerRaw['current_page'] - 1]) ?>">&laquo;</a>
    </li>
  <?php endif; ?>

  <?php for ($i = max(1, $pagerRaw['current_page'] - 3); $i <= min($pagerRaw['last_page'], $pagerRaw['current_page'] + 3); $i++): ?>
    <li class="page-item <?php echo $i === $pagerRaw['current_page'] ? 'active' : '' ?>">
      <a class="page-link" href="<?php echo url_for(['module' => 'ahgAuditTrailPlugin', 'action' => 'browse', 'page' => $i]) ?>"><?php echo $i ?></a>
    </li>
  <?php endfor; ?>

  <?php if ($pagerRaw['current_page'] < $pagerRaw['last_page']): ?>
    <li class="page-item">
      <a class="page-link" href="<?php echo url_for(['module' => 'ahgAuditTrailPlugin', 'action' => 'browse', 'page' => $pagerRaw['current_page'] + 1]) ?>">&raquo;</a>
    </li>
  <?php endif; ?>
</ul></nav>
<?php endif; ?>

<?php include_partial('ahgAuditTrailPlugin/compareModal') ?>

<?php end_slot() ?>

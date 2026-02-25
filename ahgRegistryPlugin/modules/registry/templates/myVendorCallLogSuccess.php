<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Call & Issue Log'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Vendor Dashboard'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorDashboard'])],
  ['label' => __('Call & Issue Log')],
]]); ?>

<?php $sfUser = sfContext::getInstance()->getUser(); ?>
<?php if ($sfUser->hasFlash('success')): ?>
  <div class="alert alert-success alert-dismissible fade show"><?php echo $sfUser->getFlash('success'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="fas fa-phone-alt me-2"></i><?php echo __('Call & Issue Log'); ?></h1>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorCallLogAdd']); ?>" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> <?php echo __('New Entry'); ?>
  </a>
</div>

<!-- Stats cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-4">
    <div class="card text-center h-100 border-warning">
      <div class="card-body py-2">
        <div class="h4 mb-0 text-warning"><?php echo (int) sfOutputEscaper::unescape($totalOpen); ?></div>
        <small class="text-muted"><?php echo __('Open'); ?></small>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="card text-center h-100 border-success">
      <div class="card-body py-2">
        <div class="h4 mb-0 text-success"><?php echo (int) sfOutputEscaper::unescape($totalResolved); ?></div>
        <small class="text-muted"><?php echo __('Resolved'); ?></small>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="card text-center h-100 border-danger">
      <div class="card-body py-2">
        <div class="h4 mb-0 text-danger"><?php echo (int) sfOutputEscaper::unescape($overdueFollowUps); ?></div>
        <small class="text-muted"><?php echo __('Overdue Follow-ups'); ?></small>
      </div>
    </div>
  </div>
</div>

<!-- Filters -->
<div class="card mb-4">
  <div class="card-body py-2">
    <form method="get" action="" class="row g-2 align-items-end">
      <div class="col-auto">
        <label class="form-label small mb-0"><?php echo __('Status'); ?></label>
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value=""><?php echo __('All'); ?></option>
          <?php foreach (['open', 'in_progress', 'resolved', 'closed', 'escalated'] as $s): ?>
            <option value="<?php echo $s; ?>"<?php echo ($filterStatus == $s) ? ' selected' : ''; ?>><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $s)), ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <label class="form-label small mb-0"><?php echo __('Type'); ?></label>
        <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value=""><?php echo __('All'); ?></option>
          <?php foreach (['call', 'email', 'meeting', 'support_ticket', 'site_visit', 'video_call', 'other'] as $t): ?>
            <option value="<?php echo $t; ?>"<?php echo ($filterType == $t) ? ' selected' : ''; ?>><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $t)), ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <label class="form-label small mb-0"><?php echo __('Priority'); ?></label>
        <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value=""><?php echo __('All'); ?></option>
          <?php foreach (['low', 'medium', 'high', 'urgent'] as $p): ?>
            <option value="<?php echo $p; ?>"<?php echo ($filterPriority == $p) ? ' selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($p), ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($filterStatus || $filterType || $filterPriority): ?>
      <div class="col-auto">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorCallLog']); ?>" class="btn btn-sm btn-outline-secondary"><?php echo __('Clear'); ?></a>
      </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Log entries -->
<?php
  $rawLogs = sfOutputEscaper::unescape($logs);
  $statusColors = ['open' => 'warning', 'in_progress' => 'info', 'resolved' => 'success', 'closed' => 'secondary', 'escalated' => 'danger'];
  $priorityColors = ['low' => 'secondary', 'medium' => 'primary', 'high' => 'warning', 'urgent' => 'danger'];
  $typeIcons = ['call' => 'fa-phone-alt', 'email' => 'fa-envelope', 'meeting' => 'fa-users', 'support_ticket' => 'fa-ticket-alt', 'site_visit' => 'fa-map-marker-alt', 'video_call' => 'fa-video', 'other' => 'fa-ellipsis-h'];
?>

<?php if (!empty($rawLogs) && count($rawLogs) > 0): ?>
<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th style="width: 40px;"></th>
        <th><?php echo __('Subject'); ?></th>
        <th><?php echo __('Contact'); ?></th>
        <th><?php echo __('Status'); ?></th>
        <th><?php echo __('Priority'); ?></th>
        <th><?php echo __('Follow-up'); ?></th>
        <th><?php echo __('Date'); ?></th>
        <th style="width: 90px;"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rawLogs as $log): ?>
        <?php
          $sc = $statusColors[$log->status ?? 'open'] ?? 'secondary';
          $pc = $priorityColors[$log->priority ?? 'medium'] ?? 'primary';
          $icon = $typeIcons[$log->interaction_type ?? 'other'] ?? 'fa-ellipsis-h';
          $isOverdue = !empty($log->follow_up_date) && $log->follow_up_date < date('Y-m-d') && !in_array($log->status, ['resolved', 'closed']);
        ?>
        <tr<?php echo $isOverdue ? ' class="table-danger"' : ''; ?>>
          <td class="text-center">
            <i class="fas <?php echo $icon; ?> text-muted" title="<?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $log->interaction_type ?? '')), ENT_QUOTES, 'UTF-8'); ?>"></i>
            <?php if ($log->direction === 'inbound'): ?>
              <i class="fas fa-arrow-down text-info" style="font-size: 0.6em;" title="Inbound"></i>
            <?php else: ?>
              <i class="fas fa-arrow-up text-success" style="font-size: 0.6em;" title="Outbound"></i>
            <?php endif; ?>
          </td>
          <td>
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorCallLogView', 'id' => (int) $log->id]); ?>" class="fw-semibold text-decoration-none">
              <?php echo htmlspecialchars($log->subject, ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php if (!empty($log->description)): ?>
              <br><small class="text-muted"><?php echo htmlspecialchars(mb_strimwidth($log->description, 0, 80, '...'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($log->contact_name)): ?>
              <small><?php echo htmlspecialchars($log->contact_name, ENT_QUOTES, 'UTF-8'); ?></small>
            <?php else: ?>
              <small class="text-muted">-</small>
            <?php endif; ?>
          </td>
          <td><span class="badge bg-<?php echo $sc; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $log->status ?? 'open')), ENT_QUOTES, 'UTF-8'); ?></span></td>
          <td><span class="badge bg-<?php echo $pc; ?>"><?php echo htmlspecialchars(ucfirst($log->priority ?? 'medium'), ENT_QUOTES, 'UTF-8'); ?></span></td>
          <td>
            <?php if (!empty($log->follow_up_date)): ?>
              <small class="<?php echo $isOverdue ? 'text-danger fw-bold' : 'text-muted'; ?>">
                <?php echo $isOverdue ? '<i class="fas fa-exclamation-triangle me-1"></i>' : ''; ?>
                <?php echo date('M j', strtotime($log->follow_up_date)); ?>
              </small>
            <?php else: ?>
              <small class="text-muted">-</small>
            <?php endif; ?>
          </td>
          <td><small class="text-muted"><?php echo date('M j, H:i', strtotime($log->created_at)); ?></small></td>
          <td class="text-end">
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorCallLogView', 'id' => (int) $log->id]); ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('View'); ?>"><i class="fas fa-eye"></i></a>
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorCallLogEdit', 'id' => (int) $log->id]); ?>" class="btn btn-sm btn-outline-secondary" title="<?php echo __('Edit'); ?>"><i class="fas fa-edit"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<div class="text-center py-5 text-muted">
  <i class="fas fa-phone-alt fa-3x mb-3"></i>
  <p><?php echo __('No call log entries yet.'); ?></p>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorCallLogAdd']); ?>" class="btn btn-primary"><?php echo __('Log Your First Interaction'); ?></a>
</div>
<?php endif; ?>

<?php end_slot(); ?>

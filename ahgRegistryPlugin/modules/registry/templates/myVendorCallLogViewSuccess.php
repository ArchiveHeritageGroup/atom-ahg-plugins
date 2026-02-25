<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Call Log Entry'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php $e = sfOutputEscaper::unescape($entry); ?>
<?php $inst = sfOutputEscaper::unescape($institution); ?>
<?php $v = sfOutputEscaper::unescape($vendor); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Vendor Dashboard'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorDashboard'])],
  ['label' => __('Call & Issue Log'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorCallLog'])],
  ['label' => htmlspecialchars(mb_strimwidth($e->subject, 0, 40, '...'), ENT_QUOTES, 'UTF-8')],
]]); ?>

<?php
  $statusColors = ['open' => 'warning', 'in_progress' => 'info', 'resolved' => 'success', 'closed' => 'secondary', 'escalated' => 'danger'];
  $priorityColors = ['low' => 'secondary', 'medium' => 'primary', 'high' => 'warning', 'urgent' => 'danger'];
  $typeIcons = ['call' => 'fa-phone-alt', 'email' => 'fa-envelope', 'meeting' => 'fa-users', 'support_ticket' => 'fa-ticket-alt', 'site_visit' => 'fa-map-marker-alt', 'video_call' => 'fa-video', 'other' => 'fa-ellipsis-h'];
  $sc = $statusColors[$e->status ?? 'open'] ?? 'secondary';
  $pc = $priorityColors[$e->priority ?? 'medium'] ?? 'primary';
  $icon = $typeIcons[$e->interaction_type ?? 'other'] ?? 'fa-ellipsis-h';
?>

<div class="d-flex justify-content-between align-items-start mb-4">
  <div>
    <h1 class="h3 mb-1">
      <i class="fas <?php echo $icon; ?> me-2 text-muted"></i>
      <?php echo htmlspecialchars($e->subject, ENT_QUOTES, 'UTF-8'); ?>
    </h1>
    <div class="d-flex gap-2 align-items-center">
      <span class="badge bg-<?php echo $sc; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $e->status ?? 'open')), ENT_QUOTES, 'UTF-8'); ?></span>
      <span class="badge bg-<?php echo $pc; ?>"><?php echo htmlspecialchars(ucfirst($e->priority ?? 'medium'), ENT_QUOTES, 'UTF-8'); ?></span>
      <span class="badge bg-light text-dark border"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $e->interaction_type ?? '')), ENT_QUOTES, 'UTF-8'); ?></span>
      <?php if ($e->direction === 'inbound'): ?>
        <span class="badge bg-info"><i class="fas fa-arrow-down me-1"></i><?php echo __('Inbound'); ?></span>
      <?php else: ?>
        <span class="badge bg-light text-dark border"><i class="fas fa-arrow-up me-1"></i><?php echo __('Outbound'); ?></span>
      <?php endif; ?>
    </div>
  </div>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorCallLogEdit', 'id' => (int) $e->id]); ?>" class="btn btn-primary btn-sm">
    <i class="fas fa-edit me-1"></i> <?php echo __('Edit'); ?>
  </a>
</div>

<div class="row">
  <!-- Main content -->
  <div class="col-lg-8">

    <!-- Description -->
    <?php if (!empty($e->description)): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><i class="fas fa-align-left me-2 text-primary"></i><?php echo __('Description'); ?></div>
      <div class="card-body">
        <?php echo nl2br(htmlspecialchars($e->description, ENT_QUOTES, 'UTF-8')); ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Resolution -->
    <?php if (!empty($e->resolution)): ?>
    <div class="card mb-4 border-success">
      <div class="card-header fw-semibold text-success"><i class="fas fa-check-circle me-2"></i><?php echo __('Resolution'); ?></div>
      <div class="card-body">
        <?php echo nl2br(htmlspecialchars($e->resolution, ENT_QUOTES, 'UTF-8')); ?>
        <?php if (!empty($e->resolved_at)): ?>
          <div class="mt-2 small text-muted">
            <?php echo __('Resolved:'); ?> <?php echo date('M j, Y H:i', strtotime($e->resolved_at)); ?>
            <?php if (!empty($e->resolved_by)): ?> <?php echo __('by'); ?> <?php echo htmlspecialchars($e->resolved_by, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Follow-up -->
    <?php if (!empty($e->follow_up_date)): ?>
    <?php $isOverdue = $e->follow_up_date < date('Y-m-d') && !in_array($e->status, ['resolved', 'closed']); ?>
    <div class="card mb-4 <?php echo $isOverdue ? 'border-danger' : 'border-warning'; ?>">
      <div class="card-header fw-semibold <?php echo $isOverdue ? 'text-danger' : 'text-warning'; ?>">
        <i class="fas fa-calendar-check me-2"></i><?php echo __('Follow-up'); ?>
        <?php if ($isOverdue): ?><span class="badge bg-danger ms-2"><?php echo __('Overdue'); ?></span><?php endif; ?>
      </div>
      <div class="card-body">
        <div class="fw-semibold"><?php echo date('l, M j, Y', strtotime($e->follow_up_date)); ?></div>
        <?php if (!empty($e->follow_up_notes)): ?>
          <div class="mt-1 text-muted"><?php echo htmlspecialchars($e->follow_up_notes, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Sidebar -->
  <div class="col-lg-4">

    <!-- Contact -->
    <?php if (!empty($e->contact_name) || !empty($e->contact_email) || !empty($e->contact_phone)): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><i class="fas fa-user me-2 text-primary"></i><?php echo __('Contact'); ?></div>
      <ul class="list-group list-group-flush">
        <?php if (!empty($e->contact_name)): ?>
          <li class="list-group-item"><i class="fas fa-user me-2 text-muted"></i><?php echo htmlspecialchars($e->contact_name, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php endif; ?>
        <?php if (!empty($e->contact_email)): ?>
          <li class="list-group-item"><i class="fas fa-envelope me-2 text-muted"></i><a href="mailto:<?php echo htmlspecialchars($e->contact_email, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($e->contact_email, ENT_QUOTES, 'UTF-8'); ?></a></li>
        <?php endif; ?>
        <?php if (!empty($e->contact_phone)): ?>
          <li class="list-group-item"><i class="fas fa-phone me-2 text-muted"></i><?php echo htmlspecialchars($e->contact_phone, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php endif; ?>
      </ul>
    </div>
    <?php endif; ?>

    <!-- Institution -->
    <?php if ($inst): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><i class="fas fa-building me-2 text-info"></i><?php echo __('Institution'); ?></div>
      <div class="card-body">
        <a href="/registry/institutions/<?php echo htmlspecialchars($inst->slug, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($inst->name, ENT_QUOTES, 'UTF-8'); ?></a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Details -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><i class="fas fa-info me-2 text-muted"></i><?php echo __('Details'); ?></div>
      <ul class="list-group list-group-flush small">
        <?php if (!empty($e->duration_minutes)): ?>
          <li class="list-group-item"><i class="fas fa-clock me-2 text-muted"></i><?php echo (int) $e->duration_minutes; ?> <?php echo __('minutes'); ?></li>
        <?php endif; ?>
        <?php if (!empty($e->logged_by_name)): ?>
          <li class="list-group-item"><i class="fas fa-user-edit me-2 text-muted"></i><?php echo __('Logged by:'); ?> <?php echo htmlspecialchars($e->logged_by_name, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php endif; ?>
        <li class="list-group-item"><i class="fas fa-calendar me-2 text-muted"></i><?php echo __('Created:'); ?> <?php echo date('M j, Y H:i', strtotime($e->created_at)); ?></li>
        <?php if ($e->updated_at !== $e->created_at): ?>
          <li class="list-group-item"><i class="fas fa-clock me-2 text-muted"></i><?php echo __('Updated:'); ?> <?php echo date('M j, Y H:i', strtotime($e->updated_at)); ?></li>
        <?php endif; ?>
      </ul>
    </div>

  </div>
</div>

<?php end_slot(); ?>

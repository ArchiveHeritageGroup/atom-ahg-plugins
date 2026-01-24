<?php decorate_with('layout_1col') ?>

<?php slot('title') ?>
  <h1><?php echo __('Audit Log Details') ?> <span class="badge bg-<?php echo $auditLog->status === 'success' ? 'success' : 'danger' ?>"><?php echo ucfirst($auditLog->status) ?></span></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="row">
  <div class="col-lg-8">
    <section class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Action Details') ?></h5></div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-3"><?php echo __('Date/Time') ?></dt>
          <dd class="col-sm-9"><?php echo $auditLog->created_at->format('Y-m-d H:i:s') ?></dd>
          <dt class="col-sm-3"><?php echo __('Action') ?></dt>
          <dd class="col-sm-9"><span class="badge bg-<?php echo match($auditLog->action) { 'create' => 'success', 'update' => 'primary', 'delete' => 'danger', default => 'secondary' } ?>"><?php echo $auditLog->action_label ?></span></dd>
          <dt class="col-sm-3"><?php echo __('Entity Type') ?></dt>
          <dd class="col-sm-9"><?php echo $auditLog->entity_type_label ?></dd>
          <?php if ($auditLog->entity_id): ?>
            <dt class="col-sm-3"><?php echo __('Entity ID') ?></dt>
            <dd class="col-sm-9">#<?php echo $auditLog->entity_id ?></dd>
          <?php endif; ?>
          <?php if ($auditLog->entity_title): ?>
            <dt class="col-sm-3"><?php echo __('Title') ?></dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($auditLog->entity_title) ?></dd>
          <?php endif; ?>
        </dl>
      </div>
    </section>

    <?php if ($auditLog->action === 'update' && ($auditLog->old_values || $auditLog->new_values)): ?>
    <section class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Changes') ?></h5></div>
      <div class="card-body">
        <div class="row">
          <?php if ($auditLog->old_values): ?>
          <div class="col-md-6">
            <h6 class="text-danger"><?php echo __('Before') ?></h6>
            <pre class="bg-light p-3 rounded small"><?php echo htmlspecialchars(json_encode($auditLog->old_values, JSON_PRETTY_PRINT)) ?></pre>
          </div>
          <?php endif; ?>
          <?php if ($auditLog->new_values): ?>
          <div class="col-md-6">
            <h6 class="text-success"><?php echo __('After') ?></h6>
            <pre class="bg-light p-3 rounded small"><?php echo htmlspecialchars(json_encode($auditLog->new_values, JSON_PRETTY_PRINT)) ?></pre>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <section class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><?php echo __('User Information') ?></h5></div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4"><?php echo __('User') ?></dt>
          <dd class="col-sm-8"><?php echo htmlspecialchars($auditLog->username ?? 'Anonymous') ?></dd>
          <dt class="col-sm-4"><?php echo __('IP') ?></dt>
          <dd class="col-sm-8"><code><?php echo $auditLog->ip_address ?? '-' ?></code></dd>
        </dl>
      </div>
    </section>

    <section class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Technical') ?></h5></div>
      <div class="card-body">
        <dl class="row mb-0 small">
          <dt class="col-sm-4"><?php echo __('UUID') ?></dt>
          <dd class="col-sm-8"><code><?php echo $auditLog->uuid ?></code></dd>
          <dt class="col-sm-4"><?php echo __('Method') ?></dt>
          <dd class="col-sm-8"><?php echo $auditLog->request_method ?? '-' ?></dd>
        </dl>
      </div>
    </section>
  </div>
</div>

<div class="mt-4">
  <a href="<?php echo url_for(['module' => 'auditTrail', 'action' => 'browse']) ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo __('Back to Audit Trail') ?></a>
</div>
<?php end_slot() ?>
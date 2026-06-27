<h1 class="h3 mb-4"><i class="fas fa-heartbeat me-2"></i><?php echo __('Condition Report'); ?></h1>

<?php if (!empty($resource)): ?>
  <p class="text-muted"><?php echo esc_specialchars($resource->title ?? $resource->slug ?? ''); ?></p>
<?php endif; ?>

<?php if (!empty($conditionData)): ?>
  <div class="card">
    <div class="card-body">
      <dl class="row mb-0">
        <dt class="col-sm-3"><?php echo __('Check date'); ?></dt>
        <dd class="col-sm-9"><?php echo esc_specialchars((string) ($conditionData->check_date ?? '—')); ?></dd>
        <dt class="col-sm-3"><?php echo __('Condition'); ?></dt>
        <dd class="col-sm-9"><?php echo esc_specialchars((string) ($conditionData->condition_grade ?? $conditionData->condition ?? '—')); ?></dd>
        <?php if (!empty($conditionData->notes)): ?>
          <dt class="col-sm-3"><?php echo __('Notes'); ?></dt>
          <dd class="col-sm-9"><?php echo nl2br(esc_specialchars((string) $conditionData->notes)); ?></dd>
        <?php endif; ?>
      </dl>
    </div>
  </div>
<?php else: ?>
  <div class="alert alert-secondary">
    <i class="fas fa-info-circle me-2"></i><?php echo __('No condition check has been recorded for this object yet.'); ?>
  </div>
<?php endif; ?>

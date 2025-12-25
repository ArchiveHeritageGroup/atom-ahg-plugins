<?php use_helper('Date') ?>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 mb-0">
        <i class="fas fa-bell text-warning me-2"></i>
        <?php echo __('Pending Reminders') ?>
        <span class="badge bg-warning text-dark ms-2"><?php echo count($reminders) ?></span>
      </h1>
    </div>
    <a href="<?php echo url_for(['module' => 'ahgDonor', 'action' => 'dashboard']) ?>" class="btn btn-outline-primary">
      <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Dashboard') ?>
    </a>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <?php if (!empty($reminders)): ?>
      <div class="list-group list-group-flush">
        <?php foreach ($reminders as $reminder): ?>
        <div class="list-group-item">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h6 class="mb-1">
                <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'view', 'id' => $reminder->agreement_id]) ?>">
                  <?php echo esc_entities($reminder->agreement_number) ?>
                </a>
              </h6>
              <p class="mb-1"><?php echo esc_entities($reminder->message ?? ucfirst(str_replace('_', ' ', $reminder->reminder_type))) ?></p>
              <small class="text-muted">Due: <?php echo format_date($reminder->reminder_date, 'd') ?></small>
            </div>
            <span class="badge bg-<?php echo $reminder->priority === 'urgent' ? 'danger' : ($reminder->priority === 'high' ? 'warning' : 'secondary') ?>">
              <?php echo ucfirst($reminder->priority) ?>
            </span>
          </div>
        </div>
        <?php endforeach ?>
      </div>
      <?php else: ?>
      <div class="text-center py-5">
        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
        <p class="text-muted"><?php echo __('No pending reminders') ?></p>
      </div>
      <?php endif ?>
    </div>
  </div>
</div>

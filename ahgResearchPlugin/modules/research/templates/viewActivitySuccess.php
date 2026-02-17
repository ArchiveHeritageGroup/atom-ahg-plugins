<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php slot('title') ?>
<h1><i class="fas fa-calendar-check text-primary me-2"></i><?php echo __('Activity Details'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$materials = isset($materials) && is_array($materials) ? $materials : (isset($materials) && method_exists($materials, 'getRawValue') ? $materials->getRawValue() : []);
$participants = isset($participants) && is_array($participants) ? $participants : (isset($participants) && method_exists($participants, 'getRawValue') ? $participants->getRawValue() : []);
?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><?php echo __('Research'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'activities']); ?>"><?php echo __('Activities'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo htmlspecialchars($activity->title); ?></li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-8">
    <!-- Activity Info -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?php echo htmlspecialchars($activity->title); ?></h5>
        <span class="badge bg-<?php echo match($activity->status) {
          'requested' => 'warning', 'confirmed' => 'success', 'in_progress' => 'primary',
          'completed' => 'info', 'cancelled' => 'danger', default => 'secondary'
        }; ?> fs-6"><?php echo ucfirst(str_replace('_', ' ', $activity->status)); ?></span>
      </div>
      <div class="card-body">
        <?php if (!empty($activity->description)): ?>
          <p><?php echo nl2br(htmlspecialchars($activity->description)); ?></p>
          <hr>
        <?php endif; ?>

        <div class="row">
          <div class="col-md-6">
            <dl>
              <dt><?php echo __('Type'); ?></dt>
              <dd><span class="badge bg-secondary"><?php echo ucfirst($activity->activity_type); ?></span></dd>
              <dt><?php echo __('Start Date'); ?></dt>
              <dd><?php echo date('M j, Y', strtotime($activity->start_date)); ?><?php echo !empty($activity->start_time) ? ' ' . date('H:i', strtotime($activity->start_time)) : ''; ?></dd>
              <?php if (!empty($activity->end_date)): ?>
              <dt><?php echo __('End Date'); ?></dt>
              <dd><?php echo date('M j, Y', strtotime($activity->end_date)); ?><?php echo !empty($activity->end_time) ? ' ' . date('H:i', strtotime($activity->end_time)) : ''; ?></dd>
              <?php endif; ?>
              <dt><?php echo __('Room'); ?></dt>
              <dd><?php echo htmlspecialchars($activity->room_name ?? __('Not assigned')); ?></dd>
            </dl>
          </div>
          <div class="col-md-6">
            <dl>
              <dt><?php echo __('Organizer'); ?></dt>
              <dd><?php echo htmlspecialchars($activity->organizer_name ?? '-'); ?></dd>
              <?php if (!empty($activity->organizer_email)): ?>
              <dt><?php echo __('Email'); ?></dt>
              <dd><a href="mailto:<?php echo htmlspecialchars($activity->organizer_email); ?>"><?php echo htmlspecialchars($activity->organizer_email); ?></a></dd>
              <?php endif; ?>
              <?php if (!empty($activity->organizer_phone)): ?>
              <dt><?php echo __('Phone'); ?></dt>
              <dd><?php echo htmlspecialchars($activity->organizer_phone); ?></dd>
              <?php endif; ?>
              <?php if (!empty($activity->organization)): ?>
              <dt><?php echo __('Organization'); ?></dt>
              <dd><?php echo htmlspecialchars($activity->organization); ?></dd>
              <?php endif; ?>
              <dt><?php echo __('Expected Attendees'); ?></dt>
              <dd><?php echo (int) $activity->expected_attendees; ?></dd>
            </dl>
          </div>
        </div>

        <?php if (!empty($activity->setup_requirements)): ?>
        <div class="mb-3">
          <strong><?php echo __('Setup Requirements'); ?></strong>
          <p class="text-muted"><?php echo nl2br(htmlspecialchars($activity->setup_requirements)); ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($activity->av_requirements)): ?>
        <div class="mb-3">
          <strong><?php echo __('AV Requirements'); ?></strong>
          <p class="text-muted"><?php echo nl2br(htmlspecialchars($activity->av_requirements)); ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Materials -->
    <?php if (!empty($materials)): ?>
    <div class="card mb-4">
      <div class="card-header"><h6 class="mb-0"><i class="fas fa-box me-2"></i><?php echo __('Materials'); ?> (<?php echo count($materials); ?>)</h6></div>
      <div class="list-group list-group-flush">
        <?php foreach ($materials as $material): ?>
        <div class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <span class="fw-semibold"><?php echo htmlspecialchars($material->item_title ?? 'Item #' . $material->object_id); ?></span>
            <?php if (!empty($material->call_number)): ?>
              <small class="text-muted ms-2"><?php echo htmlspecialchars($material->call_number); ?></small>
            <?php endif; ?>
          </div>
          <span class="badge bg-<?php echo ($material->status ?? 'requested') === 'delivered' ? 'success' : 'warning'; ?>">
            <?php echo ucfirst($material->status ?? 'requested'); ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Participants -->
    <?php if (!empty($participants)): ?>
    <div class="card mb-4">
      <div class="card-header"><h6 class="mb-0"><i class="fas fa-users me-2"></i><?php echo __('Participants'); ?> (<?php echo count($participants); ?>)</h6></div>
      <div class="list-group list-group-flush">
        <?php foreach ($participants as $participant): ?>
        <div class="list-group-item">
          <strong><?php echo htmlspecialchars($participant->name); ?></strong>
          <?php if (!empty($participant->email)): ?>
            <small class="text-muted ms-2"><?php echo htmlspecialchars($participant->email); ?></small>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-md-4">
    <!-- Actions -->
    <?php if ($sf_user->isAdministrator()): ?>
    <div class="card mb-4">
      <div class="card-header"><h6 class="mb-0"><i class="fas fa-cogs me-2"></i><?php echo __('Actions'); ?></h6></div>
      <div class="card-body">
        <?php if ($activity->status === 'requested'): ?>
        <form method="post" class="mb-2">
          <input type="hidden" name="form_action" value="confirm">
          <button type="submit" class="btn btn-success w-100"><i class="fas fa-check me-1"></i><?php echo __('Confirm Activity'); ?></button>
        </form>
        <?php endif; ?>
        <?php if (in_array($activity->status, ['requested', 'confirmed'])): ?>
        <form method="post" onsubmit="return confirm('<?php echo __('Cancel this activity?'); ?>');">
          <input type="hidden" name="form_action" value="cancel">
          <div class="mb-2">
            <input type="text" name="cancellation_reason" class="form-control" placeholder="<?php echo __('Reason for cancellation...'); ?>">
          </div>
          <button type="submit" class="btn btn-outline-danger w-100"><i class="fas fa-times me-1"></i><?php echo __('Cancel Activity'); ?></button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Metadata -->
    <div class="card">
      <div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Details'); ?></h6></div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Created'); ?></span>
          <span><?php echo date('M j, Y', strtotime($activity->created_at)); ?></span>
        </li>
        <?php if (!empty($activity->confirmed_at)): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Confirmed'); ?></span>
          <span><?php echo date('M j, Y', strtotime($activity->confirmed_at)); ?></span>
        </li>
        <?php endif; ?>
        <?php if (!empty($activity->cancelled_at)): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Cancelled'); ?></span>
          <span><?php echo date('M j, Y', strtotime($activity->cancelled_at)); ?></span>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>
<?php end_slot() ?>

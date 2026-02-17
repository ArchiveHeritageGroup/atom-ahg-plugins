<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php slot('title') ?>
<h1><i class="fas fa-calendar-alt text-primary me-2"></i><?php echo __('Reading Room Activities'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$activities = isset($activities) && is_array($activities) ? $activities : (isset($activities) && method_exists($activities, 'getRawValue') ? $activities->getRawValue() : []);
$rooms = isset($rooms) && is_array($rooms) ? $rooms : (isset($rooms) && method_exists($rooms, 'getRawValue') ? $rooms->getRawValue() : []);
?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><?php echo __('Research'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Activities'); ?></li>
  </ol>
</nav>

<!-- Filters -->
<div class="card mb-4">
  <div class="card-body">
    <form method="get" class="row g-3 align-items-end">
      <div class="col-auto">
        <label class="form-label"><?php echo __('Status'); ?></label>
        <select name="status" class="form-select">
          <option value=""><?php echo __('All'); ?></option>
          <?php foreach (['requested' => 'Requested', 'confirmed' => 'Confirmed', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $label): ?>
            <option value="<?php echo $val; ?>" <?php echo $sf_request->getParameter('status') === $val ? 'selected' : ''; ?>><?php echo __($label); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <label class="form-label"><?php echo __('Type'); ?></label>
        <select name="type" class="form-select">
          <option value=""><?php echo __('All'); ?></option>
          <?php foreach (['tour' => 'Tour', 'workshop' => 'Workshop', 'exhibition' => 'Exhibition', 'lecture' => 'Lecture', 'other' => 'Other'] as $val => $label): ?>
            <option value="<?php echo $val; ?>" <?php echo $sf_request->getParameter('type') === $val ? 'selected' : ''; ?>><?php echo __($label); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-outline-primary"><i class="fas fa-filter me-1"></i><?php echo __('Filter'); ?></button>
      </div>
    </form>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0"><?php echo count($activities); ?> <?php echo __('activities'); ?></h5>
  <?php if ($sf_user->isAdministrator()): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newActivityModal"><i class="fas fa-plus me-1"></i><?php echo __('New Activity'); ?></button>
  <?php endif; ?>
</div>

<?php if (empty($activities)): ?>
  <div class="alert alert-info"><?php echo __('No activities found.'); ?></div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Title'); ?></th>
          <th><?php echo __('Type'); ?></th>
          <th><?php echo __('Date'); ?></th>
          <th><?php echo __('Room'); ?></th>
          <th><?php echo __('Organizer'); ?></th>
          <th><?php echo __('Attendees'); ?></th>
          <th><?php echo __('Status'); ?></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($activities as $activity): ?>
        <tr>
          <td class="fw-semibold"><?php echo htmlspecialchars($activity->title); ?></td>
          <td><span class="badge bg-secondary"><?php echo ucfirst($activity->activity_type); ?></span></td>
          <td><?php echo date('M j, Y', strtotime($activity->start_date)); ?></td>
          <td><?php echo htmlspecialchars($activity->room_name ?? '-'); ?></td>
          <td><?php echo htmlspecialchars($activity->organizer_name ?? '-'); ?></td>
          <td><?php echo (int) $activity->expected_attendees; ?></td>
          <td>
            <span class="badge bg-<?php echo match($activity->status) {
              'requested' => 'warning', 'confirmed' => 'success', 'in_progress' => 'primary',
              'completed' => 'info', 'cancelled' => 'danger', default => 'secondary'
            }; ?>"><?php echo ucfirst(str_replace('_', ' ', $activity->status)); ?></span>
          </td>
          <td>
            <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewActivity', 'id' => $activity->id]); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php if ($sf_user->isAdministrator()): ?>
<!-- New Activity Modal -->
<div class="modal fade" id="newActivityModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="form_action" value="create">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i><?php echo __('New Activity Request'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-8">
              <label class="form-label"><?php echo __('Title'); ?> *</label>
              <input type="text" name="title" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Type'); ?></label>
              <select name="activity_type" class="form-select">
                <option value="tour"><?php echo __('Tour'); ?></option>
                <option value="workshop"><?php echo __('Workshop'); ?></option>
                <option value="exhibition"><?php echo __('Exhibition'); ?></option>
                <option value="lecture"><?php echo __('Lecture'); ?></option>
                <option value="other"><?php echo __('Other'); ?></option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Description'); ?></label>
            <textarea name="description" class="form-control" rows="3"></textarea>
          </div>
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Organizer Name'); ?></label>
              <input type="text" name="organizer_name" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Email'); ?></label>
              <input type="email" name="organizer_email" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Phone'); ?></label>
              <input type="text" name="organizer_phone" class="form-control">
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Organization'); ?></label>
              <input type="text" name="organization" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label"><?php echo __('Expected Attendees'); ?></label>
              <input type="number" name="expected_attendees" class="form-control" min="1" value="1">
            </div>
            <div class="col-md-3">
              <label class="form-label"><?php echo __('Room'); ?></label>
              <select name="reading_room_id" class="form-select">
                <option value=""><?php echo __('Any'); ?></option>
                <?php foreach ($rooms as $room): ?>
                  <option value="<?php echo $room->id; ?>"><?php echo htmlspecialchars($room->name); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-3">
              <label class="form-label"><?php echo __('Start Date'); ?> *</label>
              <input type="date" name="start_date" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label"><?php echo __('End Date'); ?></label>
              <input type="date" name="end_date" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label"><?php echo __('Start Time'); ?></label>
              <input type="time" name="start_time" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label"><?php echo __('End Time'); ?></label>
              <input type="time" name="end_time" class="form-control">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Setup Requirements'); ?></label>
            <textarea name="setup_requirements" class="form-control" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('AV Requirements'); ?></label>
            <textarea name="av_requirements" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Create Activity'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
<?php end_slot() ?>

<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-calendar-plus text-primary me-2"></i><?php echo __('Book Reading Room Visit'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-body">
        <form method="post">
          <h5 class="mb-3 border-bottom pb-2"><i class="fas fa-door-open me-2"></i><?php echo __('Select Reading Room'); ?></h5>
          
          <div class="mb-4">
            <?php if (empty($rooms)): ?>
              <div class="alert alert-warning"><?php echo __('No reading rooms available'); ?></div>
            <?php else: ?>
              <div class="row">
                <?php foreach ($rooms as $room): ?>
                <div class="col-md-6 mb-3">
                  <div class="card h-100">
                    <div class="card-body">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="reading_room_id" value="<?php echo $room->id; ?>" id="room_<?php echo $room->id; ?>" required>
                        <label class="form-check-label" for="room_<?php echo $room->id; ?>">
                          <strong><?php echo htmlspecialchars($room->name); ?></strong>
                        </label>
                      </div>
                      <small class="text-muted d-block mt-2">
                        <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($room->location ?? 'Location TBD'); ?><br>
                        <i class="fas fa-users me-1"></i><?php echo __('Capacity:'); ?> <?php echo $room->capacity ?? '-'; ?><br>
                        <i class="fas fa-clock me-1"></i><?php echo substr($room->opening_time ?? '09:00', 0, 5); ?> - <?php echo substr($room->closing_time ?? '17:00', 0, 5); ?>
                      </small>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <h5 class="mb-3 border-bottom pb-2"><i class="fas fa-clock me-2"></i><?php echo __('Date & Time'); ?></h5>
          
          <div class="row mb-4">
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Date'); ?> <span class="text-danger">*</span></label>
              <input type="date" name="booking_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Start Time'); ?> <span class="text-danger">*</span></label>
              <input type="time" name="start_time" class="form-control" required value="09:00">
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('End Time'); ?> <span class="text-danger">*</span></label>
              <input type="time" name="end_time" class="form-control" required value="17:00">
            </div>
          </div>

          <h5 class="mb-3 border-bottom pb-2"><i class="fas fa-info-circle me-2"></i><?php echo __('Visit Details'); ?></h5>
          
          <div class="mb-4">
            <label class="form-label"><?php echo __('Purpose of Visit'); ?></label>
            <textarea name="purpose" class="form-control" rows="3" placeholder="<?php echo __('Describe the purpose of your visit and any materials you wish to consult...'); ?>"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Additional Notes'); ?></label>
            <textarea name="notes" class="form-control" rows="2" 
                      placeholder="<?php echo __('Any special requirements or notes for staff'); ?>"></textarea>
            <small class="text-muted"><?php echo __('Optional notes visible to archive staff'); ?></small>
          </div>

          <?php if ($object): ?>
          <div class="alert alert-info">
            <strong><?php echo __('Requesting access to:'); ?></strong> <?php echo htmlspecialchars($object->title); ?>
            <input type="hidden" name="materials[]" value="<?php echo $object->object_id; ?>">
          </div>
          <?php endif; ?>

          <hr>

          <div class="d-flex justify-content-between">
            <a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>" class="btn btn-secondary">
              <i class="fas fa-arrow-left me-1"></i><?php echo __('Cancel'); ?>
            </a>
            <button type="submit" class="btn btn-primary" <?php echo empty($rooms) ? 'disabled' : ''; ?>>
              <i class="fas fa-paper-plane me-1"></i><?php echo __('Submit Booking Request'); ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card">
      <div class="card-header">
        <i class="fas fa-user me-2"></i><?php echo __('Your Information'); ?>
      </div>
      <div class="card-body">
        <p><strong><?php echo htmlspecialchars($researcher->first_name . ' ' . $researcher->last_name); ?></strong></p>
        <p class="text-muted mb-1"><?php echo htmlspecialchars($researcher->email); ?></p>
        <p class="text-muted"><?php echo htmlspecialchars($researcher->institution ?? 'Independent Researcher'); ?></p>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'profile']); ?>" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-edit me-1"></i><?php echo __('Edit Profile'); ?>
        </a>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-header">
        <i class="fas fa-info-circle me-2"></i><?php echo __('Information'); ?>
      </div>
      <div class="card-body">
        <ul class="mb-0 ps-3">
          <li><?php echo __('Bookings require confirmation'); ?></li>
          <li><?php echo __('Bring valid ID on visit day'); ?></li>
          <li><?php echo __('Cancel at least 24h in advance'); ?></li>
        </ul>
      </div>
    </div>
  </div>
</div>
<?php end_slot() ?>

<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-door-open text-primary me-2"></i><?php echo $isNew ? __('Add Reading Room') : __('Edit Reading Room'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <form method="post">
      <div class="row">
        <div class="col-md-6">
          <h5 class="mb-3 border-bottom pb-2"><?php echo __('Basic Information'); ?></h5>
          
          <div class="mb-3">
            <label class="form-label"><?php echo __('Room Name'); ?> <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($room->name ?? ''); ?>">
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Room Code'); ?></label>
              <input type="text" name="code" class="form-control" value="<?php echo htmlspecialchars($room->code ?? ''); ?>" placeholder="e.g. RR-01">
            </div>
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Capacity'); ?></label>
              <input type="number" name="capacity" class="form-control" value="<?php echo $room->capacity ?? 10; ?>" min="1">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Location'); ?></label>
            <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($room->location ?? ''); ?>" placeholder="e.g. Building A, Floor 2">
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Description'); ?></label>
            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($room->description ?? ''); ?></textarea>
          </div>

          <div class="mb-3 form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" <?php echo ($room->is_active ?? 1) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_active"><?php echo __('Active'); ?></label>
          </div>
        </div>

        <div class="col-md-6">
          <h5 class="mb-3 border-bottom pb-2"><?php echo __('Operating Hours'); ?></h5>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Opening Time'); ?></label>
              <input type="time" name="opening_time" class="form-control" value="<?php echo substr($room->opening_time ?? '09:00:00', 0, 5); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Closing Time'); ?></label>
              <input type="time" name="closing_time" class="form-control" value="<?php echo substr($room->closing_time ?? '17:00:00', 0, 5); ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Days Open'); ?></label>
            <input type="text" name="days_open" class="form-control" value="<?php echo htmlspecialchars($room->days_open ?? 'Mon,Tue,Wed,Thu,Fri'); ?>" placeholder="Mon,Tue,Wed,Thu,Fri">
            <small class="text-muted"><?php echo __('Comma-separated list of days'); ?></small>
          </div>

          <h5 class="mb-3 mt-4 border-bottom pb-2"><?php echo __('Additional Information'); ?></h5>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Amenities'); ?></label>
            <textarea name="amenities" class="form-control" rows="2" placeholder="<?php echo __('WiFi, Power outlets, Lockers...'); ?>"><?php echo htmlspecialchars($room->amenities ?? ''); ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Rules'); ?></label>
            <textarea name="rules" class="form-control" rows="3" placeholder="<?php echo __('No food or drinks, Handle materials with care...'); ?>"><?php echo htmlspecialchars($room->rules ?? ''); ?></textarea>
          </div>
        </div>
      </div>

      <hr>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'rooms']); ?>" class="btn btn-secondary">
          <i class="fas fa-arrow-left me-1"></i><?php echo __('Cancel'); ?>
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save me-1"></i><?php echo $isNew ? __('Create Room') : __('Save Changes'); ?>
        </button>
      </div>
    </form>
  </div>
</div>
<?php end_slot() ?>

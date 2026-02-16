<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-laptop text-primary me-2"></i><?php echo __('Book Equipment'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$availableEquipment = isset($availableEquipment) && is_array($availableEquipment) ? $availableEquipment : (isset($availableEquipment) && method_exists($availableEquipment, 'getRawValue') ? $availableEquipment->getRawValue() : []);
$bookedEquipment = isset($bookedEquipment) && is_array($bookedEquipment) ? $bookedEquipment : (isset($bookedEquipment) && method_exists($bookedEquipment, 'getRawValue') ? $bookedEquipment->getRawValue() : []);
?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><?php echo __('Research'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'bookings']); ?>"><?php echo __('Bookings'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewBooking', 'id' => $booking->id]); ?>"><?php echo __('Booking #%1%', ['%1%' => $booking->id]); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Book Equipment'); ?></li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-8">
    <!-- Currently Booked Equipment -->
    <?php if (!empty($bookedEquipment)): ?>
    <div class="card mb-4">
      <div class="card-header"><h6 class="mb-0"><i class="fas fa-check-circle me-2 text-success"></i><?php echo __('Your Booked Equipment'); ?></h6></div>
      <div class="list-group list-group-flush">
        <?php foreach ($bookedEquipment as $eq): ?>
        <div class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <strong><?php echo htmlspecialchars($eq->name ?? $eq->equipment_name ?? 'Equipment #' . $eq->equipment_id); ?></strong>
            <?php if (!empty($eq->equipment_type)): ?>
              <span class="badge bg-secondary ms-2"><?php echo ucfirst($eq->equipment_type); ?></span>
            <?php endif; ?>
            <?php if (!empty($eq->purpose)): ?>
              <small class="text-muted d-block"><?php echo htmlspecialchars($eq->purpose); ?></small>
            <?php endif; ?>
          </div>
          <form method="post" class="d-inline">
            <input type="hidden" name="form_action" value="cancel">
            <input type="hidden" name="equipment_booking_id" value="<?php echo $eq->id; ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?php echo __('Cancel this equipment booking?'); ?>')"><i class="fas fa-times"></i></button>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Available Equipment -->
    <div class="card">
      <div class="card-header"><h6 class="mb-0"><i class="fas fa-laptop me-2"></i><?php echo __('Available Equipment'); ?></h6></div>
      <?php if (empty($availableEquipment)): ?>
        <div class="card-body"><div class="alert alert-warning mb-0"><?php echo __('No equipment available for this time slot.'); ?></div></div>
      <?php else: ?>
        <div class="list-group list-group-flush">
          <?php foreach ($availableEquipment as $eq): ?>
          <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <strong><?php echo htmlspecialchars($eq->name); ?></strong>
                <?php if (!empty($eq->equipment_type)): ?>
                  <span class="badge bg-secondary ms-2"><?php echo ucfirst($eq->equipment_type); ?></span>
                <?php endif; ?>
                <?php if (!empty($eq->description)): ?>
                  <p class="small text-muted mb-0 mt-1"><?php echo htmlspecialchars($eq->description); ?></p>
                <?php endif; ?>
              </div>
              <form method="post" class="ms-3">
                <input type="hidden" name="form_action" value="book">
                <input type="hidden" name="equipment_id" value="<?php echo $eq->id; ?>">
                <div class="mb-2">
                  <input type="text" name="purpose" class="form-control form-control-sm" placeholder="<?php echo __('Purpose (optional)'); ?>" style="min-width: 150px;">
                </div>
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-plus me-1"></i><?php echo __('Book'); ?></button>
              </form>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-md-4">
    <!-- Booking Summary -->
    <div class="card">
      <div class="card-header"><h6 class="mb-0"><i class="fas fa-bookmark me-2"></i><?php echo __('Booking Summary'); ?></h6></div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Date'); ?></span>
          <span><?php echo date('M j, Y', strtotime($booking->booking_date)); ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Time'); ?></span>
          <span><?php echo date('H:i', strtotime($booking->start_time)); ?> - <?php echo date('H:i', strtotime($booking->end_time)); ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Status'); ?></span>
          <span class="badge bg-<?php echo ($booking->status ?? 'confirmed') === 'confirmed' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($booking->status ?? 'confirmed'); ?></span>
        </li>
      </ul>
    </div>
  </div>
</div>
<?php end_slot() ?>

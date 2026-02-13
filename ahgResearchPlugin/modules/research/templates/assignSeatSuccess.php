<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-chair text-primary me-2"></i><?php echo __('Assign Seat'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$availableSeats = isset($availableSeats) && is_array($availableSeats) ? $availableSeats : (isset($availableSeats) && method_exists($availableSeats, 'getRawValue') ? $availableSeats->getRawValue() : []);
?>

<?php if ($sf_user->hasFlash('success')): ?>
  <div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('success'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><?php echo __('Research'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'bookings']); ?>"><?php echo __('Bookings'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewBooking', 'id' => $booking->id]); ?>"><?php echo __('Booking #%1%', ['%1%' => $booking->id]); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Assign Seat'); ?></li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-8">
    <!-- Current Assignment -->
    <?php if (isset($currentAssignment) && $currentAssignment): ?>
    <div class="card mb-4 border-success">
      <div class="card-header bg-success text-white">
        <h6 class="mb-0"><i class="fas fa-check-circle me-2"></i><?php echo __('Current Seat Assignment'); ?></h6>
      </div>
      <div class="card-body">
        <p class="mb-2"><strong><?php echo __('Seat'); ?>:</strong> <?php echo htmlspecialchars($currentAssignment->seat_label ?? 'Seat #' . $currentAssignment->seat_id); ?></p>
        <form method="post" class="d-inline">
          <input type="hidden" name="form_action" value="release">
          <button type="submit" class="btn btn-outline-warning btn-sm" onclick="return confirm('<?php echo __('Release this seat?'); ?>')"><i class="fas fa-undo me-1"></i><?php echo __('Release Seat'); ?></button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- Available Seats -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-th me-2"></i><?php echo __('Available Seats'); ?></h6>
        <form method="post" class="d-inline">
          <input type="hidden" name="form_action" value="auto_assign">
          <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-magic me-1"></i><?php echo __('Auto-Assign'); ?></button>
        </form>
      </div>
      <?php if (empty($availableSeats)): ?>
        <div class="card-body"><div class="alert alert-warning mb-0"><?php echo __('No seats available for this time slot.'); ?></div></div>
      <?php else: ?>
        <div class="list-group list-group-flush">
          <?php foreach ($availableSeats as $seat): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <strong><?php echo htmlspecialchars($seat->label ?? 'Seat #' . $seat->id); ?></strong>
              <?php if (!empty($seat->seat_type)): ?>
                <span class="badge bg-secondary ms-2"><?php echo ucfirst($seat->seat_type); ?></span>
              <?php endif; ?>
              <?php if (!empty($seat->has_power)): ?>
                <span class="badge bg-info ms-1"><i class="fas fa-bolt"></i></span>
              <?php endif; ?>
              <?php if (!empty($seat->has_network)): ?>
                <span class="badge bg-info ms-1"><i class="fas fa-wifi"></i></span>
              <?php endif; ?>
            </div>
            <form method="post" class="d-inline">
              <input type="hidden" name="form_action" value="assign">
              <input type="hidden" name="seat_id" value="<?php echo $seat->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-check me-1"></i><?php echo __('Assign'); ?></button>
            </form>
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

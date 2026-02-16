<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-calendar-alt text-primary me-2"></i><?php echo __('Bookings'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<!-- Pending Bookings -->
<?php if (!empty($pendingBookings)): ?>
<div class="card mb-4">
  <div class="card-header bg-warning">
    <i class="fas fa-clock me-2"></i><?php echo __('Pending Confirmation'); ?>
    <span class="badge bg-dark float-end"><?php echo count($pendingBookings); ?></span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th><?php echo __('Date'); ?></th>
          <th><?php echo __('Time'); ?></th>
          <th><?php echo __('Researcher'); ?></th>
          <th><?php echo __('Room'); ?></th>
          <th width="150"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pendingBookings as $booking): ?>
        <tr>
          <td><?php echo $booking->booking_date; ?></td>
          <td><?php echo substr($booking->start_time, 0, 5) . ' - ' . substr($booking->end_time, 0, 5); ?></td>
          <td>
            <strong><?php echo htmlspecialchars($booking->first_name . ' ' . $booking->last_name); ?></strong><br>
            <small class="text-muted"><?php echo htmlspecialchars($booking->email); ?></small>
          </td>
          <td><?php echo htmlspecialchars($booking->room_name); ?></td>
          <td>
            <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewBooking', 'id' => $booking->id]); ?>" class="btn btn-sm btn-primary">
              <i class="fas fa-eye me-1"></i><?php echo __('Review'); ?>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Upcoming Bookings -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="fas fa-calendar-check me-2"></i><?php echo __('Upcoming Confirmed Bookings'); ?></span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th><?php echo __('Date'); ?></th>
          <th><?php echo __('Time'); ?></th>
          <th><?php echo __('Researcher'); ?></th>
          <th><?php echo __('Room'); ?></th>
          <th><?php echo __('Check-in'); ?></th>
          <th width="180"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($upcomingBookings)): ?>
        <tr>
          <td colspan="6" class="text-center text-muted py-4"><?php echo __('No upcoming bookings'); ?></td>
        </tr>
        <?php else: ?>
          <?php foreach ($upcomingBookings as $booking): ?>
          <tr class="<?php echo $booking->booking_date === date('Y-m-d') ? 'table-info' : ''; ?>">
            <td>
              <?php echo $booking->booking_date; ?>
              <?php if ($booking->booking_date === date('Y-m-d')): ?>
                <span class="badge bg-info"><?php echo __('Today'); ?></span>
              <?php endif; ?>
            </td>
            <td><?php echo substr($booking->start_time, 0, 5) . ' - ' . substr($booking->end_time, 0, 5); ?></td>
            <td><?php echo htmlspecialchars($booking->first_name . ' ' . $booking->last_name); ?></td>
            <td><?php echo htmlspecialchars($booking->room_name); ?></td>
            <td>
              <?php if ($booking->checked_in_at): ?>
                <span class="badge bg-success"><i class="fas fa-check me-1"></i><?php echo date('H:i', strtotime($booking->checked_in_at)); ?></span>
              <?php else: ?>
                <span class="badge bg-secondary"><?php echo __('Not yet'); ?></span>
              <?php endif; ?>
            </td>
            <td>
              <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewBooking', 'id' => $booking->id]); ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-eye"></i>
              </a>
              <?php if ($booking->booking_date === date('Y-m-d') && !$booking->checked_in_at): ?>
              <a href="<?php echo url_for(['module' => 'research', 'action' => 'checkIn', 'id' => $booking->id]); ?>" class="btn btn-sm btn-success" onclick="return confirm('<?php echo __('Check in this researcher?'); ?>')">
                <i class="fas fa-sign-in-alt"></i>
              </a>
              <?php elseif ($booking->checked_in_at && !$booking->checked_out_at): ?>
              <a href="<?php echo url_for(['module' => 'research', 'action' => 'checkOut', 'id' => $booking->id]); ?>" class="btn btn-sm btn-warning" onclick="return confirm('<?php echo __('Check out this researcher?'); ?>')">
                <i class="fas fa-sign-out-alt"></i>
              </a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php end_slot() ?>

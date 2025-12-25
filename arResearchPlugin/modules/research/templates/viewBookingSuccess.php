<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-calendar-check text-primary me-2"></i><?php echo __('Booking Details'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
// Determine if current user is the booking owner or admin/staff
$isAdmin = $sf_user->isAdministrator();
$isStaff = $sf_user->hasCredential('staff') || $isAdmin;
$currentUserId = $sf_user->getAttribute('user_id');

// Get researcher for current user
$currentResearcher = null;
if ($currentUserId) {
    $currentResearcher = \Illuminate\Database\Capsule\Manager::table('research_researcher')
        ->where('user_id', $currentUserId)
        ->first();
}
$isOwner = $currentResearcher && $currentResearcher->id == $booking->researcher_id;
?>

<?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<div class="row">
  <div class="col-md-8">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-info-circle me-2"></i><?php echo __('Booking Information'); ?></span>
        <span class="badge bg-<?php
          echo $booking->status === 'confirmed' ? 'success' :
              ($booking->status === 'pending' ? 'warning' :
              ($booking->status === 'completed' ? 'info' :
              ($booking->status === 'cancelled' ? 'danger' : 'secondary')));
        ?> fs-6"><?php echo ucfirst($booking->status); ?></span>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <h6 class="text-muted"><?php echo __('Schedule'); ?></h6>
            <table class="table table-sm">
              <tr><th width="100"><?php echo __('Date'); ?></th><td><strong><?php echo $booking->booking_date; ?></strong></td></tr>
              <tr><th><?php echo __('Time'); ?></th><td><?php echo substr($booking->start_time, 0, 5) . ' - ' . substr($booking->end_time, 0, 5); ?></td></tr>
              <tr><th><?php echo __('Room'); ?></th><td><?php echo htmlspecialchars($booking->room_name); ?></td></tr>
              <tr><th><?php echo __('Location'); ?></th><td><?php echo htmlspecialchars($booking->room_location ?? '-'); ?></td></tr>
            </table>
          </div>
          <div class="col-md-6">
            <h6 class="text-muted"><?php echo __('Researcher'); ?></h6>
            <table class="table table-sm">
              <tr><th width="100"><?php echo __('Name'); ?></th><td><?php echo htmlspecialchars($booking->first_name . ' ' . $booking->last_name); ?></td></tr>
              <tr><th><?php echo __('Email'); ?></th><td><a href="mailto:<?php echo htmlspecialchars($booking->email); ?>"><?php echo htmlspecialchars($booking->email); ?></a></td></tr>
              <tr><th><?php echo __('Institution'); ?></th><td><?php echo htmlspecialchars($booking->institution ?? '-'); ?></td></tr>
            </table>
          </div>
        </div>

        <?php if ($booking->purpose): ?>
        <hr>
        <h6 class="text-muted"><?php echo __('Purpose of Visit'); ?></h6>
        <p><?php echo nl2br(htmlspecialchars($booking->purpose)); ?></p>
        <?php endif; ?>

        <?php if ($booking->checked_in_at || $booking->checked_out_at): ?>
        <hr>
        <div class="row">
          <div class="col-md-6">
            <h6 class="text-muted"><?php echo __('Check-in'); ?></h6>
            <?php if ($booking->checked_in_at): ?>
              <span class="badge bg-success"><i class="fas fa-check me-1"></i><?php echo $booking->checked_in_at; ?></span>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <h6 class="text-muted"><?php echo __('Check-out'); ?></h6>
            <?php if ($booking->checked_out_at): ?>
              <span class="badge bg-info"><i class="fas fa-check me-1"></i><?php echo $booking->checked_out_at; ?></span>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Materials Requested -->
    <?php if (!empty($booking->materials)): ?>
    <div class="card">
      <div class="card-header">
        <i class="fas fa-archive me-2"></i><?php echo __('Materials Requested'); ?>
        <span class="badge bg-secondary float-end"><?php echo count($booking->materials); ?></span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th><?php echo __('Title'); ?></th>
              <th><?php echo __('Status'); ?></th>
              <th><?php echo __('Notes'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($booking->materials as $material): ?>
            <tr>
              <td><?php echo htmlspecialchars($material->object_title ?? 'Object #' . $material->object_id); ?></td>
              <td>
                <span class="badge bg-<?php
                  echo $material->status === 'delivered' ? 'success' :
                      ($material->status === 'retrieved' ? 'info' :
                      ($material->status === 'returned' ? 'secondary' : 'warning'));
                ?>"><?php echo ucfirst($material->status); ?></span>
              </td>
              <td><small><?php echo htmlspecialchars($material->notes ?? '-'); ?></small></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Actions Sidebar -->
  <div class="col-md-4">
    <?php if ($booking->status === 'pending' || $booking->status === 'confirmed'): ?>
    <div class="card mb-4">
      <div class="card-header bg-dark text-white">
        <i class="fas fa-tasks me-2"></i><?php echo __('Actions'); ?>
      </div>
      <div class="card-body">
        
        <?php if ($booking->status === 'pending'): ?>
          <?php if ($isStaff): ?>
          <!-- Staff can confirm -->
          <form method="post" class="mb-2">
            <input type="hidden" name="action" value="confirm">
            <button type="submit" class="btn btn-success w-100">
              <i class="fas fa-check me-2"></i><?php echo __('Confirm Booking'); ?>
            </button>
          </form>
          <?php endif; ?>
          
          <?php if ($isStaff || $isOwner): ?>
          <!-- Staff or owner can cancel pending booking -->
          <form method="post">
            <input type="hidden" name="action" value="cancel">
            <button type="submit" class="btn btn-danger w-100" onclick="return confirm('<?php echo __('Cancel this booking?'); ?>')">
              <i class="fas fa-times me-2"></i><?php echo __('Cancel Booking'); ?>
            </button>
          </form>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ($booking->status === 'confirmed'): ?>
          <?php if ($isStaff && $booking->booking_date === date('Y-m-d')): ?>
            <?php if (!$booking->checked_in_at): ?>
            <a href="<?php echo url_for(['module' => 'research', 'action' => 'checkIn', 'id' => $booking->id]); ?>" class="btn btn-success w-100 mb-2" onclick="return confirm('<?php echo __('Check in researcher?'); ?>')">
              <i class="fas fa-sign-in-alt me-2"></i><?php echo __('Check In'); ?>
            </a>
            <?php elseif (!$booking->checked_out_at): ?>
            <a href="<?php echo url_for(['module' => 'research', 'action' => 'checkOut', 'id' => $booking->id]); ?>" class="btn btn-warning w-100 mb-2" onclick="return confirm('<?php echo __('Check out researcher?'); ?>')">
              <i class="fas fa-sign-out-alt me-2"></i><?php echo __('Check Out'); ?>
            </a>
            <?php endif; ?>
            
            <?php if (!$booking->checked_in_at): ?>
            <form method="post" class="mt-2">
              <input type="hidden" name="action" value="noshow">
              <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('<?php echo __('Mark as no-show?'); ?>')">
                <i class="fas fa-user-slash me-2"></i><?php echo __('No Show'); ?>
              </button>
            </form>
            <?php endif; ?>
          <?php endif; ?>
          
          <?php if ($isOwner && !$booking->checked_in_at): ?>
          <!-- Owner can cancel confirmed booking if not yet checked in -->
          <form method="post">
            <input type="hidden" name="action" value="cancel">
            <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('<?php echo __('Cancel this booking?'); ?>')">
              <i class="fas fa-times me-2"></i><?php echo __('Cancel My Booking'); ?>
            </button>
          </form>
          <?php endif; ?>
        <?php endif; ?>
        
      </div>
    </div>
    <?php endif; ?>

    <?php if ($booking->status === 'cancelled'): ?>
    <div class="alert alert-danger">
      <i class="fas fa-ban me-2"></i><?php echo __('This booking has been cancelled.'); ?>
    </div>
    <?php endif; ?>

    <?php if ($booking->status === 'completed'): ?>
    <div class="alert alert-success">
      <i class="fas fa-check-circle me-2"></i><?php echo __('This visit has been completed.'); ?>
    </div>
    <?php endif; ?>

    <?php if ($isOwner): ?>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'workspace']); ?>" class="btn btn-secondary w-100 mb-2">
      <i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Workspace'); ?>
    </a>
    <?php endif; ?>
    
    <?php if ($isStaff): ?>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'bookings']); ?>" class="btn btn-secondary w-100">
      <i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Bookings'); ?>
    </a>
    <?php endif; ?>
  </div>
</div>
<?php end_slot() ?>

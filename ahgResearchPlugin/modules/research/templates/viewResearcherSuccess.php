<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-user-graduate text-primary me-2"></i><?php echo htmlspecialchars($researcher->first_name . ' ' . $researcher->last_name); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="row">
  <div class="col-md-8">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-id-card me-2"></i><?php echo __('Researcher Profile'); ?></span>
        <span class="badge bg-<?php 
          echo $researcher->status === 'approved' ? 'success' : 
              ($researcher->status === 'pending' ? 'warning' : 
              ($researcher->status === 'rejected' ? 'danger' : 'secondary')); 
        ?>"><?php echo ucfirst($researcher->status); ?></span>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <h6 class="text-muted"><?php echo __('Personal Information'); ?></h6>
            <table class="table table-sm">
              <tr><th width="120"><?php echo __('Title'); ?></th><td><?php echo htmlspecialchars($researcher->title ?? '-'); ?></td></tr>
              <tr><th><?php echo __('Name'); ?></th><td><?php echo htmlspecialchars($researcher->first_name . ' ' . $researcher->last_name); ?></td></tr>
              <tr><th><?php echo __('Email'); ?></th><td><a href="mailto:<?php echo htmlspecialchars($researcher->email); ?>"><?php echo htmlspecialchars($researcher->email); ?></a></td></tr>
              <tr><th><?php echo __('Phone'); ?></th><td><?php echo htmlspecialchars($researcher->phone ?? '-'); ?></td></tr>
              <tr><th><?php echo __('ID Type'); ?></th><td><?php echo htmlspecialchars($researcher->id_type ?? '-'); ?></td></tr>
              <tr><th><?php echo __('ID Number'); ?></th><td><?php echo htmlspecialchars($researcher->id_number ?? '-'); ?></td></tr>
            </table>
          </div>
          <div class="col-md-6">
            <h6 class="text-muted"><?php echo __('Affiliation'); ?></h6>
            <table class="table table-sm">
              <tr><th width="120"><?php echo __('Type'); ?></th><td><?php echo ucfirst($researcher->affiliation_type ?? '-'); ?></td></tr>
              <tr><th><?php echo __('Institution'); ?></th><td><?php echo htmlspecialchars($researcher->institution ?? '-'); ?></td></tr>
              <tr><th><?php echo __('Department'); ?></th><td><?php echo htmlspecialchars($researcher->department ?? '-'); ?></td></tr>
              <tr><th><?php echo __('Position'); ?></th><td><?php echo htmlspecialchars($researcher->position ?? '-'); ?></td></tr>
              <tr><th><?php echo __('ORCID'); ?></th><td><?php echo htmlspecialchars($researcher->orcid_id ?? '-'); ?></td></tr>
            </table>
          </div>
        </div>

        <?php if ($researcher->research_interests || $researcher->current_project): ?>
        <hr>
        <div class="row">
          <?php if ($researcher->research_interests): ?>
          <div class="col-md-6">
            <h6 class="text-muted"><?php echo __('Research Interests'); ?></h6>
            <p><?php echo nl2br(htmlspecialchars($researcher->research_interests)); ?></p>
          </div>
          <?php endif; ?>
          <?php if ($researcher->current_project): ?>
          <div class="col-md-6">
            <h6 class="text-muted"><?php echo __('Current Project'); ?></h6>
            <p><?php echo nl2br(htmlspecialchars($researcher->current_project)); ?></p>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($researcher->status === 'rejected' && $researcher->rejection_reason): ?>
        <div class="alert alert-danger mt-3">
          <strong><?php echo __('Rejection Reason:'); ?></strong> <?php echo htmlspecialchars($researcher->rejection_reason); ?>
        </div>
        <?php endif; ?>

        <hr>
        <small class="text-muted">
          <?php echo __('Registered:'); ?> <?php echo $researcher->created_at; ?>
          <?php if ($researcher->approved_at): ?>
            | <?php echo __('Approved:'); ?> <?php echo $researcher->approved_at; ?>
          <?php endif; ?>
          <?php if ($researcher->expires_at): ?>
            | <?php echo __('Expires:'); ?> <?php echo $researcher->expires_at; ?>
          <?php endif; ?>
        </small>
      </div>
    </div>

    <!-- Bookings -->
    <?php if (!empty($bookings)): ?>
    <div class="card">
      <div class="card-header">
        <i class="fas fa-calendar-alt me-2"></i><?php echo __('Booking History'); ?>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th><?php echo __('Date'); ?></th>
              <th><?php echo __('Time'); ?></th>
              <th><?php echo __('Room'); ?></th>
              <th><?php echo __('Status'); ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $booking): ?>
            <tr>
              <td><?php echo $booking->booking_date; ?></td>
              <td><?php echo substr($booking->start_time, 0, 5) . ' - ' . substr($booking->end_time, 0, 5); ?></td>
              <td><?php echo htmlspecialchars($booking->room_name); ?></td>
              <td>
                <span class="badge bg-<?php 
                  echo $booking->status === 'confirmed' ? 'success' : 
                      ($booking->status === 'pending' ? 'warning' : 
                      ($booking->status === 'completed' ? 'info' : 'secondary')); 
                ?>"><?php echo ucfirst($booking->status); ?></span>
              </td>
              <td>
                <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewBooking', 'id' => $booking->id]); ?>" class="btn btn-sm btn-outline-primary">
                  <i class="fas fa-eye"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Admin Actions Sidebar -->
  <div class="col-md-4">
    <?php if ($sf_user->hasCredential('administrator')): ?>
    <div class="card mb-4">
      <div class="card-header bg-dark text-white">
        <i class="fas fa-cog me-2"></i><?php echo __('Admin Actions'); ?>
      </div>
      <div class="card-body">
        <?php if ($researcher->status === 'pending'): ?>
        <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'approveResearcher', 'id' => $researcher->id]); ?>" class="mb-2">
          <button type="submit" class="btn btn-success w-100">
            <i class="fas fa-check me-2"></i><?php echo __('Approve Researcher'); ?>
          </button>
        </form>
        <button type="button" class="btn btn-danger w-100 mb-3" data-bs-toggle="modal" data-bs-target="#rejectModal">
          <i class="fas fa-times me-2"></i><?php echo __('Reject Registration'); ?>
        </button>
        <?php endif; ?>

        <?php if ($researcher->status === 'approved'): ?>
        <form method="post" class="mb-2">
          <input type="hidden" name="booking_action" value="suspend">
          <button type="submit" class="btn btn-warning w-100">
            <i class="fas fa-ban me-2"></i><?php echo __('Suspend Account'); ?>
          </button>
        </form>
        <?php endif; ?>

        <hr>

        <a href="<?php echo url_for(['module' => 'research', 'action' => 'adminResetPassword', 'id' => $researcher->id]); ?>" class="btn btn-outline-secondary w-100 mb-2" onclick="return confirm('<?php echo __('Generate a new password for this user?'); ?>')">
          <i class="fas fa-key me-2"></i><?php echo __('Reset Password'); ?>
        </a>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <i class="fas fa-chart-bar me-2"></i><?php echo __('Statistics'); ?>
      </div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
          <span><?php echo __('Total Bookings'); ?></span>
          <strong><?php echo count($bookings ?? []); ?></strong>
        </li>
      </ul>
    </div>

    <div class="mt-3">
      <a href="<?php echo url_for(['module' => 'research', 'action' => 'researchers']); ?>" class="btn btn-secondary w-100">
        <i class="fas fa-arrow-left me-2"></i><?php echo __('Back to List'); ?>
      </a>
    </div>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'rejectResearcher', 'id' => $researcher->id]); ?>">
        <div class="modal-header">
          <h5 class="modal-title"><?php echo __('Reject Registration'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Reason for Rejection'); ?></label>
            <textarea name="reason" class="form-control" rows="3" placeholder="<?php echo __('Provide a reason...'); ?>"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-danger"><?php echo __('Reject'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php end_slot() ?>

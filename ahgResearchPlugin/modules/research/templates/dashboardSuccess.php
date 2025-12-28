<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-book-reader text-primary me-2"></i><?php echo __('Research Services'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<!-- Welcome / Registration Banner for non-researchers -->
<?php if (!$sf_user->isAuthenticated()): ?>
<div class="alert alert-info mb-4">
  <div class="row align-items-center">
    <div class="col-md-8">
      <h4><i class="fas fa-user-plus me-2"></i><?php echo __('Register as a Researcher'); ?></h4>
      <p class="mb-0"><?php echo __('Create an account to book reading room visits, request materials, and save your research.'); ?></p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
      <a href="<?php echo url_for(['module' => 'research', 'action' => 'publicRegister']); ?>" class="btn btn-primary btn-lg">
        <i class="fas fa-user-plus me-2"></i><?php echo __('Register Now'); ?>
      </a>
      <div class="mt-2">
        <small><a href="<?php echo url_for(['module' => 'user', 'action' => 'login']); ?>"><?php echo __('Already have an account? Login'); ?></a></small>
      </div>
    </div>
  </div>
</div>
<?php elseif (!$researcher): ?>
<div class="alert alert-warning mb-4">
  <div class="row align-items-center">
    <div class="col-md-8">
      <h4><i class="fas fa-clipboard-list me-2"></i><?php echo __('Complete Your Researcher Profile'); ?></h4>
      <p class="mb-0"><?php echo __('You need to complete your researcher registration to book reading room visits.'); ?></p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
      <a href="<?php echo url_for(['module' => 'research', 'action' => 'register']); ?>" class="btn btn-warning">
        <i class="fas fa-edit me-2"></i><?php echo __('Complete Registration'); ?>
      </a>
    </div>
  </div>
</div>
<?php elseif ($researcher->status === 'pending'): ?>
<div class="alert alert-info mb-4">
  <h4><i class="fas fa-clock me-2"></i><?php echo __('Registration Pending'); ?></h4>
  <p class="mb-0"><?php echo __('Your researcher registration is being reviewed. You will be notified once approved.'); ?></p>
</div>
<?php elseif ($researcher->status === 'approved'): ?>
<div class="card bg-light mb-4">
  <div class="card-body">
    <div class="row align-items-center">
      <div class="col-md-8">
        <h5 class="mb-1"><?php echo __('Welcome back,'); ?> <?php echo htmlspecialchars($researcher->first_name); ?>!</h5>
        <p class="text-muted mb-0"><?php echo htmlspecialchars($researcher->institution ?? 'Independent Researcher'); ?></p>
      </div>
      <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'book']); ?>" class="btn btn-primary">
          <i class="fas fa-calendar-plus me-2"></i><?php echo __('Book Visit'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'profile']); ?>" class="btn btn-outline-secondary">
          <i class="fas fa-user me-2"></i><?php echo __('My Profile'); ?>
        </a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Statistics -->
<div class="row mb-4">
  <div class="col-md-3">
    <div class="card text-center h-100">
      <div class="card-body">
        <h2 class="text-primary"><?php echo number_format($stats['researchers'] ?? 0); ?></h2>
        <p class="mb-0"><?php echo __('Registered Researchers'); ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center h-100">
      <div class="card-body">
        <h2 class="text-success"><?php echo number_format($stats['bookings_today'] ?? 0); ?></h2>
        <p class="mb-0"><?php echo __("Today's Bookings"); ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center h-100">
      <div class="card-body">
        <h2 class="text-info"><?php echo number_format($stats['bookings_week'] ?? 0); ?></h2>
        <p class="mb-0"><?php echo __('This Week'); ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center h-100">
      <div class="card-body">
        <h2 class="text-warning"><?php echo number_format($stats['pending_requests'] ?? 0); ?></h2>
        <p class="mb-0"><?php echo __('Pending Requests'); ?></p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Today's Bookings -->
  <div class="col-md-8">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-calendar-day me-2"></i><?php echo __("Today's Schedule"); ?></span>
        <?php if ($sf_user->isAuthenticated()): ?>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'bookings']); ?>" class="btn btn-sm btn-outline-primary"><?php echo __('View All'); ?></a>
        <?php endif; ?>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th><?php echo __('Time'); ?></th>
              <th><?php echo __('Researcher'); ?></th>
              <th><?php echo __('Room'); ?></th>
              <th><?php echo __('Status'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($todayBookings)): ?>
            <tr>
              <td colspan="4" class="text-center text-muted py-4"><?php echo __('No bookings scheduled for today'); ?></td>
            </tr>
            <?php else: ?>
              <?php foreach ($todayBookings as $booking): ?>
              <tr>
                <td><?php echo substr($booking->start_time, 0, 5) . ' - ' . substr($booking->end_time, 0, 5); ?></td>
                <td><?php echo htmlspecialchars($booking->first_name . ' ' . $booking->last_name); ?></td>
                <td><?php echo htmlspecialchars($booking->room_name); ?></td>
                <td>
                  <span class="badge bg-<?php echo $booking->status === 'confirmed' ? 'success' : 'warning'; ?>">
                    <?php echo ucfirst($booking->status); ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Quick Links / Pending Researchers -->
  <div class="col-md-4">
    <?php if ($sf_user->isAdministrator() && !empty($pendingResearchers)): ?>
    <div class="card mb-4">
      <div class="card-header bg-warning">
        <i class="fas fa-user-clock me-2"></i><?php echo __('Pending Approvals'); ?>
        <span class="badge bg-dark float-end"><?php echo count(sfOutputEscaper::unescape($pendingResearchers)); ?></span>
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach (array_slice(sfOutputEscaper::unescape($pendingResearchers), 0, 5) as $pending): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <strong><?php echo htmlspecialchars($pending->first_name . ' ' . $pending->last_name); ?></strong><br>
            <small class="text-muted"><?php echo htmlspecialchars($pending->institution ?? 'Independent'); ?></small>
          </div>
          <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewResearcher', 'id' => $pending->id]); ?>" class="btn btn-sm btn-outline-primary">
            <?php echo __('Review'); ?>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php if (count(sfOutputEscaper::unescape($pendingResearchers)) > 5): ?>
      <div class="card-footer text-center">
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'researchers', 'status' => 'pending']); ?>"><?php echo __('View all pending'); ?></a>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <i class="fas fa-link me-2"></i><?php echo __('Quick Links'); ?>
      </div>
      <div class="list-group list-group-flush">
        <?php if ($sf_user->isAuthenticated()): ?>
          <?php if ($researcher && $researcher->status === 'approved'): ?>
          <a href="<?php echo url_for(['module' => 'research', 'action' => 'book']); ?>" class="list-group-item list-group-item-action">
            <i class="fas fa-calendar-plus me-2"></i><?php echo __('Book Reading Room'); ?>
          </a>
          <a href="<?php echo url_for(['module' => 'research', 'action' => 'workspace']); ?>" class="list-group-item list-group-item-action">
            <i class="fas fa-briefcase me-2"></i><?php echo __('My Workspace'); ?>
          </a>
          <a href="<?php echo url_for(['module' => 'research', 'action' => 'collections']); ?>" class="list-group-item list-group-item-action">
            <i class="fas fa-folder me-2"></i><?php echo __('My Collections'); ?>
          </a>
          <?php endif; ?>
          <?php if ($sf_user->isAdministrator()): ?>
          <a href="<?php echo url_for(['module' => 'research', 'action' => 'researchers']); ?>" class="list-group-item list-group-item-action">
            <i class="fas fa-users me-2"></i><?php echo __('Manage Researchers'); ?>
          </a>
          <a href="<?php echo url_for(['module' => 'research', 'action' => 'bookings']); ?>" class="list-group-item list-group-item-action">
            <i class="fas fa-calendar-alt me-2"></i><?php echo __('Manage Bookings'); ?>
          </a>
          <a href="<?php echo url_for(['module' => 'research', 'action' => 'rooms']); ?>" class="list-group-item list-group-item-action">
            <i class="fas fa-door-open me-2"></i><?php echo __('Reading Rooms'); ?>
          </a>
          <?php endif; ?>
        <?php else: ?>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'publicRegister']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-user-plus me-2"></i><?php echo __('Register as Researcher'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'user', 'action' => 'login']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-sign-in-alt me-2"></i><?php echo __('Login'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'passwordResetRequest']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-key me-2"></i><?php echo __('Forgot Password'); ?>
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php end_slot() ?>

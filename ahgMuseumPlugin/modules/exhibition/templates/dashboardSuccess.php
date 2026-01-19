<?php use_helper('Date'); ?>

<div class="row">
  <div class="col-12">
    <h1>Exhibition Dashboard</h1>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-3">
    <div class="card text-white bg-primary">
      <div class="card-body text-center">
        <h2 class="mb-0"><?php echo $stats['total_exhibitions'] ?? 0; ?></h2>
        <small>Total Exhibitions</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-white bg-success">
      <div class="card-body text-center">
        <h2 class="mb-0"><?php echo $stats['current_exhibitions'] ?? 0; ?></h2>
        <small>Currently Open</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-white bg-info">
      <div class="card-body text-center">
        <h2 class="mb-0"><?php echo $stats['upcoming_exhibitions'] ?? 0; ?></h2>
        <small>Upcoming</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-white bg-secondary">
      <div class="card-body text-center">
        <h2 class="mb-0"><?php echo $stats['total_objects_on_display'] ?? 0; ?></h2>
        <small>Objects on Display</small>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-8">
    <!-- Current Exhibitions -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Currently Open</h5>
        <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'index', 'status' => 'open']); ?>" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($currentExhibitions)): ?>
          <div class="p-4 text-center text-muted">
            <i class="fas fa-calendar-check fa-2x mb-2"></i>
            <p class="mb-0">No exhibitions currently open</p>
          </div>
        <?php else: ?>
          <div class="list-group list-group-flush">
            <?php foreach ($currentExhibitions as $exhibition): ?>
              <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'show', 'id' => $exhibition['id']]); ?>" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                  <h6 class="mb-1"><?php echo htmlspecialchars($exhibition['title']); ?></h6>
                  <small class="text-muted"><?php echo $exhibition['object_count'] ?? 0; ?> objects</small>
                </div>
                <p class="mb-1 small text-muted">
                  <?php if (!empty($exhibition['venue_name'])): ?>
                    <i class="fas fa-map-marker me-1"></i> <?php echo htmlspecialchars($exhibition['venue_name']); ?>
                  <?php endif; ?>
                  <?php if (!empty($exhibition['closing_date'])): ?>
                    <span class="ms-2"><i class="fas fa-calendar me-1"></i> Closes: <?php echo $exhibition['closing_date']; ?></span>
                  <?php endif; ?>
                </p>
                <?php if (!empty($exhibition['closing_date'])): ?>
                  <?php
                    $closingDate = new DateTime($exhibition['closing_date']);
                    $today = new DateTime();
                    $diff = $today->diff($closingDate);
                    $daysLeft = $diff->invert ? 0 : $diff->days;
                  ?>
                  <?php if ($daysLeft <= 30): ?>
                    <span class="badge bg-warning text-dark"><?php echo $daysLeft; ?> days remaining</span>
                  <?php endif; ?>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Upcoming Exhibitions -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Upcoming Exhibitions</h5>
        <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'index', 'status' => 'preparation']); ?>" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($upcomingExhibitions)): ?>
          <div class="p-4 text-center text-muted">
            <i class="fas fa-calendar fa-2x mb-2"></i>
            <p class="mb-0">No upcoming exhibitions scheduled</p>
          </div>
        <?php else: ?>
          <div class="list-group list-group-flush">
            <?php foreach ($upcomingExhibitions as $exhibition): ?>
              <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'show', 'id' => $exhibition['id']]); ?>" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                  <h6 class="mb-1"><?php echo htmlspecialchars($exhibition['title']); ?></h6>
                  <span class="badge" style="background-color: <?php echo $exhibition['status_info']['color'] ?? '#999'; ?>">
                    <?php echo $exhibition['status_info']['label'] ?? $exhibition['status']; ?>
                  </span>
                </div>
                <p class="mb-1 small text-muted">
                  <?php if (!empty($exhibition['opening_date'])): ?>
                    <i class="fas fa-calendar me-1"></i> Opens: <?php echo $exhibition['opening_date']; ?>
                  <?php endif; ?>
                  <?php if (!empty($exhibition['venue_name'])): ?>
                    <span class="ms-2"><i class="fas fa-map-marker me-1"></i> <?php echo htmlspecialchars($exhibition['venue_name']); ?></span>
                  <?php endif; ?>
                </p>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Workflow Overview -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Workflow Overview</h5>
      </div>
      <div class="card-body">
        <div class="row text-center">
          <?php foreach ($stats['by_status'] ?? [] as $status => $count): ?>
            <?php $statusInfo = $statuses[$status] ?? []; ?>
            <div class="col">
              <div class="p-2">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                     style="width: 50px; height: 50px; background-color: <?php echo $statusInfo['color'] ?? '#999'; ?>; color: white;">
                  <strong><?php echo $count; ?></strong>
                </div>
                <p class="mb-0 small"><?php echo $statusInfo['label'] ?? $status; ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <!-- Quick Actions -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Quick Actions</h5>
      </div>
      <div class="list-group list-group-flush">
        <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'add']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-plus me-2 text-success"></i> Create New Exhibition
        </a>
        <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'index']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-list me-2 text-primary"></i> View All Exhibitions
        </a>
        <a href="<?php echo url_for(['module' => 'museum', 'action' => 'index']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-archive me-2 text-secondary"></i> Object Registry
        </a>
      </div>
    </div>

    <!-- Pending Tasks -->
    <?php if (!empty($pendingChecklists)): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">Pending Checklist Items</h5>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            <?php foreach (array_slice($pendingChecklists, 0, 5) as $item): ?>
              <li class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <strong class="small"><?php echo htmlspecialchars($item['task_name']); ?></strong>
                    <br>
                    <small class="text-muted"><?php echo htmlspecialchars($item['exhibition_title']); ?></small>
                  </div>
                  <?php if (!empty($item['due_date'])): ?>
                    <?php
                      $dueDate = new DateTime($item['due_date']);
                      $today = new DateTime();
                      $isOverdue = $dueDate < $today;
                    ?>
                    <span class="badge <?php echo $isOverdue ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                      <?php echo $item['due_date']; ?>
                    </span>
                  <?php endif; ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <!-- Recent Activity -->
    <?php if (!empty($recentActivity)): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">Recent Activity</h5>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            <?php foreach (array_slice($recentActivity, 0, 8) as $activity): ?>
              <li class="list-group-item py-2">
                <small>
                  <strong><?php echo htmlspecialchars($activity['exhibition_title']); ?></strong>
                  <br>
                  <span class="text-muted">
                    <?php echo $activity['transition']; ?>
                    <?php if (!empty($activity['created_at'])): ?>
                      - <?php echo date('M j', strtotime($activity['created_at'])); ?>
                    <?php endif; ?>
                  </span>
                </small>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <!-- Exhibition Calendar Summary -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Calendar</h5>
      </div>
      <div class="card-body">
        <p class="small text-muted mb-2">Next 30 Days</p>
        <?php if (!empty($calendarEvents)): ?>
          <ul class="list-unstyled mb-0">
            <?php foreach (array_slice($calendarEvents, 0, 5) as $event): ?>
              <li class="mb-2">
                <small>
                  <strong><?php echo date('M j', strtotime($event['event_date'])); ?></strong> -
                  <?php echo htmlspecialchars($event['title']); ?>
                </small>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="small text-muted mb-0">No events scheduled</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php use_helper('Date'); ?>

<div class="row">
  <div class="col-md-8">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'index']); ?>">Exhibitions</a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($exhibition['title']); ?></li>
      </ol>
    </nav>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start mb-4">
      <div>
        <h1 class="mb-1"><?php echo $exhibition['title']; ?></h1>
        <?php if (!empty($exhibition['subtitle'])): ?>
          <p class="lead text-muted mb-0"><?php echo $exhibition['subtitle']; ?></p>
        <?php endif; ?>
      </div>
      <div class="btn-group">
        <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'edit', 'id' => $exhibition['id']]); ?>" class="btn btn-outline-primary">
          <i class="fas fa-edit"></i> Edit
        </a>
        <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
          <span class="visually-hidden">More</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'exhibition', 'action' => 'objects', 'id' => $exhibition['id']]); ?>">
            <i class="fas fa-cube me-2"></i> Manage Objects
          </a></li>
          <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'exhibition', 'action' => 'sections', 'id' => $exhibition['id']]); ?>">
            <i class="fas fa-th-large me-2"></i> Manage Sections
          </a></li>
          <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'exhibition', 'action' => 'storylines', 'id' => $exhibition['id']]); ?>">
            <i class="fas fa-book me-2"></i> Storylines
          </a></li>
          <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'exhibition', 'action' => 'events', 'id' => $exhibition['id']]); ?>">
            <i class="fas fa-calendar me-2"></i> Events
          </a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'exhibition', 'action' => 'checklists', 'id' => $exhibition['id']]); ?>">
            <i class="fas fa-check-square me-2"></i> Checklists
          </a></li>
          <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'exhibition', 'action' => 'objectList', 'id' => $exhibition['id']]); ?>">
            <i class="fas fa-list me-2"></i> Object List Report
          </a></li>
        </ul>
      </div>
    </div>

    <!-- Status Bar -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <span class="badge fs-6" style="background-color: <?php echo $exhibition['status_info']['color'] ?? '#999'; ?>">
              <?php echo $exhibition['status_info']['label'] ?? $exhibition['status']; ?>
            </span>
            <span class="badge bg-secondary ms-2"><?php echo $exhibition['type_label']; ?></span>
          </div>

          <?php if (!empty($validTransitions)): ?>
            <div class="dropdown">
              <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                Change Status
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <?php foreach ($validTransitions as $transition): ?>
                  <?php $statusInfo = $statuses[$transition] ?? []; ?>
                  <li>
                    <a class="dropdown-item transition-link" href="#"
                       data-status="<?php echo $transition; ?>"
                       data-label="<?php echo $statusInfo['label'] ?? $transition; ?>">
                      <span class="badge me-2" style="background-color: <?php echo $statusInfo['color'] ?? '#999'; ?>">&nbsp;</span>
                      <?php echo $statusInfo['label'] ?? $transition; ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
        </div>

        <!-- Progress bar -->
        <?php
          $progress = [
            'concept' => 10, 'planning' => 25, 'preparation' => 50,
            'installation' => 75, 'open' => 90, 'closing' => 95, 'closed' => 98, 'archived' => 100
          ];
          $currentProgress = $progress[$exhibition['status']] ?? 0;
        ?>
        <div class="progress mt-3" style="height: 8px;">
          <div class="progress-bar" role="progressbar" style="width: <?php echo $currentProgress; ?>%; background-color: <?php echo $exhibition['status_info']['color'] ?? '#999'; ?>"></div>
        </div>
      </div>
    </div>

    <!-- Dates -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Dates</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4">
            <label class="text-muted small">Opens</label>
            <p class="mb-0 fw-bold"><?php echo $exhibition['opening_date'] ?? 'Not set'; ?></p>
            <?php if ($exhibition['timing']['days_until_opening'] !== null): ?>
              <small class="text-info"><?php echo $exhibition['timing']['days_until_opening']; ?> days away</small>
            <?php elseif ($exhibition['timing']['days_since_opening'] !== null): ?>
              <small class="text-muted">Opened <?php echo $exhibition['timing']['days_since_opening']; ?> days ago</small>
            <?php endif; ?>
          </div>
          <div class="col-md-4">
            <label class="text-muted small">Closes</label>
            <p class="mb-0 fw-bold"><?php echo $exhibition['closing_date'] ?? 'Not set'; ?></p>
            <?php if ($exhibition['timing']['days_until_closing'] !== null): ?>
              <small class="text-warning"><?php echo $exhibition['timing']['days_until_closing']; ?> days remaining</small>
            <?php elseif ($exhibition['timing']['days_since_closing'] !== null): ?>
              <small class="text-muted">Closed <?php echo $exhibition['timing']['days_since_closing']; ?> days ago</small>
            <?php endif; ?>
          </div>
          <div class="col-md-4">
            <label class="text-muted small">Duration</label>
            <p class="mb-0 fw-bold"><?php echo $exhibition['timing']['duration_days'] ?? '-'; ?> days</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Description -->
    <?php if (!empty($exhibition['description']) || !empty($exhibition['theme'])): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>About</h5>
        </div>
        <div class="card-body">
          <?php if (!empty($exhibition['theme'])): ?>
            <p><strong>Theme:</strong> <?php echo $exhibition['theme']; ?></p>
          <?php endif; ?>
          <?php if (!empty($exhibition['description'])): ?>
            <p class="mb-0"><?php echo nl2br(htmlspecialchars($exhibition['description'])); ?></p>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Objects -->
    <?php
      // Handle Symfony output escaper for exhibition and objects
      $exhibitionRaw = ($exhibition instanceof sfOutputEscaperArrayDecorator) ? $exhibition->getRawValue() : $exhibition;
      $objectsRaw = $exhibitionRaw['objects'] ?? [];
      $objectsArray = ($objectsRaw instanceof sfOutputEscaperArrayDecorator) ? $objectsRaw->getRawValue() : (is_array($objectsRaw) ? $objectsRaw : []);
    ?>
    <?php if (!empty($objectsArray)): ?>
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fa-solid fa-boxes-stacked me-2"></i>Objects (<?php echo count($objectsArray); ?>)</h5>
          <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'objects', 'id' => $exhibition['id']]); ?>" class="btn btn-sm btn-outline-primary">
            Manage Objects
          </a>
        </div>
        <div class="list-group list-group-flush">
          <?php foreach (array_slice($objectsArray, 0, 5) as $obj): ?>
            <div class="list-group-item d-flex align-items-center">
              <?php if (!empty($obj['thumbnail_url'])): ?>
                <img src="<?php echo htmlspecialchars($obj['thumbnail_url']); ?>" alt="" class="me-3 rounded" style="width: 50px; height: 50px; object-fit: cover;"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="me-3 bg-secondary text-white align-items-center justify-content-center rounded" style="width: 50px; height: 50px; font-size: 1.2rem; display: none;">📦</div>
              <?php else: ?>
                <div class="me-3 bg-secondary text-white d-flex align-items-center justify-content-center rounded" style="width: 50px; height: 50px; font-size: 1.2rem;">
                  📦
                </div>
              <?php endif; ?>
              <div class="flex-grow-1">
                <strong><?php echo $obj['object_title'] ?? 'Untitled'; ?></strong>
                <br><small class="text-muted"><?php echo $obj['identifier'] ?? '-'; ?></small>
              </div>
              <span class="badge bg-secondary"><?php echo $obj['status_label']; ?></span>
            </div>
          <?php endforeach; ?>
          <?php if (count($objectsArray) > 5): ?>
            <div class="list-group-item text-center">
              <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'objects', 'id' => $exhibition['id']]); ?>">
                View all <?php echo count($objectsArray); ?> objects
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Sections -->
    <?php if (!empty($exhibition['sections'])): ?>
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-th-large me-2"></i>Sections (<?php echo count($exhibition['sections']); ?>)</h5>
          <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'sections', 'id' => $exhibition['id']]); ?>" class="btn btn-sm btn-outline-primary">
            Manage
          </a>
        </div>
        <div class="list-group list-group-flush">
          <?php foreach ($exhibition['sections'] as $section): ?>
            <div class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong><?php echo $section['title']; ?></strong>
                <span class="badge bg-info"><?php echo $section['object_count']; ?> objects</span>
              </div>
              <?php if (!empty($section['description'])): ?>
                <small class="text-muted"><?php echo mb_substr($section['description'], 0, 100); ?>...</small>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Storylines -->
    <?php if (!empty($exhibition['storylines'])): ?>
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-book me-2"></i>Storylines</h5>
          <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'storylines', 'id' => $exhibition['id']]); ?>" class="btn btn-sm btn-outline-primary">
            Manage
          </a>
        </div>
        <div class="list-group list-group-flush">
          <?php foreach ($exhibition['storylines'] as $storyline): ?>
            <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'storyline', 'id' => $storyline['id']]); ?>" class="list-group-item list-group-item-action">
              <div class="d-flex justify-content-between">
                <strong>
                  <?php if ($storyline['is_primary']): ?>
                    <i class="fas fa-star text-warning me-1"></i>
                  <?php endif; ?>
                  <?php echo $storyline['title']; ?>
                </strong>
                <span class="badge bg-secondary"><?php echo $storyline['stop_count']; ?> stops</span>
              </div>
              <small class="text-muted">
                <?php echo ucfirst($storyline['narrative_type']); ?>
                <?php if ($storyline['estimated_duration_minutes']): ?>
                  &middot; ~<?php echo $storyline['estimated_duration_minutes']; ?> min
                <?php endif; ?>
              </small>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-md-4">
    <!-- Statistics -->
    <?php if (!empty($exhibition['statistics'])): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">Statistics</h5>
        </div>
        <div class="card-body">
          <div class="row text-center">
            <div class="col-6 mb-3">
              <div class="fs-4 fw-bold text-primary"><?php echo $exhibition['statistics']['object_count']; ?></div>
              <small class="text-muted">Objects</small>
            </div>
            <div class="col-6 mb-3">
              <div class="fs-4 fw-bold text-info"><?php echo $exhibition['statistics']['section_count']; ?></div>
              <small class="text-muted">Sections</small>
            </div>
            <div class="col-6">
              <div class="fs-4 fw-bold text-success"><?php echo $exhibition['statistics']['storyline_count']; ?></div>
              <small class="text-muted">Storylines</small>
            </div>
            <div class="col-6">
              <div class="fs-4 fw-bold text-warning"><?php echo $exhibition['statistics']['event_count']; ?></div>
              <small class="text-muted">Events</small>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Details -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Details</h5>
      </div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted">Venue</span>
          <span><?php echo $exhibition['venue_name'] ?? '-'; ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted">Curator</span>
          <span><?php echo $exhibition['curator_name'] ?? '-'; ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted">Organized by</span>
          <span><?php echo $exhibition['organized_by'] ?? '-'; ?></span>
        </li>
        <?php if (!empty($exhibition['project_code'])): ?>
          <li class="list-group-item d-flex justify-content-between">
            <span class="text-muted">Project Code</span>
            <span><?php echo $exhibition['project_code']; ?></span>
          </li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Budget & Insurance -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Financial</h5>
      </div>
      <ul class="list-group list-group-flush">
        <?php if (!empty($exhibition['budget_amount'])): ?>
          <li class="list-group-item d-flex justify-content-between">
            <span class="text-muted">Budget</span>
            <span><?php echo number_format($exhibition['budget_amount'], 2); ?> <?php echo $exhibition['budget_currency'] ?? 'ZAR'; ?></span>
          </li>
        <?php endif; ?>
        <?php if (!empty($exhibition['statistics']['total_insurance_value'])): ?>
          <li class="list-group-item d-flex justify-content-between">
            <span class="text-muted">Total Insurance</span>
            <span><?php echo number_format($exhibition['statistics']['total_insurance_value'], 2); ?> <?php echo $exhibition['budget_currency'] ?? 'ZAR'; ?></span>
          </li>
        <?php endif; ?>
        <?php if (!empty($exhibition['admission_fee'])): ?>
          <li class="list-group-item d-flex justify-content-between">
            <span class="text-muted">Admission</span>
            <span><?php echo number_format($exhibition['admission_fee'], 2); ?> <?php echo $exhibition['admission_currency'] ?? 'ZAR'; ?></span>
          </li>
        <?php elseif ($exhibition['is_free_admission']): ?>
          <li class="list-group-item d-flex justify-content-between">
            <span class="text-muted">Admission</span>
            <span class="text-success">Free</span>
          </li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Upcoming Events -->
    <?php
      $eventsRaw = $exhibitionRaw['events'] ?? [];
      $eventsArray = ($eventsRaw instanceof sfOutputEscaperArrayDecorator) ? $eventsRaw->getRawValue() : (is_array($eventsRaw) ? $eventsRaw : []);
      $upcomingEvents = array_filter($eventsArray, fn($e) => ($e['event_date'] ?? '') >= date('Y-m-d'));
    ?>
    <?php if (!empty($upcomingEvents)): ?>
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Upcoming Events</h5>
          <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'events', 'id' => $exhibition['id']]); ?>" class="btn btn-sm btn-link">All</a>
        </div>
        <div class="list-group list-group-flush">
          <?php foreach (array_slice($upcomingEvents, 0, 3) as $event): ?>
              <div class="list-group-item">
                <div class="d-flex justify-content-between">
                  <strong><?php echo $event['title']; ?></strong>
                  <span class="badge bg-info"><?php echo $event['event_type']; ?></span>
                </div>
                <small class="text-muted">
                  <?php echo $event['event_date']; ?>
                  <?php if ($event['start_time']): ?>
                    at <?php echo $event['start_time']; ?>
                  <?php endif; ?>
                </small>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    <!-- Quick Links -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Reports</h5>
      </div>
      <div class="list-group list-group-flush">
        <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'objectList', 'id' => $exhibition['id']]); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-list me-2"></i> Object List
        </a>
        <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'objectList', 'id' => $exhibition['id'], 'format' => 'csv']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-download me-2"></i> Export Objects (CSV)
        </a>
        <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'checklists', 'id' => $exhibition['id']]); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-check-square me-2"></i> Checklists
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Status Change Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Change Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Change exhibition status to: <strong id="newStatusLabel"></strong></p>
        <div class="mb-3">
          <label class="form-label">Reason (optional)</label>
          <textarea id="statusReason" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmStatusChange">Confirm</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const modal = new bootstrap.Modal(document.getElementById('statusModal'));
  let selectedStatus = '';

  document.querySelectorAll('.transition-link').forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      selectedStatus = this.dataset.status;
      document.getElementById('newStatusLabel').textContent = this.dataset.label;
      modal.show();
    });
  });

  document.getElementById('confirmStatusChange').addEventListener('click', function() {
    const reason = document.getElementById('statusReason').value;

    fetch('<?php echo url_for(['module' => 'exhibition', 'action' => 'transition', 'id' => $exhibition['id']]); ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'status=' + encodeURIComponent(selectedStatus) + '&reason=' + encodeURIComponent(reason)
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        location.reload();
      } else {
        alert('Error: ' + data.error);
      }
    });
  });
});
</script>

<?php use_helper('Date'); ?>
<?php
// Convert escaped array to raw array for PHP array functions
$eventsRaw = $events ?? [];
if ($eventsRaw instanceof sfOutputEscaperArrayDecorator) {
    $events = $eventsRaw->getRawValue();
} else {
    $events = is_array($eventsRaw) ? $eventsRaw : [];
}
?>

<div class="row">
  <div class="col-md-8">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'index']); ?>">Exhibitions</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'show', 'id' => $exhibition['id']]); ?>"><?php echo htmlspecialchars($exhibition['title']); ?></a></li>
        <li class="breadcrumb-item active">Events</li>
      </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1>Exhibition Events</h1>
      <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEventModal">
        <i class="fas fa-plus"></i> Add Event
      </button>
    </div>

    <!-- Event Filters -->
    <div class="card mb-3">
      <div class="card-body py-2">
        <div class="d-flex align-items-center gap-2">
          <span class="small text-muted">Filter:</span>
          <a href="?" class="btn btn-sm <?php echo empty($filter) ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
          <a href="?filter=upcoming" class="btn btn-sm <?php echo ($filter ?? '') == 'upcoming' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Upcoming</a>
          <a href="?filter=past" class="btn btn-sm <?php echo ($filter ?? '') == 'past' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Past</a>
        </div>
      </div>
    </div>

    <?php if (empty($events)): ?>
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
          <h5>No events scheduled</h5>
          <p class="text-muted">Schedule events like openings, talks, workshops, and tours.</p>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
            <i class="fas fa-plus"></i> Schedule First Event
          </button>
        </div>
      </div>
    <?php else: ?>
      <?php
        $today = date('Y-m-d');
        $groupedEvents = [];
        foreach ($events as $event) {
          $month = date('F Y', strtotime($event['event_date']));
          $groupedEvents[$month][] = $event;
        }
      ?>

      <?php foreach ($groupedEvents as $month => $monthEvents): ?>
        <h5 class="text-muted mb-3"><?php echo $month; ?></h5>

        <?php foreach ($monthEvents as $event): ?>
          <?php
            $eventDate = $event['event_date'];
            $isPast = $eventDate < $today;
            $isToday = $eventDate == $today;
          ?>
          <div class="card mb-3 <?php echo $isPast ? 'opacity-75' : ''; ?>">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-auto text-center" style="min-width: 80px;">
                  <div class="<?php echo $isToday ? 'bg-primary text-white' : ($isPast ? 'bg-secondary text-white' : 'bg-light'); ?> rounded p-2">
                    <div class="h4 mb-0"><?php echo date('d', strtotime($eventDate)); ?></div>
                    <small><?php echo date('M', strtotime($eventDate)); ?></small>
                  </div>
                </div>
                <div class="col">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h5 class="mb-1"><?php echo htmlspecialchars($event['title']); ?></h5>
                      <p class="small text-muted mb-2">
                        <?php if (!empty($event['event_time'])): ?>
                          <i class="fas fa-clock me-1"></i> <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                        <?php endif; ?>
                        <?php if (!empty($event['event_type'])): ?>
                          <span class="badge bg-info ms-2 text-capitalize"><?php echo str_replace('_', ' ', $event['event_type']); ?></span>
                        <?php endif; ?>
                        <?php if ($isPast): ?>
                          <span class="badge bg-secondary ms-2">Past</span>
                        <?php elseif ($isToday): ?>
                          <span class="badge bg-success ms-2">Today</span>
                        <?php endif; ?>
                      </p>
                    </div>
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-outline-secondary"
                              data-bs-toggle="modal" data-bs-target="#editEventModal"
                              data-id="<?php echo $event['id']; ?>"
                              data-title="<?php echo htmlspecialchars($event['title']); ?>"
                              data-type="<?php echo $event['event_type'] ?? ''; ?>"
                              data-date="<?php echo $event['event_date']; ?>"
                              data-time="<?php echo $event['event_time'] ?? ''; ?>"
                              data-end-time="<?php echo $event['end_time'] ?? ''; ?>"
                              data-location="<?php echo htmlspecialchars($event['location'] ?? ''); ?>"
                              data-description="<?php echo htmlspecialchars($event['description'] ?? ''); ?>"
                              data-capacity="<?php echo $event['capacity'] ?? ''; ?>"
                              data-registration="<?php echo !empty($event['registration_required']) ? '1' : '0'; ?>"
                              data-free="<?php echo !empty($event['is_free']) ? '1' : '0'; ?>"
                              data-price="<?php echo $event['ticket_price'] ?? ''; ?>">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button type="button" class="btn btn-outline-danger"
                              onclick="deleteEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars(addslashes($event['title'])); ?>')">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </div>

                  <?php if (!empty($event['description'])): ?>
                    <p class="small mb-2"><?php echo htmlspecialchars(mb_substr($event['description'], 0, 200)); ?><?php echo strlen($event['description']) > 200 ? '...' : ''; ?></p>
                  <?php endif; ?>

                  <div class="d-flex gap-3 small text-muted">
                    <?php if (!empty($event['location'])): ?>
                      <span><i class="fas fa-map-marker me-1"></i> <?php echo htmlspecialchars($event['location']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($event['capacity'])): ?>
                      <span><i class="fas fa-users me-1"></i> Capacity: <?php echo $event['capacity']; ?></span>
                    <?php endif; ?>
                    <?php if (!empty($event['registration_required'])): ?>
                      <span class="text-warning"><i class="fas fa-ticket me-1"></i> Registration Required</span>
                    <?php endif; ?>
                    <?php if (!empty($event['is_free'])): ?>
                      <span class="text-success"><i class="fas fa-gift me-1"></i> Free</span>
                    <?php elseif (!empty($event['ticket_price'])): ?>
                      <span><i class="fas fa-money-bill me-1"></i> R<?php echo number_format($event['ticket_price'], 2); ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="col-md-4">
    <div class="card mb-3">
      <div class="card-header">
        <h5 class="mb-0">Exhibition Info</h5>
      </div>
      <div class="card-body">
        <h6><?php echo htmlspecialchars($exhibition['title']); ?></h6>
        <p class="small text-muted mb-2">
          <span class="badge" style="background-color: <?php echo $exhibition['status_info']['color'] ?? '#999'; ?>">
            <?php echo $exhibition['status_info']['label'] ?? $exhibition['status']; ?>
          </span>
        </p>
        <?php if (!empty($exhibition['opening_date'])): ?>
          <p class="small mb-0">
            <i class="fas fa-calendar me-1"></i>
            <?php echo $exhibition['opening_date']; ?>
            <?php if (!empty($exhibition['closing_date'])): ?>
              - <?php echo $exhibition['closing_date']; ?>
            <?php endif; ?>
          </p>
        <?php endif; ?>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header">
        <h5 class="mb-0">Event Types</h5>
      </div>
      <div class="card-body">
        <ul class="list-unstyled small mb-0">
          <li class="mb-1"><strong>Opening</strong> - Exhibition launch event</li>
          <li class="mb-1"><strong>Closing</strong> - Final day celebration</li>
          <li class="mb-1"><strong>Talk</strong> - Lectures and presentations</li>
          <li class="mb-1"><strong>Tour</strong> - Guided tours</li>
          <li class="mb-1"><strong>Workshop</strong> - Hands-on activities</li>
          <li class="mb-1"><strong>Performance</strong> - Live performances</li>
          <li class="mb-1"><strong>Private View</strong> - VIP or members</li>
          <li><strong>Other</strong> - General events</li>
        </ul>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Summary</h5>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0">
          <li class="d-flex justify-content-between mb-2">
            <span>Total Events</span>
            <strong><?php echo count($events); ?></strong>
          </li>
          <li class="d-flex justify-content-between mb-2">
            <span>Upcoming</span>
            <strong><?php echo count(array_filter($events, fn($e) => $e['event_date'] >= date('Y-m-d'))); ?></strong>
          </li>
          <li class="d-flex justify-content-between">
            <span>Past</span>
            <strong><?php echo count(array_filter($events, fn($e) => $e['event_date'] < date('Y-m-d'))); ?></strong>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Add Event Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Event</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?php echo url_for(['module' => 'exhibition', 'action' => 'addEvent', 'id' => $exhibition['id']]); ?>">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Event Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" required placeholder="e.g., Opening Night, Curator's Talk, Family Workshop">
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Event Type</label>
              <select name="event_type" id="addEventType" class="form-select tom-select">
                <option value="other">Other</option>
                <option value="opening">Opening</option>
                <option value="closing">Closing</option>
                <option value="talk">Talk/Lecture</option>
                <option value="tour">Tour</option>
                <option value="workshop">Workshop</option>
                <option value="performance">Performance</option>
                <option value="private_view">Private View</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date <span class="text-danger">*</span></label>
              <input type="date" name="event_date" class="form-control" required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Start Time</label>
              <input type="time" name="event_time" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">End Time</label>
              <input type="time" name="end_time" class="form-control">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control" placeholder="e.g., Main Hall, Lecture Theatre, Gallery A">
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Event details..."></textarea>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Capacity</label>
              <input type="number" name="capacity" class="form-control" min="1" placeholder="Max attendees">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">&nbsp;</label>
              <div class="form-check mt-2">
                <input type="checkbox" name="registration_required" class="form-check-input" value="1" id="addRegRequired">
                <label class="form-check-label" for="addRegRequired">Registration Required</label>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">&nbsp;</label>
              <div class="form-check mt-2">
                <input type="checkbox" name="is_free" class="form-check-input" value="1" id="addIsFree" checked>
                <label class="form-check-label" for="addIsFree">Free Event</label>
              </div>
            </div>
          </div>

          <div class="mb-3" id="addPriceRow" style="display: none;">
            <label class="form-label">Ticket Price (ZAR)</label>
            <input type="number" name="ticket_price" class="form-control" min="0" step="0.01">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Event</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Event Modal -->
<div class="modal fade" id="editEventModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Event</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?php echo url_for(['module' => 'exhibition', 'action' => 'updateEvent', 'id' => $exhibition['id']]); ?>">
        <input type="hidden" name="event_id" id="editEventId">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Event Title <span class="text-danger">*</span></label>
            <input type="text" name="title" id="editEventTitle" class="form-control" required>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Event Type</label>
              <select name="event_type" id="editEventType" class="form-select tom-select-edit">
                <option value="other">Other</option>
                <option value="opening">Opening</option>
                <option value="closing">Closing</option>
                <option value="talk">Talk/Lecture</option>
                <option value="tour">Tour</option>
                <option value="workshop">Workshop</option>
                <option value="performance">Performance</option>
                <option value="private_view">Private View</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date <span class="text-danger">*</span></label>
              <input type="date" name="event_date" id="editEventDate" class="form-control" required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Start Time</label>
              <input type="time" name="event_time" id="editEventTime" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">End Time</label>
              <input type="time" name="end_time" id="editEventEndTime" class="form-control">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Location</label>
            <input type="text" name="location" id="editEventLocation" class="form-control">
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" id="editEventDescription" class="form-control" rows="3"></textarea>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Capacity</label>
              <input type="number" name="capacity" id="editEventCapacity" class="form-control" min="1">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">&nbsp;</label>
              <div class="form-check mt-2">
                <input type="checkbox" name="registration_required" class="form-check-input" value="1" id="editRegRequired">
                <label class="form-check-label" for="editRegRequired">Registration Required</label>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">&nbsp;</label>
              <div class="form-check mt-2">
                <input type="checkbox" name="is_free" class="form-check-input" value="1" id="editIsFree">
                <label class="form-check-label" for="editIsFree">Free Event</label>
              </div>
            </div>
          </div>

          <div class="mb-3" id="editPriceRow">
            <label class="form-label">Ticket Price (ZAR)</label>
            <input type="number" name="ticket_price" id="editEventPrice" class="form-control" min="0" step="0.01">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Toggle price field based on free checkbox
document.getElementById('addIsFree').addEventListener('change', function() {
  document.getElementById('addPriceRow').style.display = this.checked ? 'none' : 'block';
});

document.getElementById('editIsFree').addEventListener('change', function() {
  document.getElementById('editPriceRow').style.display = this.checked ? 'none' : 'block';
});

// Edit modal population
document.getElementById('editEventModal').addEventListener('show.bs.modal', function(event) {
  const button = event.relatedTarget;
  document.getElementById('editEventId').value = button.dataset.id;
  document.getElementById('editEventTitle').value = button.dataset.title || '';
  document.getElementById('editEventType').value = button.dataset.type || 'other';
  document.getElementById('editEventDate').value = button.dataset.date || '';
  document.getElementById('editEventTime').value = button.dataset.time || '';
  document.getElementById('editEventEndTime').value = button.dataset.endTime || '';
  document.getElementById('editEventLocation').value = button.dataset.location || '';
  document.getElementById('editEventDescription').value = button.dataset.description || '';
  document.getElementById('editEventCapacity').value = button.dataset.capacity || '';
  document.getElementById('editRegRequired').checked = button.dataset.registration === '1';
  document.getElementById('editIsFree').checked = button.dataset.free === '1';
  document.getElementById('editEventPrice').value = button.dataset.price || '';
  document.getElementById('editPriceRow').style.display = button.dataset.free === '1' ? 'none' : 'block';
});

// Delete event
function deleteEvent(id, title) {
  if (confirm('Delete event "' + title + '"?')) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo url_for(['module' => 'exhibition', 'action' => 'deleteEvent', 'id' => $exhibition['id']]); ?>';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'event_id';
    input.value = id;
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
  }
}
</script>

<!-- TOM Select -->
<link href="/plugins/ahgCorePlugin/web/css/vendor/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="/plugins/ahgCorePlugin/web/js/vendor/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Initialize TOM Select for add modal
  document.querySelectorAll('.tom-select').forEach(function(el) {
    new TomSelect(el, {
      allowEmptyOption: true,
      create: false
    });
  });

  // Initialize TOM Select for edit modal
  var editEventTypeSelect = document.getElementById('editEventType');
  var editEventTypeTom = null;
  if (editEventTypeSelect) {
    editEventTypeTom = new TomSelect(editEventTypeSelect, {
      allowEmptyOption: true,
      create: false
    });
  }

  // Update edit modal TOM Select when modal opens
  document.getElementById('editEventModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    if (editEventTypeTom) {
      editEventTypeTom.setValue(button.dataset.type || 'other');
    }
  });
});
</script>

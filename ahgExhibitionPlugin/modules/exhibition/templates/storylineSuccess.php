<?php use_helper('Date'); ?>
<?php
// Convert escaped array to raw array for PHP array functions
$stopsRaw = $storyline['stops'] ?? [];
if ($stopsRaw instanceof sfOutputEscaperArrayDecorator) {
    $stops = $stopsRaw->getRawValue();
} else {
    $stops = is_array($stopsRaw) ? $stopsRaw : [];
}
?>

<div class="row">
  <div class="col-md-8">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'index']); ?>">Exhibitions</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'show', 'id' => $exhibition['id']]); ?>"><?php echo htmlspecialchars($exhibition['title']); ?></a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'storylines', 'id' => $exhibition['id']]); ?>">Storylines</a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($storyline['title']); ?></li>
      </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1><?php echo htmlspecialchars($storyline['title']); ?></h1>
        <?php if (!empty($storyline['type'])): ?>
          <span class="badge bg-secondary text-capitalize"><?php echo str_replace('_', ' ', $storyline['type']); ?></span>
        <?php endif; ?>
        <?php if (!empty($storyline['target_audience'])): ?>
          <span class="badge bg-info text-capitalize"><?php echo str_replace('_', ' ', $storyline['target_audience']); ?></span>
        <?php endif; ?>
      </div>
      <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStopModal">
        <i class="fas fa-plus"></i> Add Stop
      </button>
    </div>

    <?php if (!empty($storyline['description'])): ?>
      <div class="card mb-4">
        <div class="card-body">
          <p class="mb-0"><?php echo nl2br(htmlspecialchars($storyline['description'])); ?></p>
        </div>
      </div>
    <?php endif; ?>

    <?php if (empty($stops)): ?>
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="fas fa-map-signs fa-3x text-muted mb-3"></i>
          <h5>No stops added yet</h5>
          <p class="text-muted">Add stops to create a narrative journey through the exhibition.</p>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStopModal">
            <i class="fas fa-plus"></i> Add First Stop
          </button>
        </div>
      </div>
    <?php else: ?>
      <!-- Visual Timeline -->
      <div class="storyline-timeline mb-4">
        <?php foreach ($stops as $index => $stop): ?>
          <div class="card mb-3 stop-card" data-id="<?php echo $stop['id']; ?>">
            <div class="card-body">
              <div class="d-flex">
                <div class="stop-number me-3">
                  <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                       style="width: 40px; height: 40px; font-weight: bold;">
                    <?php echo $stop['stop_order']; ?>
                  </div>
                  <?php if ($index < count($stops) - 1): ?>
                    <div class="stop-connector" style="width: 2px; height: 30px; background: #dee2e6; margin: 5px auto;"></div>
                  <?php endif; ?>
                </div>
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h5 class="mb-1"><?php echo htmlspecialchars($stop['title']); ?></h5>
                      <?php if (!empty($stop['object_title'])): ?>
                        <p class="small text-muted mb-2">
                          <i class="fas fa-archive me-1"></i>
                          <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $stop['object_slug']]); ?>">
                            <?php echo htmlspecialchars($stop['object_title']); ?>
                          </a>
                        </p>
                      <?php endif; ?>
                    </div>
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-outline-secondary"
                              data-bs-toggle="modal" data-bs-target="#editStopModal"
                              data-id="<?php echo $stop['id']; ?>"
                              data-title="<?php echo htmlspecialchars($stop['title']); ?>"
                              data-content="<?php echo htmlspecialchars($stop['narrative_content'] ?? ''); ?>"
                              data-duration="<?php echo $stop['duration_seconds'] ?? ''; ?>"
                              data-order="<?php echo $stop['stop_order']; ?>"
                              data-object="<?php echo $stop['exhibition_object_id'] ?? ''; ?>">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button type="button" class="btn btn-outline-danger"
                              onclick="deleteStop(<?php echo $stop['id']; ?>, '<?php echo htmlspecialchars(addslashes($stop['title'])); ?>')">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </div>

                  <?php if (!empty($stop['narrative_content'])): ?>
                    <p class="mb-2"><?php echo nl2br(htmlspecialchars(mb_substr($stop['narrative_content'], 0, 300))); ?><?php echo strlen($stop['narrative_content']) > 300 ? '...' : ''; ?></p>
                  <?php endif; ?>

                  <div class="d-flex gap-3 small text-muted">
                    <?php if (!empty($stop['duration_seconds'])): ?>
                      <span><i class="fas fa-clock me-1"></i> <?php echo floor($stop['duration_seconds'] / 60); ?>:<?php echo str_pad($stop['duration_seconds'] % 60, 2, '0', STR_PAD_LEFT); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($stop['audio_url'])): ?>
                      <span><i class="fas fa-headphones me-1"></i> Audio</span>
                    <?php endif; ?>
                    <?php if (!empty($stop['video_url'])): ?>
                      <span><i class="fas fa-video me-1"></i> Video</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-md-4">
    <div class="card mb-3">
      <div class="card-header">
        <h5 class="mb-0">Storyline Info</h5>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0">
          <li class="mb-2">
            <strong>Stops:</strong> <?php echo count($stops); ?>
          </li>
          <?php if (!empty($storyline['duration_minutes'])): ?>
            <li class="mb-2">
              <strong>Est. Duration:</strong> <?php echo $storyline['duration_minutes']; ?> min
            </li>
          <?php endif; ?>
          <?php
            $totalSeconds = array_sum(array_column($stops, 'duration_seconds'));
            if ($totalSeconds > 0):
          ?>
            <li class="mb-2">
              <strong>Total Content Time:</strong>
              <?php echo floor($totalSeconds / 60); ?> min <?php echo $totalSeconds % 60; ?> sec
            </li>
          <?php endif; ?>
          <li class="mb-2">
            <strong>Objects Featured:</strong>
            <?php echo count(array_filter(array_column($stops, 'exhibition_object_id'))); ?>
          </li>
        </ul>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header">
        <h5 class="mb-0">Actions</h5>
      </div>
      <div class="list-group list-group-flush">
        <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'storylines', 'id' => $exhibition['id']]); ?>"
           class="list-group-item list-group-item-action">
          <i class="fas fa-arrow-left me-2"></i> Back to Storylines
        </a>
        <a href="#" class="list-group-item list-group-item-action"
           onclick="window.print(); return false;">
          <i class="fas fa-print me-2"></i> Print Script
        </a>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Available Objects</h5>
      </div>
      <div class="card-body" style="max-height: 300px; overflow-y: auto;">
        <?php if (!empty($exhibitionObjects)): ?>
          <ul class="list-unstyled small mb-0">
            <?php foreach ($exhibitionObjects as $obj): ?>
              <li class="mb-2 pb-2 border-bottom">
                <strong><?php echo htmlspecialchars($obj['object_title'] ?? $obj['object_number']); ?></strong>
                <?php if (!empty($obj['section_name'])): ?>
                  <br><span class="text-muted"><?php echo htmlspecialchars($obj['section_name']); ?></span>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="small text-muted mb-0">No objects in exhibition yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Add Stop Modal -->
<div class="modal fade" id="addStopModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Stop</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?php echo url_for(['module' => 'exhibition', 'action' => 'addStop', 'id' => $exhibition['id'], 'storyline_id' => $storyline['id']]); ?>">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Stop Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" required placeholder="e.g., Welcome, The Journey Begins, Featured Masterpiece">
          </div>

          <?php if (!empty($exhibitionObjects)): ?>
            <div class="mb-3">
              <label class="form-label">Link to Object</label>
              <select name="exhibition_object_id" id="addStopObject" class="form-select tom-select">
                <option value="">-- No object --</option>
                <?php foreach ($exhibitionObjects as $obj): ?>
                  <option value="<?php echo $obj['id']; ?>">
                    <?php echo htmlspecialchars($obj['object_title'] ?? $obj['object_number']); ?>
                    <?php if (!empty($obj['section_name'])): ?> (<?php echo htmlspecialchars($obj['section_name']); ?>)<?php endif; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Narrative Content</label>
            <textarea name="narrative_content" class="form-control" rows="5"
                      placeholder="The interpretive text for this stop. This could be a docent script, audio guide transcript, or panel text..."></textarea>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Duration (seconds)</label>
              <input type="number" name="duration_seconds" class="form-control" min="0" placeholder="e.g., 90">
              <small class="text-muted">How long visitors typically spend here</small>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Stop Order</label>
              <input type="number" name="stop_order" class="form-control" min="1" value="<?php echo count($stops) + 1; ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Audio URL</label>
            <input type="url" name="audio_url" class="form-control" placeholder="https://...">
          </div>

          <div class="mb-3">
            <label class="form-label">Video URL</label>
            <input type="url" name="video_url" class="form-control" placeholder="https://...">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Stop</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Stop Modal -->
<div class="modal fade" id="editStopModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Stop</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?php echo url_for(['module' => 'exhibition', 'action' => 'updateStop', 'id' => $exhibition['id'], 'storyline_id' => $storyline['id']]); ?>">
        <input type="hidden" name="stop_id" id="editStopId">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Stop Title <span class="text-danger">*</span></label>
            <input type="text" name="title" id="editStopTitle" class="form-control" required>
          </div>

          <?php if (!empty($exhibitionObjects)): ?>
            <div class="mb-3">
              <label class="form-label">Link to Object</label>
              <select name="exhibition_object_id" id="editStopObject" class="form-select tom-select-edit">
                <option value="">-- No object --</option>
                <?php foreach ($exhibitionObjects as $obj): ?>
                  <option value="<?php echo $obj['id']; ?>">
                    <?php echo htmlspecialchars($obj['object_title'] ?? $obj['object_number']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Narrative Content</label>
            <textarea name="narrative_content" id="editStopContent" class="form-control" rows="5"></textarea>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Duration (seconds)</label>
              <input type="number" name="duration_seconds" id="editStopDuration" class="form-control" min="0">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Stop Order</label>
              <input type="number" name="stop_order" id="editStopOrder" class="form-control" min="1">
            </div>
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
// Edit modal population
document.getElementById('editStopModal').addEventListener('show.bs.modal', function(event) {
  const button = event.relatedTarget;
  document.getElementById('editStopId').value = button.dataset.id;
  document.getElementById('editStopTitle').value = button.dataset.title || '';
  document.getElementById('editStopContent').value = button.dataset.content || '';
  document.getElementById('editStopDuration').value = button.dataset.duration || '';
  document.getElementById('editStopOrder').value = button.dataset.order || '';
  if (document.getElementById('editStopObject')) {
    document.getElementById('editStopObject').value = button.dataset.object || '';
  }
});

// Delete stop
function deleteStop(id, title) {
  if (confirm('Delete stop "' + title + '"?')) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo url_for(['module' => 'exhibition', 'action' => 'deleteStop', 'id' => $exhibition['id'], 'storyline_id' => $storyline['id']]); ?>';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'stop_id';
    input.value = id;
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
  }
}
</script>

<!-- TOM Select -->
<link href="/plugins/ahgCorePlugin/css/vendor/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="/plugins/ahgCorePlugin/js/vendor/tom-select.complete.min.js"></script>
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
  var editObjectSelect = document.getElementById('editStopObject');
  var editObjectTom = null;
  if (editObjectSelect) {
    editObjectTom = new TomSelect(editObjectSelect, {
      allowEmptyOption: true,
      create: false
    });
  }

  // Update edit modal TOM Select when modal opens
  document.getElementById('editStopModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    if (editObjectTom) {
      editObjectTom.setValue(button.dataset.object || '');
    }
  });
});
</script>

<style>
@media print {
  .btn, .breadcrumb, .card-header h5, nav, .btn-group {
    display: none !important;
  }
  .card {
    border: none !important;
    box-shadow: none !important;
  }
  .col-md-4 {
    display: none !important;
  }
  .col-md-8 {
    width: 100% !important;
  }
}
</style>

<?php use_helper('Date'); ?>
<?php
// Convert escaped arrays to raw arrays for PHP array functions
$objectsRaw = $objects ?? [];
$objects = ($objectsRaw instanceof sfOutputEscaperArrayDecorator) ? $objectsRaw->getRawValue() : (is_array($objectsRaw) ? $objectsRaw : []);
$sectionsRaw = $sections ?? [];
$sections = ($sectionsRaw instanceof sfOutputEscaperArrayDecorator) ? $sectionsRaw->getRawValue() : (is_array($sectionsRaw) ? $sectionsRaw : []);
?>

<div class="row">
  <div class="col-md-8">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'index']); ?>">Exhibitions</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'show', 'id' => $exhibition['id']]); ?>"><?php echo htmlspecialchars($exhibition['title']); ?></a></li>
        <li class="breadcrumb-item active">Objects</li>
      </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1>Exhibition Objects</h1>
      <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addObjectModal">
        <i class="fas fa-plus"></i> Add Object
      </button>
    </div>

    <?php if (empty($objects)): ?>
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="fas fa-archive fa-3x text-muted mb-3"></i>
          <h5>No objects added yet</h5>
          <p class="text-muted">Add objects from the collection to this exhibition.</p>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addObjectModal">
            <i class="fas fa-plus"></i> Add First Object
          </button>
        </div>
      </div>
    <?php else: ?>
      <!-- Section Filter -->
      <?php if (!empty($sections)): ?>
        <div class="card mb-3">
          <div class="card-body py-2">
            <div class="d-flex align-items-center gap-2">
              <span class="small text-muted">Filter by section:</span>
              <a href="?" class="btn btn-sm <?php echo empty($currentSection) ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
              <?php foreach ($sections as $section): ?>
                <a href="?section=<?php echo $section['id']; ?>" class="btn btn-sm <?php echo ($currentSection ?? '') == $section['id'] ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                  <?php echo htmlspecialchars($section['title']); ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th style="width: 60px;"></th>
                <th>Object</th>
                <th>Section</th>
                <th>Location</th>
                <th>Display Order</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody id="objectList">
              <?php foreach ($objects as $object): ?>
                <tr data-id="<?php echo $object['id']; ?>">
                  <td class="text-center">
                    <i class="fas fa-grip-vertical text-muted drag-handle" style="cursor: move;"></i>
                  </td>
                  <td>
                    <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $object['object_slug']]); ?>">
                      <strong><?php echo htmlspecialchars($object['object_title'] ?? $object['identifier']); ?></strong>
                    </a>
                    <?php if (!empty($object['identifier'])): ?>
                      <br><small class="text-muted"><?php echo htmlspecialchars($object['identifier']); ?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($object['section_title'])): ?>
                      <?php echo htmlspecialchars($object['section_title']); ?>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($object['display_position'])): ?>
                      <?php echo htmlspecialchars($object['display_position']); ?>
                    <?php else: ?>
                      <span class="text-muted">Not assigned</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge bg-secondary"><?php echo $object['sequence_order'] ?? '-'; ?></span>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-outline-primary"
                              data-bs-toggle="modal" data-bs-target="#editObjectModal"
                              data-id="<?php echo $object['id']; ?>"
                              data-section="<?php echo $object['section_id'] ?? ''; ?>"
                              data-location="<?php echo htmlspecialchars($object['display_position'] ?? ''); ?>"
                              data-notes="<?php echo htmlspecialchars($object['installation_notes'] ?? ''); ?>"
                              data-order="<?php echo $object['sequence_order'] ?? ''; ?>"
                              title="Edit">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button type="button" class="btn btn-outline-danger"
                              onclick="removeObject(<?php echo $object['id']; ?>, '<?php echo htmlspecialchars(addslashes($object['object_title'] ?? $object['identifier'])); ?>')"
                              title="Remove">
                        <i class="fas fa-times"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer">
          <span class="text-muted"><?php echo count($objects); ?> objects in exhibition</span>
        </div>
      </div>
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
          <p class="small mb-1">
            <i class="fas fa-calendar me-1"></i>
            <?php echo $exhibition['opening_date']; ?>
            <?php if (!empty($exhibition['closing_date'])): ?>
              - <?php echo $exhibition['closing_date']; ?>
            <?php endif; ?>
          </p>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($sections)): ?>
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0">Sections</h5>
        </div>
        <ul class="list-group list-group-flush">
          <?php foreach ($sections as $section): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <?php echo htmlspecialchars($section['title']); ?>
              <span class="badge bg-primary rounded-pill"><?php echo $section['object_count'] ?? 0; ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Actions</h5>
      </div>
      <div class="list-group list-group-flush">
        <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'objectList', 'id' => $exhibition['id']]); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-file-text me-2"></i> Generate Object List
        </a>
        <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'sections', 'id' => $exhibition['id']]); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-th-large me-2"></i> Manage Sections
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Add Object Modal -->
<div class="modal fade" id="addObjectModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Object to Exhibition</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?php echo url_for(['module' => 'exhibition', 'action' => 'addObject', 'id' => $exhibition['id']]); ?>">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Search Objects</label>
            <input type="text" id="objectSearch" class="form-control" placeholder="Search by title, number, or description...">
            <div id="searchResults" class="list-group mt-2" style="max-height: 200px; overflow-y: auto;"></div>
            <input type="hidden" name="museum_object_id" id="selectedObjectId" required>
            <div id="selectedObject" class="alert alert-info mt-2 d-none"></div>
          </div>

          <?php if (!empty($sections)): ?>
            <div class="mb-3">
              <label class="form-label">Section</label>
              <select name="section_id" id="addSectionId" class="form-select tom-select">
                <option value="">-- No section --</option>
                <?php foreach ($sections as $section): ?>
                  <option value="<?php echo $section['id']; ?>"><?php echo htmlspecialchars($section['title']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Display Location</label>
            <input type="text" name="display_location" class="form-control" placeholder="e.g., Gallery A, Case 3">
          </div>

          <div class="mb-3">
            <label class="form-label">Display Notes</label>
            <textarea name="display_notes" class="form-control" rows="2" placeholder="Special display requirements..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Object</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Object Modal -->
<div class="modal fade" id="editObjectModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Object Placement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?php echo url_for(['module' => 'exhibition', 'action' => 'updateObject', 'id' => $exhibition['id']]); ?>">
        <input type="hidden" name="object_id" id="editObjectId">
        <div class="modal-body">
          <?php if (!empty($sections)): ?>
            <div class="mb-3">
              <label class="form-label">Section</label>
              <select name="section_id" id="editSectionId" class="form-select tom-select-edit">
                <option value="">-- No section --</option>
                <?php foreach ($sections as $section): ?>
                  <option value="<?php echo $section['id']; ?>"><?php echo htmlspecialchars($section['title']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Display Location</label>
            <input type="text" name="display_location" id="editLocation" class="form-control">
          </div>

          <div class="mb-3">
            <label class="form-label">Display Notes</label>
            <textarea name="display_notes" id="editNotes" class="form-control" rows="2"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Display Order</label>
            <input type="number" name="display_order" id="editOrder" class="form-control" min="0">
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
// Object search
let searchTimeout;
document.getElementById('objectSearch').addEventListener('input', function() {
  clearTimeout(searchTimeout);
  const query = this.value;

  if (query.length < 2) {
    document.getElementById('searchResults').innerHTML = '';
    return;
  }

  searchTimeout = setTimeout(function() {
    fetch('<?php echo url_for(['module' => 'exhibition', 'action' => 'searchObjects']); ?>?q=' + encodeURIComponent(query) + '&exhibition_id=<?php echo $exhibition['id']; ?>')
      .then(response => response.json())
      .then(data => {
        const results = document.getElementById('searchResults');
        results.innerHTML = '';

        if (data.objects && data.objects.length > 0) {
          data.objects.forEach(function(obj) {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action';
            item.innerHTML = '<strong>' + (obj.title || obj.object_number) + '</strong>' +
                           (obj.object_number ? '<br><small class="text-muted">' + obj.object_number + '</small>' : '');
            item.onclick = function() {
              selectObject(obj);
            };
            results.appendChild(item);
          });
        } else {
          results.innerHTML = '<div class="list-group-item text-muted">No objects found</div>';
        }
      });
  }, 300);
});

function selectObject(obj) {
  document.getElementById('selectedObjectId').value = obj.id;
  document.getElementById('selectedObject').className = 'alert alert-info mt-2';
  document.getElementById('selectedObject').innerHTML =
    '<strong>' + (obj.title || obj.object_number) + '</strong>' +
    (obj.object_number ? ' <small>(' + obj.object_number + ')</small>' : '');
  document.getElementById('searchResults').innerHTML = '';
  document.getElementById('objectSearch').value = '';
}

// Edit modal population
document.getElementById('editObjectModal').addEventListener('show.bs.modal', function(event) {
  const button = event.relatedTarget;
  document.getElementById('editObjectId').value = button.dataset.id;
  document.getElementById('editSectionId').value = button.dataset.section || '';
  document.getElementById('editLocation').value = button.dataset.location || '';
  document.getElementById('editNotes').value = button.dataset.notes || '';
  document.getElementById('editOrder').value = button.dataset.order || '';
});

// Remove object
function removeObject(id, title) {
  if (confirm('Remove "' + title + '" from this exhibition?')) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo url_for(['module' => 'exhibition', 'action' => 'removeObject', 'id' => $exhibition['id']]); ?>';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'object_id';
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
<!-- Sortable for drag and drop -->
<script src="/plugins/ahgCorePlugin/js/vendor/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Initialize TOM Select for add modal
  document.querySelectorAll('.tom-select').forEach(function(el) {
    new TomSelect(el, {
      allowEmptyOption: true,
      create: false
    });
  });

  // Initialize TOM Select for edit modal (needs special handling for setValue)
  var editSectionSelect = document.getElementById('editSectionId');
  var editSectionTom = null;
  if (editSectionSelect) {
    editSectionTom = new TomSelect(editSectionSelect, {
      allowEmptyOption: true,
      create: false
    });
  }

  // Update edit modal TOM Select when modal opens
  document.getElementById('editObjectModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    if (editSectionTom) {
      editSectionTom.setValue(button.dataset.section || '');
    }
  });

  // Initialize Sortable for drag and drop reordering
  var objectList = document.getElementById('objectList');
  if (objectList) {
    new Sortable(objectList, {
      handle: '.drag-handle',
      animation: 150,
      ghostClass: 'table-warning',
      onEnd: function(evt) {
        // Collect new order
        var rows = objectList.querySelectorAll('tr[data-id]');
        var order = [];
        rows.forEach(function(row, index) {
          order.push({
            id: row.dataset.id,
            sequence_order: index + 1
          });
          // Update badge display
          var badge = row.querySelector('.badge.bg-secondary');
          if (badge) {
            badge.textContent = index + 1;
          }
        });

        // Save new order via AJAX
        fetch('<?php echo url_for(['module' => 'exhibition', 'action' => 'reorderObjects', 'id' => $exhibition['id']]); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ order: order })
        })
        .then(response => response.json())
        .then(data => {
          if (!data.success) {
            alert('Error saving order: ' + (data.error || 'Unknown error'));
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error saving order');
        });
      }
    });
  }
});
</script>

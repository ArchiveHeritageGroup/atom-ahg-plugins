<?php use_helper('Date'); ?>

<div class="row">
  <div class="col-md-8">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'index']); ?>">Exhibitions</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'show', 'id' => $exhibition['id']]); ?>"><?php echo htmlspecialchars($exhibition['title']); ?></a></li>
        <li class="breadcrumb-item active">Sections</li>
      </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1>Exhibition Sections</h1>
      <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSectionModal">
        <i class="fa fa-plus"></i> Add Section
      </button>
    </div>

    <?php if (empty($sections)): ?>
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="fa fa-th-large fa-3x text-muted mb-3"></i>
          <h5>No sections created yet</h5>
          <p class="text-muted">Organize your exhibition by creating sections or galleries.</p>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSectionModal">
            <i class="fa fa-plus"></i> Create First Section
          </button>
        </div>
      </div>
    <?php else: ?>
      <div class="row" id="sectionList">
        <?php foreach ($sections as $section): ?>
          <div class="col-md-6 mb-3" data-id="<?php echo $section['id']; ?>">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                  <i class="fa fa-grip-vertical me-2 text-muted drag-handle" style="cursor: move;"></i>
                  <?php echo htmlspecialchars($section['name']); ?>
                </h6>
                <span class="badge bg-primary"><?php echo $section['display_order'] ?? 0; ?></span>
              </div>
              <div class="card-body">
                <?php if (!empty($section['description'])): ?>
                  <p class="small text-muted mb-2"><?php echo htmlspecialchars($section['description']); ?></p>
                <?php endif; ?>

                <?php if (!empty($section['gallery_name'])): ?>
                  <p class="small mb-1">
                    <i class="fa fa-map-marker me-1"></i>
                    <strong>Gallery:</strong> <?php echo htmlspecialchars($section['gallery_name']); ?>
                  </p>
                <?php endif; ?>

                <?php if (!empty($section['theme'])): ?>
                  <p class="small mb-1">
                    <i class="fa fa-tag me-1"></i>
                    <strong>Theme:</strong> <?php echo htmlspecialchars($section['theme']); ?>
                  </p>
                <?php endif; ?>

                <p class="small mb-0">
                  <i class="fa fa-archive me-1"></i>
                  <strong>Objects:</strong> <?php echo $section['object_count'] ?? 0; ?>
                </p>
              </div>
              <div class="card-footer bg-transparent">
                <div class="btn-group btn-group-sm w-100">
                  <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'objects', 'id' => $exhibition['id'], 'section' => $section['id']]); ?>"
                     class="btn btn-outline-primary">
                    <i class="fa fa-archive"></i> Objects
                  </a>
                  <button type="button" class="btn btn-outline-secondary"
                          data-bs-toggle="modal" data-bs-target="#editSectionModal"
                          data-id="<?php echo $section['id']; ?>"
                          data-name="<?php echo htmlspecialchars($section['name']); ?>"
                          data-description="<?php echo htmlspecialchars($section['description'] ?? ''); ?>"
                          data-gallery="<?php echo htmlspecialchars($section['gallery_name'] ?? ''); ?>"
                          data-theme="<?php echo htmlspecialchars($section['theme'] ?? ''); ?>"
                          data-order="<?php echo $section['display_order'] ?? 0; ?>">
                    <i class="fa fa-edit"></i> Edit
                  </button>
                  <button type="button" class="btn btn-outline-danger"
                          onclick="deleteSection(<?php echo $section['id']; ?>, '<?php echo htmlspecialchars(addslashes($section['name'])); ?>')">
                    <i class="fa fa-trash"></i>
                  </button>
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
        <h5 class="mb-0">Exhibition Info</h5>
      </div>
      <div class="card-body">
        <h6><?php echo htmlspecialchars($exhibition['title']); ?></h6>
        <p class="small text-muted mb-2">
          <span class="badge" style="background-color: <?php echo $exhibition['status_info']['color'] ?? '#999'; ?>">
            <?php echo $exhibition['status_info']['label'] ?? $exhibition['status']; ?>
          </span>
        </p>
        <p class="small mb-0">
          <strong><?php echo count($sections); ?></strong> sections
        </p>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Tips</h5>
      </div>
      <div class="card-body">
        <p class="small text-muted mb-2">
          <strong>Sections</strong> help organize your exhibition into logical groupings or physical spaces.
        </p>
        <ul class="small text-muted mb-0">
          <li>Drag sections to reorder them</li>
          <li>Assign objects to sections for better organization</li>
          <li>Use themes to create narrative flow</li>
          <li>Link to physical galleries if applicable</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Add Section Modal -->
<div class="modal fade" id="addSectionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Section</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?php echo url_for(['module' => 'exhibition', 'action' => 'addSection', 'id' => $exhibition['id']]); ?>">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Section Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required placeholder="e.g., Introduction, Main Gallery, African Art">
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Brief description of this section..."></textarea>
          </div>

          <?php if (!empty($galleries)): ?>
            <div class="mb-3">
              <label class="form-label">Gallery</label>
              <select name="gallery_id" class="form-select">
                <option value="">-- Select gallery --</option>
                <?php foreach ($galleries as $gallery): ?>
                  <option value="<?php echo $gallery['id']; ?>"><?php echo htmlspecialchars($gallery['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php else: ?>
            <div class="mb-3">
              <label class="form-label">Gallery Name</label>
              <input type="text" name="gallery_name" class="form-control" placeholder="Physical location name">
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Theme</label>
            <input type="text" name="theme" class="form-control" placeholder="e.g., Origins, Industrial Age, Modern Era">
          </div>

          <div class="mb-3">
            <label class="form-label">Display Order</label>
            <input type="number" name="display_order" class="form-control" min="0" value="<?php echo (count($sections) + 1) * 10; ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Section</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Section Modal -->
<div class="modal fade" id="editSectionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Section</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?php echo url_for(['module' => 'exhibition', 'action' => 'updateSection', 'id' => $exhibition['id']]); ?>">
        <input type="hidden" name="section_id" id="editSectionId">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Section Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="editName" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" id="editDescription" class="form-control" rows="2"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Gallery Name</label>
            <input type="text" name="gallery_name" id="editGallery" class="form-control">
          </div>

          <div class="mb-3">
            <label class="form-label">Theme</label>
            <input type="text" name="theme" id="editTheme" class="form-control">
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
// Edit modal population
document.getElementById('editSectionModal').addEventListener('show.bs.modal', function(event) {
  const button = event.relatedTarget;
  document.getElementById('editSectionId').value = button.dataset.id;
  document.getElementById('editName').value = button.dataset.name || '';
  document.getElementById('editDescription').value = button.dataset.description || '';
  document.getElementById('editGallery').value = button.dataset.gallery || '';
  document.getElementById('editTheme').value = button.dataset.theme || '';
  document.getElementById('editOrder').value = button.dataset.order || '0';
});

// Delete section
function deleteSection(id, name) {
  if (confirm('Delete section "' + name + '"? Objects will be unassigned but not removed from the exhibition.')) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo url_for(['module' => 'exhibition', 'action' => 'deleteSection', 'id' => $exhibition['id']]); ?>';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'section_id';
    input.value = id;
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
  }
}
</script>

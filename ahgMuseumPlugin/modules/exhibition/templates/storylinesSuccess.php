<?php use_helper('Date'); ?>

<div class="row">
  <div class="col-md-8">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'index']); ?>">Exhibitions</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'show', 'id' => $exhibition['id']]); ?>"><?php echo htmlspecialchars($exhibition['title']); ?></a></li>
        <li class="breadcrumb-item active">Storylines</li>
      </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1>Storylines &amp; Narratives</h1>
      <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStorylineModal">
        <i class="fa fa-plus"></i> Create Storyline
      </button>
    </div>

    <?php if (empty($storylines)): ?>
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="fa fa-book fa-3x text-muted mb-3"></i>
          <h5>No storylines created yet</h5>
          <p class="text-muted">Create narrative journeys through your exhibition with storylines.</p>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStorylineModal">
            <i class="fa fa-plus"></i> Create First Storyline
          </button>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($storylines as $storyline): ?>
        <div class="card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-0"><?php echo htmlspecialchars($storyline['title']); ?></h5>
              <?php if (!empty($storyline['type'])): ?>
                <small class="text-muted text-capitalize"><?php echo str_replace('_', ' ', $storyline['type']); ?></small>
              <?php endif; ?>
            </div>
            <div class="btn-group btn-group-sm">
              <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'storyline', 'id' => $exhibition['id'], 'storyline_id' => $storyline['id']]); ?>"
                 class="btn btn-outline-primary">
                <i class="fa fa-eye"></i> View
              </a>
              <button type="button" class="btn btn-outline-secondary"
                      data-bs-toggle="modal" data-bs-target="#editStorylineModal"
                      data-id="<?php echo $storyline['id']; ?>"
                      data-title="<?php echo htmlspecialchars($storyline['title']); ?>"
                      data-type="<?php echo $storyline['type'] ?? 'general'; ?>"
                      data-description="<?php echo htmlspecialchars($storyline['description'] ?? ''); ?>"
                      data-audience="<?php echo $storyline['target_audience'] ?? ''; ?>">
                <i class="fa fa-edit"></i>
              </button>
              <button type="button" class="btn btn-outline-danger"
                      onclick="deleteStoryline(<?php echo $storyline['id']; ?>, '<?php echo htmlspecialchars(addslashes($storyline['title'])); ?>')">
                <i class="fa fa-trash"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <?php if (!empty($storyline['description'])): ?>
              <p class="mb-3"><?php echo htmlspecialchars($storyline['description']); ?></p>
            <?php endif; ?>

            <div class="row">
              <div class="col-md-4">
                <p class="small mb-1">
                  <i class="fa fa-map-marker me-1"></i>
                  <strong><?php echo $storyline['stop_count'] ?? 0; ?></strong> stops
                </p>
              </div>
              <?php if (!empty($storyline['target_audience'])): ?>
                <div class="col-md-4">
                  <p class="small mb-1">
                    <i class="fa fa-users me-1"></i>
                    Audience: <strong class="text-capitalize"><?php echo str_replace('_', ' ', $storyline['target_audience']); ?></strong>
                  </p>
                </div>
              <?php endif; ?>
              <?php if (!empty($storyline['duration_minutes'])): ?>
                <div class="col-md-4">
                  <p class="small mb-1">
                    <i class="fa fa-clock-o me-1"></i>
                    Duration: <strong><?php echo $storyline['duration_minutes']; ?> min</strong>
                  </p>
                </div>
              <?php endif; ?>
            </div>

            <!-- Preview of stops -->
            <?php if (!empty($storyline['stops'])): ?>
              <hr>
              <div class="d-flex gap-2 flex-wrap">
                <?php foreach (array_slice($storyline['stops'], 0, 5) as $i => $stop): ?>
                  <span class="badge bg-light text-dark border">
                    <?php echo ($i + 1); ?>. <?php echo htmlspecialchars(mb_substr($stop['title'], 0, 20)); ?><?php echo strlen($stop['title']) > 20 ? '...' : ''; ?>
                  </span>
                <?php endforeach; ?>
                <?php if (count($storyline['stops']) > 5): ?>
                  <span class="badge bg-secondary">+<?php echo count($storyline['stops']) - 5; ?> more</span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
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
        <p class="small mb-0">
          <strong><?php echo count($storylines); ?></strong> storylines
        </p>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header">
        <h5 class="mb-0">Storyline Types</h5>
      </div>
      <div class="card-body">
        <ul class="list-unstyled small mb-0">
          <li class="mb-2"><strong>General</strong> - Default visitor tour</li>
          <li class="mb-2"><strong>Guided Tour</strong> - For docent-led visits</li>
          <li class="mb-2"><strong>Self-Guided</strong> - Independent visitor path</li>
          <li class="mb-2"><strong>Educational</strong> - School groups and learning</li>
          <li class="mb-2"><strong>Accessible</strong> - Accessibility-focused route</li>
          <li class="mb-2"><strong>Highlights</strong> - Quick overview tour</li>
          <li><strong>Thematic</strong> - Topic-specific journey</li>
        </ul>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Tips</h5>
      </div>
      <div class="card-body">
        <p class="small text-muted mb-2">
          <strong>Storylines</strong> create narrative paths through your exhibition.
        </p>
        <ul class="small text-muted mb-0">
          <li>Add stops to guide visitors</li>
          <li>Link stops to specific objects</li>
          <li>Include interpretive content</li>
          <li>Create multiple tours for different audiences</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Add Storyline Modal -->
<div class="modal fade" id="addStorylineModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Storyline</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?php echo url_for(['module' => 'exhibition', 'action' => 'addStoryline', 'id' => $exhibition['id']]); ?>">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" required placeholder="e.g., Main Tour, Family Trail, Art Through the Ages">
          </div>

          <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
              <option value="general">General</option>
              <option value="guided_tour">Guided Tour</option>
              <option value="self_guided">Self-Guided</option>
              <option value="educational">Educational</option>
              <option value="accessible">Accessible</option>
              <option value="highlights">Highlights</option>
              <option value="thematic">Thematic</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Brief overview of this narrative journey..."></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Target Audience</label>
            <select name="target_audience" class="form-select">
              <option value="">-- All visitors --</option>
              <option value="general">General Public</option>
              <option value="families">Families with Children</option>
              <option value="schools">School Groups</option>
              <option value="adults">Adults</option>
              <option value="experts">Experts/Specialists</option>
              <option value="accessible">Accessibility Needs</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Estimated Duration (minutes)</label>
            <input type="number" name="duration_minutes" class="form-control" min="5" step="5" placeholder="e.g., 45">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Storyline</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Storyline Modal -->
<div class="modal fade" id="editStorylineModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Storyline</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?php echo url_for(['module' => 'exhibition', 'action' => 'updateStoryline', 'id' => $exhibition['id']]); ?>">
        <input type="hidden" name="storyline_id" id="editStorylineId">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" id="editTitle" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type" id="editType" class="form-select">
              <option value="general">General</option>
              <option value="guided_tour">Guided Tour</option>
              <option value="self_guided">Self-Guided</option>
              <option value="educational">Educational</option>
              <option value="accessible">Accessible</option>
              <option value="highlights">Highlights</option>
              <option value="thematic">Thematic</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" id="editDescription" class="form-control" rows="3"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Target Audience</label>
            <select name="target_audience" id="editAudience" class="form-select">
              <option value="">-- All visitors --</option>
              <option value="general">General Public</option>
              <option value="families">Families with Children</option>
              <option value="schools">School Groups</option>
              <option value="adults">Adults</option>
              <option value="experts">Experts/Specialists</option>
              <option value="accessible">Accessibility Needs</option>
            </select>
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
document.getElementById('editStorylineModal').addEventListener('show.bs.modal', function(event) {
  const button = event.relatedTarget;
  document.getElementById('editStorylineId').value = button.dataset.id;
  document.getElementById('editTitle').value = button.dataset.title || '';
  document.getElementById('editType').value = button.dataset.type || 'general';
  document.getElementById('editDescription').value = button.dataset.description || '';
  document.getElementById('editAudience').value = button.dataset.audience || '';
});

// Delete storyline
function deleteStoryline(id, title) {
  if (confirm('Delete storyline "' + title + '"? All stops will be removed.')) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo url_for(['module' => 'exhibition', 'action' => 'deleteStoryline', 'id' => $exhibition['id']]); ?>';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'storyline_id';
    input.value = id;
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
  }
}
</script>

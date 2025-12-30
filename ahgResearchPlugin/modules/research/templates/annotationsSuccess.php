<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<div class="d-flex justify-content-between align-items-center">
  <h1>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'workspace']); ?>" class="btn btn-outline-secondary btn-sm me-2"><i class="fas fa-arrow-left"></i></a>
    <i class="fas fa-sticky-note text-warning me-2"></i><?php echo __('My Notes & Annotations'); ?>
  </h1>
  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAnnotationModal">
    <i class="fas fa-plus me-1"></i><?php echo __('Add Note'); ?>
  </button>
</div>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$annotations = is_array($annotations) ? $annotations : (method_exists($annotations, 'getRawValue') ? $annotations->getRawValue() : (is_iterable($annotations) ? iterator_to_array($annotations) : []));
?>

<?php if ($sf_user->hasFlash('success')): ?>
  <div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('success'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if (!empty($annotations)): ?>
<div class="row">
  <?php foreach ($annotations as $annotation): ?>
  <div class="col-md-6 col-lg-4 mb-4">
    <div class="card h-100">
      <div class="card-header bg-warning bg-opacity-25 d-flex justify-content-between align-items-center py-2">
        <strong><?php echo htmlspecialchars($annotation->title ?: __('Untitled Note')); ?></strong>
        <div class="dropdown">
          <button class="btn btn-sm btn-link p-0" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item edit-annotation" href="#" data-id="<?php echo $annotation->id; ?>" data-title="<?php echo htmlspecialchars($annotation->title ?? ''); ?>" data-content="<?php echo htmlspecialchars($annotation->content ?? ''); ?>" data-object-id="<?php echo $annotation->object_id ?? ''; ?>"><i class="fas fa-edit me-2"></i><?php echo __('Edit'); ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <form method="post" class="d-inline">
                <input type="hidden" name="do" value="delete">
                <input type="hidden" name="id" value="<?php echo $annotation->id; ?>">
                <button type="submit" class="dropdown-item text-danger" onclick="return confirm('<?php echo __('Delete this note?'); ?>')">
                  <i class="fas fa-trash me-2"></i><?php echo __('Delete'); ?>
                </button>
              </form>
            </li>
          </ul>
        </div>
      </div>
      <div class="card-body">
        <p class="card-text"><?php echo nl2br(htmlspecialchars($annotation->content ?? '')); ?></p>
      </div>
      <div class="card-footer bg-transparent small text-muted">
        <?php if (!empty($annotation->object_id)): ?>
          <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $annotation->object_slug ?? $annotation->object_id]); ?>">
            <i class="fas fa-link me-1"></i><?php echo htmlspecialchars($annotation->object_title ?? __('View Item')); ?>
          </a><br>
        <?php endif; ?>
        <i class="fas fa-clock me-1"></i><?php echo date('M j, Y H:i', strtotime($annotation->created_at)); ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-sticky-note fa-4x text-muted mb-3 opacity-50"></i>
  <h4 class="text-muted"><?php echo __('No notes yet'); ?></h4>
  <p class="text-muted"><?php echo __('Add notes to items while browsing or create standalone notes here.'); ?></p>
  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAnnotationModal">
    <i class="fas fa-plus me-1"></i><?php echo __('Create Your First Note'); ?>
  </button>
</div>
<?php endif; ?>

<!-- New/Edit Annotation Modal -->
<div class="modal fade" id="newAnnotationModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" id="annotationForm">
        <input type="hidden" name="do" value="create" id="annotationAction">
        <input type="hidden" name="id" value="" id="annotationId">
        <div class="modal-header">
          <h5 class="modal-title" id="annotationModalTitle"><i class="fas fa-sticky-note me-2"></i><?php echo __('New Note'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Title'); ?></label>
            <input type="text" name="title" id="annotationTitle" class="form-control" placeholder="<?php echo __('Optional title...'); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Note Content'); ?> *</label>
            <textarea name="content" id="annotationContent" class="form-control" rows="5" required placeholder="<?php echo __('Your notes...'); ?>"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Link to Item (optional)'); ?></label>
            <select name="object_id" id="annotationObjectId" class="form-control">
              <option value=""><?php echo __('None - standalone note'); ?></option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-primary" id="annotationSubmitBtn"><i class="fas fa-save me-1"></i><?php echo __('Save Note'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Tom Select CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
document.addEventListener('DOMContentLoaded', function() {
  var modal = document.getElementById('newAnnotationModal');
  var itemSelect;
  
  // Initialize Tom Select for item search
  itemSelect = new TomSelect('#annotationObjectId', {
    valueField: 'id',
    labelField: 'title',
    searchField: ['title', 'identifier'],
    placeholder: '<?php echo __("Search for an item..."); ?>',
    loadThrottle: 300,
    load: function(query, callback) {
      if (!query.length || query.length < 2) return callback();
      fetch('<?php echo url_for(["module" => "research", "action" => "searchItems"]); ?>?q=' + encodeURIComponent(query))
        .then(function(response) { return response.json(); })
        .then(function(json) { callback(json.items || []); })
        .catch(function() { callback(); });
    },
    render: {
      option: function(item, escape) {
        return '<div class="py-1"><strong>' + escape(item.title) + '</strong>' + 
               (item.identifier ? '<br><small class="text-muted">' + escape(item.identifier) + '</small>' : '') +
               '</div>';
      },
      item: function(item, escape) {
        return '<div>' + escape(item.title) + '</div>';
      }
    }
  });
  
  // Reset modal for new annotation
  modal.addEventListener('show.bs.modal', function(event) {
    if (!event.relatedTarget || !event.relatedTarget.classList.contains('edit-annotation')) {
      document.getElementById('annotationAction').value = 'create';
      document.getElementById('annotationId').value = '';
      document.getElementById('annotationTitle').value = '';
      document.getElementById('annotationContent').value = '';
      itemSelect.clear();
      document.getElementById('annotationModalTitle').innerHTML = '<i class="fas fa-sticky-note me-2"></i><?php echo __("New Note"); ?>';
      document.getElementById('annotationSubmitBtn').innerHTML = '<i class="fas fa-save me-1"></i><?php echo __("Save Note"); ?>';
    }
  });
  
  // Edit annotation
  document.querySelectorAll('.edit-annotation').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      document.getElementById('annotationAction').value = 'update';
      document.getElementById('annotationId').value = this.dataset.id;
      document.getElementById('annotationTitle').value = this.dataset.title;
      document.getElementById('annotationContent').value = this.dataset.content;
      itemSelect.clear();
      if (this.dataset.objectId) {
        itemSelect.addOption({id: this.dataset.objectId, title: 'Item #' + this.dataset.objectId});
        itemSelect.setValue(this.dataset.objectId);
      }
      document.getElementById('annotationModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i><?php echo __("Edit Note"); ?>';
      document.getElementById('annotationSubmitBtn').innerHTML = '<i class="fas fa-save me-1"></i><?php echo __("Update Note"); ?>';
      new bootstrap.Modal(document.getElementById('newAnnotationModal')).show();
    });
  });
});
</script>
<?php end_slot() ?>

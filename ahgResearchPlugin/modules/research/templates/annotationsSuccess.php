<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<div class="d-flex justify-content-between align-items-center">
  <h1>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'workspace']); ?>" class="btn btn-outline-secondary btn-sm me-2"><i class="fas fa-arrow-left"></i></a>
    <i class="fas fa-sticky-note text-warning me-2"></i><?php echo __('My Notes & Annotations'); ?>
  </h1>
  <div class="d-flex gap-2">
    <div class="dropdown">
      <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-file-export me-1"></i><?php echo __('Export'); ?></button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'exportNotes', 'format' => 'pdf']); ?>"><i class="fas fa-file-pdf me-2 text-danger"></i><?php echo __('PDF'); ?></a></li>
        <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'exportNotes', 'format' => 'docx']); ?>"><i class="fas fa-file-word me-2 text-primary"></i><?php echo __('DOCX'); ?></a></li>
      </ul>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAnnotationModal">
      <i class="fas fa-plus me-1"></i><?php echo __('Add Note'); ?>
    </button>
  </div>
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

<!-- Search Bar -->
<div class="row mb-3">
  <div class="col-md-6">
    <form method="get" class="input-group">
      <input type="text" name="q" class="form-control" placeholder="<?php echo __('Search notes...'); ?>" value="<?php echo htmlspecialchars($sf_request->getParameter('q', '')); ?>">
      <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
      <?php if ($sf_request->getParameter('q')): ?><a href="<?php echo url_for(['module' => 'research', 'action' => 'annotations']); ?>" class="btn btn-outline-secondary"><?php echo __('Clear'); ?></a><?php endif; ?>
    </form>
  </div>
  <div class="col-md-6 text-md-end">
    <div class="btn-group btn-group-sm">
      <a href="<?php echo url_for(['module' => 'research', 'action' => 'annotations']); ?>" class="btn btn-outline-secondary <?php echo !$sf_request->getParameter('visibility') ? 'active' : ''; ?>"><?php echo __('All'); ?></a>
      <a href="<?php echo url_for(['module' => 'research', 'action' => 'annotations', 'visibility' => 'private']); ?>" class="btn btn-outline-secondary <?php echo $sf_request->getParameter('visibility') === 'private' ? 'active' : ''; ?>"><?php echo __('Private'); ?></a>
      <a href="<?php echo url_for(['module' => 'research', 'action' => 'annotations', 'visibility' => 'shared']); ?>" class="btn btn-outline-secondary <?php echo $sf_request->getParameter('visibility') === 'shared' ? 'active' : ''; ?>"><?php echo __('Shared'); ?></a>
      <a href="<?php echo url_for(['module' => 'research', 'action' => 'annotations', 'visibility' => 'public']); ?>" class="btn btn-outline-secondary <?php echo $sf_request->getParameter('visibility') === 'public' ? 'active' : ''; ?>"><?php echo __('Public'); ?></a>
    </div>
  </div>
</div>

<?php if (!empty($annotations)): ?>
<div class="row">
  <?php foreach ($annotations as $annotation): ?>
  <div class="col-md-6 col-lg-4 mb-4" id="note-<?php echo $annotation->id; ?>">
    <div class="card h-100">
      <div class="card-header bg-warning bg-opacity-25 d-flex justify-content-between align-items-center py-2">
        <div>
          <a href="#note-<?php echo $annotation->id; ?>" class="text-decoration-none text-dark">
            <strong><i class="fas fa-sticky-note text-warning me-1"></i><?php echo htmlspecialchars($annotation->title ?: __('Untitled Note')); ?></strong>
          </a>
          <?php if (($annotation->visibility ?? 'private') !== 'private'): ?>
            <span class="badge bg-<?php echo ($annotation->visibility ?? 'private') === 'shared' ? 'info' : 'success'; ?> ms-1"><?php echo ucfirst($annotation->visibility ?? 'private'); ?></span>
          <?php endif; ?>
        </div>
        <div class="dropdown">
          <button class="btn btn-sm btn-link p-0" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item edit-annotation" href="#" data-id="<?php echo $annotation->id; ?>" data-title="<?php echo htmlspecialchars($annotation->title ?? ''); ?>" data-content="<?php echo htmlspecialchars($annotation->content ?? ''); ?>" data-object-id="<?php echo $annotation->object_id ?? ''; ?>"><i class="fas fa-edit me-2"></i><?php echo __('Edit'); ?></a></li>
            <li><a class="dropdown-item copy-note-link" href="#note-<?php echo $annotation->id; ?>" data-note-id="<?php echo $annotation->id; ?>"><i class="fas fa-copy me-2"></i><?php echo __('Copy Link'); ?></a></li>
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
        <div class="card-text"><?php if (($annotation->content_format ?? 'text') === 'html'): ?><?php echo $annotation->content; ?><?php else: ?><?php echo nl2br(htmlspecialchars($annotation->content ?? '')); ?><?php endif; ?></div>
      </div>
      <div class="card-footer bg-transparent small text-muted">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <?php if (!empty($annotation->object_id)): ?>
              <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $annotation->object_slug ?? $annotation->object_id]); ?>">
                <i class="fas fa-archive me-1"></i><?php echo htmlspecialchars($annotation->object_title ?? __('View Item')); ?>
              </a><br>
            <?php endif; ?>
            <i class="fas fa-clock me-1"></i><?php echo date('M j, Y H:i', strtotime($annotation->created_at)); ?>
          </div>
          <a href="#note-<?php echo $annotation->id; ?>" class="text-muted" title="<?php echo __('Permalink'); ?>"><i class="fas fa-hashtag"></i></a>
        </div>
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
            <label class="form-label"><?php echo __('Visibility'); ?></label>
            <select name="visibility" id="annotationVisibility" class="form-select">
              <option value="private"><?php echo __('Private - only you'); ?></option>
              <option value="shared"><?php echo __('Shared - project collaborators'); ?></option>
              <option value="public"><?php echo __('Public - all researchers'); ?></option>
            </select>
          </div>
          <input type="hidden" name="content_format" value="html">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Note Content'); ?> *</label>
            <textarea name="content" id="annotationContent" class="form-control d-none" rows="5"></textarea>
            <div id="annotationQuillEditor" style="height: 200px; background: #fff; color: #212529;"></div>
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
<link href="/plugins/ahgCorePlugin/web/css/vendor/tom-select.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
/* Fix Tom Select dropdown text visibility */
.ts-dropdown { background: #fff !important; color: #212529 !important; }
.ts-dropdown .option { color: #212529 !important; }
.ts-dropdown .option.active { background: #0d6efd !important; color: #fff !important; }
.ts-control { background: #fff !important; color: #212529 !important; }
.ts-control input { color: #212529 !important; }
.ts-control .item { color: #212529 !important; }
/* Fix annotation card text visibility */
.card { background-color: #fff !important; }
.card .card-header { color: #212529 !important; }
.card .card-header strong { color: #212529 !important; }
.card .card-body { color: #212529 !important; }
.card .card-body .card-text { color: #212529 !important; }
.card .card-footer { color: #6c757d !important; }
.card .card-footer a { color: #0d6efd !important; }
.dropdown-menu { background-color: #fff !important; border: 1px solid rgba(0,0,0,.15) !important; }
.dropdown-menu .dropdown-item { color: #212529 !important; background-color: transparent !important; }
.dropdown-menu .dropdown-item:hover, .dropdown-menu .dropdown-item:focus { background-color: #e9ecef !important; color: #212529 !important; }
.dropdown-menu .dropdown-item.text-danger { color: #dc3545 !important; }
.dropdown-menu .dropdown-item.text-danger:hover { background-color: #f8d7da !important; }
.dropdown-menu .dropdown-divider { border-color: #dee2e6 !important; }
.dropdown-menu a.dropdown-item { color: #212529 !important; }
.dropdown-menu a.edit-annotation { color: #212529 !important; }
/* Fix modal text visibility */
.modal-content { background-color: #fff !important; color: #212529 !important; }
.modal-header { color: #212529 !important; }
.modal-body { color: #212529 !important; }
.modal-body .form-label { color: #212529 !important; }
.modal-body .form-control { background-color: #fff !important; color: #212529 !important; }
</style>
<script src="/plugins/ahgCorePlugin/web/js/vendor/tom-select.complete.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var modal = document.getElementById('newAnnotationModal');
  var itemSelect;
  var quillEditor;

  // Initialize Quill
  quillEditor = new Quill('#annotationQuillEditor', {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ 'header': [1, 2, 3, false] }],
        ['bold', 'italic', 'underline'],
        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
        ['blockquote', 'link'],
        ['clean']
      ]
    },
    placeholder: '<?php echo __("Your notes..."); ?>'
  });
  
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
      quillEditor.root.innerHTML = '';
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
      quillEditor.root.innerHTML = this.dataset.content || '';
      document.getElementById('annotationModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i><?php echo __("Edit Note"); ?>';
      document.getElementById('annotationSubmitBtn').innerHTML = '<i class="fas fa-save me-1"></i><?php echo __("Update Note"); ?>';
      new bootstrap.Modal(document.getElementById('newAnnotationModal')).show();
    });
  });

  // Sync Quill content to hidden textarea on form submit
  document.getElementById('annotationForm').addEventListener('submit', function() {
    document.getElementById('annotationContent').value = quillEditor.root.innerHTML;
  });
});
</script>
<?php end_slot() ?>

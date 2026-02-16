<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<div class="d-flex justify-content-between align-items-center">
  <h1>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'workspace']); ?>" class="btn btn-outline-secondary btn-sm me-2"><i class="fas fa-arrow-left"></i></a>
    <i class="fas fa-sticky-note text-warning me-2"></i><?php echo __('My Notes & Annotations'); ?>
  </h1>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#exportNotesModal"><i class="fas fa-file-export me-1"></i><?php echo __('Export'); ?></button>
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

<?php
// Collect unique tags from all annotations for filter
$allTags = [];
foreach ($annotations as $ann) {
    if (!empty($ann->tags)) {
        foreach (array_map('trim', explode(',', $ann->tags)) as $t) {
            if ($t) $allTags[$t] = ($allTags[$t] ?? 0) + 1;
        }
    }
}
ksort($allTags);
$activeTag = $sf_request->getParameter('tag', '');
?>
<?php if (!empty($allTags)): ?>
<div class="mb-3">
  <small class="text-muted me-2"><i class="fas fa-tags"></i> <?php echo __('Tags:'); ?></small>
  <a href="<?php echo url_for(['module' => 'research', 'action' => 'annotations', 'q' => $sf_request->getParameter('q', '')]); ?>" class="badge bg-<?php echo !$activeTag ? 'primary' : 'light text-dark'; ?> text-decoration-none me-1"><?php echo __('All'); ?></a>
  <?php foreach ($allTags as $tag => $count): ?>
  <a href="<?php echo url_for(['module' => 'research', 'action' => 'annotations', 'tag' => $tag, 'q' => $sf_request->getParameter('q', '')]); ?>" class="badge bg-<?php echo $activeTag === $tag ? 'primary' : 'light text-dark'; ?> text-decoration-none me-1"><?php echo htmlspecialchars($tag); ?> <small>(<?php echo $count; ?>)</small></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

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
        <div class="d-flex align-items-center gap-1">
          <a href="#" class="btn btn-sm btn-outline-primary py-0 px-1 edit-annotation" data-id="<?php echo $annotation->id; ?>" data-title="<?php echo htmlspecialchars($annotation->title ?? ''); ?>" data-content="<?php echo htmlspecialchars($annotation->content ?? ''); ?>" data-object-id="<?php echo $annotation->object_id ?? ''; ?>" data-object-title="<?php echo htmlspecialchars($annotation->object_title ?? ''); ?>" data-entity-type="<?php echo $annotation->entity_type ?? 'information_object'; ?>" data-collection-id="<?php echo $annotation->collection_id ?? ''; ?>" data-visibility="<?php echo $annotation->visibility ?? 'private'; ?>" data-tags="<?php echo htmlspecialchars($annotation->tags ?? ''); ?>" title="<?php echo __('Edit'); ?>"><i class="fas fa-edit"></i></a>
          <div class="dropdown">
          <button class="btn btn-sm btn-link p-0" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item edit-annotation" href="#" data-id="<?php echo $annotation->id; ?>" data-title="<?php echo htmlspecialchars($annotation->title ?? ''); ?>" data-content="<?php echo htmlspecialchars($annotation->content ?? ''); ?>" data-object-id="<?php echo $annotation->object_id ?? ''; ?>" data-object-title="<?php echo htmlspecialchars($annotation->object_title ?? ''); ?>" data-entity-type="<?php echo $annotation->entity_type ?? 'information_object'; ?>" data-collection-id="<?php echo $annotation->collection_id ?? ''; ?>" data-visibility="<?php echo $annotation->visibility ?? 'private'; ?>" data-tags="<?php echo htmlspecialchars($annotation->tags ?? ''); ?>"><i class="fas fa-edit me-2"></i><?php echo __('Edit'); ?></a></li>
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
      </div>
      <div class="card-body">
        <div class="card-text"><?php if (($annotation->content_format ?? 'text') === 'html'): ?><?php echo $annotation->content; ?><?php else: ?><?php echo nl2br(htmlspecialchars($annotation->content ?? '')); ?><?php endif; ?></div>
        <?php if (!empty($annotation->tags)): ?>
        <div class="mt-2">
          <?php foreach (array_map('trim', explode(',', $annotation->tags)) as $tag): ?>
            <?php if ($tag): ?><a href="<?php echo url_for(['module' => 'research', 'action' => 'annotations', 'tag' => $tag]); ?>" class="badge bg-secondary text-decoration-none me-1"><?php echo htmlspecialchars($tag); ?></a><?php endif; ?>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="card-footer bg-transparent small text-muted">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <?php if (!empty($annotation->collection_id) && !empty($annotation->collection_name)): ?>
              <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewCollection', 'id' => $annotation->collection_id]); ?>">
                <i class="fas fa-folder me-1"></i><?php echo htmlspecialchars($annotation->collection_name); ?>
              </a><br>
            <?php endif; ?>
            <?php if (!empty($annotation->object_id)): ?>
              <?php
              $entityIcon = 'archive';
              $entityModule = 'informationobject';
              $et = $annotation->entity_type ?? 'information_object';
              if ($et === 'actor') { $entityIcon = 'user-tie'; $entityModule = 'actor'; }
              elseif ($et === 'repository') { $entityIcon = 'building'; $entityModule = 'repository'; }
              elseif ($et === 'accession') { $entityIcon = 'inbox'; $entityModule = 'accession'; }
              ?>
              <a href="<?php echo url_for(['module' => $entityModule, 'slug' => $annotation->object_slug ?? $annotation->object_id]); ?>">
                <i class="fas fa-<?php echo $entityIcon; ?> me-1"></i><?php echo htmlspecialchars($annotation->object_title ?? __('View Entity')); ?>
              </a><br>
            <?php endif; ?>
            <i class="fas fa-clock me-1"></i><?php echo date('M j, Y H:i', strtotime($annotation->created_at)); ?>
          </div>
          <div class="d-flex align-items-center gap-2">
            <a href="<?php echo url_for(['module' => 'research', 'action' => 'exportNotes', 'format' => 'pdf', 'id' => $annotation->id]); ?>" class="text-danger" title="<?php echo __('Export PDF'); ?>"><i class="fas fa-file-pdf"></i></a>
            <a href="<?php echo url_for(['module' => 'research', 'action' => 'exportNotes', 'format' => 'docx', 'id' => $annotation->id]); ?>" class="text-primary" title="<?php echo __('Export Word'); ?>"><i class="fas fa-file-word"></i></a>
            <a href="#note-<?php echo $annotation->id; ?>" class="text-muted" title="<?php echo __('Permalink'); ?>"><i class="fas fa-hashtag"></i></a>
          </div>
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
  <div class="modal-dialog modal-lg">
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
            <div id="annotationQuillEditor" style="min-height: 200px; max-height: 500px; background: #fff; color: #212529;"></div>
            <div style="cursor: ns-resize; text-align: center; padding: 2px 0; color: #adb5bd; user-select: none;" id="quillResizeHandle"><i class="fas fa-grip-lines"></i></div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Link to Collection'); ?></label>
            <select name="collection_id" id="annotationCollectionId" class="form-select">
              <option value=""><?php echo __('None'); ?></option>
              <?php
              $colls = isset($collections) ? (is_array($collections) ? $collections : (method_exists($collections, 'getRawValue') ? $collections->getRawValue() : (is_iterable($collections) ? iterator_to_array($collections) : []))) : [];
              foreach ($colls as $coll): ?>
                <option value="<?php echo $coll->id; ?>"><?php echo htmlspecialchars($coll->name); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Link to Entity (optional)'); ?></label>
            <div class="row g-2">
              <div class="col-4">
                <select name="entity_type" id="annotationEntityType" class="form-select form-select-sm">
                  <option value="information_object"><?php echo __('Item'); ?></option>
                  <option value="actor"><?php echo __('Authority Record'); ?></option>
                  <option value="repository"><?php echo __('Repository'); ?></option>
                  <option value="accession"><?php echo __('Accession'); ?></option>
                </select>
              </div>
              <div class="col-8">
                <select name="object_id" id="annotationObjectId" class="form-control">
                  <option value=""><?php echo __('None'); ?></option>
                </select>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Tags'); ?></label>
            <input type="text" name="tags" id="annotationTags" class="form-control" placeholder="<?php echo __('Comma-separated tags...'); ?>">
            <small class="text-muted"><?php echo __('e.g. genealogy, 19th century, photographs'); ?></small>
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

<!-- Export Notes Modal -->
<div class="modal fade" id="exportNotesModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-file-export me-2"></i><?php echo __('Export Notes'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-bold"><?php echo __('Select Notes to Export'); ?></label>
          <div class="form-check mb-2 border-bottom pb-2">
            <input class="form-check-input" type="checkbox" id="exportSelectAll" checked>
            <label class="form-check-label fw-bold" for="exportSelectAll"><?php echo __('Select All'); ?></label>
          </div>
          <?php if (!empty($annotations)): ?>
          <div style="max-height: 300px; overflow-y: auto;">
            <?php foreach ($annotations as $ann): ?>
            <div class="form-check mb-1">
              <input class="form-check-input export-note-check" type="checkbox" value="<?php echo $ann->id; ?>" id="exportNote<?php echo $ann->id; ?>" checked>
              <label class="form-check-label" for="exportNote<?php echo $ann->id; ?>"><?php echo htmlspecialchars($ann->title ?: __('Untitled Note')); ?> <small class="text-muted">(<?php echo date('M j, Y', strtotime($ann->created_at)); ?>)</small></label>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold"><?php echo __('Format'); ?></label>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-danger flex-fill export-selected-btn" data-format="pdf"><i class="fas fa-file-pdf me-1"></i><?php echo __('PDF'); ?></button>
            <button type="button" class="btn btn-outline-primary flex-fill export-selected-btn" data-format="docx"><i class="fas fa-file-word me-1"></i><?php echo __('Word'); ?></button>
            <button type="button" class="btn btn-outline-success flex-fill export-selected-btn" data-format="csv"><i class="fas fa-file-csv me-1"></i><?php echo __('CSV'); ?></button>
          </div>
        </div>
      </div>
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
/* Image sizing in Quill editor and annotation cards */
.ql-editor img { max-width: 100%; height: auto; max-height: 300px; cursor: pointer; border-radius: 4px; margin: 4px 0; }
.card-text img { max-width: 300px; height: auto; max-height: 250px; border-radius: 4px; margin: 4px 0; cursor: pointer; transition: max-width 0.2s; }
.card-text img:hover { max-width: 100%; max-height: none; }
.card-text img.img-expanded { max-width: 100%; max-height: none; }
</style>
<script src="/plugins/ahgCorePlugin/web/js/vendor/tom-select.complete.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  // Click to expand/collapse images in annotation cards
  document.querySelectorAll('.card-text img').forEach(function(img) {
    img.addEventListener('click', function() { this.classList.toggle('img-expanded'); });
  });
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
        ['blockquote', 'link', 'image'],
        ['clean']
      ]
    },
    placeholder: '<?php echo __("Your notes..."); ?>'
  });

  // Custom image upload handler
  quillEditor.getModule('toolbar').addHandler('image', function() {
    var input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/*');
    input.click();
    input.onchange = function() {
      var file = input.files[0];
      if (!file) return;
      if (file.size > 5 * 1024 * 1024) {
        alert('Image must be under 5MB');
        return;
      }
      var formData = new FormData();
      formData.append('image', file);
      fetch('<?php echo url_for(["module" => "research", "action" => "uploadNoteImage"]); ?>', {
        method: 'POST',
        body: formData
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.url) {
          var range = quillEditor.getSelection(true);
          quillEditor.insertEmbed(range.index, 'image', data.url);
          quillEditor.setSelection(range.index + 1);
        } else {
          alert(data.error || 'Upload failed');
        }
      })
      .catch(function() { alert('Upload failed'); });
    };
  });

  // Get current entity type for search URL
  function getSearchUrl() {
    var type = document.getElementById('annotationEntityType').value;
    if (type === 'information_object') {
      return '<?php echo url_for(["module" => "research", "action" => "searchItems"]); ?>';
    }
    return '<?php echo url_for(["module" => "research", "action" => "searchEntities"]); ?>?type=' + type;
  }

  // Initialize Tom Select for entity search
  itemSelect = new TomSelect('#annotationObjectId', {
    valueField: 'id',
    labelField: 'title',
    searchField: ['title', 'identifier'],
    placeholder: '<?php echo __("Search..."); ?>',
    loadThrottle: 300,
    load: function(query, callback) {
      if (!query.length || query.length < 2) return callback();
      var url = getSearchUrl();
      var sep = url.indexOf('?') >= 0 ? '&' : '?';
      fetch(url + sep + 'q=' + encodeURIComponent(query))
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
  
  var isEditMode = false;

  // Helper to decode HTML entities from data attributes
  function decodeHtml(html) {
    var tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.innerHTML;
  }

  // Reset modal for new annotation
  modal.addEventListener('show.bs.modal', function(event) {
    if (!isEditMode) {
      document.getElementById('annotationAction').value = 'create';
      document.getElementById('annotationId').value = '';
      document.getElementById('annotationTitle').value = '';
      document.getElementById('annotationContent').value = '';
      itemSelect.clear();
      quillEditor.root.innerHTML = '';
      var collReset = document.getElementById('annotationCollectionId');
      if (collReset) collReset.value = '';
      var visReset = document.getElementById('annotationVisibility');
      if (visReset) visReset.value = 'private';
      var etReset = document.getElementById('annotationEntityType');
      if (etReset) etReset.value = 'information_object';
      var tagsReset = document.getElementById('annotationTags');
      if (tagsReset) tagsReset.value = '';
      document.getElementById('annotationModalTitle').innerHTML = '<i class="fas fa-sticky-note me-2"></i><?php echo __("New Note"); ?>';
      document.getElementById('annotationSubmitBtn').innerHTML = '<i class="fas fa-save me-1"></i><?php echo __("Save Note"); ?>';
    }
    isEditMode = false;
  });

  // Edit annotation
  document.querySelectorAll('.edit-annotation').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      isEditMode = true;
      document.getElementById('annotationAction').value = 'update';
      document.getElementById('annotationId').value = this.dataset.id;
      document.getElementById('annotationTitle').value = this.dataset.title;
      document.getElementById('annotationContent').value = this.dataset.content;
      itemSelect.clear();
      if (this.dataset.objectId) {
        itemSelect.addOption({id: this.dataset.objectId, title: this.dataset.objectTitle || ('Item #' + this.dataset.objectId)});
        itemSelect.setValue(this.dataset.objectId);
      }
      quillEditor.root.innerHTML = decodeHtml(this.dataset.content || '');
      var collSelect = document.getElementById('annotationCollectionId');
      if (collSelect) collSelect.value = this.dataset.collectionId || '';
      var visSelect = document.getElementById('annotationVisibility');
      if (visSelect) visSelect.value = this.dataset.visibility || 'private';
      var etSelect = document.getElementById('annotationEntityType');
      if (etSelect) etSelect.value = this.dataset.entityType || 'information_object';
      var tagsInput = document.getElementById('annotationTags');
      if (tagsInput) tagsInput.value = this.dataset.tags || '';
      document.getElementById('annotationModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i><?php echo __("Edit Note"); ?>';
      document.getElementById('annotationSubmitBtn').innerHTML = '<i class="fas fa-save me-1"></i><?php echo __("Update Note"); ?>';
      new bootstrap.Modal(document.getElementById('newAnnotationModal')).show();
    });
  });

  // Drag-resize handle for Quill editor
  (function() {
    var handle = document.getElementById('quillResizeHandle');
    var editor = document.getElementById('annotationQuillEditor');
    var startY, startH;
    handle.addEventListener('mousedown', function(e) {
      startY = e.clientY;
      startH = editor.offsetHeight;
      function onMove(ev) { editor.style.height = Math.max(150, startH + (ev.clientY - startY)) + 'px'; }
      function onUp() { document.removeEventListener('mousemove', onMove); document.removeEventListener('mouseup', onUp); }
      document.addEventListener('mousemove', onMove);
      document.addEventListener('mouseup', onUp);
      e.preventDefault();
    });
  })();

  // Entity type change: clear Tom Select so it reloads with correct search
  document.getElementById('annotationEntityType').addEventListener('change', function() {
    itemSelect.clear();
    itemSelect.clearOptions();
  });

  // Sync Quill content to hidden textarea on form submit
  document.getElementById('annotationForm').addEventListener('submit', function() {
    document.getElementById('annotationContent').value = quillEditor.root.innerHTML;
  });

  // Export modal: Select All toggle
  var selectAllBox = document.getElementById('exportSelectAll');
  if (selectAllBox) {
    selectAllBox.addEventListener('change', function() {
      document.querySelectorAll('.export-note-check').forEach(function(cb) { cb.checked = selectAllBox.checked; });
    });
    document.querySelectorAll('.export-note-check').forEach(function(cb) {
      cb.addEventListener('change', function() {
        var all = document.querySelectorAll('.export-note-check');
        var checked = document.querySelectorAll('.export-note-check:checked');
        selectAllBox.checked = all.length === checked.length;
        selectAllBox.indeterminate = checked.length > 0 && checked.length < all.length;
      });
    });
  }

  // Export modal: Export selected notes
  document.querySelectorAll('.export-selected-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var format = this.dataset.format;
      var checked = document.querySelectorAll('.export-note-check:checked');
      if (checked.length === 0) { alert('<?php echo __("Please select at least one note to export."); ?>'); return; }
      var all = document.querySelectorAll('.export-note-check');
      if (checked.length === all.length) {
        // Export all
        window.location.href = '<?php echo url_for(["module" => "research", "action" => "exportNotes", "format" => "__FMT__"]); ?>'.replace('__FMT__', format);
      } else if (checked.length === 1) {
        // Single note
        window.location.href = '<?php echo url_for(["module" => "research", "action" => "exportNotes", "format" => "__FMT__", "id" => "__ID__"]); ?>'.replace('__FMT__', format).replace('__ID__', checked[0].value);
      } else {
        // Multiple selected — export via comma-separated IDs
        var ids = [];
        checked.forEach(function(cb) { ids.push(cb.value); });
        window.location.href = '<?php echo url_for(["module" => "research", "action" => "exportNotes", "format" => "__FMT__"]); ?>'.replace('__FMT__', format) + '&ids=' + ids.join(',');
      }
      bootstrap.Modal.getInstance(document.getElementById('exportNotesModal')).hide();
    });
  });
});
</script>
<?php end_slot() ?>

<?php $n = sfConfig::get('csp_nonce', ''); $nattr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<div class="container-fluid py-3">

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'researcher', 'action' => 'dashboard']) ?>">Researcher</a></li>
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $submissionId]) ?>"><?php echo htmlspecialchars($submission->title) ?></a></li>
      <li class="breadcrumb-item active"><?php echo $item ? 'Edit Item' : 'Add Item' ?></li>
    </ol>
  </nav>

  <?php if ($sf_user->hasFlash('notice')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('notice') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif ?>

  <form method="post">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0">
        <i class="bi bi-<?php echo $item ? 'pencil' : 'plus-circle' ?> me-2"></i>
        <?php echo $item ? 'Edit Item' : 'Add Item' ?>
      </h4>
      <div>
        <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $submissionId]) ?>"
           class="btn btn-outline-secondary me-1">
          <i class="bi bi-arrow-left me-1"></i>Back
        </a>
        <button type="submit" class="btn btn-success">
          <i class="bi bi-check-lg me-1"></i>Save
        </button>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-8">

        <!-- Item Type -->
        <div class="card mb-3">
          <div class="card-header"><h6 class="mb-0">Item Type</h6></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-bold">Type <span class="text-danger">*</span></label>
                <select name="item_type" class="form-select" id="itemType">
                  <option value="description" <?php echo ($item->item_type ?? 'description') === 'description' ? 'selected' : '' ?>>Description (ISAD(G))</option>
                  <option value="note" <?php echo ($item->item_type ?? '') === 'note' ? 'selected' : '' ?>>Research Note</option>
                  <option value="creator" <?php echo ($item->item_type ?? '') === 'creator' ? 'selected' : '' ?>>New Creator</option>
                  <option value="repository" <?php echo ($item->item_type ?? '') === 'repository' ? 'selected' : '' ?>>New Repository</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">Parent Item</label>
                <select name="parent_item_id" class="form-select">
                  <option value="">-- Root level --</option>
                  <?php foreach ($items as $parentItem): ?>
                    <?php if ((!$item || (int) $parentItem->id !== (int) $item->id) && $parentItem->item_type === 'description'): ?>
                      <option value="<?php echo $parentItem->id ?>"
                        <?php echo ($item && (int) ($item->parent_item_id ?? 0) === (int) $parentItem->id) ? 'selected' : '' ?>>
                        <?php echo htmlspecialchars($parentItem->title) ?>
                        (<?php echo $parentItem->level_of_description ?>)
                      </option>
                    <?php endif ?>
                  <?php endforeach ?>
                </select>
                <small class="text-muted">Place this item under an existing item for hierarchy.</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Identity Area (ISAD(G) 3.1) -->
        <div class="card mb-3" id="sectionIdentity">
          <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="bi bi-card-heading me-2"></i>Identity Area</h6></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label fw-bold">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($item->title ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold">Identifier</label>
                <input type="text" name="identifier" class="form-control" value="<?php echo htmlspecialchars($item->identifier ?? '') ?>" placeholder="e.g., MS-2024-001">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold">Level of Description</label>
                <select name="level_of_description" class="form-select">
                  <?php
                    $levels = ['fonds', 'subfonds', 'collection', 'series', 'subseries', 'file', 'item'];
                    foreach ($levels as $level):
                  ?>
                    <option value="<?php echo $level ?>" <?php echo ($item->level_of_description ?? 'item') === $level ? 'selected' : '' ?>>
                      <?php echo ucfirst($level) ?>
                    </option>
                  <?php endforeach ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold">Date (display)</label>
                <input type="text" name="date_display" class="form-control" value="<?php echo htmlspecialchars($item->date_display ?? '') ?>" placeholder="e.g., 1950-1975">
              </div>
              <div class="col-md-4">
                <div class="row g-2">
                  <div class="col-6">
                    <label class="form-label fw-bold">Start Date</label>
                    <input type="date" name="date_start" class="form-control" value="<?php echo $item->date_start ?? '' ?>">
                  </div>
                  <div class="col-6">
                    <label class="form-label fw-bold">End Date</label>
                    <input type="date" name="date_end" class="form-control" value="<?php echo $item->date_end ?? '' ?>">
                  </div>
                </div>
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Extent and Medium</label>
                <input type="text" name="extent_and_medium" class="form-control" value="<?php echo htmlspecialchars($item->extent_and_medium ?? '') ?>" placeholder="e.g., 3 boxes, 150 photographs">
              </div>
            </div>
          </div>
        </div>

        <!-- Content Area (ISAD(G) 3.3) -->
        <div class="card mb-3" id="sectionContent">
          <div class="card-header"><h6 class="mb-0"><i class="bi bi-file-text me-2"></i>Content and Structure</h6></div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label fw-bold">Scope and Content</label>
              <textarea name="scope_and_content" class="form-control" rows="4"><?php echo htmlspecialchars($item->scope_and_content ?? '') ?></textarea>
            </div>
          </div>
        </div>

        <!-- Access Points -->
        <div class="card mb-3" id="sectionAccessPoints">
          <div class="card-header bg-info text-white"><h6 class="mb-0"><i class="bi bi-tags me-2"></i>Access Points</h6></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-bold">Creators</label>
                <input type="text" name="creators" class="form-control" value="<?php echo htmlspecialchars($item->creators ?? '') ?>" placeholder="Comma-separated names">
                <small class="text-muted">Names of creators (persons, organizations, families).</small>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">Subjects</label>
                <input type="text" name="subjects" class="form-control" value="<?php echo htmlspecialchars($item->subjects ?? '') ?>" placeholder="Comma-separated subjects">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">Places</label>
                <input type="text" name="places" class="form-control" value="<?php echo htmlspecialchars($item->places ?? '') ?>" placeholder="Comma-separated place names">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">Genre</label>
                <input type="text" name="genres" class="form-control" value="<?php echo htmlspecialchars($item->genres ?? '') ?>" placeholder="Comma-separated genre terms">
              </div>
            </div>
          </div>
        </div>

        <!-- Conditions Area (ISAD(G) 3.4) -->
        <div class="card mb-3" id="sectionConditions">
          <div class="card-header"><h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Conditions of Access and Use</h6></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-bold">Conditions Governing Access</label>
                <textarea name="access_conditions" class="form-control" rows="2"><?php echo htmlspecialchars($item->access_conditions ?? '') ?></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">Conditions Governing Reproduction</label>
                <textarea name="reproduction_conditions" class="form-control" rows="2"><?php echo htmlspecialchars($item->reproduction_conditions ?? '') ?></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div class="card mb-3">
          <div class="card-header"><h6 class="mb-0"><i class="bi bi-sticky me-2"></i>Notes</h6></div>
          <div class="card-body">
            <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($item->notes ?? '') ?></textarea>
          </div>
        </div>

        <!-- Repository fields (shown when item_type = repository) -->
        <div class="card mb-3" id="sectionRepository" style="display:none;">
          <div class="card-header bg-warning"><h6 class="mb-0"><i class="bi bi-building me-2"></i>Repository Details</h6></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-12">
                <label class="form-label fw-bold">Repository Name</label>
                <input type="text" name="repository_name" class="form-control" value="<?php echo htmlspecialchars($item->repository_name ?? '') ?>">
              </div>
              <div class="col-md-8">
                <label class="form-label fw-bold">Address</label>
                <textarea name="repository_address" class="form-control" rows="2"><?php echo htmlspecialchars($item->repository_address ?? '') ?></textarea>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold">Contact</label>
                <input type="text" name="repository_contact" class="form-control" value="<?php echo htmlspecialchars($item->repository_contact ?? '') ?>" placeholder="Email or phone">
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Sidebar: Files -->
      <div class="col-lg-4">

        <?php if ($item): ?>
        <div class="card mb-3 sticky-top" style="top: 1rem;">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-paperclip me-2"></i>Files (<?php echo count($itemFiles) ?>)</h6>
          </div>
          <div class="card-body">
            <!-- Upload zone -->
            <?php if (in_array($submission->status, ['draft', 'returned'])): ?>
            <div class="mb-3">
              <input type="file" id="fileUpload" class="form-control form-control-sm" multiple>
              <small class="text-muted">Drop files or click to upload.</small>
              <div id="uploadProgress" class="mt-2"></div>
            </div>
            <?php endif ?>

            <!-- File list -->
            <div id="fileList">
              <?php if (empty($itemFiles)): ?>
                <p class="text-muted small mb-0" id="noFilesMsg">No files attached.</p>
              <?php endif ?>
              <?php foreach ($itemFiles as $f): ?>
                <div class="d-flex justify-content-between align-items-center mb-2 file-entry" data-id="<?php echo $f->id ?>">
                  <div class="text-truncate me-2">
                    <i class="bi bi-file-earmark me-1"></i>
                    <small><?php echo htmlspecialchars($f->original_name) ?></small>
                    <br><small class="text-muted"><?php echo round($f->file_size / 1024, 1) ?> KB</small>
                  </div>
                  <?php if (in_array($submission->status, ['draft', 'returned'])): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger delete-file-btn" data-file-id="<?php echo $f->id ?>">
                      <i class="bi bi-trash"></i>
                    </button>
                  <?php endif ?>
                </div>
              <?php endforeach ?>
            </div>
          </div>
        </div>
        <?php else: ?>
          <div class="alert alert-info">
            <i class="bi bi-info-circle me-1"></i>
            Save the item first, then you can upload files.
          </div>
        <?php endif ?>

      </div>
    </div>
  </form>

</div>

<?php if ($item): ?>
<script <?php echo $nattr ?>>
(function() {
  // Toggle sections based on item type
  var typeSelect = document.getElementById('itemType');
  function toggleSections() {
    var type = typeSelect.value;
    var show = function(id, vis) { var el = document.getElementById(id); if(el) el.style.display = vis ? '' : 'none'; };
    show('sectionIdentity', type === 'description' || type === 'note');
    show('sectionContent', true);
    show('sectionAccessPoints', type === 'description');
    show('sectionConditions', type === 'description');
    show('sectionRepository', type === 'repository');
  }
  typeSelect.addEventListener('change', toggleSections);
  toggleSections();

  // AJAX file upload
  var fileInput = document.getElementById('fileUpload');
  if (fileInput) {
    fileInput.addEventListener('change', function() {
      var files = this.files;
      for (var i = 0; i < files.length; i++) {
        uploadFile(files[i]);
      }
      this.value = '';
    });
  }

  function uploadFile(file) {
    var fd = new FormData();
    fd.append('file', file);
    fd.append('item_id', '<?php echo $item->id ?>');

    var prog = document.getElementById('uploadProgress');
    prog.innerHTML = '<small class="text-muted">Uploading ' + file.name + '...</small>';

    fetch('<?php echo url_for(['module' => 'researcher', 'action' => 'apiUpload']) ?>', {
      method: 'POST',
      body: fd
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      prog.innerHTML = '';
      if (data.success) {
        addFileEntry(data.file);
        var noMsg = document.getElementById('noFilesMsg');
        if (noMsg) noMsg.remove();
      } else {
        prog.innerHTML = '<small class="text-danger">' + (data.error || 'Upload failed') + '</small>';
      }
    })
    .catch(function() {
      prog.innerHTML = '<small class="text-danger">Upload error</small>';
    });
  }

  function addFileEntry(f) {
    var html = '<div class="d-flex justify-content-between align-items-center mb-2 file-entry" data-id="' + f.id + '">'
      + '<div class="text-truncate me-2"><i class="bi bi-file-earmark me-1"></i><small>' + f.original_name + '</small>'
      + '<br><small class="text-muted">' + Math.round(f.file_size / 1024 * 10) / 10 + ' KB</small></div>'
      + '<button type="button" class="btn btn-sm btn-outline-danger delete-file-btn" data-file-id="' + f.id + '"><i class="bi bi-trash"></i></button>'
      + '</div>';
    document.getElementById('fileList').insertAdjacentHTML('beforeend', html);
  }

  // Delete file handler (delegated)
  document.getElementById('fileList').addEventListener('click', function(e) {
    var btn = e.target.closest('.delete-file-btn');
    if (!btn) return;
    e.preventDefault();
    if (!confirm('Delete this file?')) return;

    var fileId = btn.getAttribute('data-file-id');
    fetch('<?php echo url_for(['module' => 'researcher', 'action' => 'apiDeleteFile']) ?>?file_id=' + fileId, {
      method: 'POST'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success) {
        var entry = btn.closest('.file-entry');
        if (entry) entry.remove();
      }
    });
  });
})();
</script>
<?php endif ?>

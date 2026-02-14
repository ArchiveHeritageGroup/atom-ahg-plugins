<?php $n = sfConfig::get('csp_nonce', ''); $nattr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<div class="container-fluid py-3">

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'researcher', 'action' => 'dashboard']) ?>">Researcher</a></li>
      <li class="breadcrumb-item active">Import Exchange</li>
    </ol>
  </nav>

  <!-- Flash messages -->
  <?php if ($sf_user->hasFlash('notice')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('notice') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif ?>
  <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif ?>

  <div class="row justify-content-center">
    <div class="col-lg-8">

      <?php if ($importResult): ?>
        <!-- Import Result -->
        <div class="card mb-4 border-success">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Import Complete</h5>
          </div>
          <div class="card-body">
            <p>Your exchange file has been imported as a <strong>draft submission</strong>. Review the items and submit for archivist approval.</p>

            <div class="row g-3 mb-3">
              <?php $s = $importResult['stats']; ?>
              <?php if ($s['notes'] > 0): ?>
                <div class="col-4 col-md-2 text-center">
                  <h4 class="mb-0"><?php echo $s['notes'] ?></h4>
                  <small class="text-muted">Notes</small>
                </div>
              <?php endif ?>
              <?php if ($s['files'] > 0): ?>
                <div class="col-4 col-md-2 text-center">
                  <h4 class="mb-0"><?php echo $s['files'] ?></h4>
                  <small class="text-muted">File Items</small>
                </div>
              <?php endif ?>
              <?php if ($s['new_items'] > 0): ?>
                <div class="col-4 col-md-2 text-center">
                  <h4 class="mb-0"><?php echo $s['new_items'] ?></h4>
                  <small class="text-muted">New Items</small>
                </div>
              <?php endif ?>
              <?php if ($s['new_creators'] > 0): ?>
                <div class="col-4 col-md-2 text-center">
                  <h4 class="mb-0"><?php echo $s['new_creators'] ?></h4>
                  <small class="text-muted">Creators</small>
                </div>
              <?php endif ?>
              <?php if ($s['new_repos'] > 0): ?>
                <div class="col-4 col-md-2 text-center">
                  <h4 class="mb-0"><?php echo $s['new_repos'] ?></h4>
                  <small class="text-muted">Repositories</small>
                </div>
              <?php endif ?>
              <?php if ($s['file_count'] > 0): ?>
                <div class="col-4 col-md-2 text-center">
                  <h4 class="mb-0"><?php echo $s['file_count'] ?></h4>
                  <small class="text-muted">Files</small>
                </div>
              <?php endif ?>
            </div>

            <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'viewSubmission', 'id' => $importResult['submission_id']]) ?>"
               class="btn btn-success">
              <i class="bi bi-eye me-1"></i>View Submission
            </a>
          </div>
        </div>
      <?php endif ?>

      <!-- Upload Form -->
      <div class="card">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0"><i class="bi bi-file-earmark-arrow-up me-2"></i>Import Researcher Exchange File</h5>
        </div>
        <div class="card-body">

          <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <strong>What is this?</strong> When you use the Portable Export viewer in edit mode (offline),
            you can add notes, import files, create new items, creators, and repositories.
            The viewer exports a <code>researcher-exchange.json</code> file that you upload here.
            It becomes a draft submission for archivist review.
          </div>

          <form method="post" enctype="multipart/form-data">

            <div class="mb-3">
              <label class="form-label fw-bold">Exchange File <span class="text-danger">*</span></label>
              <input type="file" name="exchange_file" class="form-control" accept=".json" required id="exchangeFile">
              <small class="text-muted">Select a <code>researcher-exchange.json</code> file exported from the Portable Viewer.</small>
            </div>

            <!-- Preview area -->
            <div id="previewArea" class="mb-3" style="display:none;">
              <div class="card bg-light">
                <div class="card-body small">
                  <h6><i class="bi bi-eye me-2"></i>File Preview</h6>
                  <div id="previewContent"></div>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Target Repository (optional)</label>
              <select name="repository_id" class="form-select">
                <option value="">-- Auto-detect or leave unset --</option>
                <?php foreach ($repositories as $repo): ?>
                  <option value="<?php echo $repo->id ?>"><?php echo htmlspecialchars($repo->name) ?></option>
                <?php endforeach ?>
              </select>
              <small class="text-muted">Override the target repository for all imported items.</small>
            </div>

            <hr>

            <div class="d-flex justify-content-between">
              <a href="<?php echo url_for(['module' => 'researcher', 'action' => 'dashboard']) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Cancel
              </a>
              <button type="submit" class="btn btn-primary" id="importBtn">
                <i class="bi bi-upload me-1"></i>Import
              </button>
            </div>

          </form>

        </div>
      </div>

      <!-- Supported Collection Types -->
      <div class="card mt-3">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Supported Collection Types</h6>
        </div>
        <div class="card-body small">
          <dl class="row mb-0">
            <dt class="col-3"><span class="badge bg-info">notes</span></dt>
            <dd class="col-9">Research notes attached to existing AtoM records</dd>

            <dt class="col-3"><span class="badge bg-secondary">files</span></dt>
            <dd class="col-9">Imported files with captions and metadata</dd>

            <dt class="col-3"><span class="badge bg-success">new_items</span></dt>
            <dd class="col-9">New descriptive records with hierarchy, access points (subjects, places, genre, creators), extent and media</dd>

            <dt class="col-3"><span class="badge bg-primary">new_creators</span></dt>
            <dd class="col-9">New creator/actor records (persons, organizations, families)</dd>

            <dt class="col-3"><span class="badge bg-warning text-dark">new_repositories</span></dt>
            <dd class="col-9">New repository/institution records</dd>
          </dl>
        </div>
      </div>

    </div>
  </div>

</div>

<script <?php echo $nattr ?>>
(function() {
  var fileInput = document.getElementById('exchangeFile');
  var preview = document.getElementById('previewArea');
  var previewContent = document.getElementById('previewContent');

  fileInput.addEventListener('change', function() {
    if (!this.files || !this.files[0]) {
      preview.style.display = 'none';
      return;
    }

    var reader = new FileReader();
    reader.onload = function(e) {
      try {
        var data = JSON.parse(e.target.result);
        var html = '<strong>Format:</strong> v' + (data.format_version || '?') + '<br>';
        html += '<strong>Source:</strong> ' + (data.source || 'unknown') + '<br>';
        if (data.exported_at) html += '<strong>Exported:</strong> ' + data.exported_at + '<br>';
        if (data.export_options) {
          html += '<strong>Images included:</strong> ' + (data.export_options.include_images ? 'Yes' : 'No (data only)') + '<br>';
        }

        if (data.collections && data.collections.length > 0) {
          html += '<strong>Collections:</strong><ul class="mb-0">';
          data.collections.forEach(function(c) {
            var count = c.items ? c.items.length : 0;
            html += '<li><span class="badge bg-secondary me-1">' + (c.type || '?') + '</span> '
              + (c.title || 'Untitled') + ' (' + count + ' items)</li>';
          });
          html += '</ul>';
        }

        previewContent.innerHTML = html;
        preview.style.display = '';
      } catch (err) {
        previewContent.innerHTML = '<span class="text-danger">Invalid JSON file: ' + err.message + '</span>';
        preview.style.display = '';
      }
    };
    reader.readAsText(this.files[0]);
  });
})();
</script>

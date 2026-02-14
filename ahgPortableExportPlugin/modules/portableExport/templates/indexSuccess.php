<?php
$repositories = sfOutputEscaper::unescape($repositories ?? []);
$exports = sfOutputEscaper::unescape($exports ?? []);
$cspNonce = sfConfig::get('csp_nonce', '');
$nonceAttr = $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce) . '"' : '';
?>

<div class="container-fluid py-4">

  <a href="<?php echo url_for(['module' => 'admin', 'action' => 'dashboard']); ?>" class="btn btn-outline-secondary btn-sm mb-3">
    <i class="bi bi-arrow-left"></i> <?php echo __('Back to Dashboard'); ?>
  </a>

  <div class="card mb-4">
    <div class="card-header bg-primary text-white d-flex align-items-center">
      <i class="bi bi-box-arrow-up-right me-2"></i>
      <h4 class="mb-0"><?php echo __('Portable Export'); ?></h4>
    </div>
    <div class="card-body">
      <p class="text-muted">
        <?php echo __('Generate a self-contained catalogue viewer for offline access on CD, USB, or downloadable ZIP. The viewer opens in any modern browser with no server or internet connection required.'); ?>
      </p>
    </div>
  </div>

  <!-- Export Form -->
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-gear me-2"></i><?php echo __('New Export'); ?></h5>
    </div>
    <div class="card-body">
      <form id="exportForm">
        <div class="row mb-3">
          <div class="col-md-6">
            <label for="export-title" class="form-label"><?php echo __('Export Title'); ?></label>
            <input type="text" class="form-control" id="export-title" name="title" value="Portable Catalogue" required>
          </div>
          <div class="col-md-3">
            <label for="export-culture" class="form-label"><?php echo __('Language'); ?></label>
            <select class="form-select" id="export-culture" name="culture">
              <option value="en">English</option>
              <option value="fr">French</option>
              <option value="af">Afrikaans</option>
              <option value="pt">Portuguese</option>
            </select>
          </div>
          <div class="col-md-3">
            <label for="export-mode" class="form-label"><?php echo __('Viewer Mode'); ?></label>
            <select class="form-select" id="export-mode" name="mode">
              <option value="read_only"><?php echo __('Read Only'); ?></option>
              <option value="editable"><?php echo __('Editable (allows notes + file import)'); ?></option>
            </select>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label for="export-scope" class="form-label"><?php echo __('Scope'); ?></label>
            <select class="form-select" id="export-scope" name="scope_type">
              <option value="all"><?php echo __('Entire Catalogue'); ?></option>
              <option value="fonds"><?php echo __('Specific Fonds/Collection'); ?></option>
              <option value="repository"><?php echo __('By Repository'); ?></option>
            </select>
          </div>
          <div class="col-md-4" id="scope-slug-group" style="display:none;">
            <label for="export-slug" class="form-label"><?php echo __('Fonds/Collection Slug'); ?></label>
            <input type="text" class="form-control" id="export-slug" name="scope_slug" placeholder="e.g. example-fonds">
          </div>
          <div class="col-md-4" id="scope-repo-group" style="display:none;">
            <label for="export-repository" class="form-label"><?php echo __('Repository'); ?></label>
            <select class="form-select" id="export-repository" name="repository_id">
              <option value=""><?php echo __('-- Select --'); ?></option>
              <?php foreach ($repositories as $repo): ?>
                <option value="<?php echo $repo->id; ?>"><?php echo htmlspecialchars($repo->name); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-12">
            <label class="form-label"><?php echo __('Include Digital Objects'); ?></label>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" id="inc-objects" name="include_objects" value="1" checked>
              <label class="form-check-label" for="inc-objects"><?php echo __('Digital Objects'); ?></label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" id="inc-thumbs" name="include_thumbnails" value="1" checked>
              <label class="form-check-label" for="inc-thumbs"><?php echo __('Thumbnails'); ?></label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" id="inc-refs" name="include_references" value="1" checked>
              <label class="form-check-label" for="inc-refs"><?php echo __('Reference Images'); ?></label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" id="inc-masters" name="include_masters" value="0">
              <label class="form-check-label" for="inc-masters"><?php echo __('Master Files'); ?></label>
            </div>
          </div>
        </div>

        <hr>
        <h6><?php echo __('Branding (Optional)'); ?></h6>
        <div class="row mb-3">
          <div class="col-md-4">
            <label for="branding-title" class="form-label"><?php echo __('Viewer Title'); ?></label>
            <input type="text" class="form-control" id="branding-title" name="branding_title" placeholder="<?php echo __('e.g. My Archive Collection'); ?>">
          </div>
          <div class="col-md-4">
            <label for="branding-subtitle" class="form-label"><?php echo __('Subtitle'); ?></label>
            <input type="text" class="form-control" id="branding-subtitle" name="branding_subtitle">
          </div>
          <div class="col-md-4">
            <label for="branding-footer" class="form-label"><?php echo __('Footer Text'); ?></label>
            <input type="text" class="form-control" id="branding-footer" name="branding_footer">
          </div>
        </div>

        <button type="submit" class="btn btn-primary" id="btn-start-export">
          <i class="bi bi-play-circle me-1"></i> <?php echo __('Start Export'); ?>
        </button>
      </form>
    </div>
  </div>

  <!-- Progress Panel (hidden initially) -->
  <div class="card mb-4" id="progress-panel" style="display:none;">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-hourglass-split me-2"></i><?php echo __('Export Progress'); ?></h5>
    </div>
    <div class="card-body">
      <div class="progress mb-3" style="height: 25px;">
        <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;">0%</div>
      </div>
      <div id="progress-status" class="text-muted"></div>
      <div id="progress-result" style="display:none;" class="mt-3">
        <div class="alert alert-success">
          <i class="bi bi-check-circle me-1"></i>
          <span id="result-message"></span>
          <a id="result-download" href="#" class="btn btn-sm btn-success ms-2">
            <i class="bi bi-download me-1"></i><?php echo __('Download ZIP'); ?>
          </a>
        </div>
      </div>
      <div id="progress-error" style="display:none;" class="mt-3">
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-triangle me-1"></i>
          <span id="error-message"></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Past Exports -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i><?php echo __('Past Exports'); ?></h5>
      <button class="btn btn-sm btn-outline-secondary" id="btn-refresh-list">
        <i class="bi bi-arrow-clockwise"></i>
      </button>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover table-striped mb-0" id="exports-table">
        <thead class="table-light">
          <tr>
            <th><?php echo __('ID'); ?></th>
            <th><?php echo __('Title'); ?></th>
            <th><?php echo __('Scope'); ?></th>
            <th><?php echo __('Mode'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th><?php echo __('Descriptions'); ?></th>
            <th><?php echo __('Size'); ?></th>
            <th><?php echo __('Created'); ?></th>
            <th><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($exports)): ?>
            <tr><td colspan="9" class="text-center text-muted py-3"><?php echo __('No exports yet'); ?></td></tr>
          <?php else: ?>
            <?php foreach ($exports as $exp): ?>
              <tr>
                <td><?php echo $exp->id; ?></td>
                <td><?php echo htmlspecialchars($exp->title); ?></td>
                <td><span class="badge bg-secondary"><?php echo $exp->scope_type; ?></span></td>
                <td><span class="badge bg-<?php echo $exp->mode === 'editable' ? 'warning' : 'info'; ?>"><?php echo $exp->mode; ?></span></td>
                <td>
                  <?php
                  $badgeClass = match ($exp->status) {
                      'completed' => 'bg-success',
                      'processing' => 'bg-primary',
                      'failed' => 'bg-danger',
                      default => 'bg-secondary',
                  };
                  ?>
                  <span class="badge <?php echo $badgeClass; ?>"><?php echo $exp->status; ?></span>
                </td>
                <td><?php echo number_format($exp->total_descriptions); ?></td>
                <td><?php echo $exp->output_size ? round($exp->output_size / 1048576, 1) . ' MB' : '-'; ?></td>
                <td><?php echo $exp->created_at; ?></td>
                <td>
                  <?php if ($exp->status === 'completed'): ?>
                    <a href="/portable-export/download?id=<?php echo $exp->id; ?>" class="btn btn-sm btn-success" title="<?php echo __('Download'); ?>">
                      <i class="bi bi-download"></i>
                    </a>
                    <button class="btn btn-sm btn-outline-primary btn-share-token" data-id="<?php echo $exp->id; ?>" title="<?php echo __('Share Link'); ?>">
                      <i class="bi bi-link-45deg"></i>
                    </button>
                  <?php endif; ?>
                  <button class="btn btn-sm btn-outline-danger btn-delete-export" data-id="<?php echo $exp->id; ?>" title="<?php echo __('Delete'); ?>">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Share Token Modal -->
  <div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-link-45deg me-2"></i><?php echo __('Share Download Link'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Max Downloads (blank = unlimited)'); ?></label>
            <input type="number" class="form-control" id="share-max-downloads" min="1" placeholder="unlimited">
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Expires After (hours)'); ?></label>
            <input type="number" class="form-control" id="share-expires-hours" value="168" min="1">
          </div>
          <div id="share-result" style="display:none;">
            <label class="form-label"><?php echo __('Share URL'); ?></label>
            <div class="input-group">
              <input type="text" class="form-control" id="share-url" readonly>
              <button class="btn btn-outline-secondary" id="btn-copy-url" type="button">
                <i class="bi bi-clipboard"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Close'); ?></button>
          <button type="button" class="btn btn-primary" id="btn-generate-token"><?php echo __('Generate Link'); ?></button>
        </div>
      </div>
    </div>
  </div>

</div>

<script <?php echo $nonceAttr; ?>>
(function() {
  var currentExportId = null;
  var pollInterval = null;
  var shareExportId = null;

  // Scope toggle
  var scopeSelect = document.getElementById('export-scope');
  var slugGroup = document.getElementById('scope-slug-group');
  var repoGroup = document.getElementById('scope-repo-group');

  scopeSelect.addEventListener('change', function() {
    slugGroup.style.display = (this.value === 'fonds') ? '' : 'none';
    repoGroup.style.display = (this.value === 'repository') ? '' : 'none';
  });

  // Start export
  document.getElementById('exportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var btn = document.getElementById('btn-start-export');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Starting...';

    var data = new FormData(form);
    // Handle checkboxes that might not be sent when unchecked
    if (!document.getElementById('inc-objects').checked) data.set('include_objects', '0');
    if (!document.getElementById('inc-thumbs').checked) data.set('include_thumbnails', '0');
    if (!document.getElementById('inc-refs').checked) data.set('include_references', '0');

    fetch('/portable-export/api/start', { method: 'POST', body: data })
      .then(function(r) { return r.json(); })
      .then(function(resp) {
        if (resp.success) {
          currentExportId = resp.export_id;
          document.getElementById('progress-panel').style.display = '';
          document.getElementById('progress-result').style.display = 'none';
          document.getElementById('progress-error').style.display = 'none';
          startPolling(resp.export_id);
        } else {
          alert(resp.error || 'Failed to start export');
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-play-circle me-1"></i> Start Export';
        }
      })
      .catch(function(err) {
        alert('Error: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-play-circle me-1"></i> Start Export';
      });
  });

  function startPolling(exportId) {
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(function() { pollProgress(exportId); }, 2000);
  }

  function pollProgress(exportId) {
    fetch('/portable-export/api/progress?id=' + exportId)
      .then(function(r) { return r.json(); })
      .then(function(data) {
        var bar = document.getElementById('progress-bar');
        var pct = data.progress || 0;
        bar.style.width = pct + '%';
        bar.textContent = pct + '%';

        var statusEl = document.getElementById('progress-status');
        if (pct <= 40) statusEl.textContent = 'Extracting catalogue data...';
        else if (pct <= 70) statusEl.textContent = 'Collecting digital objects...';
        else if (pct <= 80) statusEl.textContent = 'Building search index...';
        else if (pct <= 90) statusEl.textContent = 'Packaging viewer...';
        else statusEl.textContent = 'Creating ZIP archive...';

        if (data.status === 'completed') {
          clearInterval(pollInterval);
          bar.classList.remove('progress-bar-animated');
          bar.classList.add('bg-success');
          var sizeMB = (data.output_size / 1048576).toFixed(1);
          document.getElementById('result-message').textContent =
            'Export complete! ' + data.total_descriptions + ' descriptions, ' +
            data.total_objects + ' objects (' + sizeMB + ' MB)';
          document.getElementById('result-download').href = '/portable-export/download?id=' + exportId;
          document.getElementById('progress-result').style.display = '';
          resetStartButton();
        } else if (data.status === 'failed') {
          clearInterval(pollInterval);
          bar.classList.remove('progress-bar-animated');
          bar.classList.add('bg-danger');
          document.getElementById('error-message').textContent = data.error_message || 'Export failed';
          document.getElementById('progress-error').style.display = '';
          resetStartButton();
        }
      });
  }

  function resetStartButton() {
    var btn = document.getElementById('btn-start-export');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-play-circle me-1"></i> Start Export';
  }

  // Delete export
  document.querySelectorAll('.btn-delete-export').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (!confirm('Delete this export and its files?')) return;
      var id = this.getAttribute('data-id');
      var row = this.closest('tr');
      fetch('/portable-export/api/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
      }).then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.success) row.remove();
        });
    });
  });

  // Share token
  document.querySelectorAll('.btn-share-token').forEach(function(btn) {
    btn.addEventListener('click', function() {
      shareExportId = this.getAttribute('data-id');
      document.getElementById('share-result').style.display = 'none';
      var modal = new bootstrap.Modal(document.getElementById('shareModal'));
      modal.show();
    });
  });

  document.getElementById('btn-generate-token').addEventListener('click', function() {
    var maxDl = document.getElementById('share-max-downloads').value;
    var hours = document.getElementById('share-expires-hours').value;
    var body = 'id=' + shareExportId + '&expires_hours=' + hours;
    if (maxDl) body += '&max_downloads=' + maxDl;

    fetch('/portable-export/api/token', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body
    }).then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          document.getElementById('share-url').value = data.download_url;
          document.getElementById('share-result').style.display = '';
        }
      });
  });

  document.getElementById('btn-copy-url').addEventListener('click', function() {
    var input = document.getElementById('share-url');
    input.select();
    document.execCommand('copy');
  });

  // Refresh list
  document.getElementById('btn-refresh-list').addEventListener('click', function() {
    window.location.reload();
  });
})();
</script>

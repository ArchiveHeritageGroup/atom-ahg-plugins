<?php
$imports = sfOutputEscaper::unescape($imports ?? []);
$cspNonce = sfConfig::get('csp_nonce', '');
$nonceAttr = $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce) . '"' : '';
?>

<div class="container-fluid py-4">

  <div class="d-flex mb-3">
    <a href="<?php echo url_for(['module' => 'admin', 'action' => 'dashboard']); ?>" class="btn btn-outline-secondary btn-sm me-2">
      <i class="bi bi-arrow-left"></i> <?php echo __('Dashboard'); ?>
    </a>
    <a href="/portable-export" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-box-arrow-up-right me-1"></i><?php echo __('Export'); ?>
    </a>
  </div>

  <div class="card mb-4">
    <div class="card-header bg-success text-white d-flex align-items-center">
      <i class="bi bi-box-arrow-in-down me-2"></i>
      <h4 class="mb-0"><?php echo __('Import Archive'); ?></h4>
    </div>
    <div class="card-body">
      <p class="text-muted mb-0">
        <?php echo __('Import an archive package (ZIP) exported from another AtoM Heratio instance. The archive must have been created using Archive Export mode.'); ?>
      </p>
    </div>
  </div>

  <!-- Import Wizard -->
  <div class="card mb-4" id="import-wizard-card">
    <div class="card-header p-0">
      <div class="d-flex" id="import-wizard-steps">
        <button class="wizard-step flex-fill btn btn-link text-decoration-none py-3 rounded-0 active" data-step="1">
          <span class="badge rounded-pill bg-primary me-1">1</span> <?php echo __('Upload'); ?>
        </button>
        <button class="wizard-step flex-fill btn btn-link text-decoration-none py-3 rounded-0" data-step="2">
          <span class="badge rounded-pill bg-secondary me-1">2</span> <?php echo __('Configure'); ?>
        </button>
        <button class="wizard-step flex-fill btn btn-link text-decoration-none py-3 rounded-0" data-step="3">
          <span class="badge rounded-pill bg-secondary me-1">3</span> <?php echo __('Import'); ?>
        </button>
      </div>
    </div>
    <div class="card-body">

      <!-- Step 1: Upload & Validate -->
      <div class="import-panel" data-step="1">
        <h5 class="mb-3"><i class="bi bi-upload me-2"></i><?php echo __('Upload Archive'); ?></h5>
        <p class="text-muted mb-3"><?php echo __('Upload a ZIP file or enter a server path to an archive package.'); ?></p>

        <ul class="nav nav-tabs mb-3" id="upload-tabs">
          <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#tab-upload"><?php echo __('Upload File'); ?></a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tab-path"><?php echo __('Server Path'); ?></a>
          </li>
        </ul>

        <div class="tab-content">
          <div class="tab-pane active" id="tab-upload">
            <div class="mb-3">
              <label for="import-file" class="form-label fw-bold"><?php echo __('Archive ZIP File'); ?></label>
              <input type="file" class="form-control" id="import-file" accept=".zip">
              <div class="form-text"><?php echo __('Select a .zip file created by AtoM Heratio Archive Export.'); ?></div>
            </div>
          </div>
          <div class="tab-pane" id="tab-path">
            <div class="mb-3">
              <label for="import-server-path" class="form-label fw-bold"><?php echo __('Server Path'); ?></label>
              <input type="text" class="form-control" id="import-server-path" placeholder="/path/to/archive.zip or /path/to/extracted-archive/">
              <div class="form-text"><?php echo __('Full path to a ZIP file or extracted archive directory on the server.'); ?></div>
            </div>
          </div>
        </div>

        <div class="d-flex align-items-center mt-3">
          <button type="button" class="btn btn-primary" id="btn-validate">
            <i class="bi bi-shield-check me-1"></i><?php echo __('Validate Archive'); ?>
          </button>
          <span id="validate-spinner" class="spinner-border spinner-border-sm ms-2" style="display:none;"></span>
        </div>

        <!-- Validation Result -->
        <div id="validation-result" style="display:none;" class="mt-3">
          <div id="validation-success" style="display:none;">
            <div class="alert alert-success mb-3">
              <i class="bi bi-check-circle me-1"></i> <?php echo __('Archive validated successfully.'); ?>
            </div>
            <div class="card bg-light">
              <div class="card-body">
                <h6><?php echo __('Archive Summary'); ?></h6>
                <table class="table table-sm mb-0" id="validation-summary">
                  <tbody>
                    <tr><th style="width:30%"><?php echo __('Source'); ?></th><td id="val-source">-</td></tr>
                    <tr><th><?php echo __('Framework'); ?></th><td id="val-framework">-</td></tr>
                    <tr><th><?php echo __('Export Date'); ?></th><td id="val-date">-</td></tr>
                    <tr><th><?php echo __('Schema Version'); ?></th><td id="val-version">-</td></tr>
                    <tr><th><?php echo __('Total Files'); ?></th><td id="val-files">-</td></tr>
                  </tbody>
                </table>
                <h6 class="mt-3"><?php echo __('Entity Counts'); ?></h6>
                <div id="val-entity-counts" class="row g-2"></div>
              </div>
            </div>
          </div>
          <div id="validation-failure" style="display:none;">
            <div class="alert alert-danger">
              <i class="bi bi-exclamation-triangle me-1"></i> <?php echo __('Validation failed.'); ?>
              <ul id="validation-errors" class="mb-0 mt-2"></ul>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-end mt-4" id="step1-next" style="display:none;">
          <button type="button" class="btn btn-primary import-next" data-next="2">
            <?php echo __('Next: Configure'); ?> <i class="bi bi-arrow-right ms-1"></i>
          </button>
        </div>
      </div>

      <!-- Step 2: Configure -->
      <div class="import-panel" data-step="2" style="display:none;">
        <h5 class="mb-3"><i class="bi bi-sliders me-2"></i><?php echo __('Import Configuration'); ?></h5>
        <p class="text-muted mb-3"><?php echo __('Choose how records should be imported.'); ?></p>

        <div class="row mb-4">
          <div class="col-md-6">
            <label for="import-title" class="form-label fw-bold"><?php echo __('Import Title'); ?></label>
            <input type="text" class="form-control" id="import-title" value="Archive Import">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold"><?php echo __('Import Mode'); ?></label>
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="import_mode" id="mode-merge" value="merge" checked>
              <label class="btn btn-outline-primary" for="mode-merge">
                <i class="bi bi-layers me-1"></i><?php echo __('Merge'); ?>
                <br><small class="fw-normal"><?php echo __('Skip existing, import new'); ?></small>
              </label>
              <input type="radio" class="btn-check" name="import_mode" id="mode-dryrun" value="dry_run">
              <label class="btn btn-outline-info" for="mode-dryrun">
                <i class="bi bi-eye me-1"></i><?php echo __('Dry Run'); ?>
                <br><small class="fw-normal"><?php echo __('Validate only, no changes'); ?></small>
              </label>
              <input type="radio" class="btn-check" name="import_mode" id="mode-replace" value="replace">
              <label class="btn btn-outline-danger" for="mode-replace">
                <i class="bi bi-arrow-repeat me-1"></i><?php echo __('Replace'); ?>
                <br><small class="fw-normal"><?php echo __('Clear and re-import'); ?></small>
              </label>
            </div>
          </div>
        </div>

        <div class="alert alert-warning" id="replace-warning" style="display:none;">
          <i class="bi bi-exclamation-triangle me-1"></i>
          <strong><?php echo __('Warning:'); ?></strong> <?php echo __('Replace mode will clear existing records before importing. This cannot be undone. Use with extreme caution.'); ?>
        </div>

        <div class="mb-4">
          <label class="form-label fw-bold"><?php echo __('Entity Types to Import'); ?></label>
          <div class="row g-2" id="import-entity-types"></div>
        </div>

        <div class="d-flex justify-content-between mt-4">
          <button type="button" class="btn btn-outline-secondary import-prev" data-prev="1">
            <i class="bi bi-arrow-left me-1"></i> <?php echo __('Back'); ?>
          </button>
          <button type="button" class="btn btn-success btn-lg" id="btn-start-import">
            <i class="bi bi-play-circle me-1"></i> <?php echo __('Start Import'); ?>
          </button>
        </div>
      </div>

      <!-- Step 3: Progress -->
      <div class="import-panel" data-step="3" style="display:none;">
        <h5 class="mb-3"><i class="bi bi-hourglass-split me-2"></i><?php echo __('Import Progress'); ?></h5>

        <div class="progress mb-3" style="height: 25px;">
          <div id="import-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;">0%</div>
        </div>

        <div class="row text-center mb-3" id="import-stats">
          <div class="col-md-3">
            <div class="card bg-light">
              <div class="card-body py-2">
                <small class="text-muted"><?php echo __('Imported'); ?></small>
                <div class="fs-5 fw-bold text-success" id="stat-imported">0</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-light">
              <div class="card-body py-2">
                <small class="text-muted"><?php echo __('Skipped'); ?></small>
                <div class="fs-5 fw-bold text-warning" id="stat-skipped">0</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-light">
              <div class="card-body py-2">
                <small class="text-muted"><?php echo __('Errors'); ?></small>
                <div class="fs-5 fw-bold text-danger" id="stat-errors">0</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-light">
              <div class="card-body py-2">
                <small class="text-muted"><?php echo __('Status'); ?></small>
                <div class="fs-5 fw-bold" id="stat-status">Pending</div>
              </div>
            </div>
          </div>
        </div>

        <div id="import-completed" style="display:none;" class="mt-3">
          <div class="alert alert-success">
            <i class="bi bi-check-circle me-1"></i>
            <span id="import-completed-msg"></span>
          </div>
        </div>

        <div id="import-failed" style="display:none;" class="mt-3">
          <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <span id="import-failed-msg"></span>
          </div>
        </div>

        <div id="import-error-log" style="display:none;" class="mt-3">
          <h6><?php echo __('Error Log'); ?></h6>
          <pre class="bg-light p-3 small" id="import-error-log-content" style="max-height:300px; overflow-y:auto;"></pre>
        </div>
      </div>

    </div>
  </div>

  <!-- Past Imports -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i><?php echo __('Past Imports'); ?></h5>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover table-striped mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('ID'); ?></th>
            <th><?php echo __('Title'); ?></th>
            <th><?php echo __('Source'); ?></th>
            <th><?php echo __('Mode'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th><?php echo __('Imported'); ?></th>
            <th><?php echo __('Skipped'); ?></th>
            <th><?php echo __('Errors'); ?></th>
            <th><?php echo __('Date'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($imports)): ?>
            <tr><td colspan="9" class="text-center text-muted py-3"><?php echo __('No imports yet'); ?></td></tr>
          <?php else: ?>
            <?php foreach ($imports as $imp): ?>
              <tr>
                <td><?php echo $imp->id; ?></td>
                <td><?php echo htmlspecialchars($imp->title); ?></td>
                <td><small class="text-muted"><?php echo htmlspecialchars($imp->source_url ?? '-'); ?></small></td>
                <td>
                  <span class="badge bg-<?php echo match ($imp->mode) {
                      'merge' => 'primary',
                      'replace' => 'danger',
                      'dry_run' => 'info',
                      default => 'secondary',
                  }; ?>"><?php echo $imp->mode; ?></span>
                </td>
                <td>
                  <span class="badge bg-<?php echo match ($imp->status) {
                      'completed' => 'success',
                      'importing' => 'primary',
                      'failed' => 'danger',
                      'validated' => 'info',
                      default => 'secondary',
                  }; ?>"><?php echo $imp->status; ?></span>
                </td>
                <td><?php echo number_format($imp->imported_entities); ?></td>
                <td><?php echo number_format($imp->skipped_entities); ?></td>
                <td><?php echo $imp->error_count > 0 ? '<span class="text-danger">' . $imp->error_count . '</span>' : '0'; ?></td>
                <td><small class="text-muted"><?php echo $imp->created_at ? date('d M Y H:i', strtotime($imp->created_at)) : '-'; ?></small></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<style <?php echo $nonceAttr; ?>>
  .wizard-step { border-bottom: 3px solid transparent; color: #6c757d; }
  .wizard-step.active { border-bottom-color: #0d6efd; color: #0d6efd; font-weight: 600; }
  .wizard-step.completed { border-bottom-color: #198754; color: #198754; }
  .wizard-step.completed .badge { background-color: #198754 !important; }
</style>

<script <?php echo $nonceAttr; ?>>
(function() {
  var currentStep = 1;
  var archivePath = null;
  var entityCounts = {};
  var currentImportId = null;
  var importPollInterval = null;

  // ─── Wizard Navigation ────────────────────────────────────────

  function goToImportStep(step) {
    document.querySelectorAll('.import-panel').forEach(function(p) { p.style.display = 'none'; });
    var target = document.querySelector('.import-panel[data-step="' + step + '"]');
    if (target) target.style.display = '';

    document.querySelectorAll('#import-wizard-steps .wizard-step').forEach(function(s) {
      var sStep = parseInt(s.getAttribute('data-step'));
      s.classList.remove('active', 'completed');
      var badge = s.querySelector('.badge');
      if (sStep === step) {
        s.classList.add('active');
        badge.className = 'badge rounded-pill bg-primary me-1';
      } else if (sStep < step) {
        s.classList.add('completed');
        badge.className = 'badge rounded-pill bg-success me-1';
      } else {
        badge.className = 'badge rounded-pill bg-secondary me-1';
      }
    });

    currentStep = step;
  }

  document.querySelectorAll('#import-wizard-steps .wizard-step').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var step = parseInt(this.getAttribute('data-step'));
      if (step <= currentStep) goToImportStep(step);
    });
  });

  document.querySelectorAll('.import-next').forEach(function(btn) {
    btn.addEventListener('click', function() { goToImportStep(parseInt(this.getAttribute('data-next'))); });
  });
  document.querySelectorAll('.import-prev').forEach(function(btn) {
    btn.addEventListener('click', function() { goToImportStep(parseInt(this.getAttribute('data-prev'))); });
  });

  // ─── Mode Toggle ──────────────────────────────────────────────

  document.querySelectorAll('input[name="import_mode"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
      document.getElementById('replace-warning').style.display = this.value === 'replace' ? '' : 'none';
    });
  });

  // ─── Validate ─────────────────────────────────────────────────

  document.getElementById('btn-validate').addEventListener('click', function() {
    var spinner = document.getElementById('validate-spinner');
    spinner.style.display = '';
    this.disabled = true;

    var formData = new FormData();

    var fileInput = document.getElementById('import-file');
    var serverPath = document.getElementById('import-server-path').value.trim();

    if (fileInput.files.length > 0) {
      formData.append('archive_file', fileInput.files[0]);
    } else if (serverPath) {
      formData.append('server_path', serverPath);
    } else {
      alert('Please select a file or enter a server path.');
      spinner.style.display = 'none';
      this.disabled = false;
      return;
    }

    fetch('/portable-export/api/import-validate', { method: 'POST', body: formData })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        spinner.style.display = 'none';
        document.getElementById('btn-validate').disabled = false;
        document.getElementById('validation-result').style.display = '';

        if (data.valid) {
          document.getElementById('validation-success').style.display = '';
          document.getElementById('validation-failure').style.display = 'none';

          // Store archive path for import
          archivePath = data.archive_path;
          entityCounts = data.entity_counts || {};

          // Fill summary
          document.getElementById('val-source').textContent = (data.source && data.source.url) || 'N/A';
          document.getElementById('val-framework').textContent = (data.source && data.source.framework) || 'unknown';
          document.getElementById('val-date').textContent = data.created_at || '-';
          document.getElementById('val-version').textContent = data.version || '-';
          document.getElementById('val-files').textContent = data.total_files || 0;

          // Entity counts + checkboxes for step 2
          var countsHtml = '';
          var checkboxHtml = '';
          var types = Object.keys(entityCounts);
          types.forEach(function(type) {
            var label = type.replace(/_/g, ' ');
            label = label.charAt(0).toUpperCase() + label.slice(1);
            countsHtml += '<div class="col-auto"><span class="badge bg-primary">' + label + '</span> <strong>' + entityCounts[type] + '</strong></div>';
            checkboxHtml += '<div class="col-md-4"><div class="form-check">'
              + '<input class="form-check-input import-entity" type="checkbox" value="' + type + '" id="imp-' + type + '" checked>'
              + '<label class="form-check-label" for="imp-' + type + '">' + label + ' (' + entityCounts[type] + ')</label>'
              + '</div></div>';
          });
          document.getElementById('val-entity-counts').innerHTML = countsHtml;
          document.getElementById('import-entity-types').innerHTML = checkboxHtml;

          document.getElementById('step1-next').style.display = '';
        } else {
          document.getElementById('validation-success').style.display = 'none';
          document.getElementById('validation-failure').style.display = '';
          document.getElementById('step1-next').style.display = 'none';

          var errHtml = '';
          (data.errors || []).forEach(function(err) {
            errHtml += '<li>' + err + '</li>';
          });
          document.getElementById('validation-errors').innerHTML = errHtml;
        }
      })
      .catch(function(err) {
        spinner.style.display = 'none';
        document.getElementById('btn-validate').disabled = false;
        alert('Validation error: ' + err.message);
      });
  });

  // ─── Start Import ─────────────────────────────────────────────

  document.getElementById('btn-start-import').addEventListener('click', function() {
    if (!archivePath) {
      alert('Please validate an archive first.');
      return;
    }

    var mode = document.querySelector('input[name="import_mode"]:checked').value;
    if (mode === 'replace' && !confirm('Replace mode will clear existing records. Are you absolutely sure?')) {
      return;
    }

    this.disabled = true;
    this.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Starting...';

    // Collect selected entity types
    var selectedTypes = [];
    document.querySelectorAll('.import-entity:checked').forEach(function(cb) {
      selectedTypes.push(cb.value);
    });

    var formData = new FormData();
    formData.append('archive_path', archivePath);
    formData.append('mode', mode);
    formData.append('title', document.getElementById('import-title').value || 'Web Import');
    formData.append('entity_types', JSON.stringify(selectedTypes));

    fetch('/portable-export/api/start-import', { method: 'POST', body: formData })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          currentImportId = data.import_id;
          goToImportStep(3);
          startImportPolling(data.import_id);
        } else {
          alert(data.error || 'Failed to start import');
          resetImportButton();
        }
      })
      .catch(function(err) {
        alert('Error: ' + err.message);
        resetImportButton();
      });
  });

  function resetImportButton() {
    var btn = document.getElementById('btn-start-import');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-play-circle me-1"></i> Start Import';
  }

  // ─── Import Progress Polling ──────────────────────────────────

  function startImportPolling(importId) {
    if (importPollInterval) clearInterval(importPollInterval);
    importPollInterval = setInterval(function() { pollImportProgress(importId); }, 2000);
  }

  function pollImportProgress(importId) {
    fetch('/portable-export/api/import-progress?id=' + importId)
      .then(function(r) { return r.json(); })
      .then(function(data) {
        var bar = document.getElementById('import-progress-bar');
        var pct = data.progress || 0;
        bar.style.width = pct + '%';
        bar.textContent = pct + '%';

        document.getElementById('stat-imported').textContent = data.imported_entities || 0;
        document.getElementById('stat-skipped').textContent = data.skipped_entities || 0;
        document.getElementById('stat-errors').textContent = data.error_count || 0;
        document.getElementById('stat-status').textContent = data.status || 'unknown';

        if (data.status === 'completed') {
          clearInterval(importPollInterval);
          bar.classList.remove('progress-bar-animated');
          bar.classList.add('bg-success');

          document.getElementById('import-completed').style.display = '';
          document.getElementById('import-completed-msg').textContent =
            'Import complete! ' + data.imported_entities + ' imported, ' +
            data.skipped_entities + ' skipped, ' + data.error_count + ' errors.';

          if (data.error_log) {
            document.getElementById('import-error-log').style.display = '';
            document.getElementById('import-error-log-content').textContent = data.error_log;
          }
        } else if (data.status === 'failed') {
          clearInterval(importPollInterval);
          bar.classList.remove('progress-bar-animated');
          bar.classList.add('bg-danger');

          document.getElementById('import-failed').style.display = '';
          document.getElementById('import-failed-msg').textContent = data.error_log || 'Import failed.';
        }
      });
  }
})();
</script>

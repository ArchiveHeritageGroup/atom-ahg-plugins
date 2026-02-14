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
      <p class="text-muted mb-0">
        <?php echo __('Generate a self-contained catalogue viewer for offline access on CD, USB, or downloadable ZIP. The viewer opens in any modern browser with no server or internet connection required.'); ?>
      </p>
    </div>
  </div>

  <!-- Wizard -->
  <div class="card mb-4" id="wizard-card">
    <div class="card-header p-0">
      <!-- Step Indicators -->
      <div class="d-flex" id="wizard-steps">
        <button class="wizard-step flex-fill btn btn-link text-decoration-none py-3 rounded-0 active" data-step="1">
          <span class="badge rounded-pill bg-primary me-1">1</span> <?php echo __('Scope'); ?>
        </button>
        <button class="wizard-step flex-fill btn btn-link text-decoration-none py-3 rounded-0" data-step="2">
          <span class="badge rounded-pill bg-secondary me-1">2</span> <?php echo __('Content'); ?>
        </button>
        <button class="wizard-step flex-fill btn btn-link text-decoration-none py-3 rounded-0" data-step="3">
          <span class="badge rounded-pill bg-secondary me-1">3</span> <?php echo __('Configure'); ?>
        </button>
        <button class="wizard-step flex-fill btn btn-link text-decoration-none py-3 rounded-0" data-step="4">
          <span class="badge rounded-pill bg-secondary me-1">4</span> <?php echo __('Generate'); ?>
        </button>
      </div>
    </div>
    <div class="card-body">
      <form id="exportForm">

        <!-- Step 1: Scope -->
        <div class="wizard-panel" data-step="1">
          <h5 class="mb-3"><i class="bi bi-bullseye me-2"></i><?php echo __('What to Export'); ?></h5>
          <p class="text-muted mb-3"><?php echo __('Select the scope of descriptions to include in the portable viewer.'); ?></p>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="export-scope" class="form-label fw-bold"><?php echo __('Scope'); ?></label>
              <select class="form-select" id="export-scope" name="scope_type">
                <option value="all"><?php echo __('Entire Catalogue'); ?></option>
                <option value="fonds"><?php echo __('Specific Fonds/Collection'); ?></option>
                <option value="repository"><?php echo __('By Repository'); ?></option>
              </select>
            </div>
          </div>

          <div class="row mb-3" id="scope-slug-group" style="display:none;">
            <div class="col-md-6">
              <label for="fonds-search" class="form-label fw-bold"><?php echo __('Fonds / Collection'); ?></label>
              <div class="position-relative">
                <input type="text" class="form-control" id="fonds-search" placeholder="<?php echo __('Type to search...'); ?>" autocomplete="off">
                <input type="hidden" id="export-slug" name="scope_slug">
                <div id="fonds-results" class="list-group position-absolute w-100 shadow-sm" style="display:none; z-index:1050; max-height:250px; overflow-y:auto;"></div>
              </div>
              <div id="fonds-selected" class="mt-2" style="display:none;">
                <span class="badge bg-primary fs-6 px-3 py-2" id="fonds-selected-label"></span>
                <button type="button" class="btn btn-sm btn-outline-danger ms-1" id="fonds-clear" title="<?php echo __('Clear'); ?>">
                  <i class="bi bi-x"></i>
                </button>
              </div>
              <div class="form-text"><?php echo __('Search by title or identifier to find the fonds/collection.'); ?></div>
            </div>
          </div>

          <div class="row mb-3" id="scope-repo-group" style="display:none;">
            <div class="col-md-6">
              <label for="export-repository" class="form-label fw-bold"><?php echo __('Repository'); ?></label>
              <select class="form-select" id="export-repository" name="repository_id">
                <option value=""><?php echo __('-- Select --'); ?></option>
                <?php foreach ($repositories as $repo): ?>
                  <option value="<?php echo $repo->id; ?>"><?php echo htmlspecialchars($repo->name); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="d-flex justify-content-end mt-4">
            <button type="button" class="btn btn-primary wizard-next" data-next="2">
              <?php echo __('Next: Content'); ?> <i class="bi bi-arrow-right ms-1"></i>
            </button>
          </div>
        </div>

        <!-- Step 2: Content -->
        <div class="wizard-panel" data-step="2" style="display:none;">
          <h5 class="mb-3"><i class="bi bi-files me-2"></i><?php echo __('Content Options'); ?></h5>
          <p class="text-muted mb-3"><?php echo __('Choose which digital objects and media to include.'); ?></p>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <div class="card h-100">
                <div class="card-body">
                  <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="inc-objects" name="include_objects" value="1" checked>
                    <label class="form-check-label fw-bold" for="inc-objects"><i class="bi bi-file-earmark-image me-1"></i><?php echo __('Digital Objects'); ?></label>
                  </div>
                  <small class="text-muted"><?php echo __('Include digital object files in the export package.'); ?></small>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card h-100">
                <div class="card-body">
                  <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="inc-thumbs" name="include_thumbnails" value="1" checked>
                    <label class="form-check-label fw-bold" for="inc-thumbs"><i class="bi bi-image me-1"></i><?php echo __('Thumbnails'); ?></label>
                  </div>
                  <small class="text-muted"><?php echo __('Small thumbnail images for browse views.'); ?></small>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card h-100">
                <div class="card-body">
                  <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="inc-refs" name="include_references" value="1" checked>
                    <label class="form-check-label fw-bold" for="inc-refs"><i class="bi bi-card-image me-1"></i><?php echo __('Reference Images'); ?></label>
                  </div>
                  <small class="text-muted"><?php echo __('Medium-resolution images for detail views.'); ?></small>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card h-100">
                <div class="card-body">
                  <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="inc-masters" name="include_masters" value="0">
                    <label class="form-check-label fw-bold" for="inc-masters"><i class="bi bi-file-earmark-zip me-1"></i><?php echo __('Master Files'); ?></label>
                  </div>
                  <small class="text-muted"><?php echo __('Full-resolution master files. Warning: can significantly increase export size.'); ?></small>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <label for="export-mode" class="form-label fw-bold"><?php echo __('Viewer Mode'); ?></label>
              <select class="form-select" id="export-mode" name="mode">
                <option value="read_only"><?php echo __('Read Only'); ?></option>
                <option value="editable"><?php echo __('Editable (allows notes + file import)'); ?></option>
              </select>
              <div class="form-text"><?php echo __('Editable mode allows researchers to add notes and import files.'); ?></div>
            </div>
          </div>

          <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-outline-secondary wizard-prev" data-prev="1">
              <i class="bi bi-arrow-left me-1"></i> <?php echo __('Back'); ?>
            </button>
            <button type="button" class="btn btn-primary wizard-next" data-next="3">
              <?php echo __('Next: Configure'); ?> <i class="bi bi-arrow-right ms-1"></i>
            </button>
          </div>
        </div>

        <!-- Step 3: Configure -->
        <div class="wizard-panel" data-step="3" style="display:none;">
          <h5 class="mb-3"><i class="bi bi-sliders me-2"></i><?php echo __('Configuration'); ?></h5>
          <p class="text-muted mb-3"><?php echo __('Set the title, language, and optional branding for the viewer.'); ?></p>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="export-title" class="form-label fw-bold"><?php echo __('Export Title'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="export-title" name="title" value="Portable Catalogue" required>
            </div>
            <div class="col-md-3">
              <label for="export-culture" class="form-label fw-bold"><?php echo __('Language'); ?></label>
              <select class="form-select" id="export-culture" name="culture">
                <option value="en">English</option>
                <option value="fr">French</option>
                <option value="af">Afrikaans</option>
                <option value="pt">Portuguese</option>
              </select>
            </div>
          </div>

          <h6 class="mt-4 mb-3"><?php echo __('Branding (Optional)'); ?></h6>
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

          <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-outline-secondary wizard-prev" data-prev="2">
              <i class="bi bi-arrow-left me-1"></i> <?php echo __('Back'); ?>
            </button>
            <button type="button" class="btn btn-primary wizard-next" data-next="4">
              <?php echo __('Next: Review & Generate'); ?> <i class="bi bi-arrow-right ms-1"></i>
            </button>
          </div>
        </div>

        <!-- Step 4: Generate -->
        <div class="wizard-panel" data-step="4" style="display:none;">
          <h5 class="mb-3"><i class="bi bi-check2-square me-2"></i><?php echo __('Review & Generate'); ?></h5>
          <p class="text-muted mb-3"><?php echo __('Review your export settings and start generation.'); ?></p>

          <div class="card mb-3">
            <div class="card-body">
              <table class="table table-sm mb-0" id="review-table">
                <tbody>
                  <tr><th style="width:30%"><?php echo __('Title'); ?></th><td id="review-title">-</td></tr>
                  <tr><th><?php echo __('Scope'); ?></th><td id="review-scope">-</td></tr>
                  <tr><th><?php echo __('Mode'); ?></th><td id="review-mode">-</td></tr>
                  <tr><th><?php echo __('Language'); ?></th><td id="review-culture">-</td></tr>
                  <tr><th><?php echo __('Digital Objects'); ?></th><td id="review-objects">-</td></tr>
                  <tr><th><?php echo __('Branding'); ?></th><td id="review-branding">-</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-outline-secondary wizard-prev" data-prev="3">
              <i class="bi bi-arrow-left me-1"></i> <?php echo __('Back'); ?>
            </button>
            <button type="submit" class="btn btn-success btn-lg" id="btn-start-export">
              <i class="bi bi-play-circle me-1"></i> <?php echo __('Start Export'); ?>
            </button>
          </div>
        </div>

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
            <th><?php echo __('Expires'); ?></th>
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
                <td>
                  <?php if (!empty($exp->expires_at)): ?>
                    <small class="<?php echo strtotime($exp->expires_at) < time() ? 'text-danger' : 'text-muted'; ?>">
                      <?php echo date('d M Y', strtotime($exp->expires_at)); ?>
                    </small>
                  <?php else: ?>
                    <small class="text-muted">-</small>
                  <?php endif; ?>
                </td>
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

<style <?php echo $nonceAttr; ?>>
  .wizard-step { border-bottom: 3px solid transparent; color: #6c757d; }
  .wizard-step.active { border-bottom-color: #0d6efd; color: #0d6efd; font-weight: 600; }
  .wizard-step.completed { border-bottom-color: #198754; color: #198754; }
  .wizard-step.completed .badge { background-color: #198754 !important; }
</style>

<script <?php echo $nonceAttr; ?>>
(function() {
  var currentStep = 1;
  var totalSteps = 4;
  var currentExportId = null;
  var pollInterval = null;
  var shareExportId = null;

  // ─── Wizard Navigation ────────────────────────────────────────

  function goToStep(step) {
    if (step < 1 || step > totalSteps) return;

    // Update review panel when going to step 4
    if (step === 4) updateReview();

    // Hide all panels, show target
    document.querySelectorAll('.wizard-panel').forEach(function(p) { p.style.display = 'none'; });
    var target = document.querySelector('.wizard-panel[data-step="' + step + '"]');
    if (target) target.style.display = '';

    // Update step indicators
    document.querySelectorAll('.wizard-step').forEach(function(s) {
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

  // Step clicks
  document.querySelectorAll('.wizard-step').forEach(function(btn) {
    btn.addEventListener('click', function() {
      goToStep(parseInt(this.getAttribute('data-step')));
    });
  });

  // Next/Prev buttons
  document.querySelectorAll('.wizard-next').forEach(function(btn) {
    btn.addEventListener('click', function() { goToStep(parseInt(this.getAttribute('data-next'))); });
  });
  document.querySelectorAll('.wizard-prev').forEach(function(btn) {
    btn.addEventListener('click', function() { goToStep(parseInt(this.getAttribute('data-prev'))); });
  });

  // ─── Scope Toggle ─────────────────────────────────────────────

  var scopeSelect = document.getElementById('export-scope');
  var slugGroup = document.getElementById('scope-slug-group');
  var repoGroup = document.getElementById('scope-repo-group');

  scopeSelect.addEventListener('change', function() {
    slugGroup.style.display = (this.value === 'fonds') ? '' : 'none';
    repoGroup.style.display = (this.value === 'repository') ? '' : 'none';
  });

  // ─── Review Summary ───────────────────────────────────────────

  function updateReview() {
    document.getElementById('review-title').textContent = document.getElementById('export-title').value || '-';

    var scope = document.getElementById('export-scope');
    var scopeText = scope.options[scope.selectedIndex].text;
    if (scope.value === 'fonds') {
      var selectedLabel = document.getElementById('fonds-selected-label').textContent.trim();
      scopeText += ' (' + (selectedLabel || document.getElementById('export-slug').value || '?') + ')';
    }
    if (scope.value === 'repository') {
      var repo = document.getElementById('export-repository');
      scopeText += ' (' + (repo.options[repo.selectedIndex].text || '?') + ')';
    }
    document.getElementById('review-scope').textContent = scopeText;

    var mode = document.getElementById('export-mode');
    document.getElementById('review-mode').textContent = mode.options[mode.selectedIndex].text;

    var culture = document.getElementById('export-culture');
    document.getElementById('review-culture').textContent = culture.options[culture.selectedIndex].text;

    var objParts = [];
    if (document.getElementById('inc-objects').checked) objParts.push('Objects');
    if (document.getElementById('inc-thumbs').checked) objParts.push('Thumbnails');
    if (document.getElementById('inc-refs').checked) objParts.push('References');
    if (document.getElementById('inc-masters').checked) objParts.push('Masters');
    document.getElementById('review-objects').textContent = objParts.length > 0 ? objParts.join(', ') : 'None';

    var brandParts = [];
    if (document.getElementById('branding-title').value) brandParts.push(document.getElementById('branding-title').value);
    if (document.getElementById('branding-subtitle').value) brandParts.push(document.getElementById('branding-subtitle').value);
    document.getElementById('review-branding').textContent = brandParts.length > 0 ? brandParts.join(' — ') : 'Default';
  }

  // ─── Start Export ─────────────────────────────────────────────

  document.getElementById('exportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('btn-start-export');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Starting...';

    var data = new FormData(this);
    if (!document.getElementById('inc-objects').checked) data.set('include_objects', '0');
    if (!document.getElementById('inc-thumbs').checked) data.set('include_thumbnails', '0');
    if (!document.getElementById('inc-refs').checked) data.set('include_references', '0');

    fetch('/portable-export/api/start', { method: 'POST', body: data })
      .then(function(r) { return r.json(); })
      .then(function(resp) {
        if (resp.success) {
          currentExportId = resp.export_id;
          document.getElementById('wizard-card').style.display = 'none';
          document.getElementById('progress-panel').style.display = '';
          document.getElementById('progress-result').style.display = 'none';
          document.getElementById('progress-error').style.display = 'none';
          startPolling(resp.export_id);
        } else {
          alert(resp.error || 'Failed to start export');
          resetStartButton();
        }
      })
      .catch(function(err) {
        alert('Error: ' + err.message);
        resetStartButton();
      });
  });

  // ─── Progress Polling ─────────────────────────────────────────

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

  // ─── Delete Export ────────────────────────────────────────────

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

  // ─── Share Token ──────────────────────────────────────────────

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
    navigator.clipboard.writeText(input.value).catch(function() { document.execCommand('copy'); });
  });

  // ─── Fonds Autocomplete ───────────────────────────────────────

  var fondsInput = document.getElementById('fonds-search');
  var fondsResults = document.getElementById('fonds-results');
  var fondsHidden = document.getElementById('export-slug');
  var fondsSelected = document.getElementById('fonds-selected');
  var fondsLabel = document.getElementById('fonds-selected-label');
  var fondsClear = document.getElementById('fonds-clear');
  var fondsTimer = null;

  fondsInput.addEventListener('input', function() {
    var q = this.value.trim();
    clearTimeout(fondsTimer);
    if (q.length < 2) { fondsResults.style.display = 'none'; return; }

    fondsTimer = setTimeout(function() {
      fetch('/portable-export/api/fonds-search?q=' + encodeURIComponent(q))
        .then(function(r) { return r.json(); })
        .then(function(data) {
          var items = data.results || [];
          if (items.length === 0) {
            fondsResults.innerHTML = '<div class="list-group-item text-muted small"><?php echo __('No fonds found'); ?></div>';
            fondsResults.style.display = '';
            return;
          }
          var html = '';
          items.forEach(function(item) {
            html += '<a href="#" class="list-group-item list-group-item-action small fonds-result" data-slug="' + item.slug + '">'
              + '<strong>' + (item.title || item.slug) + '</strong>'
              + (item.identifier ? ' <span class="text-muted">(' + item.identifier + ')</span>' : '')
              + '</a>';
          });
          fondsResults.innerHTML = html;
          fondsResults.style.display = '';
        })
        .catch(function() { fondsResults.style.display = 'none'; });
    }, 300);
  });

  fondsResults.addEventListener('click', function(e) {
    var item = e.target.closest('.fonds-result');
    if (!item) return;
    e.preventDefault();
    var slug = item.getAttribute('data-slug');
    fondsHidden.value = slug;
    fondsLabel.textContent = item.textContent.trim();
    fondsSelected.style.display = '';
    fondsInput.value = '';
    fondsResults.style.display = 'none';
  });

  fondsClear.addEventListener('click', function() {
    fondsHidden.value = '';
    fondsLabel.textContent = '';
    fondsSelected.style.display = 'none';
    fondsInput.focus();
  });

  // Close dropdown when clicking outside
  document.addEventListener('click', function(e) {
    if (!fondsInput.contains(e.target) && !fondsResults.contains(e.target)) {
      fondsResults.style.display = 'none';
    }
  });

  // ─── Refresh ──────────────────────────────────────────────────

  document.getElementById('btn-refresh-list').addEventListener('click', function() {
    window.location.reload();
  });
})();
</script>

<?php use_helper('Date') ?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">

      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="bi bi-download me-2"></i>Batch Export Records</h5>
          <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'index']) ?>" class="btn btn-sm btn-outline-light">
            <i class="bi bi-arrow-left me-1"></i>Back to Import
          </a>
        </div>
        <div class="card-body">

          <?php if ($sf_user->hasFlash('error')): ?>
            <div class="alert alert-danger"><?php echo $sf_user->getFlash('error') ?></div>
          <?php endif ?>
          <?php if ($sf_user->hasFlash('notice')): ?>
            <div class="alert alert-info"><?php echo $sf_user->getFlash('notice') ?></div>
          <?php endif ?>
          <?php if ($sf_user->hasFlash('success')): ?>
            <div class="alert alert-success"><?php echo $sf_user->getFlash('success') ?></div>
          <?php endif ?>

          <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Export existing AtoM records</strong> to sector-specific CSV format for backup, reporting, or migration to another system.
          </div>

          <form action="<?php echo url_for(['module' => 'dataMigration', 'action' => 'batchExport']) ?>"
                method="post" id="batchExportForm">

            <!-- Step 1: Select Format -->
            <div class="mb-4">
              <h6 class="text-primary"><span class="badge bg-primary me-2">1</span>Export Format</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Sector Format</label>
                  <select name="sector" id="sectorSelect" class="form-select" required>
                    <?php foreach ($sectors as $code => $label): ?>
                      <option value="<?php echo $code ?>"><?php echo htmlspecialchars($label) ?></option>
                    <?php endforeach ?>
                  </select>
                  <small class="text-muted">Select the standard format for CSV columns</small>
                </div>
              </div>
            </div>

            <!-- Step 2: Filter Records -->
            <div class="mb-4">
              <h6 class="text-primary"><span class="badge bg-primary me-2">2</span>Filter Records (Optional)</h6>

              <div class="row g-3">
                <!-- Repository Filter -->
                <div class="col-md-6">
                  <label class="form-label">Repository</label>
                  <select name="repository_id" id="repositorySelect" class="form-select">
                    <option value="">All repositories</option>
                    <?php foreach ($repositories as $repo): ?>
                      <option value="<?php echo $repo->id ?>"><?php echo htmlspecialchars($repo->name) ?></option>
                    <?php endforeach ?>
                  </select>
                </div>

                <!-- Level of Description Filter -->
                <div class="col-md-6">
                  <label class="form-label">Level of Description</label>
                  <select name="level_ids[]" id="levelSelect" class="form-select" multiple size="4">
                    <?php foreach ($levels as $level): ?>
                      <option value="<?php echo $level->id ?>"><?php echo htmlspecialchars($level->name) ?></option>
                    <?php endforeach ?>
                  </select>
                  <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                </div>
              </div>

              <div class="row g-3 mt-2">
                <!-- Parent Scope -->
                <div class="col-md-6">
                  <label class="form-label">Parent Record Slug (Scope)</label>
                  <input type="text" name="parent_slug" id="parentSlug" class="form-control"
                         placeholder="e.g. my-fonds-123">
                  <small class="text-muted">Export only children of this record</small>
                </div>

                <!-- Include Descendants -->
                <div class="col-md-6 d-flex align-items-end">
                  <div class="form-check">
                    <input type="checkbox" name="include_descendants" id="includeDescendants"
                           class="form-check-input" value="1">
                    <label class="form-check-label" for="includeDescendants">
                      Include all descendants (not just direct children)
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Export Options Info -->
            <div class="mb-4">
              <h6 class="text-primary"><span class="badge bg-primary me-2">3</span>Export</h6>
              <div class="alert alert-warning mb-3">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Note:</strong> Exports with more than 500 records will be queued as a background job.
                You can check progress on the <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'jobs']) ?>">Jobs page</a>.
              </div>
            </div>

            <!-- Submit -->
            <div class="d-flex justify-content-between">
              <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'index']) ?>" class="btn btn-secondary">
                Cancel
              </a>
              <button type="submit" class="btn btn-primary btn-lg" id="exportBtn">
                <i class="bi bi-download me-2"></i>Export CSV
              </button>
            </div>

          </form>
        </div>
      </div>

      <!-- Sector Format Descriptions -->
      <div class="card mt-4">
        <div class="card-header">
          <h6 class="mb-0">Format Descriptions</h6>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-3">Archives (ISAD-G)</dt>
            <dd class="col-sm-9">Standard archival description fields following ISAD(G) standard. Best for archives and manuscript collections.</dd>

            <dt class="col-sm-3">Museum (Spectrum)</dt>
            <dd class="col-sm-9">Spectrum 5.1 standard fields for museum objects including production, acquisition, and location data.</dd>

            <dt class="col-sm-3">Library (MARC/RDA)</dt>
            <dd class="col-sm-9">MARC and RDA cataloguing fields for bibliographic records including ISBN, call numbers, and publishing data.</dd>

            <dt class="col-sm-3">Gallery (CCO/VRA)</dt>
            <dd class="col-sm-9">Cataloging Cultural Objects (CCO) and VRA Core fields for artworks and visual resources.</dd>

            <dt class="col-sm-3">Digital Assets</dt>
            <dd class="col-sm-9">Dublin Core and IPTC metadata fields for digital asset management including technical metadata.</dd>
          </dl>
        </div>
      </div>

    </div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var form = document.getElementById('batchExportForm');
  var exportBtn = document.getElementById('exportBtn');

  form.addEventListener('submit', function() {
    exportBtn.disabled = true;
    exportBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Exporting...';
  });

  // Toggle descendants checkbox based on parent slug
  var parentSlug = document.getElementById('parentSlug');
  var includeDescendants = document.getElementById('includeDescendants');

  parentSlug.addEventListener('input', function() {
    includeDescendants.disabled = !this.value.trim();
    if (!this.value.trim()) {
      includeDescendants.checked = false;
    }
  });

  // Initial state
  includeDescendants.disabled = !parentSlug.value.trim();
});
</script>

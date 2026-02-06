<div class="container-fluid px-4">
  <h1 class="mt-4">
    <i class="bi bi-archive me-2"></i>
    Preservica Export
  </h1>
  
  <ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'admin', 'action' => 'index']) ?>">Admin</a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'preservica', 'action' => 'index']) ?>">Data Migration</a></li>
    <li class="breadcrumb-item active">Preservica Export</li>
  </ol>

  <?php if ($sf_user->hasFlash('notice')): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?php echo $sf_user->getFlash('notice') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif ?>

  <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <?php echo $sf_user->getFlash('error') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif ?>

  <?php if (isset($exportPath)): ?>
    <!-- Export Results -->
    <div class="card mb-4">
      <div class="card-header bg-success text-white">
        <i class="bi bi-check-circle me-2"></i>
        Export Complete
      </div>
      <div class="card-body">
        <div class="row text-center mb-3">
          <div class="col">
            <h3 class="text-success"><?php echo $exportStats['exported'] ?></h3>
            <small>Records Exported</small>
          </div>
          <div class="col">
            <h3 class="text-info"><?php echo $exportStats['digital_objects'] ?></h3>
            <small>Digital Objects</small>
          </div>
          <div class="col">
            <h3 class="text-danger"><?php echo $exportStats['errors'] ?></h3>
            <small>Errors</small>
          </div>
        </div>

        <div class="alert alert-info">
          <i class="bi bi-file-earmark-zip me-2"></i>
          <strong>Export file:</strong> <?php echo basename($exportPath) ?>
        </div>

        <a href="<?php echo url_for(['module' => 'preservica', 'action' => 'download', 'file' => basename($exportPath)]) ?>" class="btn btn-success btn-lg">
          <i class="bi bi-download me-2"></i> Download Export
        </a>
      </div>
    </div>
  <?php endif ?>

  <div class="row">
    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header">
          <i class="bi bi-download me-2"></i>
          Export to Preservica
        </div>
        <div class="card-body">
          <form method="post" id="preservica-export-form">
            
            <!-- Format Selection -->
            <div class="mb-3">
              <label class="form-label fw-bold">Export Format</label>
              <div class="row g-3">
                <?php foreach ($formats as $key => $name): ?>
                  <div class="col-md-6">
                    <div class="form-check border rounded p-3">
                      <input class="form-check-input" type="radio" name="format" id="format_<?php echo $key ?>" value="<?php echo $key ?>" <?php echo $key === 'opex' ? 'checked' : '' ?>>
                      <label class="form-check-label w-100" for="format_<?php echo $key ?>">
                        <strong><?php echo $name ?></strong>
                        <?php if ($key === 'opex'): ?>
                          <br><small class="text-muted">XML files with Dublin Core metadata</small>
                        <?php else: ?>
                          <br><small class="text-muted">ZIP package with XIP metadata + files</small>
                        <?php endif ?>
                      </label>
                    </div>
                  </div>
                <?php endforeach ?>
              </div>
            </div>

            <!-- Export Source -->
            <div class="mb-3">
              <label class="form-label fw-bold">What to Export</label>
              
              <div class="border rounded p-3 mb-2">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="export_type" id="export_single" value="single" checked>
                  <label class="form-check-label" for="export_single">
                    <strong>Single Record</strong>
                  </label>
                </div>
                <div class="ms-4 mt-2" id="single_options">
                  <label for="object_id" class="form-label">Record ID</label>
                  <input type="number" class="form-control" id="object_id" name="object_id" placeholder="Enter information object ID" value="<?php echo isset($objectId) ? $objectId : '' ?>">
                  <?php if (isset($object)): ?>
                    <div class="form-text text-success">
                      <i class="bi bi-check-circle me-1"></i>
                      <?php echo esc_entities($object->getTitle(['cultureFallback' => true])) ?>
                    </div>
                  <?php endif ?>
                </div>
              </div>

              <div class="border rounded p-3 mb-2">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="export_type" id="export_hierarchy" value="hierarchy">
                  <label class="form-check-label" for="export_hierarchy">
                    <strong>Record + All Descendants</strong>
                    <br><small class="text-muted">Export full hierarchy starting from a record</small>
                  </label>
                </div>
              </div>

              <div class="border rounded p-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="export_type" id="export_repository" value="repository">
                  <label class="form-check-label" for="export_repository">
                    <strong>Entire Repository</strong>
                  </label>
                </div>
                <div class="ms-4 mt-2" id="repository_options" style="display: none;">
                  <label for="repository_id" class="form-label">Repository</label>
                  <select class="form-select" id="repository_id" name="repository_id">
                    <option value="">-- Select Repository --</option>
                    <?php foreach ($repositories as $id => $name): ?>
                      <option value="<?php echo $id ?>"><?php echo esc_entities($name) ?></option>
                    <?php endforeach ?>
                  </select>
                </div>
              </div>
            </div>

            <!-- Options -->
            <div class="mb-3">
              <label class="form-label fw-bold">Export Options</label>
              
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="include_digital_objects" name="include_digital_objects" value="1" checked>
                <label class="form-check-label" for="include_digital_objects">
                  Include digital objects
                </label>
              </div>
              
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="export_hierarchy_opt" name="export_hierarchy" value="1">
                <label class="form-check-label" for="export_hierarchy_opt">
                  Export child records
                </label>
              </div>
            </div>

            <!-- Security Descriptor -->
            <div class="mb-3">
              <label for="security_descriptor" class="form-label fw-bold">Default Security Descriptor</label>
              <select class="form-select" id="security_descriptor" name="security_descriptor">
                <option value="open" selected>Open</option>
                <option value="closed">Closed</option>
                <option value="restricted">Restricted</option>
              </select>
              <div class="form-text">Applied to records without explicit access conditions</div>
            </div>

            <hr>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-success">
                <i class="bi bi-download me-1"></i> Generate Export
              </button>
              <a href="<?php echo url_for(['module' => 'preservica', 'action' => 'index']) ?>" class="btn btn-secondary">
                Cancel
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Sidebar Help -->
    <div class="col-lg-4">
      <div class="card mb-4">
        <div class="card-header">
          <i class="bi bi-info-circle me-2"></i>
          Export Information
        </div>
        <div class="card-body">
          <h6>OPEX Format</h6>
          <p class="small">Creates individual .opex XML files for each record with Dublin Core metadata. Best for single records or small exports.</p>
          
          <h6>PAX/XIP Format</h6>
          <p class="small">Creates a ZIP package containing XIP metadata and all digital objects. Best for large exports or complete transfers to Preservica.</p>
          
          <hr>
          
          <h6>Field Mapping</h6>
          <p class="small">AtoM ISAD(G) fields are mapped to Preservica/Dublin Core:</p>
          <ul class="small">
            <li>Title → dc:title</li>
            <li>Scope and Content → dc:description</li>
            <li>Creator → dc:creator</li>
            <li>Date → dc:date</li>
            <li>Access Conditions → dcterms:accessRights</li>
          </ul>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <i class="bi bi-terminal me-2"></i>
          CLI Alternative
        </div>
        <div class="card-body">
          <p class="small">For large exports, use the command line:</p>
          <pre class="bg-dark text-light p-2 rounded small"><code>php symfony preservica:export 123 \
  --format=xip \
  --hierarchy</code></pre>
        </div>
      </div>
    </div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  const exportTypes = document.querySelectorAll('input[name="export_type"]');
  const singleOptions = document.getElementById('single_options');
  const repoOptions = document.getElementById('repository_options');
  const hierarchyCheckbox = document.getElementById('export_hierarchy_opt');
  
  exportTypes.forEach(function(radio) {
    radio.addEventListener('change', function() {
      singleOptions.style.display = (this.value === 'single' || this.value === 'hierarchy') ? 'block' : 'none';
      repoOptions.style.display = this.value === 'repository' ? 'block' : 'none';
      hierarchyCheckbox.checked = this.value === 'hierarchy';
    });
  });
});
</script>

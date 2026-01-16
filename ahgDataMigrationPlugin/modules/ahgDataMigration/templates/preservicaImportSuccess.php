<?php use_helper('Javascript') ?>

<div class="container-fluid px-4">
  <h1 class="mt-4">
    <i class="bi bi-archive me-2"></i>
    Preservica Import
  </h1>
  
  <ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'admin', 'action' => 'index']) ?>">Admin</a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ahgDataMigration', 'action' => 'index']) ?>">Data Migration</a></li>
    <li class="breadcrumb-item active">Preservica Import</li>
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

  <?php if (isset($importResult)): ?>
    <!-- Import Results -->
    <div class="card mb-4">
      <div class="card-header bg-<?php echo $importResult['success'] ? 'success' : 'warning' ?> text-white">
        <i class="bi bi-clipboard-check me-2"></i>
        Import Results
      </div>
      <div class="card-body">
        <div class="row text-center">
          <div class="col">
            <h3 class="text-primary"><?php echo $importResult['stats']['total'] ?></h3>
            <small>Total Records</small>
          </div>
          <div class="col">
            <h3 class="text-success"><?php echo $importResult['stats']['imported'] ?></h3>
            <small>Imported</small>
          </div>
          <div class="col">
            <h3 class="text-info"><?php echo $importResult['stats']['updated'] ?></h3>
            <small>Updated</small>
          </div>
          <div class="col">
            <h3 class="text-secondary"><?php echo $importResult['stats']['skipped'] ?></h3>
            <small>Skipped</small>
          </div>
          <div class="col">
            <h3 class="text-danger"><?php echo $importResult['stats']['errors'] ?></h3>
            <small>Errors</small>
          </div>
        </div>

        <?php if (!empty($importResult['errors'])): ?>
          <hr>
          <h6>Errors:</h6>
          <ul class="text-danger small">
            <?php foreach ($importResult['errors'] as $error): ?>
              <li><?php echo esc_entities($error['record'] ?? 'Unknown') ?>: <?php echo esc_entities($error['message']) ?></li>
            <?php endforeach ?>
          </ul>
        <?php endif ?>
      </div>
    </div>
  <?php endif ?>

  <div class="row">
    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header">
          <i class="bi bi-upload me-2"></i>
          Import from Preservica
        </div>
        <div class="card-body">
          <form method="post" enctype="multipart/form-data" id="preservica-import-form">
            
            <!-- Format Selection -->
            <div class="mb-3">
              <label class="form-label fw-bold">Source Format</label>
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
                          <br><small class="text-muted">ZIP packages with XIP metadata + files</small>
                        <?php endif ?>
                      </label>
                    </div>
                  </div>
                <?php endforeach ?>
              </div>
            </div>

            <!-- File Upload -->
            <div class="mb-3">
              <label for="import_file" class="form-label fw-bold">Upload File</label>
              <input class="form-control" type="file" id="import_file" name="import_file" accept=".opex,.xml,.pax,.zip" required>
              <div class="form-text">
                Accepted formats: .opex, .xml (OPEX), .pax, .zip (PAX/XIP)
              </div>
            </div>

            <!-- Repository -->
            <div class="mb-3">
              <label for="repository_id" class="form-label fw-bold">Target Repository</label>
              <select class="form-select" id="repository_id" name="repository_id">
                <option value="">-- Select Repository (optional) --</option>
                <?php foreach ($repositories as $id => $name): ?>
                  <option value="<?php echo $id ?>"><?php echo esc_entities($name) ?></option>
                <?php endforeach ?>
              </select>
              <div class="form-text">Assign imported records to this repository</div>
            </div>

            <!-- Parent Record -->
            <div class="mb-3">
              <label for="parent_id" class="form-label fw-bold">Parent Record</label>
              <input type="text" class="form-control" id="parent_id" name="parent_id" placeholder="Enter parent ID (optional)">
              <div class="form-text">Import as children of this record</div>
            </div>

            <!-- Options -->
            <div class="mb-3">
              <label class="form-label fw-bold">Import Options</label>
              
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="import_digital_objects" name="import_digital_objects" value="1" checked>
                <label class="form-check-label" for="import_digital_objects">
                  Import digital objects
                </label>
              </div>
              
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="verify_checksums" name="verify_checksums" value="1" checked>
                <label class="form-check-label" for="verify_checksums">
                  Verify checksums
                </label>
              </div>
              
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="create_hierarchy" name="create_hierarchy" value="1" checked>
                <label class="form-check-label" for="create_hierarchy">
                  Create hierarchy (preserve parent-child relationships)
                </label>
              </div>
              
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="update_existing" name="update_existing" value="1">
                <label class="form-check-label" for="update_existing">
                  Update existing records (match by legacy ID)
                </label>
              </div>
            </div>

            <hr>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-upload me-1"></i> Start Import
              </button>
              <a href="<?php echo url_for(['module' => 'ahgDataMigration', 'action' => 'index']) ?>" class="btn btn-secondary">
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
          Format Information
        </div>
        <div class="card-body">
          <h6>OPEX Format</h6>
          <p class="small">Open Preservation Exchange format uses XML with Dublin Core elements. Each record is a separate .opex file.</p>
          
          <h6>PAX/XIP Format</h6>
          <p class="small">Preservica Archive eXchange packages are ZIP files containing XIP metadata and content files with full hierarchy support.</p>
          
          <hr>
          
          <h6>Field Mapping</h6>
          <p class="small">Preservica fields are automatically mapped to AtoM ISAD(G) fields:</p>
          <ul class="small">
            <li>dc:title → Title</li>
            <li>dc:description → Scope and Content</li>
            <li>dc:creator → Creator</li>
            <li>dc:date → Date</li>
            <li>dc:subject → Subject Access Points</li>
            <li>dc:coverage → Place Access Points</li>
          </ul>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <i class="bi bi-terminal me-2"></i>
          CLI Alternative
        </div>
        <div class="card-body">
          <p class="small">For large imports, use the command line:</p>
          <pre class="bg-dark text-light p-2 rounded small"><code>php symfony preservica:import \
  /path/to/file.opex \
  --repository=5</code></pre>
        </div>
      </div>
    </div>
  </div>
</div>

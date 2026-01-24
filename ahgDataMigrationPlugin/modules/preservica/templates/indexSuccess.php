<?php use_helper('Text') ?>

<div class="container-fluid px-4">
  <h1 class="mt-4">
    <i class="bi bi-arrow-left-right me-2"></i>
    Data Migration
  </h1>
  
  <ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'admin', 'action' => 'index']) ?>">Admin</a></li>
    <li class="breadcrumb-item active">Data Migration</li>
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

  <div class="row">
    <!-- Import Card -->
    <div class="col-xl-6 mb-4">
      <div class="card border-primary h-100">
        <div class="card-header bg-primary text-white">
          <i class="bi bi-cloud-upload me-2"></i>
          Import Data
        </div>
        <div class="card-body">
          <p class="card-text">Import archival descriptions and digital objects from external systems.</p>
          
          <h6 class="mt-3 mb-2">Supported Sources:</h6>
          <div class="row g-2">
            <?php foreach ($sourceSystems as $key => $system): ?>
              <div class="col-md-6">
                <div class="d-flex align-items-center p-2 border rounded">
                  <i class="<?php echo $system['icon'] ?> fs-4 me-2 text-primary"></i>
                  <div>
                    <strong><?php echo $system['name'] ?></strong>
                    <br><small class="text-muted"><?php echo truncate_text($system['description'], 40) ?></small>
                  </div>
                </div>
              </div>
            <?php endforeach ?>
          </div>
        </div>
        <div class="card-footer">
          <a href="<?php echo url_for(['module' => 'preservica', 'action' => 'import']) ?>" class="btn btn-primary">
            <i class="bi bi-upload me-1"></i> Start Import
          </a>
          <a href="<?php echo url_for(['module' => 'preservica', 'action' => 'preservicaImport']) ?>" class="btn btn-outline-primary">
            <i class="bi bi-archive me-1"></i> Preservica Import
          </a>
        </div>
      </div>
    </div>

    <!-- Export Card -->
    <div class="col-xl-6 mb-4">
      <div class="card border-success h-100">
        <div class="card-header bg-success text-white">
          <i class="bi bi-cloud-download me-2"></i>
          Export Data
        </div>
        <div class="card-body">
          <p class="card-text">Export archival descriptions and digital objects to external systems.</p>
          
          <h6 class="mt-3 mb-2">Available Formats:</h6>
          <div class="row g-2">
            <?php foreach ($exportFormats as $key => $format): ?>
              <div class="col-md-6">
                <div class="d-flex align-items-center p-2 border rounded">
                  <i class="<?php echo $format['icon'] ?> fs-4 me-2 text-success"></i>
                  <div>
                    <strong><?php echo $format['name'] ?></strong>
                    <br><small class="text-muted"><?php echo truncate_text($format['description'], 40) ?></small>
                  </div>
                </div>
              </div>
            <?php endforeach ?>
          </div>
        </div>
        <div class="card-footer">
          <a href="<?php echo url_for(['module' => 'preservica', 'action' => 'export']) ?>" class="btn btn-success">
            <i class="bi bi-download me-1"></i> Start Export
          </a>
          <a href="<?php echo url_for(['module' => 'preservica', 'action' => 'preservicaExport']) ?>" class="btn btn-outline-success">
            <i class="bi bi-archive me-1"></i> Preservica Export
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Preservica Quick Access -->
  <div class="row">
    <div class="col-12">
      <div class="card border-info mb-4">
        <div class="card-header bg-info text-white">
          <i class="bi bi-archive me-2"></i>
          Preservica Integration
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <h5><i class="bi bi-file-earmark-code me-2"></i>OPEX Format</h5>
              <p>Open Preservation Exchange - XML-based metadata format with Dublin Core elements.</p>
              <ul class="small">
                <li>Single XML files per record</li>
                <li>Dublin Core metadata (dc: and dcterms:)</li>
                <li>Security descriptors</li>
                <li>Fixity/checksum support</li>
              </ul>
            </div>
            <div class="col-md-6">
              <h5><i class="bi bi-file-zip me-2"></i>PAX/XIP Format</h5>
              <p>Preservica Archive eXchange - ZIP packages with XIP metadata and content files.</p>
              <ul class="small">
                <li>Complete packages with digital objects</li>
                <li>Structural hierarchy preserved</li>
                <li>Content Objects and Representations</li>
                <li>Embedded Dublin Core</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- CLI Reference -->
  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header">
          <i class="bi bi-terminal me-2"></i>
          CLI Commands
        </div>
        <div class="card-body">
          <p>For batch operations or automation, use the command-line interface:</p>
          <pre class="bg-dark text-light p-3 rounded"><code># Preservica Import
php symfony preservica:import /path/to/file.opex
php symfony preservica:import /path/to/package.pax --format=xip
php symfony preservica:import /path/to/directory --batch

# Preservica Export
php symfony preservica:export 123 --format=opex
php symfony preservica:export 123 --format=xip --hierarchy
php symfony preservica:export --repository=5

# Show format info
php symfony preservica:info --show-fields</code></pre>
        </div>
      </div>
    </div>
  </div>
</div>

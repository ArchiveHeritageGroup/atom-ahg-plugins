<?php use_helper('Text') ?>

<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-check-circle text-success me-2"></i>AHG Import Complete</h4>
    <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'index']) ?>" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-arrow-left me-1"></i> New Import
    </a>
  </div>

  <div class="row g-3 mb-4">
    <!-- Records Stats -->
    <div class="col-md-3">
      <div class="card border-success h-100">
        <div class="card-body text-center">
          <h2 class="text-success mb-0"><?php echo $stats['created'] ?? 0 ?></h2>
          <small class="text-muted">Records Created</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-primary h-100">
        <div class="card-body text-center">
          <h2 class="text-primary mb-0"><?php echo $stats['updated'] ?? 0 ?></h2>
          <small class="text-muted">Records Updated</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-warning h-100">
        <div class="card-body text-center">
          <h2 class="text-warning mb-0"><?php echo $stats['skipped'] ?? 0 ?></h2>
          <small class="text-muted">Skipped</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-danger h-100">
        <div class="card-body text-center">
          <h2 class="text-danger mb-0"><?php echo count($stats['errors'] ?? []) ?></h2>
          <small class="text-muted">Errors</small>
        </div>
      </div>
    </div>
  </div>

  <!-- AHG Plugin Stats -->
  <div class="card mb-4">
    <div class="card-header bg-info text-white">
      <i class="bi bi-puzzle me-2"></i>AHG Plugin Integration
    </div>
    <div class="card-body">
      <div class="row text-center">
        <div class="col-md-4">
          <h4 class="mb-0"><?php echo $stats['provenance_created'] ?? 0 ?></h4>
          <small class="text-muted"><i class="bi bi-clock-history me-1"></i>Provenance Records</small>
        </div>
        <div class="col-md-4">
          <h4 class="mb-0"><?php echo $stats['rights_created'] ?? 0 ?></h4>
          <small class="text-muted"><i class="bi bi-shield-check me-1"></i>Rights Statements</small>
        </div>
        <div class="col-md-4">
          <h4 class="mb-0"><?php echo $stats['security_set'] ?? 0 ?></h4>
          <small class="text-muted"><i class="bi bi-lock me-1"></i>Security Classifications</small>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($stats['errors'])): ?>
  <!-- Errors -->
  <div class="card border-danger">
    <div class="card-header bg-danger text-white">
      <i class="bi bi-exclamation-triangle me-2"></i>Import Errors
    </div>
    <div class="card-body">
      <ul class="list-unstyled mb-0" style="max-height: 300px; overflow-y: auto;">
        <?php foreach ($stats['errors'] as $error): ?>
          <li class="text-danger small mb-1"><i class="bi bi-x-circle me-1"></i><?php echo esc_specialchars($error) ?></li>
        <?php endforeach ?>
      </ul>
    </div>
  </div>
  <?php endif ?>

  <div class="mt-4">
    <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']) ?>" class="btn btn-success">
      <i class="bi bi-folder2-open me-1"></i> Browse Records
    </a>
    <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'index']) ?>" class="btn btn-outline-secondary ms-2">
      <i class="bi bi-upload me-1"></i> Import More
    </a>
  </div>
</div>

<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
  <!-- Quick Info Card -->
  <div class="card mb-3">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Record Info'); ?></h5>
    </div>
    <div class="card-body">
      <dl class="mb-0">
        <?php if ($resource->identifier): ?>
        <dt><?php echo __('Identifier'); ?></dt>
        <dd><?php echo esc_entities($resource->identifier); ?></dd>
        <?php endif; ?>
        <dt><?php echo __('Title'); ?></dt>
        <dd><?php echo esc_entities($resource->title ?? $resource->slug); ?></dd>
      </dl>
    </div>
  </div>

  <!-- Quick Actions Card -->
  <div class="card mb-3">
    <div class="card-header">
      <h5 class="mb-0"><i class="fas fa-bolt me-2"></i><?php echo __('Quick Actions'); ?></h5>
    </div>
    <ul class="list-group list-group-flush">
      <li class="list-group-item">
        <a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'label', 'slug' => $resource->slug]); ?>">
          <i class="fas fa-barcode me-2"></i><?php echo __('Print Labels'); ?>
        </a>
      </li>
      <li class="list-group-item">
        <a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'conditionPhotos', 'slug' => $resource->slug]); ?>">
          <i class="fas fa-camera me-2"></i><?php echo __('Condition Photos'); ?>
        </a>
      </li>
      <li class="list-group-item">
        <a href="<?php echo url_for(['module' => 'grap', 'action' => 'index', 'slug' => $resource->slug]); ?>">
          <i class="fas fa-file-invoice-dollar me-2"></i><?php echo __('GRAP Data'); ?>
        </a>
      </li>
    </ul>
  </div>

  <!-- Back Link -->
  <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $resource->slug]); ?>" class="btn btn-outline-secondary w-100">
    <i class="fas fa-arrow-left me-2"></i><?php echo __('Back to record'); ?>
  </a>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <div class="d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-layer-group me-3 text-primary"></i>
    <div>
      <h1 class="mb-0"><?php echo __('Spectrum Data'); ?></h1>
      <span class="text-muted"><?php echo esc_entities($resource->title ?? $resource->slug); ?></span>
    </div>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $resource->slug]); ?>"><?php echo esc_entities($resource->title ?? $resource->slug); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Spectrum Data'); ?></li>
  </ol>
</nav>

<!-- Spectrum 5.1 Procedures Grid -->
<div class="card mb-4">
  <div class="card-header bg-success text-white">
    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i><?php echo __('Spectrum 5.1 Procedures'); ?></h5>
  </div>
  <div class="card-body">
    <p class="text-muted mb-4"><?php echo __('Manage collections management procedures according to Spectrum 5.1 standard.'); ?></p>
    
    <div class="row g-3">
      <?php
      $procedures = arSpectrumWorkflowService::getProcedures();
      $colors = ['primary', 'success', 'info', 'warning', 'secondary', 'dark'];
      $i = 0;
      foreach ($procedures as $key => $proc): 
        $color = $colors[$i % count($colors)];
        $i++;
      ?>
      <div class="col-lg-3 col-md-4 col-sm-6">
        <div class="card h-100 border-<?php echo $color; ?>">
          <div class="card-body text-center p-3">
            <i class="fas <?php echo $proc['icon']; ?> fa-2x mb-2 text-<?php echo $color; ?>"></i>
            <h6 class="card-title mb-2"><?php echo $proc['label']; ?></h6>
            <a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'workflow', 'slug' => $resource->slug, 'procedure_type' => $key]); ?>" 
               class="btn btn-sm btn-outline-<?php echo $color; ?>">
              <i class="fas fa-cog me-1"></i><?php echo __('Manage'); ?>
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Recent Activity -->
<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0"><i class="fas fa-history me-2"></i><?php echo __('Recent Procedure Activity'); ?></h5>
  </div>
  <div class="card-body">
    <?php
    // Load recent procedure history
    if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
        \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
    }
    $recentHistory = \Illuminate\Database\Capsule\Manager::table('spectrum_procedure_history')
        ->where('object_id', $resource->id)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    ?>
    <?php if ($recentHistory->isEmpty()): ?>
      <p class="text-muted mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('No procedure history recorded yet.'); ?></p>
    <?php else: ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($recentHistory as $entry): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span>
            <strong><?php echo ucfirst(str_replace('_', ' ', $entry->procedure_type)); ?></strong>
            <span class="text-muted"> - <?php echo $entry->action; ?></span>
          </span>
          <small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($entry->created_at)); ?></small>
        </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<!-- Export Options -->
<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><i class="fas fa-download me-2"></i><?php echo __('Export Options'); ?></h5>
  </div>
  <div class="card-body">
    <div class="btn-group flex-wrap" role="group">
      <a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'export', 'slug' => $resource->slug, 'format' => 'pdf']); ?>" class="btn btn-outline-danger">
        <i class="fas fa-file-pdf me-1"></i><?php echo __('Export PDF'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'export', 'slug' => $resource->slug, 'format' => 'csv']); ?>" class="btn btn-outline-success">
        <i class="fas fa-file-csv me-1"></i><?php echo __('Export CSV'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'export', 'slug' => $resource->slug, 'format' => 'json']); ?>" class="btn btn-outline-primary">
        <i class="fas fa-file-code me-1"></i><?php echo __('Export JSON'); ?>
      </a>
    </div>
  </div>
</div>

<?php end_slot(); ?>

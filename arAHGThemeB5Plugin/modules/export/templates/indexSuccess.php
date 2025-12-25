<?php
$exportFormats = sfOutputEscaper::unescape($exportFormats ?? []);
?>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'staticpage', 'action' => 'static', 'id' => 'homepage']); ?>"><?php echo __('Home'); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'admin', 'action' => 'index']); ?>"><?php echo __('Admin'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('Export'); ?></li>
        </ol>
    </nav>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-file-export me-2"></i><?php echo __('Export Data'); ?></h4>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-archive me-2"></i><?php echo __('Archival Descriptions'); ?></h5>
                </div>
                <div class="card-body">
                    <p class="text-muted"><?php echo __('Export archival descriptions in various formats.'); ?></p>
                    <div class="d-grid gap-2">
                        <a href="<?php echo url_for(['module' => 'export', 'action' => 'archival', 'format' => 'ead']); ?>" class="btn btn-outline-primary">
                            <i class="fas fa-code me-1"></i>EAD 2002
                        </a>
                        <a href="<?php echo url_for(['module' => 'export', 'action' => 'archival', 'format' => 'dc']); ?>" class="btn btn-outline-primary">
                            <i class="fas fa-file-code me-1"></i>Dublin Core
                        </a>
                        <a href="<?php echo url_for(['module' => 'export', 'action' => 'archival', 'format' => 'csv']); ?>" class="btn btn-outline-primary">
                            <i class="fas fa-file-csv me-1"></i>CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i><?php echo __('Authority Records'); ?></h5>
                </div>
                <div class="card-body">
                    <p class="text-muted"><?php echo __('Export authority records (people, organizations, families).'); ?></p>
                    <div class="d-grid gap-2">
                        <a href="<?php echo url_for(['module' => 'export', 'action' => 'authority', 'format' => 'eac']); ?>" class="btn btn-outline-success">
                            <i class="fas fa-code me-1"></i>EAC-CPF
                        </a>
                        <a href="<?php echo url_for(['module' => 'export', 'action' => 'authority', 'format' => 'csv']); ?>" class="btn btn-outline-success">
                            <i class="fas fa-file-csv me-1"></i>CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i><?php echo __('Repositories'); ?></h5>
                </div>
                <div class="card-body">
                    <p class="text-muted"><?php echo __('Export repository/institution records.'); ?></p>
                    <div class="d-grid gap-2">
                        <a href="<?php echo url_for(['module' => 'export', 'action' => 'repository', 'format' => 'csv']); ?>" class="btn btn-outline-info">
                            <i class="fas fa-file-csv me-1"></i>CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <?php echo __('For bulk exports, use the command line tools: php symfony export:bulk'); ?>
    </div>
</div>

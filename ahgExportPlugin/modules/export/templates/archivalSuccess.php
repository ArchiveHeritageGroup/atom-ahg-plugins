<?php $format = $format ?? 'ead'; ?>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'staticpage', 'action' => 'static', 'id' => 'homepage']); ?>"><?php echo __('Home'); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'export', 'action' => 'index']); ?>"><?php echo __('Export'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('Archival Descriptions'); ?></li>
        </ol>
    </nav>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-archive me-2"></i><?php echo __('Export Archival Descriptions'); ?></h4>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo url_for(['module' => 'object', 'action' => 'export']); ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo __('Format'); ?></label>
                        <select name="format" class="form-select">
                            <option value="ead" <?php echo $format === 'ead' ? 'selected' : ''; ?>>EAD 2002</option>
                            <option value="dc" <?php echo $format === 'dc' ? 'selected' : ''; ?>>Dublin Core</option>
                            <option value="mods" <?php echo $format === 'mods' ? 'selected' : ''; ?>>MODS</option>
                            <option value="csv" <?php echo $format === 'csv' ? 'selected' : ''; ?>>CSV</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo __('Include'); ?></label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_descendants" id="includeDescendants" checked>
                            <label class="form-check-label" for="includeDescendants"><?php echo __('Include descendants'); ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_digital" id="includeDigital">
                            <label class="form-check-label" for="includeDigital"><?php echo __('Include digital objects'); ?></label>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo __('To export specific records, navigate to the record and use the export option from the More menu. This page is for bulk/batch exports.'); ?>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="<?php echo url_for(['module' => 'export', 'action' => 'index']); ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i><?php echo __('Back'); ?>
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download me-1"></i><?php echo __('Export'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

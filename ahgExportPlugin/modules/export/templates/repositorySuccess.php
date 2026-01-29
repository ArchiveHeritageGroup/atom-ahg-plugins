<?php $format = $format ?? 'csv'; ?>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'staticpage', 'action' => 'static', 'id' => 'homepage']); ?>"><?php echo __('Home'); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'export', 'action' => 'index']); ?>"><?php echo __('Export'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('Repositories'); ?></li>
        </ol>
    </nav>
    
    <div class="card">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0"><i class="fas fa-building me-2"></i><?php echo __('Export Repositories'); ?></h4>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Format'); ?></label>
                    <select name="format" class="form-select">
                        <option value="csv" <?php echo $format === 'csv' ? 'selected' : ''; ?>>CSV</option>
                    </select>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="<?php echo url_for(['module' => 'export', 'action' => 'index']); ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i><?php echo __('Back'); ?>
                    </a>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-download me-1"></i><?php echo __('Export'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $format = $format ?? 'eac'; ?>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'staticpage', 'action' => 'static', 'id' => 'homepage']); ?>"><?php echo __('Home'); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'export', 'action' => 'index']); ?>"><?php echo __('Export'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('Authority Records'); ?></li>
        </ol>
    </nav>
    
    <div class="card">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="fas fa-user me-2"></i><?php echo __('Export Authority Records'); ?></h4>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Format'); ?></label>
                    <select name="format" class="form-select">
                        <option value="eac" <?php echo $format === 'eac' ? 'selected' : ''; ?>>EAC-CPF</option>
                        <option value="csv" <?php echo $format === 'csv' ? 'selected' : ''; ?>>CSV</option>
                    </select>
                </div>
                
                <a href="<?php echo url_for(['module' => 'export', 'action' => 'index']); ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i><?php echo __('Back'); ?>
                </a>
            </form>
        </div>
    </div>
</div>

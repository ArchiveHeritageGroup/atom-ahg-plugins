<?php
/**
 * AHG Settings Landing Page Partial
 */
?>
<div class="card-header">
    <h4 class="mb-0">
        <i class="fas fa-th-large"></i>
        <?php echo __('Settings Overview'); ?>
    </h4>
    <small class="text-muted"><?php echo __('Select a section to configure'); ?></small>
</div>
<div class="card-body">
    <div class="row">
        <?php foreach ($sections as $sectionKey => $sectionInfo): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas <?php echo $sectionInfo['icon']; ?> fa-3x text-primary"></i>
                    </div>
                    <h5 class="card-title"><?php echo __($sectionInfo['label']); ?></h5>
                    <p class="card-text text-muted small"><?php echo __($sectionInfo['description']); ?></p>
                </div>
                <div class="card-footer bg-transparent border-0 text-center">
                    <a href="<?php echo url_for(['module' => 'settings', 'action' => 'ahgSettings', 'section' => $sectionKey]); ?>" class="btn btn-outline-primary">
                        <i class="fas fa-cog"></i> <?php echo __('Configure'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

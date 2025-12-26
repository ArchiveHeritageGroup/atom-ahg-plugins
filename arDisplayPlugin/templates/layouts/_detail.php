<?php
/**
 * Detail layout - standard full record view
 */
$thumbnailSize = $data['thumbnail_size'];
$thumbnailPosition = $data['thumbnail_position'];
$colImage = match($thumbnailSize) {
    'hero', 'full' => 12,
    'large' => 5,
    'medium' => 4,
    'small' => 3,
    default => 0,
};
$colContent = $colImage > 0 && $colImage < 12 ? 12 - $colImage : 12;
?>

<div class="row">
    <?php if ($digitalObject && $thumbnailSize !== 'none'): ?>
    <div class="col-md-<?php echo $colImage; ?> <?php echo $thumbnailPosition === 'right' ? 'order-md-2' : ''; ?> mb-4">
        <div class="digital-object-display thumbnail-<?php echo $thumbnailSize; ?>">
            <a href="<?php echo $digitalObject->path; ?>" data-lightbox="object" data-title="<?php echo $object->title ?? ''; ?>">
                <img src="<?php echo $digitalObject->path; ?>" 
                     class="img-fluid rounded shadow-sm" 
                     alt="<?php echo $object->title ?? ''; ?>">
            </a>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="col-md-<?php echo $colContent; ?>">
        <?php // Identity Section ?>
        <?php if (!empty($fields['identity'])): ?>
        <section class="field-section identity-section mb-4">
            <dl class="row mb-0">
                <?php foreach ($fields['identity'] as $field): ?>
                <dt class="col-sm-3 text-muted"><?php echo $field['label']; ?></dt>
                <dd class="col-sm-9"><?php echo format_field_value($field); ?></dd>
                <?php endforeach; ?>
            </dl>
        </section>
        <?php endif; ?>

        <?php // Description Section ?>
        <?php if (!empty($fields['description'])): ?>
        <section class="field-section description-section mb-4">
            <h5 class="section-title border-bottom pb-2 mb-3">
                <i class="fas fa-align-left text-muted me-2"></i>Description
            </h5>
            <?php foreach ($fields['description'] as $field): ?>
            <div class="field-block mb-3">
                <h6 class="field-label text-muted"><?php echo $field['label']; ?></h6>
                <div class="field-value"><?php echo format_field_value($field); ?></div>
            </div>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>

        <?php // Context Section ?>
        <?php if (!empty($fields['context'])): ?>
        <section class="field-section context-section mb-4">
            <h5 class="section-title border-bottom pb-2 mb-3">
                <i class="fas fa-history text-muted me-2"></i>Context
            </h5>
            <?php foreach ($fields['context'] as $field): ?>
            <div class="field-block mb-3">
                <h6 class="field-label text-muted"><?php echo $field['label']; ?></h6>
                <div class="field-value"><?php echo format_field_value($field); ?></div>
            </div>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>

        <?php // Access Section ?>
        <?php if (!empty($fields['access'])): ?>
        <section class="field-section access-section mb-4">
            <h5 class="section-title border-bottom pb-2 mb-3">
                <i class="fas fa-lock-open text-muted me-2"></i>Access & Use
            </h5>
            <?php foreach ($fields['access'] as $field): ?>
            <div class="field-block mb-3">
                <h6 class="field-label text-muted"><?php echo $field['label']; ?></h6>
                <div class="field-value"><?php echo format_field_value($field); ?></div>
            </div>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>

        <?php // Technical Section (DAM) ?>
        <?php if (!empty($fields['technical'])): ?>
        <section class="field-section technical-section mb-4">
            <h5 class="section-title border-bottom pb-2 mb-3">
                <i class="fas fa-cog text-muted me-2"></i>Technical Details
            </h5>
            <dl class="row mb-0">
                <?php foreach ($fields['technical'] as $field): ?>
                <dt class="col-sm-4 text-muted"><?php echo $field['label']; ?></dt>
                <dd class="col-sm-8"><?php echo format_field_value($field); ?></dd>
                <?php endforeach; ?>
            </dl>
        </section>
        <?php endif; ?>

        <?php // Rights Section ?>
        <?php 
        $rightsSectionPath = __DIR__ . '/_rights_section.php';
        if (file_exists($rightsSectionPath) && !empty($data['rights'])) {
            include $rightsSectionPath;
        }
        ?>
    </div>
</div>

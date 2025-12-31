<?php
/**
 * Condition Check Photos Button Partial
 * 
 * Add this to your condition check display template to show
 * the "Manage Photos" button.
 * 
 * Usage: 
 * <?php include_partial('spectrum/conditionPhotosButton', [
 *     'resource' => $resource, 
 *     'conditionCheck' => $conditionCheck
 * ]); ?>
 */

$photoCount = 0;
$primaryPhoto = null;

// Get photo count and primary photo
try {
    $photos = SpectrumConditionPhoto::getByConditionCheck($conditionCheck['id']);
    $photoCount = count($photos);
    $primaryPhoto = SpectrumConditionPhoto::getPrimaryPhoto($conditionCheck['id']);
} catch (Exception $e) {
    // Table might not exist yet
}
?>

<div class="condition-photos-section">
    <?php if ($primaryPhoto): ?>
        <!-- Primary Photo Thumbnail -->
        <div class="primary-photo-preview mb-2">
            <a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'conditionPhotos', 'slug' => $resource->slug, 'condition_id' => $conditionCheck['id']]); ?>">
                <img src="/uploads/<?php echo $primaryPhoto['file_path']; ?>" 
                     alt="<?php echo htmlspecialchars($primaryPhoto['caption'] ?? 'Primary photo'); ?>"
                     class="img-thumbnail"
                     style="max-height: 150px; max-width: 200px;">
            </a>
        </div>
    <?php endif; ?>
    
    <!-- Manage Photos Button -->
    <a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'conditionPhotos', 'slug' => $resource->slug, 'condition_id' => $conditionCheck['id']]); ?>" 
       class="btn btn-info btn-sm">
        <i class="fas fa-camera"></i>
        <?php echo __('Manage Photos'); ?>
        <?php if ($photoCount > 0): ?>
            <span class="badge badge-light ml-1"><?php echo $photoCount; ?></span>
        <?php endif; ?>
    </a>
    
    <?php if ($photoCount == 0): ?>
        <small class="text-muted d-block mt-1">
            <i class="fas fa-info-circle"></i> <?php echo __('No photos uploaded yet'); ?>
        </small>
    <?php endif; ?>
</div>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.condition-photos-section {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #eee;
}

.primary-photo-preview img {
    border-radius: 4px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.primary-photo-preview img:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
</style>

<?php
/**
 * Masonry layout - Pinterest-style grid for photos
 */
?>
<div class="masonry-item mb-3">
    <div class="card">
        <?php if ($digitalObject): ?>
        <a href="<?php echo $digitalObject->path; ?>" data-lightbox="masonry" data-title="<?php echo $object->title ?? ''; ?>">
            <img src="<?php echo $digitalObject->path; ?>" 
                 class="card-img-top" 
                 alt="<?php echo $object->title ?? ''; ?>"
                 loading="lazy">
        </a>
        <?php endif; ?>
        <div class="card-body p-2">
            <h6 class="card-title mb-1 small"><?php echo $object->title ?? 'Untitled'; ?></h6>
            <div class="btn-group btn-group-sm w-100">
                <?php if (in_array('select', $data['actions'])): ?>
                <button type="button" class="btn btn-outline-success select-toggle" data-id="<?php echo $object->id; ?>">
                    <i class="fas fa-check"></i>
                </button>
                <?php endif; ?>
                <?php if (in_array('compare', $data['actions'])): ?>
                <button type="button" class="btn btn-outline-info compare-toggle" data-id="<?php echo $object->id; ?>">
                    <i class="fas fa-columns"></i>
                </button>
                <?php endif; ?>
                <?php if (in_array('download', $data['actions']) && $digitalObject): ?>
                <a href="<?php echo $digitalObject->path; ?>" class="btn btn-outline-secondary" download>
                    <i class="fas fa-download"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

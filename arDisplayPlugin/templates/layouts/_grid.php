<?php
/**
 * Grid layout - thumbnail grid for photos/DAM
 */
?>
<div class="grid-item">
    <div class="card h-100 shadow-sm">
        <?php if ($digitalObject): ?>
        <div class="card-img-wrapper position-relative overflow-hidden">
            <img src="<?php echo $digitalObject->path; ?>" 
                 class="card-img-top" 
                 style="height: 180px; object-fit: cover;"
                 alt="<?php echo $object->title ?? ''; ?>"
                 loading="lazy">
            <div class="card-img-overlay d-flex flex-column justify-content-end p-0">
                <div class="bg-gradient-dark p-2" style="background: linear-gradient(transparent, rgba(0,0,0,0.8));">
                    <div class="btn-group btn-group-sm">
                        <?php if (in_array('view', $data['actions'])): ?>
                        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $object->slug]); ?>" 
                           class="btn btn-light" title="View"><i class="fas fa-eye"></i></a>
                        <?php endif; ?>
                        <?php if (in_array('zoom', $data['actions'])): ?>
                        <a href="<?php echo $digitalObject->path; ?>" 
                           class="btn btn-light" data-lightbox="grid" title="Zoom"><i class="fas fa-search-plus"></i></a>
                        <?php endif; ?>
                        <?php if (in_array('add_to_lightbox', $data['actions'])): ?>
                        <a href="<?php echo url_for(['module' => 'dam', 'action' => 'addToLightbox', 'digital_object_id' => $digitalObject->id]); ?>" 
                           class="btn btn-light" title="Add to Lightbox"><i class="fas fa-plus"></i></a>
                        <?php endif; ?>
                        <?php if (in_array('select', $data['actions'])): ?>
                        <button type="button" class="btn btn-light select-toggle" data-id="<?php echo $object->id; ?>" title="Select">
                            <i class="fas fa-check"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="card-body p-2">
            <h6 class="card-title mb-1 text-truncate" title="<?php echo $object->title ?? ''; ?>">
                <?php echo $object->title ?? $digitalObject->name ?? 'Untitled'; ?>
            </h6>
            <?php if (!empty($fields['identity']['date'])): ?>
            <small class="text-muted"><?php echo $fields['identity']['date']['value']; ?></small>
            <?php endif; ?>
        </div>
    </div>
</div>

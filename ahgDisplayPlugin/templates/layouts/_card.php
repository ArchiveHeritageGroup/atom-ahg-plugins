<?php
/**
 * Card layout - compact card view for search results
 */
?>
<div class="card h-100 shadow-sm">
    <div class="row g-0">
        <?php if ($digitalObject && $data['thumbnail_size'] !== 'none'): ?>
        <div class="col-4">
            <img src="<?php echo $digitalObject->path; ?>" 
                 class="img-fluid rounded-start h-100" 
                 style="object-fit: cover; min-height: 120px;"
                 alt="<?php echo $object->title ?? ''; ?>">
        </div>
        <?php endif; ?>
        <div class="col-<?php echo $digitalObject && $data['thumbnail_size'] !== 'none' ? '8' : '12'; ?>">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-<?php echo get_type_color($objectType); ?> bg-opacity-75">
                        <i class="fas <?php echo get_type_icon($objectType); ?> me-1"></i>
                        <?php echo $object->level_name ?? ucfirst($objectType); ?>
                    </span>
                    <?php if (!empty($fields['identity']['identifier'])): ?>
                    <small class="text-muted"><?php echo $fields['identity']['identifier']['value']; ?></small>
                    <?php endif; ?>
                </div>
                
                <h6 class="card-title mb-1">
                    <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $object->slug]); ?>" class="text-decoration-none">
                        <?php echo $object->title ?? 'Untitled'; ?>
                    </a>
                </h6>
                
                <?php if (!empty($fields['identity']['creator'])): ?>
                <p class="card-text small text-muted mb-1"><?php echo $fields['identity']['creator']['value']; ?></p>
                <?php endif; ?>
                
                <?php if (!empty($fields['identity']['date'])): ?>
                <p class="card-text small text-muted mb-0"><?php echo $fields['identity']['date']['value']; ?></p>
                <?php endif; ?>
                
                <?php if (!empty($fields['description']['description'])): ?>
                <p class="card-text small mt-2 text-truncate-3">
                    <?php echo substr(strip_tags($fields['description']['description']['value']), 0, 150); ?>...
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

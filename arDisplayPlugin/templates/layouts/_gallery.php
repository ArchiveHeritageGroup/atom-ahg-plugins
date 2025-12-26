<?php
/**
 * Gallery layout - artwork/hero image view
 */
$siblings = $data['siblings'] ?? [];
?>

<div class="gallery-view">
    <div class="row">
        <div class="col-lg-8">
            <?php if ($digitalObject): ?>
            <div class="gallery-image mb-4 text-center bg-light rounded p-3">
                <a href="<?php echo $digitalObject->path; ?>" data-lightbox="gallery" data-title="<?php echo $object->title ?? ''; ?>">
                    <img src="<?php echo $digitalObject->path; ?>" 
                         class="img-fluid" 
                         alt="<?php echo $object->title ?? ''; ?>"
                         style="max-height: 70vh; object-fit: contain;">
                </a>
            </div>
            <?php endif; ?>
            
            <?php // Siblings/related works ?>
            <?php if (!empty($siblings)): ?>
            <div class="gallery-siblings mt-4">
                <h6 class="text-muted mb-3">Related Works</h6>
                <div class="row g-2">
                    <?php foreach ($siblings as $s): ?>
                    <div class="col-3">
                        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $s->slug]); ?>">
                            <?php if ($s->thumbnail_path): ?>
                            <img src="<?php echo $s->thumbnail_path; ?>" class="img-fluid rounded" alt="<?php echo $s->title; ?>">
                            <?php else: ?>
                            <div class="bg-light rounded p-3 text-center"><i class="fas fa-image fa-2x text-muted"></i></div>
                            <?php endif; ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <div class="artwork-details sticky-top" style="top: 20px;">
                <?php // Artist ?>
                <?php if (!empty($fields['identity']['artist'])): ?>
                <h4 class="artist-name mb-1"><?php echo $fields['identity']['artist']['value']; ?></h4>
                <?php elseif (!empty($fields['identity']['creator'])): ?>
                <h4 class="artist-name mb-1"><?php echo $fields['identity']['creator']['value']; ?></h4>
                <?php endif; ?>
                
                <?php // Title & Date ?>
                <h2 class="artwork-title mb-3">
                    <em><?php echo $object->title ?? 'Untitled'; ?></em>
                    <?php if (!empty($fields['identity']['date'])): ?>
                    <span class="text-muted">, <?php echo $fields['identity']['date']['value']; ?></span>
                    <?php endif; ?>
                </h2>
                
                <?php // Physical details ?>
                <table class="table table-sm table-borderless">
                    <?php if (!empty($fields['identity']['medium'])): ?>
                    <tr><th class="text-muted" width="100">Medium</th><td><?php echo $fields['identity']['medium']['value']; ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($fields['identity']['dimensions'])): ?>
                    <tr><th class="text-muted">Dimensions</th><td><?php echo $fields['identity']['dimensions']['value']; ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($fields['identity']['edition_info'])): ?>
                    <tr><th class="text-muted">Edition</th><td><?php echo $fields['identity']['edition_info']['value']; ?></td></tr>
                    <?php endif; ?>
                </table>
                
                <?php // Description ?>
                <?php if (!empty($fields['description'])): ?>
                <div class="mt-4">
                    <?php foreach ($fields['description'] as $field): ?>
                    <div class="mb-3">
                        <h6 class="text-muted"><?php echo $field['label']; ?></h6>
                        <p><?php echo format_field_value($field); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php // Context (provenance, exhibitions) ?>
                <?php if (!empty($fields['context'])): ?>
                <div class="mt-4">
                    <?php foreach ($fields['context'] as $field): ?>
                    <div class="mb-3">
                        <h6 class="text-muted"><?php echo $field['label']; ?></h6>
                        <p class="small"><?php echo format_field_value($field); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

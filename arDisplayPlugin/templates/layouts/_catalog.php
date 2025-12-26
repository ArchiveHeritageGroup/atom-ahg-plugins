<?php
/**
 * Catalog layout - formal catalog entry view
 */
?>
<div class="catalog-entry border-bottom pb-4 mb-4">
    <div class="row">
        <?php if ($digitalObject && $data['thumbnail_size'] !== 'none'): ?>
        <div class="col-md-3 mb-3">
            <img src="<?php echo $digitalObject->path; ?>" 
                 class="img-fluid rounded" 
                 alt="<?php echo $object->title ?? ''; ?>">
        </div>
        <?php endif; ?>
        
        <div class="col-md-<?php echo $digitalObject ? '9' : '12'; ?>">
            <div class="catalog-header mb-3">
                <?php if (!empty($fields['identity']['identifier'])): ?>
                <span class="catalog-number text-muted me-3"><?php echo $fields['identity']['identifier']['value']; ?></span>
                <?php endif; ?>
                
                <?php if (!empty($fields['identity']['artist']) || !empty($fields['identity']['creator'])): ?>
                <span class="catalog-creator">
                    <strong><?php echo $fields['identity']['artist']['value'] ?? $fields['identity']['creator']['value'] ?? ''; ?></strong>
                </span>
                <?php endif; ?>
            </div>
            
            <h4 class="catalog-title mb-2">
                <em><?php echo $object->title ?? 'Untitled'; ?></em>
                <?php if (!empty($fields['identity']['date'])): ?>
                <span class="text-muted">, <?php echo $fields['identity']['date']['value']; ?></span>
                <?php endif; ?>
            </h4>
            
            <?php // Physical description line ?>
            <p class="catalog-physical text-muted mb-2">
                <?php 
                $physParts = [];
                if (!empty($fields['identity']['medium'])) $physParts[] = $fields['identity']['medium']['value'];
                if (!empty($fields['identity']['dimensions'])) $physParts[] = $fields['identity']['dimensions']['value'];
                if (!empty($fields['identity']['materials'])) $physParts[] = $fields['identity']['materials']['value'];
                echo implode('; ', $physParts);
                ?>
            </p>
            
            <?php // Description ?>
            <?php if (!empty($fields['description']['description']) || !empty($fields['description']['scope_content'])): ?>
            <p class="catalog-description">
                <?php echo substr(strip_tags($fields['description']['description']['value'] ?? $fields['description']['scope_content']['value'] ?? ''), 0, 400); ?>...
            </p>
            <?php endif; ?>
            
            <?php // Provenance ?>
            <?php if (!empty($fields['context']['provenance'])): ?>
            <p class="catalog-provenance small text-muted">
                <strong>Provenance:</strong> <?php echo substr(strip_tags($fields['context']['provenance']['value']), 0, 200); ?>...
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>

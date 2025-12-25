<?php
/**
 * List View Partial
 * 
 * @param array $items   Items to display
 * @param string $module Module context
 */

$items = $items ?? [];
$module = $module ?? 'informationobject';
?>

<div class="display-list-view" data-display-container>
    <?php if (empty($items)): ?>
        <p class="text-muted text-center py-4"><?php echo __('No items to display'); ?></p>
    <?php else: ?>
        <?php foreach ($items as $item): ?>
            <div class="result-item browse-item" data-display-mode="list">
                <?php if (!empty($item['thumbnail'])): ?>
                    <img src="<?php echo $item['thumbnail']; ?>" 
                         alt="" 
                         class="item-thumbnail"
                         loading="lazy">
                <?php else: ?>
                    <div class="item-thumbnail d-flex align-items-center justify-content-center bg-light">
                        <i class="bi bi-file-earmark text-muted"></i>
                    </div>
                <?php endif; ?>
                
                <div class="item-content">
                    <h3 class="item-title">
                        <a href="<?php echo url_for(['module' => $module, 'slug' => $item['slug']]); ?>">
                            <?php echo $item['title'] ?? $item['slug']; ?>
                        </a>
                    </h3>
                    
                    <div class="item-meta">
                        <?php if (!empty($item['reference_code'])): ?>
                            <span class="me-3">
                                <i class="bi bi-hash me-1"></i><?php echo $item['reference_code']; ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($item['dates'])): ?>
                            <span class="me-3">
                                <i class="bi bi-calendar me-1"></i><?php echo $item['dates']; ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($item['level_of_description'])): ?>
                            <span>
                                <i class="bi bi-layers me-1"></i><?php echo $item['level_of_description']; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($item['scope_and_content'])): ?>
                        <p class="item-description">
                            <?php echo truncate_text(strip_tags($item['scope_and_content']), 200); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

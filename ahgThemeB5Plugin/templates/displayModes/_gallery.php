<?php
/**
 * Gallery View Partial
 * 
 * @param array $items   Items to display
 * @param string $module Module context
 */

$items = $items ?? [];
$module = $module ?? 'informationobject';
?>

<div class="display-gallery-view row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" data-display-container>
    <?php if (empty($items)): ?>
        <div class="col-12">
            <p class="text-muted text-center py-4"><?php echo __('No items to display'); ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($items as $item): ?>
            <div class="col">
                <div class="result-item browse-item card h-100" data-display-mode="gallery">
                    <?php if (!empty($item['thumbnail'])): ?>
                        <a href="<?php echo url_for(['module' => $module, 'slug' => $item['slug']]); ?>"
                           data-lightbox="gallery"
                           data-title="<?php echo htmlspecialchars($item['title'] ?? ''); ?>">
                            <img src="<?php echo $item['thumbnail_large'] ?? $item['thumbnail']; ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($item['title'] ?? ''); ?>"
                                 loading="lazy">
                        </a>
                    <?php else: ?>
                        <a href="<?php echo url_for(['module' => $module, 'slug' => $item['slug']]); ?>">
                            <div class="card-img-top d-flex align-items-center justify-content-center bg-light">
                                <i class="bi bi-image display-1 text-muted"></i>
                            </div>
                        </a>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="<?php echo url_for(['module' => $module, 'slug' => $item['slug']]); ?>" 
                               class="text-decoration-none">
                                <?php echo $item['title'] ?? $item['slug']; ?>
                            </a>
                        </h5>
                        
                        <?php if (!empty($item['dates'])): ?>
                            <p class="card-text">
                                <small class="text-muted"><?php echo $item['dates']; ?></small>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

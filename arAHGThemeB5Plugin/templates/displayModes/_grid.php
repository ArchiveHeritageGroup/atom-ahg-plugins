<?php
/**
 * Grid View Partial
 * 
 * @param array $items   Items to display
 * @param string $module Module context
 */

$items = $items ?? [];
$module = $module ?? 'informationobject';
?>

<div class="display-grid-view row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3" data-display-container>
    <?php if (empty($items)): ?>
        <div class="col-12">
            <p class="text-muted text-center py-4"><?php echo __('No items to display'); ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($items as $item): ?>
            <div class="col">
                <div class="result-item browse-item card h-100" data-display-mode="grid">
                    <?php if (!empty($item['thumbnail'])): ?>
                        <img src="<?php echo $item['thumbnail']; ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($item['title'] ?? ''); ?>"
                             loading="lazy">
                    <?php else: ?>
                        <div class="card-img-top d-flex align-items-center justify-content-center bg-light">
                            <i class="bi bi-file-earmark display-4 text-muted"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="<?php echo url_for(['module' => $module, 'slug' => $item['slug']]); ?>" 
                               class="stretched-link text-decoration-none">
                                <?php echo $item['title'] ?? $item['slug']; ?>
                            </a>
                        </h5>
                        
                        <p class="card-text">
                            <?php if (!empty($item['reference_code'])): ?>
                                <small><?php echo $item['reference_code']; ?></small><br>
                            <?php endif; ?>
                            <?php if (!empty($item['dates'])): ?>
                                <small><?php echo $item['dates']; ?></small>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <?php if (!empty($item['level_of_description'])): ?>
                        <div class="card-footer bg-transparent border-top-0">
                            <small class="text-muted">
                                <i class="bi bi-layers me-1"></i>
                                <?php echo $item['level_of_description']; ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

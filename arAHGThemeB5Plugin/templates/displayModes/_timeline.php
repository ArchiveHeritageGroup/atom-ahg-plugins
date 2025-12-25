<?php
/**
 * Timeline View Partial
 * 
 * @param array $items   Items to display (should have date fields)
 * @param string $module Module context
 */

$items = $items ?? [];
$module = $module ?? 'informationobject';

// Group items by year if they have dates
$groupedItems = [];
foreach ($items as $item) {
    $year = 'Unknown';
    if (!empty($item['start_date'])) {
        $year = date('Y', strtotime($item['start_date']));
    } elseif (!empty($item['dates'])) {
        // Try to extract year from dates string
        if (preg_match('/\d{4}/', $item['dates'], $matches)) {
            $year = $matches[0];
        }
    }
    $groupedItems[$year][] = $item;
}

// Sort by year descending
krsort($groupedItems);
?>

<div class="display-timeline-view" data-display-container>
    <?php if (empty($items)): ?>
        <p class="text-muted text-center py-4"><?php echo __('No items to display'); ?></p>
    <?php else: ?>
        <?php foreach ($groupedItems as $year => $yearItems): ?>
            <div class="timeline-year mb-4">
                <h4 class="timeline-year-label text-primary mb-3">
                    <i class="bi bi-calendar3 me-2"></i><?php echo $year; ?>
                </h4>
                
                <?php foreach ($yearItems as $item): ?>
                    <div class="result-item browse-item" data-display-mode="timeline">
                        <div class="timeline-marker"></div>
                        
                        <?php if (!empty($item['dates'])): ?>
                            <div class="timeline-date"><?php echo $item['dates']; ?></div>
                        <?php endif; ?>
                        
                        <div class="timeline-content">
                            <h5 class="timeline-title">
                                <a href="<?php echo url_for(['module' => $module, 'slug' => $item['slug']]); ?>">
                                    <?php echo $item['title'] ?? $item['slug']; ?>
                                </a>
                            </h5>
                            
                            <?php if (!empty($item['scope_and_content'])): ?>
                                <p class="timeline-description">
                                    <?php echo truncate_text(strip_tags($item['scope_and_content']), 150); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="timeline-meta text-muted small">
                                <?php if (!empty($item['reference_code'])): ?>
                                    <span class="me-3">
                                        <i class="bi bi-hash"></i> <?php echo $item['reference_code']; ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($item['level_of_description'])): ?>
                                    <span>
                                        <i class="bi bi-layers"></i> <?php echo $item['level_of_description']; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

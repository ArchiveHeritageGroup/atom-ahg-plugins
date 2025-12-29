<?php
/**
 * Tree/Hierarchy View Partial
 * 
 * @param array $items   Items to display (hierarchical structure)
 * @param string $module Module context
 */

$items = $items ?? [];
$module = $module ?? 'informationobject';

/**
 * Recursive function to render tree nodes
 */
function renderTreeNode($node, $module, $level = 0) {
    $hasChildren = !empty($node['children']);
    $childCount = $hasChildren ? count($node['children']) : 0;
    ?>
    <li>
        <div class="tree-item">
            <?php if ($hasChildren): ?>
                <span class="tree-toggle" 
                      role="button" 
                      aria-expanded="false"
                      onclick="this.classList.toggle('expanded'); this.closest('li').querySelector(':scope > ul')?.classList.toggle('d-none');">
                    <i class="bi bi-chevron-right"></i>
                </span>
            <?php else: ?>
                <span class="tree-toggle" style="visibility: hidden;">
                    <i class="bi bi-chevron-right"></i>
                </span>
            <?php endif; ?>
            
            <span class="tree-icon">
                <?php
                $icon = 'bi-folder';
                if (isset($node['level_of_description'])) {
                    $levelIcons = [
                        'Fonds' => 'bi-archive',
                        'Collection' => 'bi-collection',
                        'Series' => 'bi-folder2',
                        'Sub-series' => 'bi-folder2-open',
                        'File' => 'bi-file-earmark',
                        'Item' => 'bi-file-earmark-text',
                    ];
                    $icon = $levelIcons[$node['level_of_description']] ?? 'bi-folder';
                }
                ?>
                <i class="bi <?php echo $icon; ?>"></i>
            </span>
            
            <span class="tree-label">
                <a href="<?php echo url_for(['module' => $module, 'slug' => $node['slug']]); ?>">
                    <?php echo $node['title'] ?? $node['slug']; ?>
                </a>
            </span>
            
            <?php if ($childCount > 0): ?>
                <span class="tree-count"><?php echo $childCount; ?></span>
            <?php endif; ?>
        </div>
        
        <?php if ($hasChildren): ?>
            <ul class="d-none">
                <?php foreach ($node['children'] as $child): ?>
                    <?php renderTreeNode($child, $module, $level + 1); ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </li>
    <?php
}
?>

<div class="display-tree-view" data-display-container>
    <?php if (empty($items)): ?>
        <p class="text-muted text-center py-4"><?php echo __('No items to display'); ?></p>
    <?php else: ?>
        <ul>
            <?php foreach ($items as $item): ?>
                <?php renderTreeNode($item, $module); ?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

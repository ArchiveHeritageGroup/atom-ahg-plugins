<?php
/**
 * Featured Items Block Template
 * Reuses the existing _featuredCollection partial
 */

// Map block config to partial variables
$collectionId = $config['collection_id'] ?? null;
$maxItems = $config['limit'] ?? 12;
$height = $config['height'] ?? '450px';
$autoplay = $config['auto_rotate'] ?? true;
$interval = $config['interval'] ?? 5000;
$showTitle = true;
$showCaptions = $config['show_captions'] ?? true;
$showViewAll = $config['show_view_all'] ?? false;
$customTitle = $config['title'] ?? null;
$customSubtitle = $config['subtitle'] ?? null;

if (!$collectionId) {
    echo '<p class="text-muted">No collection configured. Edit this block to select an IIIF collection.</p>';
    return;
}

// Include the existing partial - it will use the variables we defined above
include sfConfig::get('sf_plugins_dir') . '/ahgThemeB5Plugin/modules/staticpage/templates/_featuredCollection.php';

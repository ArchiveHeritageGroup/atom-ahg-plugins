<?php
/**
 * Image Carousel Block Template
 * Uses the same partial as featured_items
 */
$collectionSlug = $config['collection_id'] ?? null;
$maxItems = (int)($config['limit'] ?? 12) ?: 12;
$height = $config['height'] ?? '450px';
$autoplay = $config['auto_play'] ?? true;
$interval = (int)($config['interval'] ?? 5000) ?: 5000;
$showTitle = !empty($config['title']);
$showCaptions = $config['show_captions'] ?? true;
$showViewAll = $config['show_view_all'] ?? false;
$customTitle = $config['title'] ?? null;
$customSubtitle = $config['subtitle'] ?? null;

if (!$collectionSlug) {
    echo '<p class="text-muted">No collection configured. Edit this block to select an IIIF collection.</p>';
    return;
}

// The partial expects collectionSlug variable
include sfConfig::get('sf_plugins_dir') . '/ahgThemeB5Plugin/modules/staticpage/templates/_featuredCollection.php';

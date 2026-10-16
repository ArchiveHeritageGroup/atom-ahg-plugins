<?php
/**
 * Image Carousel Block Template
 * Uses the same partial as featured_items
 */
$collectionSlug = $config['collection_id'] ?? null;
$maxItems = (int)($config['limit'] ?? 12) ?: 12;
$height = $config['height'] ?? '450px';
$autoplay = $config['autoplay'] ?? $config['auto_play'] ?? true;
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

// Resolve the slug to an id here: the IIIF partial uses $collectionId first and
// otherwise falls back to the homepage collection setting, so passing only a
// slug showed the homepage collection instead of this block's one.
$collectionId = \Illuminate\Database\Capsule\Manager::table('iiif_collection')->where('slug', $collectionSlug)->value('id');
if (!$collectionId) {
    echo '<p class="text-muted">The IIIF collection "' . esc_entities($collectionSlug) . '" does not exist. Edit this block to choose another.</p>';
    return;
}

// The partial expects collectionSlug variable
// The carousel partial lives in ahgIiifPlugin (it moved out of the theme).
$partial = sfConfig::get('sf_plugins_dir') . '/ahgIiifPlugin/modules/iiif/templates/_featuredCollection.php';
if (!file_exists($partial)) {
    echo '<p class="text-muted">This block needs the IIIF plugin (ahgIiifPlugin) to be enabled.</p>';
    return;
}
include $partial;

<?php
/**
 * Featured Items Block Template
 * Reuses the existing _featuredCollection partial
 */

// Map block config to partial variables
$collectionId = $config['collection_id'] ?? null;
if ($collectionId && !ctype_digit((string) $collectionId)) {
    // A slug was entered: look up its id, so the partial cannot fall back to the homepage collection.
    $collectionId = \Illuminate\Database\Capsule\Manager::table('iiif_collection')->where('slug', $collectionId)->value('id');
}
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
// The carousel partial lives in ahgIiifPlugin (it moved out of the theme).
$partial = sfConfig::get('sf_plugins_dir') . '/ahgIiifPlugin/modules/iiif/templates/_featuredCollection.php';
if (!file_exists($partial)) {
    echo '<p class="text-muted">This block needs the IIIF plugin (ahgIiifPlugin) to be enabled.</p>';
    return;
}
include $partial;

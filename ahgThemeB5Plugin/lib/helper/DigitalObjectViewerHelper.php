<?php

/**
 * DigitalObjectViewerHelper - Backward compatibility wrapper
 *
 * Delegates to ahgIiifPlugin helper when available, falls back to framework.
 * This ensures the theme works regardless of which plugins are enabled.
 */

// Try to use ahgIiifPlugin helper first (plugin autonomy principle)
$pluginHelper = sfConfig::get('sf_plugins_dir') . '/ahgIiifPlugin/lib/helper/IiifViewerHelper.php';
if (file_exists($pluginHelper)) {
    require_once $pluginHelper;
    define('IIIF_HELPER_SOURCE', 'plugin');
} else {
    // Fallback to framework helper
    $frameworkHelper = sfConfig::get('sf_root_dir') . '/atom-framework/src/Helpers/DigitalObjectViewerHelper.php';
    if (file_exists($frameworkHelper)) {
        require_once $frameworkHelper;
        define('IIIF_HELPER_SOURCE', 'framework');
    } else {
        define('IIIF_HELPER_SOURCE', 'none');
    }
}

/**
 * Get preferred IIIF viewer type
 */
function get_preferred_iiif_viewer()
{
    if (IIIF_HELPER_SOURCE === 'plugin' && function_exists('is_iiif_available')) {
        // Plugin uses settings table
        return sfConfig::get('app_iiif_default_viewer', 'openseadragon');
    }

    if (IIIF_HELPER_SOURCE === 'framework' && class_exists('AtomFramework\Helpers\DigitalObjectViewerHelper')) {
        return \AtomFramework\Helpers\DigitalObjectViewerHelper::getPreferredIiifViewer();
    }

    return 'openseadragon';
}

/**
 * Render viewer toggle buttons
 */
function render_viewer_toggle($objId, $currentViewer = 'openseadragon')
{
    if (IIIF_HELPER_SOURCE === 'framework' && class_exists('AtomFramework\Helpers\DigitalObjectViewerHelper')) {
        return \AtomFramework\Helpers\DigitalObjectViewerHelper::renderViewerToggle((int) $objId, $currentViewer);
    }

    // Simple fallback toggle
    return '<div class="viewer-toggle btn-group btn-group-sm mb-2">'
        . '<button type="button" class="btn btn-outline-primary active" data-viewer="openseadragon">'
        . '<i class="fas fa-image"></i></button>'
        . '</div>';
}

/**
 * Render Mirador viewer
 */
function render_mirador_viewer($iiifIdentifier, $objId, $root, $request)
{
    if (IIIF_HELPER_SOURCE === 'framework' && class_exists('AtomFramework\Helpers\DigitalObjectViewerHelper')) {
        return \AtomFramework\Helpers\DigitalObjectViewerHelper::renderMiradorViewer($iiifIdentifier, (int) $objId, $root, $request);
    }

    return '<!-- Mirador viewer not available -->';
}

/**
 * Render OpenSeadragon viewer
 */
function render_openseadragon_viewer($iiifIdentifier, $objId, $cantaloupeUrl)
{
    if (IIIF_HELPER_SOURCE === 'framework' && class_exists('AtomFramework\Helpers\DigitalObjectViewerHelper')) {
        return \AtomFramework\Helpers\DigitalObjectViewerHelper::renderOpenSeadragonViewer($iiifIdentifier, (int) $objId, $cantaloupeUrl);
    }

    return '<!-- OpenSeadragon viewer not available -->';
}

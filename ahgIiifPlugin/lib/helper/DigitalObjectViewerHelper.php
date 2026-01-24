<?php

/**
 * DigitalObjectViewerHelper - Plugin-native implementation
 *
 * Part of ahgIiifPlugin - provides backward-compatible helper functions
 * that delegate to the plugin's IiifViewerHelper.php
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */

// Ensure the main helper is loaded
require_once dirname(__FILE__) . '/IiifViewerHelper.php';

/**
 * Get preferred IIIF viewer type from settings
 */
function get_preferred_iiif_viewer()
{
    // Check iiif_viewer_settings table
    try {
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/vendor/autoload.php';

        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $viewer = $db::table('iiif_viewer_settings')
            ->where('setting_key', 'viewer_type')
            ->value('setting_value');

        return $viewer ?: sfConfig::get('app_iiif_default_viewer', 'openseadragon');
    } catch (Exception $e) {
        return sfConfig::get('app_iiif_default_viewer', 'openseadragon');
    }
}

/**
 * Render viewer toggle buttons
 */
function render_viewer_toggle($objId, $currentViewer = 'openseadragon')
{
    $viewerId = 'viewer-toggle-' . $objId;

    $html = '<div class="viewer-toggle btn-group btn-group-sm mb-2" id="' . $viewerId . '">';

    $activeOsd = ($currentViewer === 'openseadragon') ? ' active' : '';
    $activeMirador = ($currentViewer === 'mirador') ? ' active' : '';

    $html .= '<button type="button" class="btn btn-outline-primary' . $activeOsd . '" data-viewer="openseadragon" title="Image Viewer">';
    $html .= '<i class="fas fa-image"></i></button>';

    $html .= '<button type="button" class="btn btn-outline-primary' . $activeMirador . '" data-viewer="mirador" title="Mirador Viewer">';
    $html .= '<i class="fas fa-columns"></i></button>';

    $html .= '</div>';

    return $html;
}

/**
 * Render Mirador viewer for an object
 */
function render_mirador_viewer($iiifIdentifier, $objId, $root, $request)
{
    $baseUrl = sfConfig::get('app_iiif_base_url', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $slug = is_object($root) ? ($root->slug ?? $objId) : $objId;
    $manifestUrl = $baseUrl . '/iiif/manifest/' . $slug;
    $height = sfConfig::get('app_iiif_viewer_height', '600px');

    $viewerId = 'mirador-' . $objId;

    $html = '<div id="' . $viewerId . '-wrapper" class="mirador-wrapper" style="position:relative;">';
    $html .= '<div id="' . $viewerId . '" style="width:100%;height:' . $height . ';"></div>';
    $html .= '</div>';

    // JavaScript initialization
    $n = sfConfig::get('csp_nonce', '');
    $nonceAttr = $n ? preg_replace('/^nonce=/', ' nonce="', $n) . '"' : '';

    $html .= '<script' . $nonceAttr . '>';
    $html .= 'document.addEventListener("DOMContentLoaded", function() {';
    $html .= '  if (typeof Mirador !== "undefined") {';
    $html .= '    Mirador.viewer({';
    $html .= '      id: "' . $viewerId . '",';
    $html .= '      windows: [{ manifestId: "' . $manifestUrl . '" }]';
    $html .= '    });';
    $html .= '  }';
    $html .= '});';
    $html .= '</script>';

    return $html;
}

/**
 * Render OpenSeadragon viewer for an object
 */
function render_openseadragon_viewer($iiifIdentifier, $objId, $cantaloupeUrl)
{
    $height = sfConfig::get('app_iiif_viewer_height', '600px');
    $viewerId = 'osd-' . $objId;
    $infoUrl = rtrim($cantaloupeUrl, '/') . '/' . urlencode($iiifIdentifier) . '/info.json';

    $html = '<div id="' . $viewerId . '" class="osd-viewer" style="width:100%;height:' . $height . ';background:#1a1a1a;border-radius:8px;"></div>';

    // JavaScript initialization
    $n = sfConfig::get('csp_nonce', '');
    $nonceAttr = $n ? preg_replace('/^nonce=/', ' nonce="', $n) . '"' : '';

    $html .= '<script' . $nonceAttr . '>';
    $html .= 'document.addEventListener("DOMContentLoaded", function() {';
    $html .= '  if (typeof OpenSeadragon !== "undefined") {';
    $html .= '    OpenSeadragon({';
    $html .= '      id: "' . $viewerId . '",';
    $html .= '      tileSources: "' . $infoUrl . '",';
    $html .= '      showNavigator: true,';
    $html .= '      navigatorPosition: "BOTTOM_RIGHT",';
    $html .= '      showRotationControl: true,';
    $html .= '      showFlipControl: true';
    $html .= '    });';
    $html .= '  }';
    $html .= '});';
    $html .= '</script>';

    return $html;
}

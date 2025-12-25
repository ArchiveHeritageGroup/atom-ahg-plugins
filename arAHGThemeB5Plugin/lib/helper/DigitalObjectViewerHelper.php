<?php

/**
 * DigitalObjectViewerHelper - Wrapper for backward compatibility
 * Delegates to AtomFramework\Helpers\DigitalObjectViewerHelper
 */

require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Helpers/DigitalObjectViewerHelper.php';

use AtomFramework\Helpers\DigitalObjectViewerHelper as FrameworkHelper;

function get_preferred_iiif_viewer()
{
    return FrameworkHelper::getPreferredIiifViewer();
}

function render_viewer_toggle($objId, $currentViewer = 'openseadragon')
{
    return FrameworkHelper::renderViewerToggle((int)$objId, $currentViewer);
}

function render_mirador_viewer($iiifIdentifier, $objId, $root, $request)
{
    return FrameworkHelper::renderMiradorViewer($iiifIdentifier, (int)$objId, $root, $request);
}

function render_openseadragon_viewer($iiifIdentifier, $objId, $cantaloupeUrl)
{
    return FrameworkHelper::renderOpenSeadragonViewer($iiifIdentifier, (int)$objId, $cantaloupeUrl);
}

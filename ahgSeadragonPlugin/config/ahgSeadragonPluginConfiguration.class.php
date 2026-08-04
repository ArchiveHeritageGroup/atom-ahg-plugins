<?php

/**
 * ahgSeadragonPlugin Configuration
 *
 * Contributes an OpenSeadragon deep-zoom renderer to ahgIiifPlugin's
 * RendererRegistry. The plugin ships no modules and no routes - it exists purely
 * to register a viewer, so installing or disabling it is how an institution
 * chooses its image viewer.
 */
class ahgSeadragonPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'OpenSeadragon deep-zoom image viewer for IIIF';

    public static $version = '1.0.0';

    public function initialize()
    {
        // Nothing to wire: RendererRegistry auto-discovers lib/Renderers/*.php
        // across enabled plugins.
    }
}

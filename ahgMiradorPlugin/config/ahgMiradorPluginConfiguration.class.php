<?php

/**
 * ahgMiradorPlugin Configuration
 *
 * Contributes a Mirador renderer to ahgIiifPlugin's RendererRegistry. Ships no
 * modules and no routes - installing or disabling it is how an institution
 * chooses its image viewer.
 */
class ahgMiradorPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Mirador IIIF viewer with multi-manifest comparison';

    public static $version = '1.0.0';

    public function initialize()
    {
        // Nothing to wire: RendererRegistry auto-discovers lib/Renderers/*.php
        // across enabled plugins.
    }
}

<?php

/**
 * ahgUiOverridesPlugin configuration.
 *
 * This plugin contains centralized UI action overrides for AtoM modules.
 * All module action customizations should be placed here, not in the theme plugin.
 */
class ahgUiOverridesPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'UI action overrides for AtoM modules';
    public static $version = '1.0.0';

    public function initialize()
    {
        // Plugin initializes automatically via Symfony module discovery
    }
}

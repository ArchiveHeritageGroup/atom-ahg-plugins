<?php

/**
 * ahgAnnotationsPlugin configuration (#146).
 *
 * Registers the annotation module: W3C Web Annotation Protocol backend.
 */
class ahgAnnotationsPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'W3C Web Annotation Data Model + Protocol backend';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'annotation';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}

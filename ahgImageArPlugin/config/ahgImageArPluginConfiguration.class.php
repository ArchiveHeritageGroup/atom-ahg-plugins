<?php

/**
 * ahgImageArPlugin configuration (#147).
 */
class ahgImageArPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Place a 2D image into augmented reality (WebXR)';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'imageAr';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}

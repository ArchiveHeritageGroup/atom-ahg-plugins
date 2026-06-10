<?php

/**
 * ahgFunctionsDocsPlugin configuration (#148).
 */
class ahgFunctionsDocsPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Browsable catalogue of routes, CLI tasks and services';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'functionsDocs';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}

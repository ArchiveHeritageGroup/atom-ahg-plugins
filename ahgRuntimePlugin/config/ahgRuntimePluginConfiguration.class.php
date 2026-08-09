<?php

/**
 * AHG runtime - the shared classes and services every AHG plugin builds on.
 *
 * GENERATED from atom-framework by bin/build-runtime-plugin. Do not edit here; edit
 * the framework and regenerate, or the two copies drift.
 *
 * Installing this is what makes every other AHG plugin work: 112 of 115 of them use
 * something from it, and 99 extend AhgController directly.
 */
class ahgRuntimePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'AHG runtime - shared services and base classes required by AHG plugins';
    public static $version = '2.14.1';

    public function initialize()
    {
        $bootstrap = __DIR__.'/../bootstrap.php';

        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
    }
}

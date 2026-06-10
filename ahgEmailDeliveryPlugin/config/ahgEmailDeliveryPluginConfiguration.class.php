<?php

/**
 * ahgEmailDeliveryPlugin configuration (#145).
 *
 * Registers the emailDelivery module: bounce webhook + suppression admin.
 */
class ahgEmailDeliveryPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Email bounce capture + suppression list + send-time gate';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'emailDelivery';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}

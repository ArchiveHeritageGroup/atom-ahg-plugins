<?php

class ahgRadManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'RAD information object CRUD management';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'radManage';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}

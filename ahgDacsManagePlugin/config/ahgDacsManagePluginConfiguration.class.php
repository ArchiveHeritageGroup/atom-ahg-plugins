<?php

class ahgDacsManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'DACS information object CRUD management';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'dacsManage';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}

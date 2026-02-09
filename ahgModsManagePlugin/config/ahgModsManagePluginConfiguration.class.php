<?php

class ahgModsManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'MODS information object CRUD management';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'modsManage';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}

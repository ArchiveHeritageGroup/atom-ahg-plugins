<?php

class ahgDcManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Dublin Core information object CRUD management';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'dcManage';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}

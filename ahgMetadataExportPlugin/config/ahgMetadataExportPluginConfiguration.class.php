<?php

class ahgMetadataExportPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'GLAM Metadata Export: Unified export framework supporting 10 metadata standards across GLAM sectors';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'metadataExport';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}

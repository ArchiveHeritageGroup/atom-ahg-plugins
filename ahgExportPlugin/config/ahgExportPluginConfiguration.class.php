<?php

class ahgExportPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Archival export functionality for AtoM';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'export';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
    }

    public function loadRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        $routing->prependRoute('export_index', new sfRoute('/export', [
            'module' => 'export',
            'action' => 'index',
        ]));

        $routing->prependRoute('export_archival', new sfRoute('/export/archival', [
            'module' => 'export',
            'action' => 'archival',
        ]));

        $routing->prependRoute('export_authority', new sfRoute('/export/authority', [
            'module' => 'export',
            'action' => 'authority',
        ]));

        $routing->prependRoute('export_repository', new sfRoute('/export/repository', [
            'module' => 'export',
            'action' => 'repository',
        ]));

        $routing->prependRoute('export_csv', new sfRoute('/export/csv', [
            'module' => 'export',
            'action' => 'archival',
        ]));

        $routing->prependRoute('export_ead', new sfRoute('/export/ead', [
            'module' => 'export',
            'action' => 'archival',
        ]));

        $routing->prependRoute('export_grap', new sfRoute('/export/grap', [
            'module' => 'export',
            'action' => 'archival',
        ]));

        $routing->prependRoute('export_authorities', new sfRoute('/export/authorities', [
            'module' => 'export',
            'action' => 'authority',
        ]));

        // Legacy route for object/export
        $routing->prependRoute('object_export', new sfRoute('/object/export', [
            'module' => 'export',
            'action' => 'index',
        ]));
    }
}

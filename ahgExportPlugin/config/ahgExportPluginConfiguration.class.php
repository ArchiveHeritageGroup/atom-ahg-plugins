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

        // Custom fields in the EAD download (#202): base sfEadPlugin renders the
        // XML; this adds each record's custom fields to it on the way out.
        $this->dispatcher->connect('response.filter_content', [$this, 'addCustomFieldsToEad']);
    }

    public function addCustomFieldsToEad(sfEvent $event, $content)
    {
        try {
            $context = sfContext::getInstance();
            if (!is_string($content) || 'sfEadPlugin' !== $context->getModuleName() || false === strpos($content, '<ead')
                || !class_exists('\\AtomFramework\\FindingAid\\CustomFieldEad')) {
                return $content;
            }
            $action = $context->getActionStack()->getLastEntry()->getActionInstance();
            if (!isset($action->resource) || !$action->resource instanceof QubitInformationObject) {
                return $content;
            }

            return \AtomFramework\FindingAid\CustomFieldEad::augment(
                $content,
                $action->resource,
                ['current-level-only' => false],
                !$context->getUser()->isAuthenticated()
            );
        } catch (\Throwable $e) {
            error_log('export.ead_custom_fields_failed: '.$e->getMessage());

            return $content;
        }
    }

    public function loadRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('export');

        $router->any('export_index', '/export', 'index');
        $router->any('export_archival', '/export/archival', 'archival');
        $router->any('export_authority', '/export/authority', 'authority');
        $router->any('export_repository', '/export/repository', 'repository');
        $router->any('export_csv', '/export/csv', 'archival');
        $router->any('export_ead', '/export/ead', 'archival');
        $router->any('export_grap', '/export/grap', 'archival');
        $router->any('export_authorities', '/export/authorities', 'authority');

        // Accession CSV export
        $router->any('export_accession_csv', '/export/accession-csv', 'accessionCsv');

        // Legacy route for object/export
        $router->any('object_export', '/object/export', 'index');

        $router->register($event->getSubject());
    }
}

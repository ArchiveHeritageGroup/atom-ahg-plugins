<?php

/**
 * ahgDoiPlugin Configuration
 *
 * DOI minting and management via DataCite for persistent identifiers.
 */
class ahgDoiPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'DOI Integration: Mint and manage DOIs via DataCite for finding aids and digital collections';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        $context->response->addStylesheet('/plugins/ahgDoiPlugin/web/css/doi.css', 'last');
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        // Hook into record publish to auto-mint DOI if configured
        $this->dispatcher->connect('QubitInformationObject.postSave', [$this, 'onRecordSave']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'doi';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function addRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('doi');

        // Dashboard
        $router->any('ahg_doi_index', '/admin/doi', 'index');

        // Configuration
        $router->any('ahg_doi_config', '/admin/doi/config', 'config');
        $router->any('ahg_doi_config_save', '/admin/doi/config/save', 'configSave');
        $router->any('ahg_doi_config_test', '/admin/doi/config/test', 'configTest');

        // DOI Management
        $router->any('ahg_doi_browse', '/admin/doi/browse', 'browse');
        $router->any('ahg_doi_view', '/admin/doi/view/:id', 'view', ['id' => '\d+']);
        $router->any('ahg_doi_mint', '/admin/doi/mint/:id', 'mint', ['id' => '\d+']);
        $router->any('ahg_doi_batch_mint', '/admin/doi/batch-mint', 'batchMint');
        $router->any('ahg_doi_update', '/admin/doi/update/:id', 'update', ['id' => '\d+']);

        // Queue management
        $router->any('ahg_doi_queue', '/admin/doi/queue', 'queue');
        $router->any('ahg_doi_queue_retry', '/admin/doi/queue/:id/retry', 'queueRetry', ['id' => '\d+']);

        // Mapping configuration
        $router->any('ahg_doi_mapping', '/admin/doi/mapping', 'mapping');

        // Reports
        $router->any('ahg_doi_report', '/admin/doi/report', 'report');

        // Export
        $router->any('ahg_doi_export', '/admin/doi/export', 'export');

        // Bulk sync
        $router->any('ahg_doi_sync', '/admin/doi/sync', 'sync');

        // Deactivate/Reactivate
        $router->any('ahg_doi_deactivate', '/admin/doi/deactivate/:id', 'deactivate', ['id' => '\d+']);
        $router->any('ahg_doi_reactivate', '/admin/doi/reactivate/:id', 'reactivate', ['id' => '\d+']);

        // Verify resolution
        $router->any('ahg_doi_verify', '/admin/doi/verify/:id', 'verify', ['id' => '\d+']);

        // API routes
        $router->any('ahg_doi_api_mint', '/api/doi/mint/:id', 'apiMint', ['id' => '\d+']);
        $router->any('ahg_doi_api_status', '/api/doi/status/:id', 'apiStatus', ['id' => '\d+']);

        // Public DOI landing page (redirect to record)
        $router->any('ahg_doi_resolve', '/doi/:doi', 'resolve', ['doi' => '10\..+']);

        $router->register($event->getSubject());
    }

    /**
     * Hook: Auto-mint DOI when record is published (if configured)
     */
    public function onRecordSave(sfEvent $event)
    {
        $record = $event->getSubject();

        // Only process InformationObject
        if (!$record instanceof QubitInformationObject) {
            return;
        }

        // Check if auto-mint is enabled for this repository
        try {
            require_once dirname(__FILE__) . '/../lib/Services/DoiService.php';
            $service = new \ahgDoiPlugin\Services\DoiService();

            if ($service->shouldAutoMint($record)) {
                // Queue for minting (don't block the save)
                $service->queueForMinting($record->id, 'mint');
            }
        } catch (\Exception $e) {
            error_log('DOI auto-mint check failed: ' . $e->getMessage());
        }
    }
}

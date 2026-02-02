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
        $routing = $event->getSubject();

        // Dashboard
        $routing->prependRoute('ahg_doi_index', new sfRoute(
            '/admin/doi',
            ['module' => 'doi', 'action' => 'index']
        ));

        // Configuration
        $routing->prependRoute('ahg_doi_config', new sfRoute(
            '/admin/doi/config',
            ['module' => 'doi', 'action' => 'config']
        ));

        $routing->prependRoute('ahg_doi_config_save', new sfRoute(
            '/admin/doi/config/save',
            ['module' => 'doi', 'action' => 'configSave']
        ));

        $routing->prependRoute('ahg_doi_config_test', new sfRoute(
            '/admin/doi/config/test',
            ['module' => 'doi', 'action' => 'configTest']
        ));

        // DOI Management
        $routing->prependRoute('ahg_doi_browse', new sfRoute(
            '/admin/doi/browse',
            ['module' => 'doi', 'action' => 'browse']
        ));

        $routing->prependRoute('ahg_doi_view', new sfRoute(
            '/admin/doi/view/:id',
            ['module' => 'doi', 'action' => 'view'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('ahg_doi_mint', new sfRoute(
            '/admin/doi/mint/:id',
            ['module' => 'doi', 'action' => 'mint'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('ahg_doi_batch_mint', new sfRoute(
            '/admin/doi/batch-mint',
            ['module' => 'doi', 'action' => 'batchMint']
        ));

        $routing->prependRoute('ahg_doi_update', new sfRoute(
            '/admin/doi/update/:id',
            ['module' => 'doi', 'action' => 'update'],
            ['id' => '\d+']
        ));

        // Queue management
        $routing->prependRoute('ahg_doi_queue', new sfRoute(
            '/admin/doi/queue',
            ['module' => 'doi', 'action' => 'queue']
        ));

        $routing->prependRoute('ahg_doi_queue_retry', new sfRoute(
            '/admin/doi/queue/:id/retry',
            ['module' => 'doi', 'action' => 'queueRetry'],
            ['id' => '\d+']
        ));

        // Mapping configuration
        $routing->prependRoute('ahg_doi_mapping', new sfRoute(
            '/admin/doi/mapping',
            ['module' => 'doi', 'action' => 'mapping']
        ));

        // Reports
        $routing->prependRoute('ahg_doi_report', new sfRoute(
            '/admin/doi/report',
            ['module' => 'doi', 'action' => 'report']
        ));

        // Export
        $routing->prependRoute('ahg_doi_export', new sfRoute(
            '/admin/doi/export',
            ['module' => 'doi', 'action' => 'export']
        ));

        // Bulk sync
        $routing->prependRoute('ahg_doi_sync', new sfRoute(
            '/admin/doi/sync',
            ['module' => 'doi', 'action' => 'sync']
        ));

        // Deactivate/Reactivate
        $routing->prependRoute('ahg_doi_deactivate', new sfRoute(
            '/admin/doi/deactivate/:id',
            ['module' => 'doi', 'action' => 'deactivate'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('ahg_doi_reactivate', new sfRoute(
            '/admin/doi/reactivate/:id',
            ['module' => 'doi', 'action' => 'reactivate'],
            ['id' => '\d+']
        ));

        // Verify resolution
        $routing->prependRoute('ahg_doi_verify', new sfRoute(
            '/admin/doi/verify/:id',
            ['module' => 'doi', 'action' => 'verify'],
            ['id' => '\d+']
        ));

        // API routes
        $routing->prependRoute('ahg_doi_api_mint', new sfRoute(
            '/api/doi/mint/:id',
            ['module' => 'doi', 'action' => 'apiMint'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('ahg_doi_api_status', new sfRoute(
            '/api/doi/status/:id',
            ['module' => 'doi', 'action' => 'apiStatus'],
            ['id' => '\d+']
        ));

        // Public DOI landing page (redirect to record)
        $routing->prependRoute('ahg_doi_resolve', new sfRoute(
            '/doi/:doi',
            ['module' => 'doi', 'action' => 'resolve'],
            ['doi' => '10\..+']
        ));
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

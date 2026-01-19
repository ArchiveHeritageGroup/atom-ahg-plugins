<?php

class ahgPreservationPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Digital preservation: checksums, fixity verification, PREMIS events, format registry';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        $context->getConfiguration()->loadHelpers(['Asset', 'Url']);
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        // Hook into digital object save to generate checksums
        $this->dispatcher->connect('QubitDigitalObject.postSave', [$this, 'onDigitalObjectSave']);

        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'preservation';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // API routes
        $routing->prependRoute('preservation_api_generate_checksum', new sfRoute(
            '/api/preservation/checksum/:id/generate',
            ['module' => 'preservation', 'action' => 'apiGenerateChecksum'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('preservation_api_verify_fixity', new sfRoute(
            '/api/preservation/fixity/:id/verify',
            ['module' => 'preservation', 'action' => 'apiVerifyFixity'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('preservation_api_stats', new sfRoute(
            '/api/preservation/stats',
            ['module' => 'preservation', 'action' => 'apiStats']
        ));

        // Dashboard and management routes
        $routing->prependRoute('preservation_object', new sfRoute(
            '/admin/preservation/object/:id',
            ['module' => 'preservation', 'action' => 'object'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('preservation_fixity_log', new sfRoute(
            '/admin/preservation/fixity-log',
            ['module' => 'preservation', 'action' => 'fixityLog']
        ));
        $routing->prependRoute('preservation_events', new sfRoute(
            '/admin/preservation/events',
            ['module' => 'preservation', 'action' => 'events']
        ));
        $routing->prependRoute('preservation_formats', new sfRoute(
            '/admin/preservation/formats',
            ['module' => 'preservation', 'action' => 'formats']
        ));
        $routing->prependRoute('preservation_policies', new sfRoute(
            '/admin/preservation/policies',
            ['module' => 'preservation', 'action' => 'policies']
        ));
        $routing->prependRoute('preservation_reports', new sfRoute(
            '/admin/preservation/reports',
            ['module' => 'preservation', 'action' => 'reports']
        ));
        $routing->prependRoute('preservation_index', new sfRoute(
            '/admin/preservation',
            ['module' => 'preservation', 'action' => 'index']
        ));
    }

    /**
     * Hook: Generate checksums when a digital object is saved
     */
    public function onDigitalObjectSave(sfEvent $event)
    {
        $digitalObject = $event->getSubject();

        // Only process if we have a file path
        if (!$digitalObject->getPath()) {
            return;
        }

        // Queue checksum generation (don't block the save)
        // In production, this could use a job queue
        try {
            require_once dirname(__FILE__) . '/../lib/PreservationService.php';
            $service = new PreservationService();
            $service->generateChecksums($digitalObject->id, ['sha256']);
            $service->identifyFormat($digitalObject->id);
            $service->logEvent(
                $digitalObject->id,
                null,
                'ingestion',
                'Digital object ingested into repository',
                'success'
            );
        } catch (Exception $e) {
            // Log error but don't fail the save
            error_log('Preservation checksum generation failed: ' . $e->getMessage());
        }
    }
}

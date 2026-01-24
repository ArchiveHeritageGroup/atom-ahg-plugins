<?php

class ahgPreservationPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Digital preservation: checksums, fixity verification, PREMIS events, format registry, TIFF/PDF merge';
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
        $enabledModules[] = 'tiffpdfmerge';
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

        // TIFF to PDF Merge routes
        $routing->prependRoute('tiffpdfmerge', new sfRoute(
            '/tiff-pdf-merge',
            ['module' => 'tiffpdfmerge', 'action' => 'index']
        ));
        $routing->prependRoute('tiffpdfmerge_with_object', new sfRoute(
            '/tiff-pdf-merge/:informationObject',
            ['module' => 'tiffpdfmerge', 'action' => 'index']
        ));
        $routing->prependRoute('tiffpdfmerge_create', new sfRoute(
            '/tiff-pdf-merge/create',
            ['module' => 'tiffpdfmerge', 'action' => 'create']
        ));
        $routing->prependRoute('tiffpdfmerge_upload', new sfRoute(
            '/tiff-pdf-merge/upload',
            ['module' => 'tiffpdfmerge', 'action' => 'upload']
        ));
        $routing->prependRoute('tiffpdfmerge_reorder', new sfRoute(
            '/tiff-pdf-merge/reorder',
            ['module' => 'tiffpdfmerge', 'action' => 'reorder']
        ));
        $routing->prependRoute('tiffpdfmerge_remove_file', new sfRoute(
            '/tiff-pdf-merge/remove-file',
            ['module' => 'tiffpdfmerge', 'action' => 'removeFile']
        ));
        $routing->prependRoute('tiffpdfmerge_get_job', new sfRoute(
            '/tiff-pdf-merge/job/:job_id',
            ['module' => 'tiffpdfmerge', 'action' => 'getJob']
        ));
        $routing->prependRoute('tiffpdfmerge_process', new sfRoute(
            '/tiff-pdf-merge/process',
            ['module' => 'tiffpdfmerge', 'action' => 'process']
        ));
        $routing->prependRoute('tiffpdfmerge_download', new sfRoute(
            '/tiff-pdf-merge/download/:job_id',
            ['module' => 'tiffpdfmerge', 'action' => 'download']
        ));
        $routing->prependRoute('tiffpdfmerge_delete', new sfRoute(
            '/tiff-pdf-merge/delete',
            ['module' => 'tiffpdfmerge', 'action' => 'delete']
        ));
        $routing->prependRoute('tiffpdfmerge_browse', new sfRoute(
            '/tiff-pdf-merge/jobs',
            ['module' => 'tiffpdfmerge', 'action' => 'browse']
        ));
        $routing->prependRoute('tiffpdfmerge_view', new sfRoute(
            '/tiff-pdf-merge/job/:job_id/view',
            ['module' => 'tiffpdfmerge', 'action' => 'view'],
            ['job_id' => '\d+']
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

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

        // Preservation module routes
        $preservation = new \AtomFramework\Routing\RouteLoader('preservation');

        // API routes
        $preservation->any('preservation_api_generate_checksum', '/api/preservation/checksum/:id/generate', 'apiGenerateChecksum', ['id' => '\d+']);
        $preservation->any('preservation_api_verify_fixity', '/api/preservation/fixity/:id/verify', 'apiVerifyFixity', ['id' => '\d+']);
        $preservation->any('preservation_api_stats', '/api/preservation/stats', 'apiStats');

        // Dashboard and management routes
        $preservation->any('preservation_object', '/admin/preservation/object/:id', 'object', ['id' => '\d+']);
        $preservation->any('preservation_fixity_log', '/admin/preservation/fixity-log', 'fixityLog');
        $preservation->any('preservation_events', '/admin/preservation/events', 'events');
        $preservation->any('preservation_formats', '/admin/preservation/formats', 'formats');
        $preservation->any('preservation_policies', '/admin/preservation/policies', 'policies');
        $preservation->any('preservation_reports', '/admin/preservation/reports', 'reports');
        $preservation->any('preservation_index', '/admin/preservation', 'index');
        $preservation->register($routing);

        // TIFF to PDF Merge module routes
        $tiffpdf = new \AtomFramework\Routing\RouteLoader('tiffpdfmerge');
        $tiffpdf->any('tiffpdfmerge', '/tiff-pdf-merge', 'index');
        $tiffpdf->any('tiffpdfmerge_with_object', '/tiff-pdf-merge/:informationObject', 'index');
        $tiffpdf->any('tiffpdfmerge_create', '/tiff-pdf-merge/create', 'create');
        $tiffpdf->any('tiffpdfmerge_upload', '/tiff-pdf-merge/upload', 'upload');
        $tiffpdf->any('tiffpdfmerge_reorder', '/tiff-pdf-merge/reorder', 'reorder');
        $tiffpdf->any('tiffpdfmerge_remove_file', '/tiff-pdf-merge/remove-file', 'removeFile');
        $tiffpdf->any('tiffpdfmerge_get_job', '/tiff-pdf-merge/job/:job_id', 'getJob');
        $tiffpdf->any('tiffpdfmerge_process', '/tiff-pdf-merge/process', 'process');
        $tiffpdf->any('tiffpdfmerge_download', '/tiff-pdf-merge/download/:job_id', 'download');
        $tiffpdf->any('tiffpdfmerge_delete', '/tiff-pdf-merge/delete', 'delete');
        $tiffpdf->any('tiffpdfmerge_browse', '/tiff-pdf-merge/jobs', 'browse');
        $tiffpdf->any('tiffpdfmerge_view', '/tiff-pdf-merge/job/:job_id/view', 'view', ['job_id' => '\d+']);
        $tiffpdf->register($routing);
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

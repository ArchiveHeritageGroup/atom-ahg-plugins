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

        // OAIS Package routes
        $preservation->any('preservation_packages', '/admin/preservation/packages', 'packages');
        $preservation->any('preservation_package_edit', '/admin/preservation/package/edit', 'packageEdit');
        $preservation->any('preservation_package_view', '/admin/preservation/package/:id', 'packageView', ['id' => '\d+']);
        $preservation->any('preservation_package_download', '/admin/preservation/package/:id/download', 'packageDownload', ['id' => '\d+']);

        // Package API routes
        $preservation->any('preservation_api_package_add_object', '/api/preservation/package/add-object', 'apiPackageAddObject');
        $preservation->any('preservation_api_package_remove_object', '/api/preservation/package/remove-object', 'apiPackageRemoveObject');
        $preservation->any('preservation_api_package_build', '/api/preservation/package/build', 'apiPackageBuild');
        $preservation->any('preservation_api_package_validate', '/api/preservation/package/validate', 'apiPackageValidate');
        $preservation->any('preservation_api_package_export', '/api/preservation/package/export', 'apiPackageExport');
        $preservation->any('preservation_api_package_convert', '/api/preservation/package/convert', 'apiPackageConvert');
        $preservation->any('preservation_api_package_delete', '/api/preservation/package/delete', 'apiPackageDelete');

        // Workflow scheduler routes
        $preservation->any('preservation_scheduler', '/admin/preservation/scheduler', 'scheduler');
        $preservation->any('preservation_schedule_edit', '/admin/preservation/schedule/edit', 'scheduleEdit');
        $preservation->any('preservation_schedule_run_view', '/admin/preservation/schedule/run/:id', 'scheduleRunView', ['id' => '\d+']);
        $preservation->any('preservation_api_schedule_toggle', '/api/preservation/schedule/toggle', 'apiScheduleToggle');
        $preservation->any('preservation_api_schedule_run', '/api/preservation/schedule/run', 'apiScheduleRun');
        $preservation->any('preservation_api_schedule_delete', '/api/preservation/schedule/delete', 'apiScheduleDelete');

        // Format identification & conversion routes
        $preservation->any('preservation_identification', '/admin/preservation/identification', 'identification');
        $preservation->any('preservation_api_identify', '/api/preservation/identify', 'apiIdentify');
        $preservation->any('preservation_conversion', '/admin/preservation/conversion', 'conversion');
        $preservation->any('preservation_api_convert', '/api/preservation/convert', 'apiConvert');

        // Virus scan routes
        $preservation->any('preservation_virus_scan', '/admin/preservation/virus-scan', 'virusScan');
        $preservation->any('preservation_api_virus_scan', '/api/preservation/virus-scan', 'apiVirusScan');

        // Backup verification routes
        $preservation->any('preservation_backup', '/admin/preservation/backup', 'backup');
        $preservation->any('preservation_api_verify_backup', '/api/preservation/backup/verify', 'apiVerifyBackup');

        // Extended dashboard
        $preservation->any('preservation_extended', '/admin/preservation/extended', 'extended');

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

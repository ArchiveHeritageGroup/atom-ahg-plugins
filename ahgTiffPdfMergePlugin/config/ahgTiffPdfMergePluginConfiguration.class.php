<?php

class ahgTiffPdfMergePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'TIFF and PDF merge job management';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'tiffpdfmerge';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        // Register templates directory for include_partial() calls
        $decoratorDirs = sfConfig::get('sf_decorator_dirs', []);
        $decoratorDirs[] = $this->rootDir . '/templates';
        sfConfig::set('sf_decorator_dirs', $decoratorDirs);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
    }

    public function loadRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('tiffpdfmerge');

        // Index/main page
        $router->any('tiffpdfmerge', '/tiff-pdf-merge', 'index');

        // With information object context
        $router->any('tiffpdfmerge_with_object', '/tiff-pdf-merge/:informationObject', 'index');

        // Create
        $router->any('tiffpdfmerge_create', '/tiff-pdf-merge/create', 'create');

        // Upload
        $router->any('tiffpdfmerge_upload', '/tiff-pdf-merge/upload', 'upload');

        // Reorder
        $router->any('tiffpdfmerge_reorder', '/tiff-pdf-merge/reorder', 'reorder');

        // Remove file
        $router->any('tiffpdfmerge_remove_file', '/tiff-pdf-merge/remove-file', 'removeFile');

        // Get job
        $router->any('tiffpdfmerge_get_job', '/tiff-pdf-merge/job/:job_id', 'getJob');

        // Process
        $router->any('tiffpdfmerge_process', '/tiff-pdf-merge/process', 'process');

        // Download
        $router->any('tiffpdfmerge_download', '/tiff-pdf-merge/download/:job_id', 'download');

        // Delete
        $router->any('tiffpdfmerge_delete', '/tiff-pdf-merge/delete', 'delete');

        // Browse jobs (admin)
        $router->any('tiffpdfmerge_browse', '/tiff-pdf-merge/jobs', 'browse');

        // View single job
        $router->any('tiffpdfmerge_view', '/tiff-pdf-merge/job/:job_id/view', 'view', ['job_id' => '\d+']);

        $router->register($event->getSubject());
    }
}

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
        $routing = $event->getSubject();

        // Index/main page
        $routing->prependRoute('tiffpdfmerge', new sfRoute(
            '/tiff-pdf-merge',
            ['module' => 'tiffpdfmerge', 'action' => 'index']
        ));

        // With information object context
        $routing->prependRoute('tiffpdfmerge_with_object', new sfRoute(
            '/tiff-pdf-merge/:informationObject',
            ['module' => 'tiffpdfmerge', 'action' => 'index']
        ));

        // Create
        $routing->prependRoute('tiffpdfmerge_create', new sfRoute(
            '/tiff-pdf-merge/create',
            ['module' => 'tiffpdfmerge', 'action' => 'create']
        ));

        // Upload
        $routing->prependRoute('tiffpdfmerge_upload', new sfRoute(
            '/tiff-pdf-merge/upload',
            ['module' => 'tiffpdfmerge', 'action' => 'upload']
        ));

        // Reorder
        $routing->prependRoute('tiffpdfmerge_reorder', new sfRoute(
            '/tiff-pdf-merge/reorder',
            ['module' => 'tiffpdfmerge', 'action' => 'reorder']
        ));

        // Remove file
        $routing->prependRoute('tiffpdfmerge_remove_file', new sfRoute(
            '/tiff-pdf-merge/remove-file',
            ['module' => 'tiffpdfmerge', 'action' => 'removeFile']
        ));

        // Get job
        $routing->prependRoute('tiffpdfmerge_get_job', new sfRoute(
            '/tiff-pdf-merge/job/:job_id',
            ['module' => 'tiffpdfmerge', 'action' => 'getJob']
        ));

        // Process
        $routing->prependRoute('tiffpdfmerge_process', new sfRoute(
            '/tiff-pdf-merge/process',
            ['module' => 'tiffpdfmerge', 'action' => 'process']
        ));

        // Download
        $routing->prependRoute('tiffpdfmerge_download', new sfRoute(
            '/tiff-pdf-merge/download/:job_id',
            ['module' => 'tiffpdfmerge', 'action' => 'download']
        ));

        // Delete
        $routing->prependRoute('tiffpdfmerge_delete', new sfRoute(
            '/tiff-pdf-merge/delete',
            ['module' => 'tiffpdfmerge', 'action' => 'delete']
        ));

        // Browse jobs (admin)
        $routing->prependRoute('tiffpdfmerge_browse', new sfRoute(
            '/tiff-pdf-merge/jobs',
            ['module' => 'tiffpdfmerge', 'action' => 'browse']
        ));

        // View single job
        $routing->prependRoute('tiffpdfmerge_view', new sfRoute(
            '/tiff-pdf-merge/job/:job_id/view',
            ['module' => 'tiffpdfmerge', 'action' => 'view'],
            ['job_id' => '\d+']
        ));
    }
}

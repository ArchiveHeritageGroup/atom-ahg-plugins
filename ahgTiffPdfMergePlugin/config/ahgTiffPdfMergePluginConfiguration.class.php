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

        $routing->prependRoute('tiffpdfmerge_browse', new sfRoute('/tiff-pdf-merge/jobs', [
            'module' => 'tiffpdfmerge',
            'action' => 'browse',
        ]));

        $routing->prependRoute('tiffpdfmerge_view', new sfRoute('/tiff-pdf-merge/job/:job_id', [
            'module' => 'tiffpdfmerge',
            'action' => 'view',
        ], [
            'job_id' => '\d+',
        ]));
    }
}

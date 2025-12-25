<?php

class arSecurityClearanceWatermarkSettingsAction extends sfAction
{
    public function execute($request)
    {
        // Check admin permission
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir').'/atom-framework/src/Services/WatermarkSettingsService.php';

        if ($request->isMethod('post')) {
            // Save settings
            \AtomExtensions\Services\WatermarkSettingsService::setSetting(
                'default_watermark_enabled',
                $request->getParameter('default_watermark_enabled', '0')
            );
            \AtomExtensions\Services\WatermarkSettingsService::setSetting(
                'default_watermark_type',
                $request->getParameter('default_watermark_type', 'COPYRIGHT')
            );
            \AtomExtensions\Services\WatermarkSettingsService::setSetting(
                'apply_watermark_on_view',
                $request->getParameter('apply_watermark_on_view', '0')
            );
            \AtomExtensions\Services\WatermarkSettingsService::setSetting(
                'apply_watermark_on_download',
                $request->getParameter('apply_watermark_on_download', '0')
            );
            \AtomExtensions\Services\WatermarkSettingsService::setSetting(
                'security_watermark_override',
                $request->getParameter('security_watermark_override', '0')
            );
            \AtomExtensions\Services\WatermarkSettingsService::setSetting(
                'watermark_min_size',
                $request->getParameter('watermark_min_size', '200')
            );

            // Update Cantaloupe cache
            \AtomExtensions\Services\WatermarkSettingsService::updateCantaloupeCache();

            $this->getUser()->setFlash('notice', 'Watermark settings saved successfully.');

            $this->redirect(['module' => 'arSecurityClearance', 'action' => 'watermarkSettings']);
        }

        // Load current settings
        $this->defaultEnabled = \AtomExtensions\Services\WatermarkSettingsService::getSetting('default_watermark_enabled', '1');
        $this->defaultType = \AtomExtensions\Services\WatermarkSettingsService::getSetting('default_watermark_type', 'COPYRIGHT');
        $this->applyOnView = \AtomExtensions\Services\WatermarkSettingsService::getSetting('apply_watermark_on_view', '1');
        $this->applyOnDownload = \AtomExtensions\Services\WatermarkSettingsService::getSetting('apply_watermark_on_download', '1');
        $this->securityOverride = \AtomExtensions\Services\WatermarkSettingsService::getSetting('security_watermark_override', '1');
        $this->minSize = \AtomExtensions\Services\WatermarkSettingsService::getSetting('watermark_min_size', '200');

        $this->watermarkTypes = \AtomExtensions\Services\WatermarkSettingsService::getWatermarkTypes();
    }
}

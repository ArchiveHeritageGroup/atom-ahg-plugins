<?php

class extendedRightsIndexAction extends sfAction
{
    public function execute($request)
    {
        $culture = $this->context->user->getCulture();

        // Load service
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgExtendedRightsPlugin/lib/Services/ExtendedRightsService.php';

        $service = new \ahgExtendedRightsPlugin\Services\ExtendedRightsService($culture);

        $this->rightsStatements = $service->getRightsStatements();
        $this->ccLicenses = $service->getCreativeCommonsLicenses();
        $this->tkLabels = $service->getTkLabels();
        $this->stats = $service->getDashboardStats();
    }
}

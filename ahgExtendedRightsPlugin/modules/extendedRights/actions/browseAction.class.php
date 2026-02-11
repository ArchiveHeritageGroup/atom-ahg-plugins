<?php

use AtomFramework\Http\Controllers\AhgController;
class extendedRightsBrowseAction extends AhgController
{
    public function execute($request)
    {
        $culture = $this->culture();

        // Load service
        require_once $this->config('sf_root_dir') . '/atom-ahg-plugins/ahgExtendedRightsPlugin/lib/Services/ExtendedRightsService.php';

        $service = new \ahgExtendedRightsPlugin\Services\ExtendedRightsService($culture);

        $this->rightsStatements = $service->getRightsStatements();
        $this->ccLicenses = $service->getCreativeCommonsLicenses();
        $this->tkLabels = $service->getTkLabels();
        $this->stats = $service->getDashboardStats();
    }
}

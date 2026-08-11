<?php

use AtomFramework\Http\Controllers\AhgController;
class extendedRightsIndexAction extends AhgController
{
    public function execute($request)
    {
        $culture = $this->culture();

        // Load service
        require_once dirname(__FILE__) . '/../../../lib/Services/ExtendedRightsService.php';

        $service = new \ahgExtendedRightsPlugin\Services\ExtendedRightsService($culture);

        $this->rightsStatements = $service->getRightsStatements();
        $this->ccLicenses = $service->getCreativeCommonsLicenses();
        $this->tkLabels = $service->getTkLabels();
        $this->stats = $service->getDashboardStats();
    }
}

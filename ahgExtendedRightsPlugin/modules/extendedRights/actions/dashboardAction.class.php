<?php

use AtomFramework\Http\Controllers\AhgController;
class extendedRightsDashboardAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $culture = $this->culture();

        // Load service
        require_once dirname(__FILE__) . '/../../../lib/Services/ExtendedRightsService.php';

        $service = new \ahgExtendedRightsPlugin\Services\ExtendedRightsService($culture);

        $this->stats = $service->getDashboardStats();
        $this->embargoes = $service->getActiveEmbargoes();
        $this->rightsStatements = $service->getRightsStatements();
        $this->ccLicenses = $service->getCreativeCommonsLicenses();
        $this->tkLabels = $service->getTkLabels();
    }
}

<?php

class extendedRightsDashboardAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $culture = $this->context->user->getCulture();

        // Load service
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgExtendedRightsPlugin/lib/Services/ExtendedRightsService.php';

        $service = new \ahgExtendedRightsPlugin\Services\ExtendedRightsService($culture);

        $this->stats = $service->getRightsStatistics();
        $this->embargoes = $service->getActiveEmbargoes();
        $this->rightsStatements = $service->getRightsStatements();
        $this->ccLicenses = $service->getCreativeCommonsLicenses();
        $this->tkLabels = $service->getTkLabels();
    }
}

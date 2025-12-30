<?php

class extendedRightsIndexAction extends sfAction
{
    public function execute($request)
    {
        $culture = $this->context->user->getCulture();

        // Load service
        require_once sfConfig::get('sf_root_dir').'/atom-framework/app/Services/Rights/ExtendedRightsService.php';

        $service = new \App\Services\Rights\ExtendedRightsService($culture);

        $this->rightsStatements = $service->getRightsStatements();
        $this->ccLicenses = $service->getCreativeCommonsLicenses();
        $this->tkLabels = $service->getTkLabels();
        $this->stats = $service->getRightsStatistics();
    }
}

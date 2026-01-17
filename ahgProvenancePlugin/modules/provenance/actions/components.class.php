<?php

/**
 * Provenance module components.
 */
class provenanceComponents extends sfComponents
{
    /**
     * Display provenance summary for an information object.
     * Used as a component in ISAD, Museum, Library, DAM views.
     */
    public function executeProvenanceDisplay(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgProvenancePlugin/lib/Service/ProvenanceService.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgProvenancePlugin/lib/Repository/ProvenanceRepository.php';

        $this->objectId = $request->getParameter('objectId');
        
        if (!$this->objectId) {
            $this->provenance = ['exists' => false];
            return sfView::SUCCESS;
        }

        $service = new \AhgProvenancePlugin\Service\ProvenanceService();
        $culture = $this->context->user->getCulture();
        
        $this->provenance = $service->getProvenanceForObject($this->objectId, $culture);
    }
}

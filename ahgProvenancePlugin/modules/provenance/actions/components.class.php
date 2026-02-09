<?php

/**
 * Provenance module components.
 */
class provenanceComponents extends AhgComponents
{
    /**
     * Display provenance summary for an information object.
     * Used as a component in ISAD, Museum, Library, DAM views.
     */
    public function executeProvenanceDisplay(sfWebRequest $request)
    {
        $this->objectId = $request->getParameter('objectId');

        if (!$this->objectId) {
            $this->provenance = ['exists' => false];

            return sfView::SUCCESS;
        }

        $service = new \AhgProvenancePlugin\Service\ProvenanceService();

        $this->provenance = $service->getProvenanceForObject($this->objectId, $this->culture());
    }
}

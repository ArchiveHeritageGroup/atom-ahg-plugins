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
        // objectId normally arrives as a component variable via
        // include_component('provenance', 'provenanceDisplay', ['objectId' => ...]);
        // fall back to a request parameter when invoked as a standalone action.
        $objectId = $this->getVarHolder()->get('objectId') ?: $request->getParameter('objectId');
        $this->objectId = $objectId;

        if (!$objectId) {
            $this->provenance = ['exists' => false];

            return sfView::SUCCESS;
        }

        $service = new \AhgProvenancePlugin\Service\ProvenanceService();

        $this->provenance = $service->getProvenanceForObject((int) $objectId, $this->culture());
    }
}

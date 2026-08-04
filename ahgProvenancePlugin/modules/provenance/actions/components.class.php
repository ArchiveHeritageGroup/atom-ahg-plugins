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

        if (!$this->isVisible($this->provenance, (int) $objectId)) {
            $this->provenance = ['exists' => false];
        }
    }

    /**
     * Enforce the record's is_public flag on display.
     *
     * The flag was written by the edit form and reported by the export, but no read
     * path ever checked it, so a record marked non-public still published its donor
     * and prior-owner names to anonymous visitors. Every caller of this component
     * (theme templates and ProvenanceInjector alike) goes through here, so the check
     * belongs here rather than in each caller.
     *
     * Non-public provenance stays visible to users who may edit the description -
     * the people doing the research.
     */
    private function isVisible(array $provenance, int $objectId): bool
    {
        if (empty($provenance['exists'])) {
            return true;
        }

        $record = $provenance['record'] ?? null;

        // Legacy museum rows carry no is_public column; default to visible so the
        // read-bridge keeps behaving as it did.
        if (null === $record || ($record->is_public ?? 1)) {
            return true;
        }

        $resource = \QubitInformationObject::getById($objectId);

        return $resource && \QubitAcl::check($resource, 'update');
    }
}

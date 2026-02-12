<?php

/*
 * AHG Display Plugin - Digital Object Show Download Component
 *
 * Migrates base AtoM DigitalObjectShowDownloadComponent.
 * Displays a download representation of a digital object.
 */

class showDownloadComponent extends AhgComponents
{
    public function execute($request)
    {
        switch ($this->usageType) {
            case QubitTerm::REFERENCE_ID:
                $this->representation = $this->resource->getRepresentationByUsage(QubitTerm::REFERENCE_ID);

                break;

            case QubitTerm::THUMBNAIL_ID:
                $this->representation = $this->resource->getRepresentationByUsage(QubitTerm::THUMBNAIL_ID);

                break;

            case QubitTerm::MASTER_ID:
            default:
                $this->representation = QubitDigitalObject::getGenericRepresentation($this->resource->mimeType, $this->usageType);
        }

        // If no representation found, then default to generic rep
        if (!$this->representation) {
            $this->representation = QubitDigitalObject::getGenericRepresentation($this->resource->mimeType, $this->usageType);
        }

        // Build a fully qualified URL to this digital object asset
        if (
            (
                QubitTerm::IMAGE_ID != $this->resource->mediaTypeId
                || QubitTerm::REFERENCE_ID == $this->usageType
            )
            && QubitTerm::OFFLINE_ID != $this->resource->usageId
            && \AtomExtensions\Services\AclService::check($this->resource->object, 'readMaster')
        ) {
            $this->link = $this->resource->getPublicPath();
        }
    }
}

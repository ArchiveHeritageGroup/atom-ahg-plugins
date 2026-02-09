<?php

/*
 * AHG Display Plugin - Digital Object Show Image Component
 *
 * Migrates base AtoM DigitalObjectShowImageComponent.
 * Displays an image representation of a digital object.
 */

class showImageComponent extends sfComponent
{
    public function execute($request)
    {
        // Get representation by usage type
        $this->representation = $this->resource->getRepresentationByUsage($this->usageType);

        // If we can't find a representation for this object, try their parent
        if (!$this->representation && ($parent = $this->resource->parent)) {
            $this->representation = $parent->getRepresentationByUsage($this->usageType);
        }

        // If representation is not a valid digital object, return a generic icon
        if (!$this->representation) {
            $this->representation = QubitDigitalObject::getGenericRepresentation($this->resource->mimeType, $this->usageType);
        }
    }
}

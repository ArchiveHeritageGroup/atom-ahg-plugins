<?php

/*
 * AHG Display Plugin - Digital Object Show Generic Icon Component
 *
 * Migrates base AtoM DigitalObjectShowGenericIconComponent.
 * Displays a generic representation of a digital object.
 */

class showGenericIconComponent extends sfComponent
{
    public function execute($request)
    {
        $this->representation = QubitDigitalObject::getGenericRepresentation(
            $this->resource->mimeType,
            $this->resource->usageId
        );

        $this->canReadMaster = QubitAcl::check(
            $this->resource->object,
            'readMaster'
        );
    }
}

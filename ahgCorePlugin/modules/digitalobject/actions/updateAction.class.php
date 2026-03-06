<?php

/**
 * AHG stub for digitalobject/update action.
 * Replaces apps/qubit/modules/digitalobject/actions/updateAction.class.php.
 *
 * Updates digital object metadata (usageId, mediaTypeId).
 */
class DigitalObjectUpdateAction extends sfAction
{
    public function execute($request)
    {
        $this->resource = $this->getRoute()->resource;

        // Check user authorization
        if (!QubitAcl::check($this->resource->object, 'update')) {
            QubitAcl::forwardUnauthorized();
        }

        // Check if uploads are allowed
        if (!QubitDigitalObject::isUploadAllowed()) {
            QubitAcl::forwardToSecureAction();
        }

        // Set the digital object's attributes
        $this->resource->usageId = $request->usage_id;
        $this->resource->mediaTypeId = $request->media_type_id;

        // Save the digital object
        $this->resource->save();

        // Return to edit page
        $this->redirect('digitalobject/edit?id='.$this->resource->id);
    }
}

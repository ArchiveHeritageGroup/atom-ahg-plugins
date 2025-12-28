<?php

/**
 * Extended Information Object Edit Action.
 *
 * This extends the base edit action to handle security classification
 * when saving archival descriptions.
 *
 * Option 1: Override in your plugin
 * Option 2: Use event listener approach (cleaner)
 */

/**
 * Event listener for saving security classification.
 *
 * Add this to your plugin's configuration to handle security saving via events.
 */
class SecurityClassificationSaveListener
{
    /**
     * Handle post-save event for information objects.
     */
    public static function onInformationObjectSave(sfEvent $event)
    {
        $resource = $event->getSubject();
        $request = sfContext::getInstance()->getRequest();

        // Check if security fields were submitted
        $classificationId = $request->getParameter('security_classification_id');

        if (null === $classificationId) {
            return; // Security fieldset not present in form
        }

        // Load security service
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $currentUserId = sfContext::getInstance()->user->getUserId();

        if (!empty($classificationId) && $classificationId > 0) {
            // Apply classification
            SecurityClearanceService::classifyObject(
                $resource->id,
                (int) $classificationId,
                $currentUserId,
                $request->getParameter('security_reason'),
                $request->getParameter('security_review_date') ?: null,
                $request->getParameter('security_declassify_date') ?: null,
                null,
                $request->getParameter('security_handling_instructions'),
                (bool) $request->getParameter('security_inherit_to_children', true)
            );
        } else {
            // Remove classification if previously classified
            $existing = SecurityClearanceService::getObjectClassification($resource->id);
            if ($existing) {
                SecurityClearanceService::declassifyObject(
                    $resource->id,
                    $currentUserId,
                    'Classification removed via edit form'
                );
            }
        }
    }
}

/**
 * Alternative: Extended Edit Action Class.
 *
 * If you prefer to override the action directly, use this approach.
 * Place in: apps/qubit/modules/informationobject/actions/editAction.class.php
 */
class SecurityAwareEditAction extends InformationObjectEditAction
{
    /**
     * Process form submission.
     */
    public function processForm()
    {
        // Call parent to save main form data
        parent::processForm();

        // Handle security classification
        $this->saveSecurityClassification();
    }

    /**
     * Save security classification data.
     */
    protected function saveSecurityClassification()
    {
        $classificationId = $this->request->getParameter('security_classification_id');

        if (null === $classificationId) {
            return; // Security fieldset not present
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $currentUserId = $this->context->user->getUserId();

        if (!empty($classificationId) && $classificationId > 0) {
            SecurityClearanceService::classifyObject(
                $this->resource->id,
                (int) $classificationId,
                $currentUserId,
                $this->request->getParameter('security_reason'),
                $this->request->getParameter('security_review_date') ?: null,
                $this->request->getParameter('security_declassify_date') ?: null,
                null,
                $this->request->getParameter('security_handling_instructions'),
                (bool) $this->request->getParameter('security_inherit_to_children', true)
            );
        } else {
            $existing = SecurityClearanceService::getObjectClassification($this->resource->id);
            if ($existing) {
                SecurityClearanceService::declassifyObject(
                    $this->resource->id,
                    $currentUserId,
                    'Classification removed via edit form'
                );
            }
        }
    }
}

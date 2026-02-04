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
            // Build classification data array
            $data = [
                'reason' => $request->getParameter('security_reason'),
                'review_date' => $request->getParameter('security_review_date') ?: null,
                'declassify_date' => $request->getParameter('security_declassify_date') ?: null,
                'handling_instructions' => $request->getParameter('security_handling_instructions'),
                'inherit_to_children' => (bool) $request->getParameter('security_inherit_to_children', true),
            ];

            // Apply classification (with escalation constraint validation)
            $result = SecurityClearanceService::classifyObject(
                $resource->id,
                (int) $classificationId,
                $data,
                $currentUserId
            );

            // Handle validation failure (e.g., escalation constraint violated)
            if (!$result['success']) {
                sfContext::getInstance()->getUser()->setFlash('error', $result['error']);
            }
        } else {
            // Remove classification if previously classified
            $existing = SecurityClearanceService::getObjectClassification($resource->id);
            if ($existing) {
                // Check escalation constraint for declassification too
                $parentClass = SecurityClearanceService::getParentEffectiveClassification($resource->id);
                if ($parentClass) {
                    // Cannot remove classification if parent has one (would be a downgrade to "Public")
                    sfContext::getInstance()->getUser()->setFlash(
                        'error',
                        sprintf(
                            'Cannot remove classification. Parent record has classification "%s". Child records must maintain at least the parent\'s classification level.',
                            $parentClass->name
                        )
                    );
                    return;
                }

                SecurityClearanceService::declassifyObject(
                    $resource->id,
                    null,
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
            // Build classification data array
            $data = [
                'reason' => $this->request->getParameter('security_reason'),
                'review_date' => $this->request->getParameter('security_review_date') ?: null,
                'declassify_date' => $this->request->getParameter('security_declassify_date') ?: null,
                'handling_instructions' => $this->request->getParameter('security_handling_instructions'),
                'inherit_to_children' => (bool) $this->request->getParameter('security_inherit_to_children', true),
            ];

            // Apply classification (with escalation constraint validation)
            $result = SecurityClearanceService::classifyObject(
                $this->resource->id,
                (int) $classificationId,
                $data,
                $currentUserId
            );

            // Handle validation failure (e.g., escalation constraint violated)
            if (!$result['success']) {
                $this->context->user->setFlash('error', $result['error']);
            }
        } else {
            $existing = SecurityClearanceService::getObjectClassification($this->resource->id);
            if ($existing) {
                // Check escalation constraint for declassification too
                $parentClass = SecurityClearanceService::getParentEffectiveClassification($this->resource->id);
                if ($parentClass) {
                    // Cannot remove classification if parent has one
                    $this->context->user->setFlash(
                        'error',
                        sprintf(
                            'Cannot remove classification. Parent record has classification "%s". Child records must maintain at least the parent\'s classification level.',
                            $parentClass->name
                        )
                    );
                    return;
                }

                SecurityClearanceService::declassifyObject(
                    $this->resource->id,
                    null,
                    $currentUserId,
                    'Classification removed via edit form'
                );
            }
        }
    }
}

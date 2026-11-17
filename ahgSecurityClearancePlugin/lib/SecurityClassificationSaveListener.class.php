<?php

/**
 * Saves the Security Classification and Watermark Settings fieldsets when a
 * description is saved through base AtoM's edit form.
 *
 * This plugin puts both fieldsets on base AtoM's ISAD form, but nothing saved
 * them: on a site whose record "Edit" button opens that form (any AtoM without
 * a base routing patch), a classification chosen there was silently discarded
 * and the record stayed public. The AHG edit form (ioManage) saves them itself,
 * through ahgCorePlugin's IoFormHelper, so it is left alone here.
 *
 * Stock AtoM fires no save event, so this hooks the redirect that follows a
 * successful save: sfWebController::redirect() sends the response, and with it
 * 'response.filter_content', after the action has saved and before it stops.
 * A failed validation re-renders the form instead of redirecting, so nothing
 * is saved then. Base AtoM is not touched.
 *
 * The write itself waits for shutdown: QubitTransactionFilter commits the
 * description only after the redirect has been sent, and writing before that
 * (on the separate Laravel connection) waits on its locks until it times out.
 * The parent-level rule is checked during the redirect, while a refusal can
 * still be shown to the user as a flash message.
 */
class SecurityClassificationSaveListener
{
    /** 'response.filter_content' listener; returns the content unchanged. */
    public static function onResponse(sfEvent $event, $content)
    {
        try {
            $context = sfContext::getInstance();
            $request = $context->getRequest();
            $response = $event->getSubject();

            if (!$request->isMethod('post')
                || !$request->hasParameter('security_classification_id')
                || $response->getStatusCode() < 300 || $response->getStatusCode() >= 400) {
                return $content;
            }

            $entry = $context->getActionStack()->getLastEntry();
            $action = $entry ? $entry->getActionInstance() : null;
            // Base AtoM's description edit and add (every standard's edit action extends it).
            // Subclasses that save the fieldset themselves (the museum CCO form) are left alone.
            if (!$action instanceof InformationObjectEditAction || empty($action->resource->id)
                || method_exists($action, 'saveSecurityClassification')) {
                return $content;
            }

            $objectId = (int) $action->resource->id;
            $refusal = self::refusal($request, $objectId);
            if (null !== $refusal) {
                $context->getUser()->setFlash('error', $refusal);

                return $content;
            }

            $userId = (int) $context->getUser()->getUserId();
            register_shutdown_function(static function () use ($request, $objectId, $userId) {
                try {
                    self::save($request, $objectId, $userId);
                } catch (Throwable $e) {
                    error_log('SecurityClassificationSaveListener: '.$e->getMessage());
                }
            });
        } catch (Throwable $e) {
            // The description itself has saved; never turn that into an error page.
            error_log('SecurityClassificationSaveListener: '.$e->getMessage());
        }

        return $content;
    }

    /**
     * Why the posted classification cannot be applied, or null if it can. Read-only.
     * A child record cannot be classified, or declassified, below its parent.
     */
    public static function refusal(sfWebRequest $request, int $objectId): ?string
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $parent = SecurityClearanceService::getParentEffectiveClassification($objectId);
        if (!$parent) {
            return null;
        }
        $classificationId = (int) $request->getParameter('security_classification_id');
        if ($classificationId <= 0) {
            return null !== SecurityClearanceService::getObjectClassification($objectId)
                ? sprintf('The description was saved, but its classification was kept: the parent record is classified "%s", and a child record cannot be lower.', $parent->name)
                : null;
        }
        $new = SecurityClearanceService::getClassification($classificationId);

        return ($new && $new->level < $parent->level)
            ? sprintf('The description was saved, but not its classification: the parent record is classified "%s", and a child record cannot be lower.', $parent->name)
            : null;
    }

    /** Persist both fieldsets for one object, from the posted form. */
    public static function save(sfWebRequest $request, int $objectId, int $userId): void
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $classificationId = (int) $request->getParameter('security_classification_id');

        if ($classificationId > 0) {
            $result = SecurityClearanceService::classifyObject($objectId, $classificationId, [
                'reason' => $request->getParameter('security_reason') ?: null,
                'review_date' => $request->getParameter('security_review_date') ?: null,
                'declassify_date' => $request->getParameter('security_declassify_date') ?: null,
                'handling_instructions' => $request->getParameter('security_handling_instructions') ?: null,
                'inherit_to_children' => $request->getParameter('security_inherit_to_children') ? 1 : 0,
            ], $userId);
            if (empty($result['success'])) {
                error_log('SecurityClassificationSaveListener: object '.$objectId.' not classified: '.($result['error'] ?? 'unknown error'));
            }
        } elseif (null !== SecurityClearanceService::getObjectClassification($objectId)
            && !SecurityClearanceService::getParentEffectiveClassification($objectId)) {
            // The fieldset preselects the current level, so "Public (No Classification)"
            // posted on a classified record is a deliberate declassification.
            SecurityClearanceService::declassifyObject($objectId, null, $userId, 'Cleared via edit form');
        }

        self::saveWatermark($request, $objectId);
    }

    private static function saveWatermark(sfWebRequest $request, int $objectId): void
    {
        $svc = '\\AhgCore\\Services\\WatermarkSettingsService';
        if (!$request->hasParameter('watermark_type_id') || !class_exists($svc)) {
            return;
        }

        $opacity = $request->getParameter('new_watermark_opacity');
        $opacity = is_numeric($opacity) ? max(0.0, min(1.0, round(((float) $opacity > 1 ? (float) $opacity / 100 : (float) $opacity), 2))) : null;
        $typeId = (int) $request->getParameter('watermark_type_id');

        $svc::saveObjectWatermark(
            $objectId,
            $typeId > 0 ? $typeId : null,
            (bool) $request->getParameter('watermark_enabled'),
            (int) $request->getParameter('custom_watermark_id'),
            $request->getParameter('new_watermark_position') ?: null,
            $opacity
        );

        // Derivatives carry the mark; regenerating them is opt-in, as on the AHG form.
        $regen = '\\AhgCore\\Services\\DerivativeWatermarkService';
        if ($request->getParameter('regenerate_derivatives') && class_exists($regen) && method_exists($regen, 'regenerateDerivatives')) {
            $regen::regenerateDerivatives($objectId);
        }
    }
}

<?php

/**
 * Collections Procedures panel for a description page.
 *
 * Rendered through ahgDisplayPlugin's DisplayActionRegistry, which supplies $resource.
 * It decides whether there is anything worth showing, then hands off to the existing
 * _workflowTimeline partial, so there is one timeline implementation rather than two.
 *
 * Naming note: user-facing text says "Collections Procedures". The module, tables,
 * classes and setting keys keep the `spectrum` identifier deliberately - renaming
 * those would break 636 table references and 256 class names for no user benefit.
 */

if (!isset($resource) || !isset($resource->id)) {
    return;
}

if (!class_exists('ahgSpectrumWorkflowService')) {
    return;
}

$objectId = (int) $resource->id;

try {
    // _workflowTimeline fetches everything it needs itself, so all this does is
    // decide whether the panel is worth showing at all. The registry drops panels
    // that render ''.
    //
    // Note getObjectProcedureStatus() is never empty: it walks the full hardcoded
    // procedure list and returns every one of them, defaulting to 'not_started'.
    // Testing it with empty() would show a complete "nothing started yet" timeline
    // on every description in the catalogue. Real activity is the test.
    $hasActivity = false;

    foreach (ahgSpectrumWorkflowService::getObjectProcedureStatus($objectId) as $procStatus) {
        if (ahgSpectrumWorkflowService::STATUS_NOT_STARTED !== ($procStatus['status'] ?? ahgSpectrumWorkflowService::STATUS_NOT_STARTED)
            || !empty($procStatus['events'])
            || !empty($procStatus['lastUpdate'])) {
            $hasActivity = true;

            break;
        }
    }

    if (!$hasActivity) {
        return;
    }
} catch (Throwable $e) {
    error_log('ahgSpectrumPlugin: Collections Procedures panel failed: ' . $e->getMessage());

    return;
}

include sfConfig::get('sf_plugins_dir')
    . '/ahgSpectrumPlugin/modules/spectrum/templates/_workflowTimeline.php';

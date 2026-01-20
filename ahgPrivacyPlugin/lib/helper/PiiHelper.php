<?php
/**
 * PII Masking Helper Functions
 *
 * Global helper functions for masking PII in templates.
 * Include with: sfContext::getInstance()->getConfiguration()->loadHelpers('Pii');
 * Or auto-loaded via plugin configuration.
 */

/**
 * Mask PII in content for a specific information object
 *
 * Usage in templates:
 *   <?php echo pii_mask($resource->id, $resource->getScopeAndContent(['cultureFallback' => true])); ?>
 *
 * @param int $objectId The information object ID
 * @param string|null $content The content to mask
 * @return string|null
 */
function pii_mask($objectId, $content)
{
    if (!$objectId || $content === null) {
        return $content;
    }

    require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PiiMaskingService.php';
    return \ahgPrivacyPlugin\Service\PiiMaskingService::mask((int)$objectId, $content);
}

/**
 * Check if an object has redacted PII (to show warning badge)
 *
 * @param int $objectId
 * @return bool
 */
function pii_has_redacted($objectId)
{
    if (!$objectId) {
        return false;
    }

    require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PiiMaskingService.php';
    return \ahgPrivacyPlugin\Service\PiiMaskingService::hasRedactedPii((int)$objectId);
}

/**
 * Get count of redacted PII entities
 *
 * @param int $objectId
 * @return int
 */
function pii_redacted_count($objectId)
{
    if (!$objectId) {
        return 0;
    }

    require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PiiMaskingService.php';
    return \ahgPrivacyPlugin\Service\PiiMaskingService::getRedactedCount((int)$objectId);
}

/**
 * Check if current user can see unmasked PII
 *
 * @return bool
 */
function pii_can_view_unmasked()
{
    require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PiiMaskingService.php';
    return \ahgPrivacyPlugin\Service\PiiMaskingService::canBypassMasking();
}

/**
 * Mask a specific value (like an actor name) if it matches redacted PII
 *
 * @param int $objectId
 * @param string|null $value
 * @param string $defaultType Default entity type for mask pattern
 * @return array ['masked' => bool, 'value' => string]
 */
function pii_mask_value($objectId, $value, $defaultType = 'PERSON')
{
    if (!$objectId || $value === null) {
        return ['masked' => false, 'value' => $value];
    }

    require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PiiMaskingService.php';
    return \ahgPrivacyPlugin\Service\PiiMaskingService::maskValue((int)$objectId, $value, $defaultType);
}

/**
 * Check if a value should be hidden (matches redacted PII)
 *
 * @param int $objectId
 * @param string|null $value
 * @return bool
 */
function pii_should_hide($objectId, $value)
{
    if (!$objectId || $value === null) {
        return false;
    }

    require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PiiMaskingService.php';
    return \ahgPrivacyPlugin\Service\PiiMaskingService::shouldHideValue((int)$objectId, $value);
}

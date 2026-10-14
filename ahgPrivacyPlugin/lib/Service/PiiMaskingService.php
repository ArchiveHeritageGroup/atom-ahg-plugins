<?php
namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * PII Masking Service
 *
 * Provides display-time masking of PII entities that have been marked as "redacted".
 * Original data is preserved in the database; masking only affects display output.
 */
class PiiMaskingService
{
    /** @var array Cache of redacted entities by object_id */
    private static $cache = [];

    /** @var array Mask patterns by entity type */
    private static $maskPatterns = [
        'PERSON' => '[NAME REDACTED]',
        'SA_ID' => '[ID REDACTED]',
        'NG_NIN' => '[ID REDACTED]',
        'PASSPORT' => '[PASSPORT REDACTED]',
        'EMAIL' => '[EMAIL REDACTED]',
        'PHONE_SA' => '[PHONE REDACTED]',
        'PHONE_INTL' => '[PHONE REDACTED]',
        'BANK_ACCOUNT' => '[ACCOUNT REDACTED]',
        'TAX_NUMBER' => '[TAX NUMBER REDACTED]',
        'CREDIT_CARD' => '[CARD REDACTED]',
        'ORG' => '[ORG REDACTED]',
        'GPE' => '[LOCATION REDACTED]',
        'DATE' => '[DATE REDACTED]',
    ];

    /**
     * Get redacted PII entities for an object
     *
     * @param int $objectId
     * @return array
     */
    public static function getRedactedEntities(int $objectId): array
    {
        if (isset(self::$cache[$objectId])) {
            return self::$cache[$objectId];
        }

        $entities = DB::table('ahg_ner_entity as e')
            ->join('ahg_ner_extraction as ex', 'ex.id', '=', 'e.extraction_id')
            ->where('e.object_id', $objectId)
            ->where('e.status', 'redacted')
            ->select(['e.entity_type', 'e.entity_value'])
            ->get()
            ->toArray();

        self::$cache[$objectId] = $entities;
        return $entities;
    }

    /**
     * Mask every redacted identifier (ID, passport, email, phone, account, tax
     * and card numbers), whichever record it belongs to.
     *
     * mask() works per record and only record pages called it, so the same ID
     * number came back in full from the EAD export and in search snippets.
     * This runs over a whole response instead. Values shorter than six
     * characters are left alone, so a short value cannot blank out unrelated
     * text. The list is cached for a minute.
     */
    public static function maskAll(string $content): string
    {
        static $rules = null;
        if (null === $rules) {
            $key = 'ahg_privacy_redacted_patterns';
            $rules = function_exists('apcu_fetch') ? apcu_fetch($key) : false;
            if (!is_array($rules)) {
                // Identifiers only. Names, places, dates and subjects are redacted
                // per record and may appear legitimately elsewhere ("Cape Town").
                $rows = DB::table('ahg_ner_entity')
                    ->where('status', 'redacted')
                    ->whereIn('entity_type', ['SA_ID', 'NG_NIN', 'PASSPORT', 'EMAIL', 'PHONE_SA', 'PHONE_INTL', 'BANK_ACCOUNT', 'TAX_NUMBER', 'CREDIT_CARD'])
                    ->distinct()->get(['entity_type', 'entity_value']);
                $rules = [];
                foreach ($rows as $e) {
                    $re = self::previewToPattern((string) $e->entity_type, (string) $e->entity_value);
                    if (null !== $re) {
                        $rules[] = [$re, self::$maskPatterns[$e->entity_type] ?? '[REDACTED]'];
                    }
                }
                if (function_exists('apcu_store')) {
                    apcu_store($key, $rules, 60);
                }
            }
        }
        foreach ($rules as [$re, $mask]) {
            $content = preg_replace($re, $mask, $content) ?? $content;
        }

        return $content;
    }

    /**
     * The detector stores a starred preview, not the identifier (see
     * PiiDetectionService::maskPii), so a plain replace never matched. Rebuild a
     * pattern from the preview's fixed shape: kept prefix and suffix, and the
     * starred length where the preview preserves it.
     */
    private static function previewToPattern(string $type, string $preview): ?string
    {
        $q = fn ($t) => preg_quote($t, '/');
        switch ($type) {
            case 'SA_ID':
            case 'NG_NIN':
            case 'PASSPORT':
                if (!preg_match('/^(.{4})(\*+)(.{2})$/u', $preview, $m)) {
                    return null;
                }

                return '/(?<![0-9A-Za-z])'.$q($m[1]).'[0-9A-Za-z]{'.strlen($m[2]).'}'.$q($m[3]).'(?![0-9A-Za-z])/';
            case 'EMAIL':
                if (!preg_match('/^(.{1,2})\*\*\*@(.+)$/u', $preview, $m)) {
                    return null;
                }

                return '/(?<![\w.+-])'.$q($m[1]).'[\w.+-]{0,64}@'.$q($m[2]).'/i';
            case 'PHONE_SA':
            case 'PHONE_INTL':
                if (!preg_match('/^(.{4})\*\*\*(.{3})$/u', $preview, $m)) {
                    return null;
                }

                return '/(?<!\d)'.$q($m[1]).'[\d \-]{1,12}'.$q($m[2]).'(?!\d)/';
            case 'CREDIT_CARD':
                return preg_match('/(\d{4})$/', $preview, $m) ? '/(?<!\d)(?:\d[ -]?){12}'.$m[1].'(?!\d)/' : null;
            default:
                if (!preg_match('/^(.{3})(\*+)(.{2})$/u', $preview, $m)) {
                    return null;
                }

                return '/(?<![0-9A-Za-z])'.$q($m[1]).'[0-9A-Za-z]{'.strlen($m[2]).'}'.$q($m[3]).'(?![0-9A-Za-z])/';
        }
    }

    /**
     * Check if user can bypass PII masking (administrators)
     *
     * @return bool
     */
    public static function canBypassMasking(): bool
    {
        $context = \sfContext::getInstance();
        if (!$context->getUser()->isAuthenticated()) {
            return false;
        }

        // Administrators can see unmasked content
        return $context->getUser()->hasCredential('administrator');
    }

    /**
     * Mask PII in content for a specific object
     *
     * @param int $objectId The information object ID
     * @param string|null $content The content to mask
     * @param bool $forceShow Force showing original (for admin preview)
     * @return string|null
     */
    public static function mask(int $objectId, ?string $content, bool $forceShow = false): ?string
    {
        if ($content === null || $content === '') {
            return $content;
        }

        // Administrators can bypass masking (unless force disabled)
        if (!$forceShow && self::canBypassMasking()) {
            return $content;
        }

        $entities = self::getRedactedEntities($objectId);
        if (empty($entities)) {
            return $content;
        }

        // Sort by length descending to replace longer matches first
        usort($entities, function($a, $b) {
            return strlen($b->entity_value) - strlen($a->entity_value);
        });

        foreach ($entities as $entity) {
            $mask = self::$maskPatterns[$entity->entity_type] ?? '[REDACTED]';

            // Case-insensitive replacement
            $content = str_ireplace($entity->entity_value, $mask, $content);
        }

        return $content;
    }

    /**
     * Mask PII in multiple fields at once
     *
     * @param int $objectId
     * @param array $fields Associative array of field_name => content
     * @return array
     */
    public static function maskFields(int $objectId, array $fields): array
    {
        $result = [];
        foreach ($fields as $name => $content) {
            $result[$name] = self::mask($objectId, $content);
        }
        return $result;
    }

    /**
     * Check if an object has any redacted PII
     *
     * @param int $objectId
     * @return bool
     */
    public static function hasRedactedPii(int $objectId): bool
    {
        return count(self::getRedactedEntities($objectId)) > 0;
    }

    /**
     * Get count of redacted entities for an object
     *
     * @param int $objectId
     * @return int
     */
    public static function getRedactedCount(int $objectId): int
    {
        return count(self::getRedactedEntities($objectId));
    }

    /**
     * Clear cache for an object (call after updating entity status)
     *
     * @param int|null $objectId Specific object or null for all
     */
    public static function clearCache(?int $objectId = null): void
    {
        if ($objectId === null) {
            self::$cache = [];
        } else {
            unset(self::$cache[$objectId]);
        }
    }

    /**
     * Get mask pattern for a specific entity type
     *
     * @param string $entityType
     * @return string
     */
    public static function getMaskPattern(string $entityType): string
    {
        return self::$maskPatterns[$entityType] ?? '[REDACTED]';
    }

    /**
     * Check if a specific value matches a redacted PII entity and return masked version
     * Useful for access points where we check exact matches
     *
     * @param int $objectId
     * @param string|null $value The value to check (e.g., actor name, place name)
     * @param string $defaultType Default entity type for masking if not found
     * @return array ['masked' => bool, 'value' => string]
     */
    public static function maskValue(int $objectId, ?string $value, string $defaultType = 'PERSON'): array
    {
        if ($value === null || $value === '') {
            return ['masked' => false, 'value' => $value];
        }

        // Administrators can bypass masking
        if (self::canBypassMasking()) {
            return ['masked' => false, 'value' => $value];
        }

        $entities = self::getRedactedEntities($objectId);
        if (empty($entities)) {
            return ['masked' => false, 'value' => $value];
        }

        // Check for exact or partial match (case-insensitive)
        foreach ($entities as $entity) {
            // Check if entity value is contained in the value or vice versa
            if (stripos($value, $entity->entity_value) !== false ||
                stripos($entity->entity_value, $value) !== false) {
                $mask = self::$maskPatterns[$entity->entity_type] ?? self::$maskPatterns[$defaultType] ?? '[REDACTED]';
                return ['masked' => true, 'value' => $mask, 'original' => $value, 'type' => $entity->entity_type];
            }
        }

        return ['masked' => false, 'value' => $value];
    }

    /**
     * Check if a value should be hidden (matches redacted PII)
     *
     * @param int $objectId
     * @param string|null $value
     * @return bool
     */
    public static function shouldHideValue(int $objectId, ?string $value): bool
    {
        if ($value === null || $value === '' || self::canBypassMasking()) {
            return false;
        }

        $entities = self::getRedactedEntities($objectId);
        foreach ($entities as $entity) {
            if (stripos($value, $entity->entity_value) !== false ||
                stripos($entity->entity_value, $value) !== false) {
                return true;
            }
        }

        return false;
    }
}

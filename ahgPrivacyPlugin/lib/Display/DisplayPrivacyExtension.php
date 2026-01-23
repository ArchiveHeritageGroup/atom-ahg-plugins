<?php
/**
 * DisplayPrivacyExtension
 *
 * Adds visual redaction functionality to the DisplayService.
 * Integrates with ahgPrivacyPlugin to provide redaction capabilities in display views.
 *
 * Usage in DisplayService::prepareForDisplay():
 *   $actions = DisplayPrivacyExtension::addRedactionAction($actions, $objectId, $canEdit);
 */

use Illuminate\Database\Capsule\Manager as DB;

class DisplayPrivacyExtension
{
    /**
     * Check if object has a digital object (PDF, image, etc.)
     */
    public static function hasDigitalObject(int $objectId): bool
    {
        return DB::table('digital_object')
            ->where('object_id', $objectId)
            ->exists();
    }

    /**
     * Check if object has any visual redaction regions
     */
    public static function hasRedactionRegions(int $objectId): int
    {
        return DB::table('privacy_visual_redaction')
            ->where('object_id', $objectId)
            ->count();
    }

    /**
     * Get redaction status for an object
     */
    public static function getRedactionStatus(int $objectId): array
    {
        $regions = DB::table('privacy_visual_redaction')
            ->where('object_id', $objectId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => array_sum($regions),
            'pending' => $regions['pending'] ?? 0,
            'approved' => $regions['approved'] ?? 0,
            'applied' => $regions['applied'] ?? 0,
            'rejected' => $regions['rejected'] ?? 0,
        ];
    }

    /**
     * Add visual_redaction action if appropriate
     *
     * @param array $actions Current actions array
     * @param int $objectId The object ID
     * @param bool $canEdit Whether user can edit
     * @return array Updated actions array
     */
    public static function addRedactionAction(array $actions, int $objectId, bool $canEdit): array
    {
        // Only add for editors with digital objects
        if (!$canEdit) {
            return $actions;
        }

        if (!self::hasDigitalObject($objectId)) {
            return $actions;
        }

        // Add the visual_redaction action if not already present
        if (!in_array('visual_redaction', $actions)) {
            $actions[] = 'visual_redaction';
        }

        return $actions;
    }

    /**
     * Get privacy/redaction data for display
     */
    public static function getPrivacyData(int $objectId, bool $canEdit = false): array
    {
        if (!$canEdit) {
            return [];
        }

        $status = self::getRedactionStatus($objectId);

        // Check for PII entities detected
        $piiCount = 0;
        try {
            $piiCount = DB::table('privacy_pii_entity')
                ->where('object_id', $objectId)
                ->where('status', 'pending')
                ->count();
        } catch (\Exception $e) {
            // Table may not exist
        }

        return [
            'redaction_status' => $status,
            'pii_pending' => $piiCount,
            'has_redactions' => $status['total'] > 0,
            'has_pending_pii' => $piiCount > 0,
        ];
    }
}

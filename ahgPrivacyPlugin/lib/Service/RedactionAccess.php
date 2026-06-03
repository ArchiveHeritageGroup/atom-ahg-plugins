<?php

namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * RedactionAccess — single authority for "may this viewer see the unredacted
 * record?" (#130 refinement 3). Used by both the web content filter and the
 * REST API so the bypass rule is defined in exactly one place.
 *
 * Rule: staff (administrator / editor) always bypass. In addition, an
 * authenticated user holding an APPROVED, unexpired research_researcher
 * agreement (the AtoM-AHG "active access agreement") sees the full record.
 * Everyone else — anonymous, or authenticated without an active agreement —
 * is served the redacted view. Fail-closed: any error, or the research plugin
 * not being installed, means "redact".
 *
 * @package ahgPrivacyPlugin
 */
class RedactionAccess
{
    /** Web path: resolve from the Symfony user. */
    public static function userMaySeeUnredacted(\sfUser $user): bool
    {
        if (!$user->isAuthenticated()) {
            return false;
        }
        if ($user->hasCredential('administrator') || $user->hasCredential('editor')) {
            return true;
        }

        $uid = $user->getAttribute('user_id');

        return self::hasActiveAgreement($uid ? (int) $uid : null);
    }

    /**
     * True when the user holds an approved, unexpired research_researcher
     * agreement. Wrapped so installs without the research plugin (no
     * research_researcher table) simply fail closed.
     */
    public static function hasActiveAgreement(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        try {
            return DB::table('research_researcher')
                ->where('user_id', $userId)
                ->where('status', 'approved')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', date('Y-m-d'));
                })
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }
}

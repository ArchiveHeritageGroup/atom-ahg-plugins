<?php

namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * RedactionAccess - authority for "may this WEB viewer see the unredacted
 * record?" (#130 refinement 3).
 *
 * Rule: staff (administrator / editor) always bypass. In addition, an
 * authenticated user holding an APPROVED, unexpired research_researcher
 * agreement (the AtoM-AHG "active access agreement") sees the full record.
 * Everyone else - anonymous, or authenticated without an active agreement -
 * is served the redacted view. Fail-closed: any error, or the research plugin
 * not being installed, means "redact".
 *
 * ## Both surfaces now resolve here
 *
 * The web content filter calls userMaySeeUnredacted(). The apiv2 description
 * actions call apiMaySeeUnredacted(), which is the same rule plus one documented
 * addition for administratively issued keys. They previously disagreed by
 * construction - the API gated on hasScope('admin') alone and never reached this
 * class - so a researcher holding an approved agreement saw the full record on
 * the web and a redacted one through the API. That is fixed.
 *
 * Keep it that way. If a third surface needs to make this decision, add a method
 * here rather than re-deriving the rule at the call site, which is how the two
 * drifted apart in the first place.
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
     * REST path: the same rule as the web, plus an admin-scoped key.
     *
     * AhgApiController::authenticate() signs the key's owner in, so the sfUser
     * passed here carries that person's credentials and userMaySeeUnredacted()
     * answers correctly for them. An admin-scoped key bypasses on its own
     * because such keys are issued deliberately by an administrator and need not
     * belong to someone who could hold a research agreement.
     */
    public static function apiMaySeeUnredacted(\sfUser $user, bool $hasAdminScope): bool
    {
        if ($hasAdminScope) {
            return true;
        }

        return self::userMaySeeUnredacted($user);
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

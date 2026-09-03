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
 * ## Scope: this is NOT the only bypass rule in the system
 *
 * An earlier version of this docblock claimed the web filter and the REST API
 * shared this class so the rule lived in exactly one place. They do not, and it
 * did not. RedactionContentFilter calls this class; the apiv2 description
 * actions in ahgAPIPlugin gate on hasScope('admin') instead and never reach
 * here. The two rules therefore disagree by construction: a researcher with an
 * approved agreement is served the full record on the web and a redacted one
 * through the API.
 *
 * That divergence is arguably correct - an API key is not a person and cannot
 * hold a research agreement - but it must be stated, because a docblock
 * asserting a single authority is how the two drift further apart unnoticed.
 * Converging them (or deliberately keying API access to the agreement of the
 * key's owner) requires changes in ahgAPIPlugin, not here.
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

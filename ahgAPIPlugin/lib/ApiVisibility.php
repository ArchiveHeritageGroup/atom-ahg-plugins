<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Which descriptions an API caller may read. The REST API used to apply no
 * check at all: any key with "read" scope - and any approved researcher can
 * issue one in the research portal - listed and read drafts and classified,
 * donor-restricted and embargoed records in full.
 *
 * The caller is the key's owner (AhgApiController::authenticate signs them in):
 *   - drafts only for staff (administrator, editor, contributor);
 *   - never a description SearchAccessFilterService hides from that user
 *     (classification, donor restriction, embargo, ICIP, ODRL);
 *     administrators see everything.
 *
 * Fails closed: if the rule cannot be evaluated, nothing is visible.
 */
class ApiVisibility
{
    private const PUBLICATION_STATUS_TYPE_ID = 158;
    private const PUBLISHED_ID = 160;

    /** @var array<int,int[]|null> hidden ids per user id, for this request */
    private static array $hidden = [];

    public static function isStaff(\sfUser $user): bool
    {
        return $user->isAuthenticated() && $user->hasCredential(['administrator', 'editor', 'contributor'], false);
    }

    /** @return int[]|null null when it cannot be determined */
    public static function hiddenIds(\sfUser $user): ?array
    {
        $userId = $user->isAuthenticated() ? (int) $user->getAttribute('user_id') : 0;
        if (!array_key_exists($userId, self::$hidden)) {
            try {
                self::$hidden[$userId] = array_map('intval', \AtomExtensions\Services\Search\SearchAccessFilterService::getInstance()
                    ->getRestrictedObjectIds($userId ?: null));
            } catch (\Throwable $e) {
                error_log('api.visibility_failed: '.$e->getMessage());
                self::$hidden[$userId] = null;
            }
        }

        return self::$hidden[$userId];
    }

    /** Restrict a query aliased "io" to what this caller may see. */
    public static function applyToQuery($query, \sfUser $user): void
    {
        if (!self::isStaff($user)) {
            $query->whereExists(function ($q) {
                $q->select(DB::raw(1))->from('status')
                    ->whereColumn('status.object_id', 'io.id')
                    ->where('status.type_id', self::PUBLICATION_STATUS_TYPE_ID)
                    ->where('status.status_id', self::PUBLISHED_ID);
            });
        }

        $hidden = self::hiddenIds($user);
        if (null === $hidden) {
            $query->whereRaw('1 = 0');
        } elseif ([] !== $hidden) {
            $query->whereNotIn('io.id', $hidden);
        }
    }

    /** Whether this caller may read the description with this slug. */
    public static function canSeeSlug(string $slug, \sfUser $user): bool
    {
        $query = DB::table('slug as s')
            ->join('information_object as io', 'io.id', '=', 's.object_id')
            ->where('s.slug', $slug);
        self::applyToQuery($query, $user);

        return $query->exists();
    }

    /**
     * Drop hits this caller may not see from a list of ['id' => ...] rows.
     *
     * @param array<int,array> $rows
     */
    public static function filterRows(array $rows, \sfUser $user): array
    {
        $ids = array_values(array_filter(array_map(static fn ($r) => (int) ($r['id'] ?? 0), $rows)));
        if ([] === $ids) {
            return [];
        }
        $query = DB::table('information_object as io')->whereIn('io.id', $ids);
        self::applyToQuery($query, $user);
        $keep = array_flip(array_map('intval', $query->pluck('io.id')->all()));

        return array_values(array_filter($rows, static fn ($r) => isset($keep[(int) ($r['id'] ?? 0)])));
    }
}

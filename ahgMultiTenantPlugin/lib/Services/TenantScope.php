<?php

namespace AhgMultiTenant\Services;

use AhgMultiTenant\Models\Tenant;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Which descriptions a tenant user may not see.
 *
 * Registered with the framework's SearchAccessFilterService, so the rule
 * reaches everything that already honours it: GLAM browse, search, the REST
 * API, GraphQL, the chatbot, and every page addressed by a description's slug
 * (view, edit, delete, print, exports - refused by ahgSecurityClearancePlugin).
 *
 * A user is scoped when they are not an administrator and have any tenant
 * membership or repository assignment. A scoped user sees only descriptions
 * under their own repositories; records without one are hidden too. Users
 * with no assignment at all are not scoped, so enabling the plugin does not
 * hide the archive from existing staff. Membership of a suspended tenant
 * still scopes the user but grants nothing, so suspension never widens access.
 *
 * ponytail: the hidden list is every other tenant's description id, which
 * callers pass to the search index as one terms filter (OpenSearch's default
 * limit is 65,536 terms). Fine for tenants of tens of thousands of records;
 * beyond that, filter the index by repository id instead of by record ids.
 */
class TenantScope
{
    /** @var array<int, int[]> per-request cache, by user id */
    private static array $hidden = [];

    /**
     * Repositories the user may see, or null when the user is not scoped.
     *
     * @return null|int[]
     */
    public static function allowedRepositoryIds(int $userId): ?array
    {
        if (TenantContext::isAdmin($userId)) {
            return null;
        }

        $assigned = array_merge(
            TenantContext::getSuperUserRepositoryIds($userId),
            TenantContext::getAssignedRepositoryIds($userId)
        );
        $memberships = DB::table('heritage_tenant_user as tu')
            ->join('heritage_tenant as t', 't.id', '=', 'tu.tenant_id')
            ->where('tu.user_id', $userId)
            ->get(['t.repository_id', 't.status']);

        if (!$assigned && $memberships->isEmpty()) {
            return null;
        }

        foreach ($memberships as $m) {
            if (null !== $m->repository_id && in_array($m->status, [Tenant::STATUS_ACTIVE, Tenant::STATUS_TRIAL], true)) {
                $assigned[] = $m->repository_id;
            }
        }

        return array_values(array_unique(array_map('intval', $assigned)));
    }

    /** @return int[] description ids hidden from this user */
    public static function hiddenObjectIds(?int $userId): array
    {
        if (!$userId) {
            return [];
        }
        if (isset(self::$hidden[$userId])) {
            return self::$hidden[$userId];
        }

        $allowed = self::allowedRepositoryIds($userId);
        if (null === $allowed) {
            return self::$hidden[$userId] = [];
        }

        // A description belongs to the repository of the nearest record above
        // it that names one (children usually name none), so the visible part
        // of the tree is the union of the subtrees of records in an allowed
        // repository. Merge them into disjoint lft ranges.
        $ranges = [];
        if ($allowed) {
            foreach (DB::table('information_object')->whereIn('repository_id', $allowed)->orderBy('lft')->get(['lft', 'rgt']) as $r) {
                $last = count($ranges) - 1;
                if ($last >= 0 && $r->lft <= $ranges[$last][1]) {
                    $ranges[$last][1] = max($ranges[$last][1], (int) $r->rgt);
                } else {
                    $ranges[] = [(int) $r->lft, (int) $r->rgt];
                }
            }
        }

        $query = DB::table('information_object')->where('id', '<>', \QubitInformationObject::ROOT_ID);
        foreach ($ranges as [$lft, $rgt]) {
            $query->whereNotBetween('lft', [$lft, $rgt]);
        }

        return self::$hidden[$userId] = array_map('intval', $query->pluck('id')->all());
    }

    /** Forget cached results (after assignments change, and in tests). */
    public static function clearCache(): void
    {
        self::$hidden = [];
    }
}

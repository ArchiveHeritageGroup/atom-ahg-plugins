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

    /** @var array<int, int[]> per-request cache, by user id */
    private static array $hiddenAccessions = [];

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

        $ranges = self::visibleRanges($allowed);

        $query = DB::table('information_object')->where('id', '<>', \QubitInformationObject::ROOT_ID);
        foreach ($ranges as [$lft, $rgt]) {
            $query->whereNotBetween('lft', [$lft, $rgt]);
        }

        return self::$hidden[$userId] = array_map('intval', $query->pluck('id')->all());
    }

    /**
     * Accession ids hidden from this user (#217).
     *
     * An accession belongs to the repository named in its CAAIS record
     * (accession_caais.repository_id). Without one, it belongs to the
     * descriptions it is linked to, and a scoped user sees it when at least
     * one of them is visible. An accession with neither is hidden, as a
     * description without a repository is.
     *
     * @return int[]
     */
    public static function hiddenAccessionIds(?int $userId): array
    {
        if (!$userId) {
            return [];
        }
        if (isset(self::$hiddenAccessions[$userId])) {
            return self::$hiddenAccessions[$userId];
        }

        $allowed = self::allowedRepositoryIds($userId);
        if (null === $allowed) {
            return self::$hiddenAccessions[$userId] = [];
        }

        $hasCaais = DB::schema()->hasTable('accession_caais');
        $visible = $hasCaais && $allowed
            ? DB::table('accession_caais')->whereIn('repository_id', $allowed)->pluck('accession_id')->all()
            : [];

        $ranges = self::visibleRanges($allowed);
        if ($ranges) {
            $linked = DB::table('relation as r')
                ->join('information_object as io', 'io.id', '=', 'r.subject_id')
                ->where('r.type_id', \QubitTerm::ACCESSION_ID)
                ->where(function ($q) use ($ranges) {
                    foreach ($ranges as [$lft, $rgt]) {
                        $q->orWhereBetween('io.lft', [$lft, $rgt]);
                    }
                });
            if ($hasCaais) {
                // A repository on the accession wins over its links.
                $linked->leftJoin('accession_caais as c', 'c.accession_id', '=', 'r.object_id')->whereNull('c.repository_id');
            }
            $visible = array_merge($visible, $linked->pluck('r.object_id')->all());
        }

        return self::$hiddenAccessions[$userId] = array_map('intval', DB::table('accession')
            ->whereNotIn('id', array_unique(array_map('intval', $visible)))
            ->pluck('id')->all());
    }

    /**
     * The visible part of the description tree, as disjoint lft ranges. A
     * description belongs to the repository of the nearest record above it
     * that names one (children usually name none), so the visible part is the
     * union of the subtrees of records in an allowed repository.
     *
     * @param int[] $allowed
     *
     * @return array<int, array{0: int, 1: int}>
     */
    private static function visibleRanges(array $allowed): array
    {
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

        return $ranges;
    }

    /** Forget cached results (after assignments change, and in tests). */
    public static function clearCache(): void
    {
        self::$hidden = [];
        self::$hiddenAccessions = [];
    }
}

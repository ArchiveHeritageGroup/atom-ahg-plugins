<?php

namespace AhgMultiTenant\Filter;

use AhgMultiTenant\Services\TenantContext;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * TenantQueryFilter
 *
 * Helper class for applying tenant filters to database queries.
 * Use this to ensure users only see data from their assigned repositories.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class TenantQueryFilter
{
    /**
     * Apply tenant filter to an information object query
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Query\Builder
     */
    public static function filterInformationObjects($query)
    {
        return TenantContext::applyRepositoryFilter($query, 'repository_id');
    }

    /**
     * Apply tenant filter to an accession query
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Query\Builder
     */
    public static function filterAccessions($query)
    {
        // Accessions are linked to repository through information_object
        // This requires a join or subquery
        $repoId = TenantContext::getCurrentRepositoryId();

        if ($repoId === null || TenantContext::isViewAllMode()) {
            return $query;
        }

        return $query->whereExists(function ($subquery) use ($repoId) {
            $subquery->select(DB::raw(1))
                ->from('information_object')
                ->whereColumn('accession.resource_id', 'information_object.id')
                ->where('information_object.repository_id', $repoId);
        });
    }

    /**
     * Apply tenant filter to a digital object query
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Query\Builder
     */
    public static function filterDigitalObjects($query)
    {
        $repoId = TenantContext::getCurrentRepositoryId();

        if ($repoId === null || TenantContext::isViewAllMode()) {
            return $query;
        }

        return $query->whereExists(function ($subquery) use ($repoId) {
            $subquery->select(DB::raw(1))
                ->from('information_object')
                ->whereColumn('digital_object.object_id', 'information_object.id')
                ->where('information_object.repository_id', $repoId);
        });
    }

    /**
     * Get Elasticsearch filter for current tenant
     *
     * @return array|null Filter array or null if viewing all
     */
    public static function getElasticsearchFilter(): ?array
    {
        $repoId = TenantContext::getCurrentRepositoryId();

        if ($repoId === null || TenantContext::isViewAllMode()) {
            return null;
        }

        return ['term' => ['repository.id' => $repoId]];
    }

    /**
     * Apply tenant filter to Elasticsearch query
     *
     * @param array $query Elasticsearch query array
     * @return array Modified query
     */
    public static function applyElasticsearchFilter(array $query): array
    {
        $filter = self::getElasticsearchFilter();

        if ($filter === null) {
            return $query;
        }

        // Ensure bool query exists
        if (!isset($query['bool'])) {
            $query = ['bool' => ['must' => [$query]]];
        }

        // Add filter
        if (!isset($query['bool']['filter'])) {
            $query['bool']['filter'] = [];
        }

        $query['bool']['filter'][] = $filter;

        return $query;
    }

    /**
     * Check if current user can view a specific repository's data
     *
     * @param int $repositoryId Repository ID to check
     * @return bool
     */
    public static function canViewRepository(int $repositoryId): bool
    {
        $userId = TenantContext::getCurrentUserId();

        if ($userId === null) {
            return false;
        }

        // Admin can view all
        if (TenantContext::isAdmin($userId)) {
            return true;
        }

        // Check if user has access to this repository
        require_once dirname(__DIR__) . '/Services/TenantAccess.php';
        return \AhgMultiTenant\Services\TenantAccess::canAccessRepository($userId, $repositoryId);
    }
}

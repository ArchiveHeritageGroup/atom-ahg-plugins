<?php

namespace AhgPortableExportPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Quick COUNT + SUM queries for dry-run export estimates.
 */
class ExportEstimator
{
    /**
     * Estimate export size and record counts.
     *
     * @param string      $scopeType     all, fonds, repository, custom
     * @param string|null $scopeSlug     Fonds slug
     * @param int|null    $repositoryId  Repository ID
     * @param array|null  $scopeItems    Custom item IDs
     * @param string      $mode          read_only, editable, archive
     * @return array
     */
    public function estimate(
        string $scopeType = 'all',
        ?string $scopeSlug = null,
        ?int $repositoryId = null,
        ?array $scopeItems = null,
        string $mode = 'archive'
    ): array {
        // Build base description query
        $descQuery = DB::table('information_object as io')
            ->where('io.id', '!=', 1);

        $this->applyScopeFilter($descQuery, $scopeType, $scopeSlug, $repositoryId, $scopeItems);

        $descCount = $descQuery->count();

        // Get scoped IO ids for related entity counts
        $scopeIds = null;
        if ($scopeType !== 'all' || $repositoryId) {
            $idsQuery = DB::table('information_object as io')
                ->where('io.id', '!=', 1);
            $this->applyScopeFilter($idsQuery, $scopeType, $scopeSlug, $repositoryId, $scopeItems);
            $scopeIds = $idsQuery->pluck('io.id')->toArray();
        }

        // Digital objects
        $doQuery = DB::table('digital_object');
        if ($scopeIds !== null) {
            $doQuery->whereIn('object_id', $scopeIds);
        }
        $doCount = $doQuery->count();
        $doSize = $doQuery->sum('byte_size') ?: 0;

        $result = [
            'descriptions' => $descCount,
            'digital_objects' => [
                'count' => $doCount,
                'size_bytes' => (int) $doSize,
            ],
        ];

        // Archive mode includes additional entity counts
        if ($mode === 'archive') {
            $result['authorities'] = $this->countAuthorities($scopeIds);
            $result['taxonomies'] = DB::table('taxonomy')->count();
            $result['terms'] = DB::table('term')->count();
            $result['rights'] = $this->countRights($scopeIds);
            $result['accessions'] = DB::table('accession')->count();
            $result['physical_objects'] = $this->countPhysicalObjects($scopeIds);
            $result['events'] = $this->countEvents($scopeIds);
            $result['notes'] = $this->countNotes($scopeIds);
            $result['relations'] = $this->countRelations($scopeIds);
            $result['repositories'] = $repositoryId
                ? 1
                : DB::table('repository')->where('id', '!=', \QubitRepository::ROOT_ID ?? 6)->count();
        }

        // Estimate package size
        $metadataEstimate = $descCount * 2048; // ~2KB per description JSON
        $totalEstimate = $doSize + $metadataEstimate;

        $result['estimated_package_size'] = $this->formatSize($totalEstimate);
        $result['estimated_package_bytes'] = (int) $totalEstimate;

        // Rough duration estimate: ~100 records/sec extraction + 50MB/sec file copy
        $extractSeconds = $descCount / 100;
        $copySeconds = $doSize / (50 * 1048576);
        $totalMinutes = max(1, (int) ceil(($extractSeconds + $copySeconds) / 60));
        $result['estimated_duration_minutes'] = $totalMinutes;

        return $result;
    }

    protected function applyScopeFilter($query, string $scopeType, ?string $scopeSlug, ?int $repositoryId, ?array $scopeItems): void
    {
        switch ($scopeType) {
            case 'fonds':
                if ($scopeSlug) {
                    $slugRow = DB::table('slug')->where('slug', $scopeSlug)->first();
                    if ($slugRow) {
                        $root = DB::table('information_object')->where('id', $slugRow->object_id)->first();
                        if ($root) {
                            $query->where('io.lft', '>=', $root->lft)
                                ->where('io.rgt', '<=', $root->rgt);
                        }
                    }
                }
                break;

            case 'repository':
                if ($repositoryId) {
                    $query->where('io.repository_id', '=', $repositoryId);
                }
                break;

            case 'custom':
                if (!empty($scopeItems)) {
                    $ranges = DB::table('information_object')
                        ->whereIn('id', $scopeItems)
                        ->select('lft', 'rgt')
                        ->get();

                    if ($ranges->isNotEmpty()) {
                        $query->where(function ($q) use ($ranges) {
                            foreach ($ranges as $range) {
                                $q->orWhere(function ($q2) use ($range) {
                                    $q2->where('io.lft', '>=', $range->lft)
                                        ->where('io.rgt', '<=', $range->rgt);
                                });
                            }
                        });
                    }
                }
                break;
        }

        if ($repositoryId && $scopeType !== 'repository') {
            $query->where('io.repository_id', '=', $repositoryId);
        }
    }

    protected function countAuthorities(?array $scopeIds): int
    {
        if ($scopeIds === null) {
            return DB::table('actor')->where('id', '!=', \QubitActor::ROOT_ID ?? 3)->count();
        }

        $actorIds = [];
        $chunks = array_chunk($scopeIds, 500);
        foreach ($chunks as $chunk) {
            $ids = DB::table('event')->whereIn('object_id', $chunk)->whereNotNull('actor_id')->pluck('actor_id')->toArray();
            $actorIds = array_merge($actorIds, $ids);
            $ids = DB::table('relation')->whereIn('subject_id', $chunk)->whereNotNull('object_id')->pluck('object_id')->toArray();
            $actorIds = array_merge($actorIds, $ids);
        }

        return count(array_unique($actorIds));
    }

    protected function countRights(?array $scopeIds): int
    {
        $query = DB::table('rights');
        if ($scopeIds !== null) {
            $query->whereIn('object_id', $scopeIds);
        }

        return $query->count();
    }

    protected function countPhysicalObjects(?array $scopeIds): int
    {
        if ($scopeIds === null) {
            return DB::table('physical_object')->count();
        }

        $poIds = DB::table('relation')
            ->whereIn('subject_id', $scopeIds)
            ->pluck('object_id')
            ->toArray();

        return count(array_unique($poIds));
    }

    protected function countEvents(?array $scopeIds): int
    {
        $query = DB::table('event');
        if ($scopeIds !== null) {
            $query->whereIn('object_id', $scopeIds);
        }

        return $query->count();
    }

    protected function countNotes(?array $scopeIds): int
    {
        $query = DB::table('note');
        if ($scopeIds !== null) {
            $query->whereIn('object_id', $scopeIds);
        }

        return $query->count();
    }

    protected function countRelations(?array $scopeIds): int
    {
        if ($scopeIds === null) {
            return DB::table('relation')->count();
        }

        $query = DB::table('relation');
        $chunks = array_chunk($scopeIds, 500);
        $query->where(function ($q) use ($chunks) {
            foreach ($chunks as $chunk) {
                $q->orWhereIn('subject_id', $chunk)
                  ->orWhereIn('object_id', $chunk);
            }
        });

        return $query->count();
    }

    protected function formatSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 1) . ' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }
}

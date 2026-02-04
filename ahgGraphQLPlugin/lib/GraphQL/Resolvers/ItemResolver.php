<?php

namespace AhgGraphQLPlugin\GraphQL\Resolvers;

use Illuminate\Database\Capsule\Manager as DB;

class ItemResolver extends BaseResolver
{
    public function resolveBySlug(string $slug): ?array
    {
        return $this->repository->getFullDescription($slug);
    }

    public function resolveById(int $id): ?array
    {
        $row = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('term_i18n as lod', function ($join) {
                $join->on('io.level_of_description_id', '=', 'lod.id')
                    ->where('lod.culture', '=', $this->culture);
            })
            ->leftJoin('display_standard_sector as dss', 'io.display_standard_id', '=', 'dss.term_id')
            ->where('io.id', $id)
            ->select([
                'io.id',
                'slug.slug',
                'io.identifier',
                'ioi.title',
                'io.level_of_description_id',
                'lod.name as level_of_description',
                'dss.sector',
                'ioi.scope_and_content',
                'ioi.extent_and_medium',
                'ioi.archival_history',
                'ioi.acquisition',
                'ioi.arrangement',
                'ioi.access_conditions',
                'ioi.reproduction_conditions',
                'io.repository_id',
                'io.parent_id',
            ])
            ->first();

        if (!$row) {
            return null;
        }

        return (array) $row;
    }

    public function resolveList(int $first, int $offset, ?string $repository = null, ?string $level = null, ?string $sector = null): array
    {
        $params = [
            'limit' => $first,
            'skip' => $offset,
        ];

        if ($repository) {
            $params['repository'] = $repository;
        }
        if ($level) {
            $params['level'] = $level;
        }
        if ($sector) {
            $params['sector'] = $sector;
        }

        $result = $this->repository->getDescriptions($params);

        return $this->buildConnection($result['results'], $result['total'], $offset, $first);
    }

    public function resolveChildren(int $parentId, int $first, int $offset): array
    {
        $query = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('term_i18n as lod', function ($join) {
                $join->on('io.level_of_description_id', '=', 'lod.id')
                    ->where('lod.culture', '=', $this->culture);
            })
            ->leftJoin('display_standard_sector as dss', 'io.display_standard_id', '=', 'dss.term_id')
            ->where('io.parent_id', $parentId)
            ->select([
                'io.id',
                'slug.slug',
                'io.identifier',
                'ioi.title',
                'io.level_of_description_id',
                'lod.name as level_of_description',
                'dss.sector',
                'io.repository_id',
                'io.parent_id',
            ]);

        $total = $query->count();
        $results = $query->orderBy('io.lft', 'asc')
            ->skip($offset)
            ->take($first)
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        return $this->buildConnection($results, $total, $offset, $first);
    }

    public function countChildren(int $parentId): int
    {
        return DB::table('information_object')
            ->where('parent_id', $parentId)
            ->count();
    }

    public function resolveAncestors(int $itemId): array
    {
        $current = DB::table('information_object')
            ->where('id', $itemId)
            ->select(['parent_id', 'lft', 'rgt'])
            ->first();

        if (!$current || $current->parent_id == 1) {
            return [];
        }

        $ancestors = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.lft', '<', $current->lft)
            ->where('io.rgt', '>', $current->rgt)
            ->where('io.id', '!=', 1)
            ->select(['io.id', 'slug.slug', 'ioi.title', 'io.lft'])
            ->orderBy('io.lft', 'asc')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        return $ancestors;
    }

    public function resolveDates(int $itemId): array
    {
        $events = DB::table('event as e')
            ->leftJoin('event_i18n as ei', function ($join) {
                $join->on('e.id', '=', 'ei.id')
                    ->where('ei.culture', '=', $this->culture);
            })
            ->leftJoin('term_i18n as ti', function ($join) {
                $join->on('e.type_id', '=', 'ti.id')
                    ->where('ti.culture', '=', $this->culture);
            })
            ->where('e.object_id', $itemId)
            ->select([
                'e.id',
                'e.start_date',
                'e.end_date',
                'e.type_id',
                'ti.name as event_type',
                'ei.date as date_display',
                'ei.description',
                'e.actor_id',
            ])
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        return $events;
    }

    public function resolveSubjects(int $itemId): array
    {
        return DB::table('object_term_relation as otr')
            ->join('term as t', 'otr.term_id', '=', 't.id')
            ->join('term_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                    ->where('ti.culture', '=', $this->culture);
            })
            ->where('otr.object_id', $itemId)
            ->where('t.taxonomy_id', 35)
            ->select(['t.id', 'ti.name'])
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();
    }

    public function resolvePlaces(int $itemId): array
    {
        return DB::table('object_term_relation as otr')
            ->join('term as t', 'otr.term_id', '=', 't.id')
            ->join('term_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                    ->where('ti.culture', '=', $this->culture);
            })
            ->where('otr.object_id', $itemId)
            ->where('t.taxonomy_id', 42)
            ->select(['t.id', 'ti.name'])
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();
    }

    public function resolveCreators(int $itemId): array
    {
        return DB::table('event as e')
            ->join('actor as a', 'e.actor_id', '=', 'a.id')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', $this->culture);
            })
            ->leftJoin('slug', 'a.id', '=', 'slug.object_id')
            ->leftJoin('term_i18n as ti', function ($join) {
                $join->on('e.type_id', '=', 'ti.id')
                    ->where('ti.culture', '=', $this->culture);
            })
            ->where('e.object_id', $itemId)
            ->whereNotNull('e.actor_id')
            ->select([
                'a.id',
                'slug.slug',
                'ai.authorized_form_of_name',
                'ti.name as relation_type',
            ])
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();
    }

    public function resolveDigitalObjects(int $itemId): array
    {
        $objects = DB::table('digital_object as do')
            ->where('do.object_id', $itemId)
            ->select(['do.id', 'do.name', 'do.path', 'do.mime_type', 'do.byte_size', 'do.checksum', 'do.usage_id'])
            ->get();

        return $objects->map(function ($row) {
            $thumbnailPath = null;
            $masterPath = null;
            if ($row->path) {
                $pathInfo = pathinfo($row->path);
                $thumbnailPath = '/uploads/' . $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_142.' . ($pathInfo['extension'] ?? 'jpg');
                $masterPath = '/uploads/' . $row->path;
            }

            return [
                'id' => $row->id,
                'name' => $row->name,
                'mime_type' => $row->mime_type,
                'byte_size' => $row->byte_size,
                'checksum' => $row->checksum,
                'thumbnail_url' => $thumbnailPath,
                'master_url' => $masterPath,
            ];
        })->toArray();
    }

    public function resolveRepository(int $repositoryId): ?array
    {
        $row = DB::table('repository as r')
            ->join('actor as a', 'r.id', '=', 'a.id')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', $this->culture);
            })
            ->leftJoin('slug', 'r.id', '=', 'slug.object_id')
            ->where('r.id', $repositoryId)
            ->select(['r.id', 'slug.slug', 'ai.authorized_form_of_name as name', 'r.identifier'])
            ->first();

        return $row ? (array) $row : null;
    }

    public function resolveRepositoryBySlug(string $slug): ?array
    {
        $row = DB::table('repository as r')
            ->join('actor as a', 'r.id', '=', 'a.id')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', $this->culture);
            })
            ->join('slug', 'r.id', '=', 'slug.object_id')
            ->where('slug.slug', $slug)
            ->select(['r.id', 'slug.slug', 'ai.authorized_form_of_name as name', 'r.identifier'])
            ->first();

        return $row ? (array) $row : null;
    }

    public function resolveRepositories(int $first, int $offset): array
    {
        $result = $this->repository->getRepositories([
            'limit' => $first,
            'skip' => $offset,
        ]);

        return $this->buildConnection($result['results'], $result['total'], $offset, $first);
    }

    public function resolveByRepository(int $repositoryId, int $first, int $offset): array
    {
        $query = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('term_i18n as lod', function ($join) {
                $join->on('io.level_of_description_id', '=', 'lod.id')
                    ->where('lod.culture', '=', $this->culture);
            })
            ->leftJoin('display_standard_sector as dss', 'io.display_standard_id', '=', 'dss.term_id')
            ->where('io.repository_id', $repositoryId)
            ->where('io.id', '!=', 1)
            ->select([
                'io.id',
                'slug.slug',
                'io.identifier',
                'ioi.title',
                'io.level_of_description_id',
                'lod.name as level_of_description',
                'dss.sector',
                'io.repository_id',
                'io.parent_id',
            ]);

        $total = $query->count();
        $results = $query->orderBy('io.id', 'desc')
            ->skip($offset)
            ->take($first)
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        return $this->buildConnection($results, $total, $offset, $first);
    }

    public function countByRepository(int $repositoryId): int
    {
        return DB::table('information_object')
            ->where('repository_id', $repositoryId)
            ->where('id', '!=', 1)
            ->count();
    }

    public function resolveSearch(string $query, int $first, int $offset): array
    {
        $searchQuery = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('term_i18n as lod', function ($join) {
                $join->on('io.level_of_description_id', '=', 'lod.id')
                    ->where('lod.culture', '=', $this->culture);
            })
            ->leftJoin('display_standard_sector as dss', 'io.display_standard_id', '=', 'dss.term_id')
            ->where('io.id', '!=', 1)
            ->where(function ($q) use ($query) {
                $q->where('ioi.title', 'LIKE', "%{$query}%")
                    ->orWhere('io.identifier', 'LIKE', "%{$query}%")
                    ->orWhere('ioi.scope_and_content', 'LIKE', "%{$query}%");
            })
            ->select([
                'io.id',
                'slug.slug',
                'io.identifier',
                'ioi.title',
                'io.level_of_description_id',
                'lod.name as level_of_description',
                'dss.sector',
                'io.repository_id',
                'io.parent_id',
            ]);

        $total = $searchQuery->count();
        $results = $searchQuery->orderBy('io.id', 'desc')
            ->skip($offset)
            ->take($first)
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        return $this->buildConnection($results, $total, $offset, $first);
    }
}

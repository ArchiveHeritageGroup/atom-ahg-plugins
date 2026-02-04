<?php

namespace AhgGraphQLPlugin\GraphQL\Resolvers;

use Illuminate\Database\Capsule\Manager as DB;

class ActorResolver extends BaseResolver
{
    public function resolveBySlug(string $slug): ?array
    {
        return $this->repository->getAuthorityBySlug($slug);
    }

    public function resolveById(int $id): ?array
    {
        $row = DB::table('actor as a')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', $this->culture);
            })
            ->leftJoin('slug', 'a.id', '=', 'slug.object_id')
            ->leftJoin('term_i18n as et', function ($join) {
                $join->on('a.entity_type_id', '=', 'et.id')
                    ->where('et.culture', '=', $this->culture);
            })
            ->where('a.id', $id)
            ->select([
                'a.id',
                'slug.slug',
                'ai.authorized_form_of_name',
                'a.entity_type_id',
                'et.name as entity_type',
                'ai.dates_of_existence',
                'ai.history',
                'ai.places',
                'ai.functions',
            ])
            ->first();

        return $row ? (array) $row : null;
    }

    public function resolveList(int $first, int $offset, ?string $entityType = null): array
    {
        $query = DB::table('actor as a')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', $this->culture);
            })
            ->leftJoin('slug', 'a.id', '=', 'slug.object_id')
            ->leftJoin('term_i18n as et', function ($join) {
                $join->on('a.entity_type_id', '=', 'et.id')
                    ->where('et.culture', '=', $this->culture);
            })
            ->where('a.id', '!=', 1)
            ->select([
                'a.id',
                'slug.slug',
                'ai.authorized_form_of_name',
                'a.entity_type_id',
                'et.name as entity_type',
            ]);

        if ($entityType) {
            $query->whereRaw('LOWER(et.name) = ?', [strtolower($entityType)]);
        }

        $total = $query->count();
        $results = $query->orderBy('ai.authorized_form_of_name', 'asc')
            ->skip($offset)
            ->take($first)
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        return $this->buildConnection($results, $total, $offset, $first);
    }

    public function resolveRelatedItems(int $actorId, int $first, int $offset): array
    {
        $query = DB::table('event as e')
            ->join('information_object as io', 'e.object_id', '=', 'io.id')
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
            ->where('e.actor_id', $actorId)
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
            ])
            ->distinct();

        $total = $query->count('io.id');
        $results = $query->orderBy('io.id', 'desc')
            ->skip($offset)
            ->take($first)
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        return $this->buildConnection($results, $total, $offset, $first);
    }
}

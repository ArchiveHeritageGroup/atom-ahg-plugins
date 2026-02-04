<?php

namespace AhgGraphQLPlugin\GraphQL\Resolvers;

use Illuminate\Database\Capsule\Manager as DB;

class TaxonomyResolver extends BaseResolver
{
    public function resolveAll(): array
    {
        return $this->repository->getTaxonomies();
    }

    public function resolveById(int $id): ?array
    {
        $row = DB::table('taxonomy as t')
            ->join('taxonomy_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                    ->where('ti.culture', '=', $this->culture);
            })
            ->where('t.id', $id)
            ->select(['t.id', 'ti.name', 't.usage'])
            ->first();

        return $row ? (array) $row : null;
    }

    public function resolveTerms(int $taxonomyId): array
    {
        return $this->repository->getTaxonomyTerms($taxonomyId);
    }

    public function resolveTermById(int $termId): ?array
    {
        $row = DB::table('term as t')
            ->join('term_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                    ->where('ti.culture', '=', $this->culture);
            })
            ->where('t.id', $termId)
            ->select(['t.id', 'ti.name', 't.code', 't.parent_id', 't.taxonomy_id'])
            ->first();

        return $row ? (array) $row : null;
    }

    public function resolveTermChildren(int $parentId): array
    {
        return DB::table('term as t')
            ->join('term_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                    ->where('ti.culture', '=', $this->culture);
            })
            ->where('t.parent_id', $parentId)
            ->where('t.id', '!=', 1)
            ->select(['t.id', 'ti.name', 't.code', 't.parent_id', 't.taxonomy_id'])
            ->orderBy('t.lft', 'asc')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();
    }
}

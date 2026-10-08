<?php

namespace AhgStorageManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

class StorageBrowseService
{
    protected string $culture;

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    public function browse(array $params): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, min(100, (int) ($params['limit'] ?? \sfConfig::get('app_hits_per_page', 30))));
        $skip = ($page - 1) * $limit;

        $sort = $params['sort'] ?? 'nameUp';
        $subquery = trim($params['subquery'] ?? '');

        try {
            $query = DB::table('physical_object')
                ->join('physical_object_i18n', 'physical_object.id', '=', 'physical_object_i18n.id')
                ->join('slug', 'physical_object.id', '=', 'slug.object_id')
                ->leftJoin('term_i18n as type_i18n', function ($join) {
                    $join->on('physical_object.type_id', '=', 'type_i18n.id')
                        ->where('type_i18n.culture', '=', $this->culture);
                })
                ->leftJoin('physical_object_extended as poe', 'poe.physical_object_id', '=', 'physical_object.id')
                ->where('physical_object_i18n.culture', $this->culture)
                ->select([
                    'physical_object.id',
                    'physical_object_i18n.name',
                    'physical_object_i18n.location',
                    'poe.building', 'poe.floor', 'poe.room', 'poe.aisle', 'poe.bay', 'poe.rack', 'poe.shelf',
                    'type_i18n.name as type_name',
                    'slug.slug',
                ]);

            // Text search: match name OR location OR type
            if ('' !== $subquery) {
                $query->where(function ($q) use ($subquery) {
                    $q->where('physical_object_i18n.name', 'LIKE', "%{$subquery}%")
                      ->orWhere('physical_object_i18n.location', 'LIKE', "%{$subquery}%")
                      ->orWhere('poe.building', 'LIKE', "%{$subquery}%")
                      ->orWhere('poe.room', 'LIKE', "%{$subquery}%")
                      ->orWhere('poe.shelf', 'LIKE', "%{$subquery}%")
                      ->orWhere('type_i18n.name', 'LIKE', "%{$subquery}%");
                });
            }

            // Get total before pagination
            $total = $query->count();

            // Sort
            switch ($sort) {
                case 'nameDown':
                    $query->orderBy('physical_object_i18n.name', 'desc');
                    break;

                case 'locationUp':
                    // Empty places last, then building, floor, room, then the old free text.
                    $query->orderByRaw("COALESCE(poe.building, '') = '' asc")->orderBy('poe.building', 'asc')
                        ->orderBy('poe.floor', 'asc')->orderBy('poe.room', 'asc')
                        ->orderBy('physical_object_i18n.location', 'asc');
                    break;

                case 'locationDown':
                    // Empty places last, then building, floor, room, then the old free text.
                    $query->orderByRaw("COALESCE(poe.building, '') = '' asc")->orderBy('poe.building', 'desc')
                        ->orderBy('poe.floor', 'desc')->orderBy('poe.room', 'desc')
                        ->orderBy('physical_object_i18n.location', 'desc');
                    break;

                case 'nameUp':
                default:
                    $query->orderBy('physical_object_i18n.name', 'asc');
                    break;
            }

            $rows = $query->skip($skip)->take($limit)->get();

            $paths = $this->treePaths(array_map(static fn ($r) => (int) $r->id, $rows->all()));

            $hits = [];
            foreach ($rows as $row) {
                $hits[] = [
                    'id' => $row->id,
                    'name' => $row->name ?? '',
                    'location' => $row->location ?? '',
                    'tree_path' => $paths[(int) $row->id] ?? [],
                    'flat_path' => self::flatPath($row),
                    'type_name' => $row->type_name ?? '',
                    'slug' => $row->slug ?? '',
                ];
            }

            return [
                'hits' => $hits,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
            ];
        } catch (\Exception $e) {
            error_log('ahgStorageManagePlugin browse error: ' . $e->getMessage());

            return [
                'hits' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
            ];
        }
    }

    /**
     * Each object's place in the storage location tree, outermost first, for one
     * page of results: two queries, not one per row.
     *
     * @param int[] $objectIds
     *
     * @return array<int, array<int, array{id: int, name: string}>>
     */
    protected function treePaths(array $objectIds): array
    {
        if (!$objectIds || !DB::schema()->hasTable('ahg_physical_object_location')) {
            return [];
        }

        $placed = DB::table('ahg_physical_object_location')->whereIn('physical_object_id', $objectIds)
            ->pluck('location_id', 'physical_object_id')->all();
        if (!$placed) {
            return [];
        }

        $steps = [];
        foreach (DB::table('ahg_storage_location_closure as c')
            ->join('ahg_storage_location as l', 'l.id', '=', 'c.ancestor')
            ->whereIn('c.descendant', array_unique(array_values($placed)))
            ->orderBy('c.depth', 'desc')
            ->get(['c.descendant', 'l.id', 'l.name']) as $row) {
            $steps[(int) $row->descendant][] = ['id' => (int) $row->id, 'name' => (string) $row->name];
        }

        $paths = [];
        foreach ($placed as $objectId => $locationId) {
            $paths[(int) $objectId] = $steps[(int) $locationId] ?? [];
        }

        return $paths;
    }

    /** The flat location fields as one line, as the object's own page shows them. */
    protected static function flatPath(object $row): string
    {
        $parts = [];
        foreach (['building' => '', 'floor' => 'Floor ', 'room' => 'Room ', 'aisle' => 'Aisle ', 'bay' => 'Bay ', 'rack' => 'Rack ', 'shelf' => 'Shelf '] as $field => $prefix) {
            $value = trim((string) ($row->{$field} ?? ''));
            if ('' !== $value) {
                $parts[] = (0 === stripos($value, trim($prefix)) || '' === $prefix) ? $value : $prefix.$value;
            }
        }

        return implode(' > ', $parts);
    }
}

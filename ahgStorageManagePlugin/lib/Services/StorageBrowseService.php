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
                ->where('physical_object_i18n.culture', $this->culture)
                ->select([
                    'physical_object.id',
                    'physical_object_i18n.name',
                    'physical_object_i18n.location',
                    'type_i18n.name as type_name',
                    'slug.slug',
                ]);

            // Text search: match name OR location OR type
            if ('' !== $subquery) {
                $query->where(function ($q) use ($subquery) {
                    $q->where('physical_object_i18n.name', 'LIKE', "%{$subquery}%")
                      ->orWhere('physical_object_i18n.location', 'LIKE', "%{$subquery}%")
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
                    $query->orderBy('physical_object_i18n.location', 'asc');
                    break;

                case 'locationDown':
                    $query->orderBy('physical_object_i18n.location', 'desc');
                    break;

                case 'nameUp':
                default:
                    $query->orderBy('physical_object_i18n.name', 'asc');
                    break;
            }

            $rows = $query->skip($skip)->take($limit)->get();

            $hits = [];
            foreach ($rows as $row) {
                $hits[] = [
                    'id' => $row->id,
                    'name' => $row->name ?? '',
                    'location' => $row->location ?? '',
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
}

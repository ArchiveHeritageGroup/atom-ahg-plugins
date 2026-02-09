<?php

namespace AhgFunctionManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

class FunctionBrowseService
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

        $sort = $params['sort'] ?? 'lastUpdated';
        $sortDir = $params['sortDir'] ?? 'asc';
        $subquery = trim($params['subquery'] ?? '');

        try {
            $query = DB::table('function_object')
                ->join('function_object_i18n', 'function_object.id', '=', 'function_object_i18n.id')
                ->join('object', 'function_object.id', '=', 'object.id')
                ->join('slug', 'function_object.id', '=', 'slug.object_id')
                ->leftJoin('term_i18n as type_name', function ($join) {
                    $join->on('function_object.type_id', '=', 'type_name.id')
                         ->where('type_name.culture', '=', $this->culture);
                })
                ->where('function_object_i18n.culture', $this->culture)
                ->select([
                    'function_object.id',
                    'function_object_i18n.authorized_form_of_name as name',
                    'function_object.description_identifier as identifier',
                    'type_name.name as type_name',
                    'object.updated_at',
                    'slug.slug',
                ]);

            // Text search
            if ('' !== $subquery) {
                $query->where(function ($q) use ($subquery) {
                    $q->where('function_object_i18n.authorized_form_of_name', 'LIKE', "%{$subquery}%")
                      ->orWhere('function_object.description_identifier', 'LIKE', "%{$subquery}%");
                });
            }

            // Get total before pagination
            $total = $query->count();

            // Sort
            switch ($sort) {
                case 'identifier':
                    $query->orderBy('function_object.description_identifier', $sortDir);
                    $query->orderBy('function_object_i18n.authorized_form_of_name', $sortDir);
                    break;

                case 'alphabetic':
                    $query->orderBy('function_object_i18n.authorized_form_of_name', $sortDir);
                    break;

                case 'lastUpdated':
                default:
                    $query->orderBy('object.updated_at', $sortDir);
                    break;
            }

            $rows = $query->skip($skip)->take($limit)->get();

            $hits = [];
            foreach ($rows as $row) {
                $hits[] = [
                    'id' => $row->id,
                    'name' => $row->name ?? '',
                    'identifier' => $row->identifier ?? '',
                    'type_name' => $row->type_name ?? '',
                    'updated_at' => $row->updated_at ?? '',
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
            error_log('ahgFunctionManagePlugin browse error: ' . $e->getMessage());

            return [
                'hits' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
            ];
        }
    }
}

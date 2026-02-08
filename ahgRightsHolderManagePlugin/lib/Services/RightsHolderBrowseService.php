<?php

namespace AhgRightsHolderManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

class RightsHolderBrowseService
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
            $query = DB::table('rights_holder')
                ->join('actor_i18n', 'rights_holder.id', '=', 'actor_i18n.id')
                ->join('object', 'rights_holder.id', '=', 'object.id')
                ->join('slug', 'rights_holder.id', '=', 'slug.object_id')
                ->leftJoin('actor', 'rights_holder.id', '=', 'actor.id')
                ->where('actor_i18n.culture', $this->culture)
                ->select([
                    'rights_holder.id',
                    'actor_i18n.authorized_form_of_name as name',
                    'actor.description_identifier as identifier',
                    'object.updated_at',
                    'slug.slug',
                ]);

            // Text search
            if ('' !== $subquery) {
                $query->where('actor_i18n.authorized_form_of_name', 'LIKE', "%{$subquery}%");
            }

            // Get total before pagination
            $total = $query->count();

            // Sort
            switch ($sort) {
                case 'identifier':
                    $query->orderBy('actor.description_identifier', $sortDir);
                    $query->orderBy('actor_i18n.authorized_form_of_name', $sortDir);
                    break;

                case 'alphabetic':
                    $query->orderBy('actor_i18n.authorized_form_of_name', $sortDir);
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
            error_log('ahgRightsHolderManagePlugin browse error: ' . $e->getMessage());

            return [
                'hits' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
            ];
        }
    }
}

<?php

namespace AhgUserManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

class UserBrowseService
{
    protected string $culture;

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    public function browse(array $params = []): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, (int) ($params['limit'] ?? 30));
        $sort = $params['sort'] ?? 'username';
        $sortDir = $params['sortDir'] ?? 'asc';
        $subquery = trim($params['subquery'] ?? '');
        $filter = $params['filter'] ?? '';

        $query = DB::table('user')
            ->leftJoin('acl_user_group', 'user.id', '=', 'acl_user_group.user_id')
            ->leftJoin('acl_group_i18n', function ($join) {
                $join->on('acl_user_group.group_id', '=', 'acl_group_i18n.id')
                    ->where('acl_group_i18n.culture', '=', $this->culture);
            })
            ->leftJoin('object', 'user.id', '=', 'object.id')
            ->leftJoin('slug', 'user.id', '=', 'slug.object_id')
            ->select(
                'user.id',
                'user.username',
                'user.email',
                'user.active',
                'object.updated_at',
                'slug.slug',
                DB::raw('GROUP_CONCAT(acl_group_i18n.name SEPARATOR \', \') as user_groups')
            )
            ->groupBy('user.id', 'user.username', 'user.email', 'user.active', 'object.updated_at', 'slug.slug');

        // Active/inactive filter
        if ('onlyActive' === $filter) {
            $query->where('user.active', 1);
        } elseif ('onlyInactive' === $filter) {
            $query->where('user.active', 0);
        }

        // Text search on username and email
        if ('' !== $subquery) {
            $like = '%' . $subquery . '%';
            $query->where(function ($q) use ($like) {
                $q->where('user.username', 'LIKE', $like)
                    ->orWhere('user.email', 'LIKE', $like);
            });
        }

        // Count total before pagination (wrap the grouped query)
        $countQuery = DB::table(DB::raw('(' . $query->toSql() . ') as sub'))
            ->mergeBindings($query);
        $total = $countQuery->count();

        // Sorting
        switch ($sort) {
            case 'email':
                $query->orderBy('user.email', $sortDir);
                break;
            case 'lastUpdated':
                $query->orderBy('object.updated_at', $sortDir);
                break;
            case 'username':
            default:
                $query->orderBy('user.username', $sortDir);
                break;
        }

        // Pagination
        $offset = ($page - 1) * $limit;
        $results = $query->skip($offset)->take($limit)->get();

        $hits = [];
        foreach ($results as $row) {
            $hits[] = [
                'id' => $row->id,
                'username' => $row->username,
                'email' => $row->email,
                'active' => (bool) $row->active,
                'groups' => $row->user_groups ?? '',
                'updated_at' => $row->updated_at,
                'slug' => $row->slug ?? '',
            ];
        }

        return [
            'hits' => $hits,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }
}
